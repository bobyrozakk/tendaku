<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class ShippingSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isVendorOwner() && ! empty($user->vendor_id);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'rate_per_km' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'weight_threshold_kg' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'extra_weight_fee_per_kg' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rate_per_km.required' => 'Tarif per kilometer wajib diisi.',
            'rate_per_km.numeric' => 'Tarif per kilometer harus berupa angka yang valid.',
            'rate_per_km.min' => 'Tarif per kilometer tidak boleh bernilai negatif.',
            'rate_per_km.max' => 'Tarif per kilometer melebihi batas maksimal yang diizinkan.',
            'weight_threshold_kg.required' => 'Batas berat normal wajib diisi.',
            'weight_threshold_kg.numeric' => 'Batas berat normal harus berupa angka yang valid.',
            'weight_threshold_kg.min' => 'Batas berat normal tidak boleh bernilai negatif.',
            'weight_threshold_kg.max' => 'Batas berat normal melebihi batas maksimal (999.999,99 kg).',
            'extra_weight_fee_per_kg.required' => 'Biaya tambahan per kilogram wajib diisi.',
            'extra_weight_fee_per_kg.numeric' => 'Biaya tambahan per kilogram harus berupa angka yang valid.',
            'extra_weight_fee_per_kg.min' => 'Biaya tambahan per kilogram tidak boleh bernilai negatif.',
            'extra_weight_fee_per_kg.max' => 'Biaya tambahan per kilogram melebihi batas maksimal yang diizinkan.',
        ];
    }
}
