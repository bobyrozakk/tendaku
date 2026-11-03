<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Services\Booking\BookingReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Pengecekan status sewa publik memakai kode booking pada receipt.
 *
 * Kode booking menjadi kunci akses, jadi halaman ini tidak memerlukan login.
 * Hanya informasi ringkas yang dibutuhkan penyewa yang ditampilkan; nomor
 * telepon, nomor identitas, dan data internal pembayaran tidak diikutkan.
 */
class RentalStatusLookupController extends Controller
{
    public function index(): View
    {
        return view('customer.rental-status.index');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_code' => ['required', 'string', 'max:50'],
        ], [
            'booking_code.required' => 'Masukkan kode sewa terlebih dahulu.',
            'booking_code.max' => 'Kode sewa terlalu panjang. Periksa kembali kode pada receipt.',
        ]);

        $code = $this->normalizeCode($validated['booking_code']);

        if (! Rental::query()->where('booking_code', $code)->exists()) {
            return back()
                ->withInput()
                ->withErrors([
                    'booking_code' => 'Kode sewa tidak valid atau tidak ditemukan. Periksa kembali kode pada receipt pemesanan Anda.',
                ]);
        }

        return redirect()->route('customer.rental-status.show', ['code' => $code]);
    }

    public function show(string $code, BookingReceiptService $receiptService): Response|RedirectResponse
    {
        $rental = Rental::query()
            ->with([
                'vendor:id,name,city',
                'customer:id,name',
                'details.masterItem:id,name,category_id',
                'details.masterItem.category:id,name',
            ])
            ->where('booking_code', $this->normalizeCode($code))
            ->first();

        if (! $rental) {
            return redirect()
                ->route('customer.rental-status.index')
                ->with('error', 'Kode sewa tidak valid atau tidak ditemukan. Periksa kembali kode pada receipt pemesanan Anda.');
        }

        // Samakan perilaku dengan halaman status: pending yang lewat batas bayar
        // langsung ditampilkan sebagai kedaluwarsa.
        $rental->expireIfPastDue();
        $rental->load('payments:id,rental_id,status,payment_type,amount,paid_at');

        $receipt = $receiptService->build($rental);
        $paymentStatus = $this->paymentStatus($rental, $receipt);

        return response()
            ->view('customer.rental-status.show', [
                'rental' => $rental,
                'receipt' => $receipt,
                'paymentStatus' => $paymentStatus,
                'timeline' => $this->timeline($rental, (bool) $receipt['isPaid']),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    }

    /**
     * Samakan input dengan kode yang disimpan: huruf besar, tanpa spasi,
     * dan tanpa tanda pagar yang biasa ikut tersalin dari receipt.
     */
    private function normalizeCode(string $code): string
    {
        return strtoupper(str_replace(['#', ' '], '', trim($code)));
    }

    /**
     * @param  array<string, mixed>  $receipt
     * @return array{label: string, tone: 'success'|'warning'|'danger'|'info'}
     */
    private function paymentStatus(Rental $rental, array $receipt): array
    {
        $paidPayments = $rental->payments->where('status', 'paid');
        $latestPayment = $rental->payments->sortByDesc('id')->first();

        if ($paidPayments->isNotEmpty()) {
            $amountPaid = (float) $paidPayments->sum('amount');
            $total = (float) $receipt['grandTotal'];

            return $amountPaid + 0.01 >= $total
                ? ['label' => 'Lunas', 'tone' => 'success']
                : ['label' => 'Dibayar Sebagian', 'tone' => 'warning'];
        }

        return match ($latestPayment?->status) {
            'pending' => ['label' => 'Menunggu Pembayaran', 'tone' => 'warning'],
            'expired' => ['label' => 'Kedaluwarsa', 'tone' => 'danger'],
            'cancelled' => ['label' => 'Dibatalkan', 'tone' => 'danger'],
            'failed' => ['label' => 'Gagal', 'tone' => 'danger'],
            'refunded' => ['label' => 'Dikembalikan', 'tone' => 'info'],
            default => [
                'label' => $rental->status === 'cancelled' ? 'Dibatalkan' : 'Belum Ada Tagihan',
                'tone' => 'info',
            ],
        };
    }

    /**
     * Tahapan mengikuti alur rental yang sudah ada:
     * pending -> confirmed -> picked_up -> returned -> completed.
     *
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     state: 'done'|'active'|'todo',
     *     at: string|null
     * }>
     */
    private function timeline(Rental $rental, bool $isPaid): array
    {
        $paidAt = $rental->payments
            ->where('status', 'paid')
            ->sortByDesc('paid_at')
            ->first()?->paid_at;

        $steps = [
            [
                'key' => 'pending',
                'label' => 'Pesanan Dibuat',
                'description' => 'Pesanan tercatat dengan kode '.$rental->booking_code.'.',
                'at' => $rental->created_at,
            ],
            [
                'key' => 'confirmed',
                'label' => 'Dikonfirmasi',
                'description' => 'Pesanan dan pembayaran telah dikonfirmasi vendor.',
                'at' => $paidAt,
            ],
            [
                'key' => 'picked_up',
                'label' => 'Alat Diambil',
                'description' => 'Peralatan telah diserahkan kepada penyewa.',
                'at' => $rental->picked_up_at,
            ],
            [
                'key' => 'returned',
                'label' => 'Dikembalikan',
                'description' => 'Peralatan telah dikembalikan dan diperiksa vendor.',
                'at' => $rental->returned_at,
            ],
            [
                'key' => 'completed',
                'label' => 'Selesai',
                'description' => 'Seluruh kewajiban sewa telah selesai.',
                'at' => $rental->completed_at
                    ?? ($rental->status === 'completed' ? $rental->updated_at : null),
            ],
        ];

        $currentIndex = match ($rental->status) {
            'pending' => $isPaid ? 1 : 0,
            'confirmed' => 1,
            'picked_up' => 2,
            'returned' => 3,
            'completed' => 4,
            default => null,
        };

        return array_map(
            function (array $step, int $index) use ($currentIndex, $rental): array {
                $state = match (true) {
                    $currentIndex === null => $index === 0 ? 'done' : 'todo',
                    $index < $currentIndex => 'done',
                    $index === $currentIndex => $rental->status === 'completed' ? 'done' : 'active',
                    default => 'todo',
                };

                $step['state'] = $state;
                $step['at'] = $step['at']?->translatedFormat('d M Y, H:i');

                return $step;
            },
            $steps,
            array_keys($steps),
        );
    }
}
