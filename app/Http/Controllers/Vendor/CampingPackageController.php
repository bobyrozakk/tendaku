<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\CampingPackageRequest;
use App\Models\CampingPackage;
use App\Models\MasterItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CampingPackageController extends Controller
{
    /**
     * Daftar paket camping milik Owner Vendor.
     */
    public function index(Request $request): View
    {
        $vendorId = (int) $request->user()->vendor_id;

        $query = CampingPackage::query()
            ->with('packageItems')
            ->withCount('packageItems')
            ->where('vendor_id', $vendorId);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $packages = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('vendor.packages.index', [
            'packages' => $packages,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Form pembuatan paket camping baru.
     */
    public function create(Request $request): View
    {
        return view('vendor.packages.create', [
            'products' => $this->availableProducts($request),
        ]);
    }

    /**
     * Simpan paket camping baru.
     */
    public function store(CampingPackageRequest $request): RedirectResponse
    {
        $vendorId = (int) $request->user()->vendor_id;
        $items = $request->packageItems();

        if ($items === []) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Paket camping minimal berisi satu produk yang valid.');
        }

        DB::transaction(function () use ($request, $vendorId, $items) {
            $package = CampingPackage::create([
                'vendor_id' => $vendorId,
                'name' => $request->name,
                'slug' => $this->uniqueSlug($vendorId, $request->name),
                'description' => $request->description,
                'price' => $request->price,
                'duration_days' => (int) $request->duration_days,
                'max_people' => $request->max_people,
                'image_url' => $this->storeImage($request),
                'is_active' => $request->boolean('is_active', true),
            ]);

            $package->packageItems()->createMany($items);
        });

        return redirect()->route('vendor.packages.index')
            ->with('success', 'Paket camping berhasil ditambahkan.');
    }

    /**
     * Form edit paket camping.
     */
    public function edit(Request $request, CampingPackage $package): View
    {
        $this->authorizeOwnership($request, $package);

        $package->load(['packageItems.masterItem']);

        return view('vendor.packages.edit', [
            'package' => $package,
            'products' => $this->availableProducts($request),
        ]);
    }

    /**
     * Perbarui paket camping.
     */
    public function update(CampingPackageRequest $request, CampingPackage $package): RedirectResponse
    {
        $this->authorizeOwnership($request, $package);

        $items = $request->packageItems();

        if ($items === []) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Paket camping minimal berisi satu produk yang valid.');
        }

        DB::transaction(function () use ($request, $package, $items) {
            $imageUrl = $package->image_url;

            if ($request->hasFile('image')) {
                $this->deleteStoredImage($package->image_url);
                $imageUrl = $this->storeImage($request);
            }

            $package->update([
                'name' => $request->name,
                'slug' => $package->name === $request->name
                    ? $package->slug
                    : $this->uniqueSlug((int) $package->vendor_id, $request->name, $package->id),
                'description' => $request->description,
                'price' => $request->price,
                'duration_days' => (int) $request->duration_days,
                'max_people' => $request->max_people,
                'image_url' => $imageUrl,
                'is_active' => $request->boolean('is_active', true),
            ]);

            $package->packageItems()->delete();
            $package->packageItems()->createMany($items);
        });

        return redirect()->route('vendor.packages.index')
            ->with('success', 'Paket camping berhasil diperbarui.');
    }

    /**
     * Hapus paket camping.
     */
    public function destroy(Request $request, CampingPackage $package): RedirectResponse
    {
        $this->authorizeOwnership($request, $package);

        $this->deleteStoredImage($package->image_url);
        $package->packageItems()->delete();
        $package->delete();

        return redirect()->route('vendor.packages.index')
            ->with('success', 'Paket camping berhasil dihapus.');
    }

    /**
     * Produk aktif milik vendor yang bisa dimasukkan ke paket.
     *
     * @return Collection<int, MasterItem>
     */
    protected function availableProducts(Request $request)
    {
        return MasterItem::query()
            ->where('vendor_id', (int) $request->user()->vendor_id)
            ->where('is_active', true)
            ->with('category')
            ->orderBy('name')
            ->get();
    }

    protected function storeImage(CampingPackageRequest $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return Storage::url($request->file('image')->store('packages', 'public'));
    }

    protected function deleteStoredImage(?string $imageUrl): void
    {
        if ($imageUrl && Str::contains($imageUrl, '/storage/packages/')) {
            Storage::disk('public')->delete(Str::after($imageUrl, '/storage/'));
        }
    }

    protected function uniqueSlug(int $vendorId, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $count = 1;

        while (
            CampingPackage::withoutGlobalScope('vendor')
                ->where('vendor_id', $vendorId)
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    protected function authorizeOwnership(Request $request, CampingPackage $package): void
    {
        if ((int) $package->vendor_id !== (int) $request->user()->vendor_id) {
            abort(403, 'Akses ditolak. Paket camping ini bukan milik toko Anda.');
        }
    }
}
