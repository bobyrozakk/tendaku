<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ShippingPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'checkout_type' => ['required', 'in:regular,package'],
            'pickup_method' => ['required', 'in:self_pickup,delivery'],
            'customer_address_id' => ['required_if:pickup_method,delivery', 'nullable', 'integer', 'min:1'],
            'package_id' => ['exclude_unless:checkout_type,package', 'required', 'integer', 'min:1'],
            'items' => ['exclude_unless:checkout_type,regular', 'sometimes', 'array', 'min:1', 'max:100'],
            'items.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:10000'],
            'pickup_date' => ['exclude_unless:checkout_type,regular', 'required_with:return_date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'return_date' => ['exclude_unless:checkout_type,regular', 'required_with:pickup_date', 'date_format:Y-m-d', 'after_or_equal:pickup_date'],
        ];
    }
}
