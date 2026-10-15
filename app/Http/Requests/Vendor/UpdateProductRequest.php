<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'vendor_owner';
    }

    public function rules(): array
    {
        $vendorId = $this->user()->vendor_id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('item_categories', 'id')->where(function ($query) use ($vendorId) {
                    $query->where('vendor_id', $vendorId)->whereNull('deleted_at');
                }),
            ],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'late_fee_per_day' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama produk wajib diisi.',
            'category_id.required' => 'Pilih kategori produk yang valid.',
            'category_id.exists' => 'Kategori yang dipilih tidak tersedia untuk toko Anda.',
            'daily_rate.required' => 'Tarif sewa per hari wajib diisi.',
            'daily_rate.numeric' => 'Tarif sewa harus berupa angka.',
            'daily_rate.min' => 'Tarif sewa tidak boleh bernilai negatif.',
            'late_fee_per_day.required' => 'Denda keterlambatan per hari wajib diisi.',
            'late_fee_per_day.numeric' => 'Denda keterlambatan harus berupa angka.',
            'late_fee_per_day.min' => 'Denda keterlambatan tidak boleh bernilai negatif.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format gambar harus jpeg, png, jpg, atau webp.',
            'image.max' => 'Ukuran gambar maksimal adalah 2MB.',
        ];
    }
}
