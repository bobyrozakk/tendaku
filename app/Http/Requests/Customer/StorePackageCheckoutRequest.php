<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi POST /paket-camping/{id}/checkout.
 */
class StorePackageCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $isGuest = $this->user() === null;

        return [
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'pickup_time' => ['nullable', 'date_format:H:i'],
            'return_date' => ['nullable', 'date', 'after_or_equal:pickup_date'],
            'return_time' => ['nullable', 'date_format:H:i'],
            'pickup_method' => ['nullable', 'in:self_pickup,delivery'],
            'guest_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'guest_phone' => [$isGuest ? 'required' : 'nullable', 'string', 'max:32'],
            'guest_id_number' => [$isGuest ? 'required' : 'nullable', 'string', 'max:32'],
            'customer_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:32'],
            'nik' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pickup_date.required' => 'Tanggal mulai sewa wajib dipilih.',
            'pickup_date.after_or_equal' => 'Tanggal mulai sewa tidak boleh di masa lalu.',
            'return_date.after_or_equal' => 'Tanggal pengembalian harus sama atau setelah tanggal mulai sewa.',
            'guest_name.required' => 'Nama lengkap wajib diisi untuk pemesanan tanpa akun.',
            'guest_phone.required' => 'Nomor WhatsApp wajib diisi agar pengingat pengembalian dapat dikirim.',
            'guest_id_number.required' => 'NIK wajib diisi untuk pemesanan tanpa akun.',
            'customer_address_id.exists' => 'Alamat pengantaran yang dipilih tidak valid.',
        ];
    }
}
