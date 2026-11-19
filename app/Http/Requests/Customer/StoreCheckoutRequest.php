<?php

namespace App\Http\Requests\Customer;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi payload POST /katalog/booking dari form checkout.
 *
 * Field yang dikirim view (checkout/index.blade.php):
 *   pickup_method, payment_option, payment_channel,
 *   guest_name?, guest_phone?, guest_id_number?,
 *   delivery_address?, notes?
 */
class StoreCheckoutRequest extends FormRequest
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
        return [
            'pickup_method' => ['required', 'string', 'in:self_pickup,delivery'],
            'payment_option' => ['nullable', 'string', 'in:full,dp'],
            'payment_channel' => ['nullable', 'string', 'max:50'],
            'pickup_date' => ['nullable', 'date', 'after_or_equal:today'],
            'pickup_time' => ['nullable', 'date_format:H:i'],
            'return_date' => ['nullable', 'date', 'after:pickup_date'],
            'items' => ['nullable', 'array', 'max:100'],
            'items.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:10000'],
            'expected_delivery_fee' => ['nullable', 'integer', 'min:0'],
            'expected_shipping_context' => ['nullable', 'string', 'size:64'],

            // Data identitas guest (divalidasi di controller setelah pengecekan keranjang & delivery guard)
            'guest_name' => ['nullable', 'string', 'max:100'],
            'guest_phone' => ['nullable', 'string', 'regex:/^[0-9]{10,13}$/'],
            'guest_id_number' => ['nullable', 'string', 'max:30'],

            // Data penyewa akun jika belum lengkap di profil
            'name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{10,13}$/'],
            'nik' => ['nullable', 'string', 'regex:/^[0-9]{16}$/'],

            // Delivery & alamat
            'customer_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $date = $this->input('pickup_date');
            $time = $this->input('pickup_time');

            if (
                is_string($date) && is_string($time)
                && ! $validator->errors()->hasAny(['pickup_date', 'pickup_time'])
                && Carbon::parse("{$date} {$time}")->isPast()
            ) {
                $validator->errors()->add('pickup_time', 'Jam ambil tidak boleh di masa lalu.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pickup_method.required' => 'Metode pengambilan barang wajib dipilih.',
            'pickup_method.in' => 'Metode pengambilan tidak valid.',
            'payment_option.required' => 'Opsi pembayaran wajib dipilih.',
            'payment_option.in' => 'Opsi pembayaran tidak valid. Pilih antara pembayaran penuh atau uang muka (DP).',
            'guest_name.required' => 'Nama lengkap wajib diisi untuk pemesanan tanpa akun.',
            'guest_phone.required' => 'Nomor telepon wajib diisi untuk pemesanan tanpa akun.',
            'guest_phone.regex' => 'Nomor ponsel harus terdiri dari 10 sampai 13 digit angka.',
            'guest_id_number.required' => 'Nomor KTP wajib diisi untuk pemesanan tanpa akun.',
            'phone.required' => 'Nomor ponsel wajib diisi.',
            'phone.regex' => 'Nomor ponsel harus terdiri dari 10 sampai 13 digit angka.',
            'nik.required' => 'NIK wajib diisi.',
            'nik.regex' => 'NIK harus terdiri dari 16 digit angka.',
            'customer_address_id.exists' => 'Alamat pengantaran yang dipilih tidak valid.',
        ];
    }
}
