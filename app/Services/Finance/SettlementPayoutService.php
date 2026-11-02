<?php

namespace App\Services\Finance;

use App\Exceptions\IrisConnectionException;
use App\Exceptions\IrisPayoutException;
use App\Models\Settlement;
use App\Models\User;
use App\Models\VendorInvoice;
use App\Services\Payment\MidtransIrisService;
use Illuminate\Support\Str;

class SettlementPayoutService
{
    public function __construct(
        private readonly MidtransIrisService $iris,
        private readonly SettlementService $settlements,
    ) {}

    /**
     * Buat payout Iris, setujui, lalu sinkronkan statusnya.
     *
     * Settlement hanya ditandai paid setelah Midtrans mengembalikan status
     * `completed`, bukan saat payout baru dibuat atau disetujui.
     *
     * @return array{status: string, settlement: Settlement, invoice: ?VendorInvoice}
     */
    public function start(Settlement $settlement, User $admin, ?string $otp = null): array
    {
        $settlement = $this->fresh($settlement);

        if ($settlement->isPaid()) {
            return $this->alreadyPaid($settlement);
        }

        if (! $settlement->isApproved()) {
            throw new IrisPayoutException('Batch harus berstatus approved sebelum dicairkan melalui Midtrans Iris.');
        }

        if (! $this->iris->isConfigured()) {
            throw new IrisPayoutException('Kredensial Midtrans Iris belum lengkap. Isi MIDTRANS_IRIS_CREATOR_KEY dan MIDTRANS_IRIS_APPROVER_KEY.');
        }

        $hasReference = filled($settlement->iris_reference_no);

        if ($hasReference && in_array($settlement->iris_status, ['failed', 'rejected'], true)) {
            throw new IrisPayoutException('Payout Iris sebelumnya '.$settlement->iris_status.'. Gunakan transfer manual atau proses ulang dari dashboard Midtrans.');
        }

        if (! $hasReference) {
            if (! in_array($settlement->iris_status, [null, 'requested', 'unknown', 'failed', 'rejected'], true)) {
                throw new IrisPayoutException('Status payout Midtrans Iris tidak dapat dilanjutkan. Periksa dashboard Midtrans sebelum mencoba lagi.');
            }

            $this->iris->ensurePayoutDetails($settlement);

            $settlement->update([
                'iris_idempotency_key' => filled($settlement->iris_idempotency_key)
                    ? (string) $settlement->iris_idempotency_key
                    : 'TDK-SETTLEMENT-'.$settlement->getKey(),
                'iris_bank_code' => $this->iris->bankCode($settlement->bank_name),
                'iris_status' => 'requested',
                'iris_requested_at' => $settlement->iris_requested_at ?? now(),
                'iris_error' => null,
            ]);

            try {
                $created = $this->iris->createPayout($settlement->refresh());

                $settlement->update([
                    'iris_reference_no' => $created['reference_no'],
                    'iris_status' => $created['status'],
                ]);
            } catch (IrisConnectionException $exception) {
                $settlement->update([
                    'iris_status' => 'unknown',
                    'iris_error' => $exception->getMessage(),
                ]);

                throw $exception;
            } catch (IrisPayoutException $exception) {
                $settlement->update([
                    'iris_status' => 'failed',
                    'iris_error' => $exception->getMessage(),
                ]);

                throw $exception;
            }
        }

        $settlement->refresh();

        if (in_array($settlement->iris_status, ['queued', 'requested'], true)) {
            try {
                $this->iris->approvePayout((string) $settlement->iris_reference_no, $otp);

                $settlement->update([
                    'iris_status' => 'approved',
                    'iris_approved_at' => now(),
                    'iris_error' => null,
                ]);
            } catch (IrisConnectionException $exception) {
                $settlement->update(['iris_error' => $exception->getMessage()]);

                throw $exception;
            } catch (IrisPayoutException $exception) {
                $settlement->update(['iris_error' => $exception->getMessage()]);

                throw $exception;
            }
        }

        return $this->sync($settlement, $admin);
    }

    /**
     * Sinkronkan status payout dan tandai settlement paid hanya bila `completed`.
     *
     * @return array{status: string, settlement: Settlement, invoice: ?VendorInvoice}
     */
    public function sync(Settlement $settlement, User $admin): array
    {
        $settlement = $this->fresh($settlement);

        if ($settlement->isPaid()) {
            return $this->alreadyPaid($settlement);
        }

        if (! filled($settlement->iris_reference_no)) {
            throw new IrisPayoutException('Batch ini belum memiliki payout Midtrans Iris.');
        }

        try {
            $payout = $this->iris->getPayout((string) $settlement->iris_reference_no);
        } catch (IrisPayoutException $exception) {
            $settlement->update(['iris_error' => $exception->getMessage()]);

            throw $exception;
        }

        $status = strtolower((string) ($payout['status'] ?? 'unknown'));
        $error = $this->errorFromPayout($payout);

        $attributes = [
            'iris_status' => $status,
            'iris_error' => $error,
        ];

        if (in_array($status, ['approved', 'processed', 'completed'], true) && $settlement->iris_approved_at === null) {
            $attributes['iris_approved_at'] = now();
        }

        if ($status === 'completed' && $settlement->iris_completed_at === null) {
            $attributes['iris_completed_at'] = now();
        }

        $settlement->update($attributes);

        if ($status === 'completed') {
            $notes = trim(implode(' | ', array_filter([
                $settlement->notes,
                config('services.midtrans_iris.is_production')
                    ? 'Payout Midtrans Iris'
                    : 'Payout Midtrans Iris sandbox',
            ])));

            $invoice = $this->settlements->markPaid(
                $settlement,
                $admin,
                (string) $settlement->iris_reference_no,
                route('settlements.payout-proof', $settlement),
                $notes,
            );

            if ($invoice === null) {
                $settlement->refresh();

                if ($settlement->isPaid()) {
                    return $this->alreadyPaid($settlement);
                }

                throw new IrisPayoutException('Payout Midtrans Iris selesai, tetapi batch gagal ditandai lunas.');
            }

            return [
                'status' => 'completed',
                'settlement' => $settlement->refresh(),
                'invoice' => $invoice,
            ];
        }

        if (in_array($status, ['failed', 'rejected'], true)) {
            throw new IrisPayoutException(
                'Payout Midtrans Iris '.$status.($error !== null ? ': '.$error : '.')
            );
        }

        return [
            'status' => $status,
            'settlement' => $settlement->refresh(),
            'invoice' => null,
        ];
    }

    private function fresh(Settlement $settlement): Settlement
    {
        return Settlement::withoutGlobalScope('vendor')
            ->with(['vendor', 'invoices'])
            ->findOrFail($settlement->getKey());
    }

    /**
     * @return array{status: string, settlement: Settlement, invoice: ?VendorInvoice}
     */
    private function alreadyPaid(Settlement $settlement): array
    {
        return [
            'status' => 'completed',
            'settlement' => $settlement,
            'invoice' => $settlement->invoices->first(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payout
     */
    private function errorFromPayout(array $payout): ?string
    {
        $message = data_get($payout, 'error_details.error_message')
            ?? data_get($payout, 'error_details.message')
            ?? data_get($payout, 'error_message');

        if (! is_string($message) || trim($message) === '') {
            return null;
        }

        return Str::limit(trim($message), 500);
    }
}
