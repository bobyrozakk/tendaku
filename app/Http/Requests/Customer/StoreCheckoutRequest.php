<?php

namespace App\Http\Requests\Customer;

use App\Services\PhoneNumberNormalizer;
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
        $isGuest = $this->user() === null;

        return [
            'pickup_method' => ['required', 'string', 'in:self_pickup,delivery'],
            'payment_option' => ['nullable', 'string', 'in:full,dp'],
            'payment_channel' => ['nullable', 'string', 'max:50'],
            'pickup_date' => ['nullable', 'date', 'after_or_equal:today'],
            'return_date' => ['nullable', 'date', 'after_or_equal:pickup_date'],

            // Data identitas guest (divalidasi di controller setelah pengecekan keranjang & delivery guard)
            'guest_name' => ['nullable', 'string', 'max:100'],
            'guest_phone' => ['nullable', 'string', 'max:20'],
            'guest_id_number' => ['nullable', 'string', 'max:30'],

            // Data penyewa akun jika belum lengkap di profil
            'name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'nik' => ['nullable', 'string', 'max:30'],

            // Delivery & alamat
            'customer_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->user() !== null) {
                return;
            }

            $phone = $this->input('guest_phone');

            if ($validator->errors()->has('guest_phone') || ! is_string($phone)) {
                return;
            }

            if (PhoneNumberNormalizer::normalize($phone) === null) {
                $validator->errors()->add(
                    'guest_phone',
                    'Nomor WhatsApp tidak valid. Gunakan format 08xxxxxxxxxx.'
                );
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
            'guest_id_number.required' => 'Nomor KTP wajib diisi untuk pemesanan tanpa akun.',
            'customer_address_id.exists' => 'Alamat pengantaran yang dipilih tidak valid.',
        ];
    }
}
