<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\CartService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutAvailabilityController extends Controller
{
    public function __invoke(Request $request, CartService $cart, AvailabilityService $availability): JsonResponse
    {
        $itemsToCheck = [];

        if ($request->filled('item_id')) {
            $itemId = (int) $request->input('item_id');
            $qty = max(1, (int) $request->input('qty', 1));
            $itemsToCheck[$itemId] = $qty;
        } elseif ($request->filled('items') && is_array($request->input('items'))) {
            foreach ($request->input('items') as $row) {
                $id = (int) ($row['id'] ?? $row['master_item_id'] ?? 0);
                $qty = max(1, (int) ($row['qty'] ?? $row['quantity'] ?? 1));
                if ($id > 0) {
                    $itemsToCheck[$id] = max($itemsToCheck[$id] ?? 0, $qty);
                }
            }
        } else {
            $cartData = $cart->get(Auth::user());
            if (is_array($cartData)) {
                $rawItems = ! empty($cartData['items']) && is_array($cartData['items'])
                    ? array_values($cartData['items'])
                    : [['master_item_id' => $cartData['master_item_id'] ?? 0, 'qty' => $cartData['qty'] ?? 1]];

                foreach ($rawItems as $entry) {
                    $id = (int) ($entry['master_item_id'] ?? 0);
                    $qty = max(1, (int) ($entry['qty'] ?? $entry['quantity'] ?? 1));
                    if ($id > 0) {
                        $itemsToCheck[$id] = max($itemsToCheck[$id] ?? 0, $qty);
                    }
                }
            }
        }

        if (empty($itemsToCheck)) {
            return response()->json(['dates' => []]);
        }

        $today = Carbon::today();
        $from = $request->filled('from')
            ? Carbon::parse($request->string('from')->toString())->startOfDay()
            : $today;

        $to = $request->filled('to')
            ? Carbon::parse($request->string('to')->toString())->startOfDay()
            : $from->copy()->addDays(90);

        if ($from->lt($today)) {
            $from = $today;
        }

        if ($to->lt($from)) {
            $to = $from->copy()->addDays(30);
        }

        $allDates = [];

        foreach ($itemsToCheck as $itemId => $qty) {
            try {
                $dates = $availability->getBookedDates($itemId, $from, $to, $qty);
                foreach ($dates as $date) {
                    $allDates[$date] = true;
                }
            } catch (\Throwable) {
            }
        }

        ksort($allDates);

        return response()->json(['dates' => array_keys($allDates)]);
    }
}
