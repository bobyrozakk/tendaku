<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
        $isGuest = Auth::guest();

        return [
            'pickup_method' => ['required', 'string', 'in:self_pickup,delivery'],
            'payment_option' => ['required', 'string', 'in:full,dp'],
            'payment_channel' => ['nullable', 'string', 'max:50'],
            'pickup_date' => ['nullable', 'date', 'after_or_equal:today'],
            'return_date' => ['nullable', 'date', 'after_or_equal:pickup_date'],

            // Wajib untuk guest
            'guest_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:100'],
            'guest_phone' => [$isGuest ? 'required' : 'nullable', 'string', 'max:20'],
            'guest_id_number' => [$isGuest ? 'required' : 'nullable', 'string', 'max:30'],

            // Delivery hanya untuk user login
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
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
        ];
    }
}
