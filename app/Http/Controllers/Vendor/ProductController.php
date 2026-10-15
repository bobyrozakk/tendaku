<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreProductRequest;
use App\Http\Requests\Vendor\UpdateProductRequest;
use App\Models\ItemCategory;
use App\Models\MasterItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Tampilkan daftar produk milik Owner Vendor.
     */
    public function index(Request $request): View
    {
        $vendorId = (int) $request->user()->vendor_id;

        $categories = ItemCategory::where('vendor_id', $vendorId)
            ->orderBy('name')
            ->get();

        $query = MasterItem::with(['category'])
            ->withCount([
                'units as total_units_count',
                'units as available_units_count' => function ($q) {
                    $q->where('status', 'available');
                },
                'units as rented_units_count' => function ($q) {
                    $q->where('status', 'rented');
                },
            ])
            ->where('vendor_id', $vendorId);

        // Filter Pencarian (Nama / Deskripsi)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter Kategori
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Filter Status Keaktifan
        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $products = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('vendor.products.index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'status']),
        ]);
    }

    /**
     * Tampilkan form penambahan produk baru.
     */
    public function create(Request $request): View
    {
        $vendorId = (int) $request->user()->vendor_id;
        $categories = ItemCategory::where('vendor_id', $vendorId)->orderBy('name')->get();

        return view('vendor.products.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Simpan produk baru ke database.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $vendorId = (int) $request->user()->vendor_id;

        $baseSlug = Str::slug($request->name);
        $slug = $baseSlug;
        $count = 1;

        while (MasterItem::withoutGlobalScope('vendor')->where('vendor_id', $vendorId)->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $imageUrl = Storage::url($path);
        }

        MasterItem::create([
            'vendor_id' => $vendorId,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'daily_rate' => $request->daily_rate,
            'deposit_amount' => 0, // Tanpa deposit moneter sesuai revisi bisnis
            'late_fee_per_day' => $request->late_fee_per_day,
            'image_url' => $imageUrl,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('vendor.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Tampilkan detail produk beserta daftar unit fisiknya.
     */
    public function show(Request $request, MasterItem $product): View
    {
        $this->authorizeProductOwnership($request, $product);

        $product->load(['category', 'units' => function ($q) {
            $q->orderBy('unit_code');
        }]);

        return view('vendor.products.show', [
            'product' => $product,
        ]);
    }

    /**
     * Tampilkan form edit produk.
     */
    public function edit(Request $request, MasterItem $product): View
    {
        $this->authorizeProductOwnership($request, $product);

        $vendorId = (int) $request->user()->vendor_id;
        $categories = ItemCategory::where('vendor_id', $vendorId)->orderBy('name')->get();

        return view('vendor.products.edit', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    /**
     * Perbarui data produk.
     */
    public function update(UpdateProductRequest $request, MasterItem $product): RedirectResponse
    {
        $this->authorizeProductOwnership($request, $product);

        $vendorId = (int) $request->user()->vendor_id;

        $slug = $product->slug;
        if ($product->name !== $request->name) {
            $baseSlug = Str::slug($request->name);
            $slug = $baseSlug;
            $count = 1;

            while (MasterItem::withoutGlobalScope('vendor')
                ->where('vendor_id', $vendorId)
                ->where('slug', $slug)
                ->where('id', '!=', $product->id)
                ->exists()) {
                $slug = "{$baseSlug}-{$count}";
                $count++;
            }
        }

        $imageUrl = $product->image_url;
        if ($request->hasFile('image')) {
            // Hapus foto lama bila tersimpan lokal
            if ($product->image_url && Str::contains($product->image_url, '/storage/products/')) {
                $oldPath = Str::after($product->image_url, '/storage/');
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('products', 'public');
            $imageUrl = Storage::url($path);
        }

        $product->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'daily_rate' => $request->daily_rate,
            'late_fee_per_day' => $request->late_fee_per_day,
            'image_url' => $imageUrl,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('vendor.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Hapus produk (Soft Delete) dengan validasi integritas relasi.
     */
    public function destroy(Request $request, MasterItem $product): RedirectResponse
    {
        $this->authorizeProductOwnership($request, $product);

        // Cegah hapus jika ada unit yang sedang disewa
        $hasRentedUnits = $product->units()->where('status', 'rented')->exists();
        if ($hasRentedUnits) {
            return redirect()->back()
                ->with('error', 'Produk tidak dapat dihapus karena salah satu atau lebih unitnya sedang disewa oleh pelanggan.');
        }

        // Cegah hapus jika ada detail sewa yang belum dikembalikan
        $hasActiveRentals = $product->rentalDetails()
            ->where('is_returned', false)
            ->whereHas('rental', function ($q) {
                $q->whereIn('status', ['confirmed', 'picked_up']);
            })
            ->exists();

        if ($hasActiveRentals) {
            return redirect()->back()
                ->with('error', 'Produk tidak dapat dihapus karena terikat pada transaksi sewa aktif yang belum selesai.');
        }

        // Hapus soft delete produk beserta unit fisiknya
        $product->units()->delete();
        $product->delete();

        return redirect()->route('vendor.products.index')
            ->with('success', 'Produk dan unit terkait berhasil dihapus.');
    }

    /**
     * Pastikan produk dimiliki oleh vendor yang sedang login.
     */
    protected function authorizeProductOwnership(Request $request, MasterItem $product): void
    {
        if ((int) $product->vendor_id !== (int) $request->user()->vendor_id) {
            abort(403, 'Akses ditolak. Produk ini bukan milik toko Anda.');
        }
    }
}
