<?php

namespace App\Http\Requests\Vendor;

use App\Models\MasterItem;
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
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'deleted_images' => ['nullable', 'array'],
            'deleted_images.*' => ['integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $product = $this->route('product');
            if ($product instanceof MasterItem) {
                $existingCount = $product->images()->count();
                if ($existingCount === 0 && ! empty($product->image_url)) {
                    $existingCount = 1;
                }

                $deletedCount = is_array($this->deleted_images) ? count($this->deleted_images) : 0;
                $remainingExisting = max(0, $existingCount - $deletedCount);

                $newImages = $this->file('images');
                $newCount = is_array($newImages) ? count($newImages) : ($this->hasFile('image') ? 1 : 0);

                if ($remainingExisting + $newCount > 5) {
                    $validator->errors()->add('images', 'Maksimal total foto untuk produk ini adalah 5 foto.');
                }
            }
        });
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
