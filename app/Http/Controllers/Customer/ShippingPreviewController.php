<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ShippingPreviewRequest;
use App\Services\Booking\CartService;
use App\Services\Booking\CheckoutShippingService;
use Illuminate\Http\JsonResponse;

class ShippingPreviewController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ShippingPreviewRequest $request, CheckoutShippingService $shipping, CartService $cart): JsonResponse
    {
        $result = $shipping->preview($request->validated(), $request->user());
        if ($request->validated('checkout_type') === 'regular' && $request->has(['pickup_date', 'return_date'])) {
            $dates = $request->safe()->only(['pickup_date', 'return_date', 'pickup_time']);
            if (isset($dates['pickup_time'])) {
                $dates['return_time'] = $dates['pickup_time'];
            }
            $cart->updateDates($dates, $request->user());
        }

        return response()->json($result);
    }
}
