<?php

namespace App\Services\Booking;

use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Models\Payment;
use App\Models\Rental;
use App\Services\Payment\MidtransService;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Satu pintu checkout untuk barang dan paket.
 *
 * Urutan: buat rental (pending, stok tertahan) -> buat payment bila metode
 * sudah dipilih -> (opsional) minta token Snap.
 */
class CheckoutService
{
    public function __construct(
        private readonly RentalService $rentals,
        private readonly PaymentService $payments,
        private readonly MidtransService $midtrans,
    ) {}

    /**
     * @param  array<string, mixed>  $bookingData  Data untuk RentalService::createBooking()
     * @param  bool  $withSnapToken  Whether to request a Snap token for the payment
     * @param  bool  $deferPayment  Keep payment out of the database until a method is selected
     * @return array{rental: Rental, payment: ?Payment, snap_token: ?string}
     */
    public function checkout(
        array $bookingData,
        string $paymentOption = 'full',
        ?string $channel = null,
        bool $withSnapToken = true,
        ?string $previewBookingCode = null,
        bool $deferPayment = false,
    ): array {
        $rental = $this->rentals->createBooking($bookingData);

        try {
            $rental = $this->applyPreviewBookingCode($rental, $previewBookingCode);

            if ($deferPayment) {
                if ($paymentOption === 'dp') {
                    $rental->update([
                        'dp_amount' => $this->payments->calculateDpAmount(
                            (float) $rental->subtotal,
                            (float) $rental->delivery_fee,
                            (float) $rental->discount_amount,
                        ),
                    ]);
                }

                return [
                    'rental' => $rental,
                    'payment' => null,
                    'snap_token' => null,
                ];
            }

            $payment = $this->payments->createOnlinePayment(
                $rental,
                $paymentOption,
                1,
                $channel,
            );

            $snapToken = $withSnapToken
                ? $this->midtrans->createTransaction($payment)
                : null;
        } catch (Throwable $e) {
            $this->cancelPending($rental);

            throw $e;
        }

        return [
            'rental' => $rental,
            'payment' => $payment,
            'snap_token' => $snapToken,
        ];
    }

    /**
     * Terapkan kode booking yang sudah ditampilkan di pratinjau checkout.
     */
    private function applyPreviewBookingCode(Rental $rental, ?string $previewCode): Rental
    {
        if (! is_string($previewCode) || $previewCode === '' || $previewCode === $rental->booking_code) {
            return $rental;
        }

        $exists = Rental::withoutGlobalScopes()
            ->where('booking_code', $previewCode)
            ->where('id', '!=', $rental->id)
            ->exists();

        if ($exists) {
            return $rental;
        }

        $rental->booking_code = $previewCode;
        $rental->save();

        return $rental;
    }

    /**
     * Batalkan rental pending beserta payment pending-nya (stok langsung bebas).
     */
    private function cancelPending(Rental $rental): void
    {
        try {
            DB::transaction(function () use ($rental): void {
                Rental::query()
                    ->whereKey($rental->getKey())
                    ->where('status', RentalStatus::Pending->value)
                    ->update(['status' => RentalStatus::Cancelled->value]);

                Payment::query()
                    ->where('rental_id', $rental->getKey())
                    ->where('status', PaymentStatus::Pending->value)
                    ->update(['status' => PaymentStatus::Cancelled->value]);
            });
        } catch (Throwable $e) {
            report($e);
        }
    }
}
