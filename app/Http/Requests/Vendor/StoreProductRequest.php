<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
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
            'images.array' => 'Daftar foto produk harus berupa array file.',
            'images.max' => 'Maksimal 5 foto produk yang dapat diunggah.',
            'images.*.image' => 'File yang diunggah harus berupa gambar.',
            'images.*.mimes' => 'Format setiap foto harus JPG, JPEG, PNG, atau WEBP.',
            'images.*.max' => 'Ukuran setiap foto maksimal adalah 2MB.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.',
            'image.max' => 'Ukuran gambar maksimal adalah 2MB.',
        ];
    }
}
