<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampingPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'vendor_owner';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $vendorId = (int) $this->user()->vendor_id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:30'],
            'max_people' => ['nullable', 'integer', 'min:1', 'max:100'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.master_item_id' => [
                'required',
                'integer',
                Rule::exists('master_items', 'id')->where(function ($query) use ($vendorId) {
                    $query->where('vendor_id', $vendorId)->whereNull('deleted_at');
                }),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama paket camping wajib diisi.',
            'name.max' => 'Nama paket maksimal 150 karakter.',
            'price.required' => 'Harga paket wajib diisi.',
            'price.numeric' => 'Harga paket harus berupa angka.',
            'price.min' => 'Harga paket tidak boleh bernilai negatif.',
            'duration_days.required' => 'Durasi sewa paket wajib diisi.',
            'duration_days.min' => 'Durasi sewa paket minimal 1 hari.',
            'duration_days.max' => 'Durasi sewa paket maksimal 30 hari.',
            'max_people.min' => 'Kapasitas minimal 1 orang.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format gambar harus jpeg, png, jpg, atau webp.',
            'image.max' => 'Ukuran gambar maksimal adalah 2MB.',
            'items.required' => 'Paket camping minimal berisi satu produk.',
            'items.min' => 'Paket camping minimal berisi satu produk.',
            'items.*.master_item_id.required' => 'Produk pada paket belum dipilih.',
            'items.*.master_item_id.exists' => 'Produk yang dipilih tidak tersedia untuk toko Anda.',
            'items.*.quantity.required' => 'Jumlah produk wajib diisi.',
            'items.*.quantity.min' => 'Jumlah produk minimal 1.',
        ];
    }

    /**
     * Produk unik yang dipilih beserta kuantitasnya.
     *
     * @return array<int, array{master_item_id: int, quantity: int}>
     */
    public function packageItems(): array
    {
        $merged = [];

        foreach ((array) $this->input('items', []) as $row) {
            $masterItemId = (int) ($row['master_item_id'] ?? 0);
            $quantity = (int) ($row['quantity'] ?? 0);

            if ($masterItemId <= 0 || $quantity <= 0) {
                continue;
            }

            $merged[$masterItemId] = ($merged[$masterItemId] ?? 0) + $quantity;
        }

        $items = [];

        foreach ($merged as $masterItemId => $quantity) {
            $items[] = [
                'master_item_id' => $masterItemId,
                'quantity' => $quantity,
            ];
        }

        return $items;
    }
}
