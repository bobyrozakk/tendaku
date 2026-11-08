<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ShippingPreviewRequest;
use App\Services\Booking\CheckoutShippingService;
use Illuminate\Http\JsonResponse;

class ShippingPreviewController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ShippingPreviewRequest $request, CheckoutShippingService $shipping): JsonResponse
    {
        return response()->json($shipping->preview($request->validated(), $request->user()));
    }
}
