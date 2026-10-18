<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Tampilkan daftar kategori produk milik vendor.
     */
    public function index(Request $request): View
    {
        $vendorId = (int) $request->user()->vendor_id;

        $query = ItemCategory::withCount('items')
            ->where('vendor_id', $vendorId);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $categories = $query->orderBy('name')->get();

        return view('vendor.categories.index', [
            'categories' => $categories,
            'search' => $request->input('search', ''),
        ]);
    }

    /**
     * Simpan kategori baru milik vendor.
     */
    public function store(Request $request): RedirectResponse
    {
        $vendorId = (int) $request->user()->vendor_id;

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
        ]);

        $baseSlug = Str::slug($request->name);
        $slug = $baseSlug;
        $count = 1;

        while (ItemCategory::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }

        ItemCategory::create([
            'vendor_id' => $vendorId,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'icon' => $request->icon ?: 'tent',
        ]);

        return redirect()->route('vendor.categories.index')
            ->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    /**
     * Perbarui data kategori.
     */
    public function update(Request $request, ItemCategory $category): RedirectResponse
    {
        $this->authorizeCategoryOwnership($request, $category);

        $vendorId = (int) $request->user()->vendor_id;

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
        ]);

        $slug = $category->slug;
        if ($category->name !== $request->name) {
            $baseSlug = Str::slug($request->name);
            $slug = $baseSlug;
            $count = 1;

            while (ItemCategory::withoutGlobalScope('vendor')
                ->where('vendor_id', $vendorId)
                ->where('slug', $slug)
                ->where('id', '!=', $category->id)
                ->exists()) {
                $slug = "{$baseSlug}-{$count}";
                $count++;
            }
        }

        $category->update([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'icon' => $request->icon ?: $category->icon,
        ]);

        return redirect()->route('vendor.categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Hapus kategori dengan validasi relasi produk.
     */
    public function destroy(Request $request, ItemCategory $category): RedirectResponse
    {
        $this->authorizeCategoryOwnership($request, $category);

        $itemsCount = $category->items()->count();
        if ($itemsCount > 0) {
            return redirect()->back()
                ->with('error', "Kategori '{$category->name}' tidak dapat dihapus karena masih memuat {$itemsCount} produk. Pindahkan atau hapus produk terlebih dahulu.");
        }

        $category->delete();

        return redirect()->route('vendor.categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * Pastikan kategori dimiliki oleh vendor yang sedang login.
     */
    protected function authorizeCategoryOwnership(Request $request, ItemCategory $category): void
    {
        if ((int) $category->vendor_id !== (int) $request->user()->vendor_id) {
            abort(403, 'Akses ditolak. Kategori ini bukan milik toko Anda.');
        }
    }
}
