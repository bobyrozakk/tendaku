<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class VendorLocationRequest extends FormRequest
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
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address.string' => 'Alamat toko harus berupa teks yang valid.',
            'address.max' => 'Alamat toko maksimal 500 karakter.',
            'city.string' => 'Nama kota harus berupa teks yang valid.',
            'city.max' => 'Nama kota maksimal 100 karakter.',
            'latitude.required' => 'Titik koordinat latitude wajib dipilih pada peta.',
            'latitude.numeric' => 'Koordinat latitude harus berupa angka yang valid.',
            'latitude.between' => 'Koordinat latitude harus berada pada rentang -90 hingga 90.',
            'longitude.required' => 'Titik koordinat longitude wajib dipilih pada peta.',
            'longitude.numeric' => 'Koordinat longitude harus berupa angka yang valid.',
            'longitude.between' => 'Koordinat longitude harus berada pada rentang -180 hingga 180.',
        ];
    }
}
