<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CampingPackage;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampingPackageController extends Controller
{
    /**
     * Katalog paket camping publik dari seluruh vendor aktif.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $vendorId = $request->query('vendor_id');
        $sort = $request->query('sort', 'latest');

        $vendors = Vendor::whereIn('status', ['active', 'approved'])
            ->whereHas('campingPackages', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        $query = CampingPackage::publicCatalog()
            ->with(['vendor', 'packageItems.masterItem', 'masterItems'])
            ->when(! empty($search), function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(! empty($vendorId), fn ($q) => $q->where('vendor_id', $vendorId));

        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'duration_asc' => $query->orderBy('duration_days', 'asc'),
            default => $query->latest(),
        };

        $packages = $query->paginate(9)->withQueryString();

        return view('customer.packages.index', compact('packages', 'vendors', 'search', 'vendorId', 'sort'));
    }

    /**
     * Detail paket camping beserta isi paket dan ketersediaan unit.
     */
    public function show(int $id): View
    {
        /** @var CampingPackage $package */
        $package = CampingPackage::publicCatalog()
            ->with(['vendor', 'packageItems.masterItem.category', 'packageItems.masterItem.units', 'masterItems'])
            ->findOrFail($id);

        return view('customer.packages.show', compact('package'));
    }
}
