<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreProductRequest;
use App\Http\Requests\Vendor\UpdateProductRequest;
use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\MasterItemImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'images as images_count',
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

        // Kumpulkan file foto: utamakan array 'images', atau fallback 'image'
        $files = [];
        if ($request->hasFile('images')) {
            $files = array_slice($request->file('images'), 0, 5);
        } elseif ($request->hasFile('image')) {
            $files = [$request->file('image')];
        }

        $storedPaths = [];

        try {
            DB::beginTransaction();

            $product = MasterItem::create([
                'vendor_id' => $vendorId,
                'category_id' => $request->category_id,
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'daily_rate' => $request->daily_rate,
                'deposit_amount' => 0, // Tanpa deposit moneter sesuai revisi bisnis
                'late_fee_per_day' => $request->late_fee_per_day,
                'image_url' => null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            $primaryImageUrl = null;

            foreach ($files as $index => $file) {
                $path = $file->store('products', 'public');
                $storedPaths[] = $path;
                $imageUrl = Storage::url($path);

                $isPrimary = ($index === 0);
                if ($isPrimary) {
                    $primaryImageUrl = $imageUrl;
                }

                $product->images()->create([
                    'image_path' => $path,
                    'image_url' => $imageUrl,
                    'is_primary' => $isPrimary,
                    'sort_order' => $index + 1,
                ]);
            }

            if ($primaryImageUrl) {
                $product->update(['image_url' => $primaryImageUrl]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            // Bersihkan file yang sudah terlanjur diunggah agar tidak menjadi orphan file
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }

        return redirect()->route('vendor.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Tampilkan detail produk beserta daftar unit fisiknya.
     */
    public function show(Request $request, MasterItem $product): View
    {
        $this->authorizeProductOwnership($request, $product);

        $product->load(['category', 'images', 'units' => function ($q) {
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
        $product->load('images');

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

        $storedPaths = [];

        try {
            DB::beginTransaction();

            // 1. Hapus foto lama yang ditandai untuk dihapus
            if ($request->filled('deleted_images')) {
                $deletedIds = (array) $request->input('deleted_images');
                $product->images()->whereIn('id', $deletedIds)->get()->each(function (MasterItemImage $image) {
                    $image->delete();
                });
            }

            // 2. Kumpulkan foto baru yang diunggah
            $newFiles = [];
            if ($request->hasFile('images')) {
                $newFiles = $request->file('images');
            } elseif ($request->hasFile('image')) {
                $newFiles = [$request->file('image')];
            }

            $currentMaxOrder = $product->images()->max('sort_order') ?? 0;

            foreach ($newFiles as $index => $file) {
                $path = $file->store('products', 'public');
                $storedPaths[] = $path;
                $imageUrl = Storage::url($path);

                $product->images()->create([
                    'image_path' => $path,
                    'image_url' => $imageUrl,
                    'is_primary' => false,
                    'sort_order' => $currentMaxOrder + $index + 1,
                ]);
            }

            // 3. Pastikan ada tepat 1 foto utama jika produk memiliki foto
            $allImages = $product->images()->orderBy('sort_order')->orderBy('id')->get();
            $primaryImage = $allImages->firstWhere('is_primary', true);

            if (! $primaryImage && $allImages->isNotEmpty()) {
                $primaryImage = $allImages->first();
                $primaryImage->update(['is_primary' => true]);
            }

            $imageUrl = $primaryImage?->image_url;

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

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }

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

        // Hapus soft delete produk beserta unit fisiknya dan bersihkan foto
        $product->units()->delete();
        $product->images->each->delete();
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
