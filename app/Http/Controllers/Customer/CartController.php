<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\MasterItem;
use App\Services\Booking\CartService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * Tampilkan Halaman Keranjang Sewa (Mendukung Multi-Item dari Toko yang Sama).
     */
    public function index(): View
    {
        $user = Auth::user();
        $cart = $this->cartService->get($user);

        $items = [];
        $vendor = null;
        $cartSummary = null;

        if (is_array($cart) && ! empty($cart['items'])) {
            $rawItems = $cart['items'];
            $itemIds = array_keys($rawItems);
            $masterItems = MasterItem::with(['vendor', 'category'])
                ->whereIn('id', $itemIds)
                ->get()
                ->keyBy('id');

            $pickupDate = $cart['pickup_date'] ?? Carbon::now()->addDay()->format('Y-m-d');
            $returnDate = $cart['return_date'] ?? Carbon::now()->addDays(2)->format('Y-m-d');
            $durationDays = max(1, (int) ($cart['total_days'] ?? $cart['duration_days'] ?? Carbon::parse($pickupDate)->diffInDays(Carbon::parse($returnDate)) + 1));

            $totalPrice = 0;
            $totalQty = 0;

            foreach ($rawItems as $rawId => $it) {
                $masterItem = $masterItems->get((int) $rawId);
                if (! $masterItem) {
                    continue;
                }

                $vendor ??= [
                    'id' => $masterItem->vendor_id,
                    'name' => $masterItem->vendor?->name ?? 'Tendaku Vendor',
                    'city' => $masterItem->vendor?->city ?? 'Malang',
                ];

                $qty = max(1, (int) ($it['qty'] ?? $it['quantity'] ?? 1));
                $dailyRate = (int) $masterItem->daily_rate;
                $itemTotal = $dailyRate * $qty * $durationDays;

                $totalQty += $qty;
                $totalPrice += $itemTotal;

                $items[] = [
                    'id' => $masterItem->id,
                    'master_item_id' => $masterItem->id,
                    'name' => $masterItem->name,
                    'image' => $masterItem->image_url ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=400&q=80',
                    'category' => $masterItem->category?->name ?? 'Alat Outdoor',
                    'price_per_day' => $dailyRate,
                    'qty' => $qty,
                    'max_qty' => max(1, (int) ($masterItem->units_count ?? 10)),
                    'pickup_date' => $pickupDate,
                    'return_date' => $returnDate,
                    'duration_days' => $durationDays,
                    'total_price' => $itemTotal,
                ];
            }

            if (! empty($items)) {
                $cartSummary = [
                    'total_qty' => $totalQty,
                    'total_price' => $totalPrice,
                    'pickup_date' => $pickupDate,
                    'return_date' => $returnDate,
                    'duration_days' => $durationDays,
                ];
            }
        }

        return view('customer.cart.index', compact('items', 'vendor', 'cartSummary'));
    }

    /**
     * Update jumlah unit satu alat di keranjang.
     */
    public function updateQty(Request $request): RedirectResponse
    {
        $request->validate([
            'master_item_id' => 'required|integer',
            'qty' => 'required|integer|min:0',
        ]);

        $masterItemId = (int) $request->input('master_item_id');
        $qty = (int) $request->input('qty');

        $this->cartService->updateItemQty($masterItemId, $qty, Auth::user());

        return redirect()->route('customer.cart.index')->with('success', 'Jumlah alat berhasil diperbarui.');
    }

    /**
     * Hapus satu alat dari keranjang.
     */
    public function removeItem(Request $request): RedirectResponse
    {
        $request->validate([
            'master_item_id' => 'required|integer',
        ]);

        $masterItemId = (int) $request->input('master_item_id');
        $this->cartService->removeItem($masterItemId, Auth::user());

        return redirect()->route('customer.cart.index')->with('success', 'Alat berhasil dihapus dari keranjang.');
    }

    /**
     * Kosongkan seluruh isi keranjang.
     */
    public function clear(): RedirectResponse
    {
        $this->cartService->clear(Auth::user());

        return redirect()->route('customer.cart.index')->with('success', 'Keranjang sewa berhasil dikosongkan.');
    }

    /**
     * Lanjut dari keranjang ke Checkout.
     */
    public function checkout(): RedirectResponse
    {
        $user = Auth::user();
        $cart = $this->cartService->get($user);

        if (! is_array($cart) || empty($cart['master_item_id'])) {
            return redirect()
                ->route('customer.cart.index')
                ->with('warning', 'Keranjang sewa Anda masih kosong.');
        }

        return redirect()->route('checkout.index');
    }
}
