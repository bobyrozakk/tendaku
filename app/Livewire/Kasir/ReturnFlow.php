<?php

namespace App\Livewire\Kasir;

use App\Enums\PaymentStatus;
use App\Models\ItemUnit;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalDetail;
use App\Models\User;
use App\Services\Payment\MidtransService;
use App\Services\Payment\PaymentService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

class ReturnFlow extends Component
{
    // Tab Management ('return' or 'history')
    public $activeTab = 'return';

    public $activeOrderId = null;

    public $searchOrder = '';

    // History Search & Filter
    public $searchHistory = '';

    public $statusFilter = 'all';

    // Form Inputs for Inspection
    public $inspectionNotes = [];

    public $damageFees = [];

    public $itemNotes = [];

    public $returnedItems = [];

    // Modal Pelunasan Denda & Modal Struk
    public $showFineModal = false;

    public $finePaymentMethod = 'cash'; // 'cash' or 'qris'

    public $cashTendered = 0;

    public $finePaymentId = null;

    // Struk Print Modal
    public $showReceiptModal = false;

    public $receiptData = null;

    public function mount()
    {
        $this->loadDefaultActive();
    }

    public function updatedSearchOrder()
    {
        $this->loadDefaultActive();
    }

    private function loadDefaultActive(): void
    {
        $vendorId = Auth::user()?->vendor_id;
        $today = Carbon::today();

        $first = Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where(function ($q) use ($today) {
                $q->where('status', 'picked_up')
                    ->orWhere(function ($sub) use ($today) {
                        $sub->where('status', 'confirmed')
                            ->whereDate('return_date', '<', $today);
                    });
            })
            ->when($this->searchOrder, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('booking_code', 'like', "%{$this->searchOrder}%")
                        ->orWhere('guest_name', 'like', "%{$this->searchOrder}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$this->searchOrder}%"));
                });
            })
            ->first();

        if ($first && ! $this->activeOrderId) {
            $this->selectRental($first->id);
        }
    }

    public function selectRental($rentalId)
    {
        $this->activeOrderId = $rentalId;
        $this->inspectionNotes = [];
        $this->damageFees = [];
        $this->itemNotes = [];
        $this->returnedItems = [];
        $this->showFineModal = false;
        $this->finePaymentMethod = 'cash';
        $this->cashTendered = 0;
        $this->finePaymentId = null;

        $order = $this->getActiveOrderProperty();
        if ($order) {
            $isExpiredUnpicked = $this->getIsExpiredUnpickedProperty();

            foreach ($order->details as $detail) {
                if ($isExpiredUnpicked) {
                    // Pesanan tidak diambil hingga lewat tanggal kembali:
                    // Unit fisik tidak pernah dibawa penyewa, otomatis kondisinya Baik (good) dan bebas denda
                    $this->inspectionNotes[$detail->id] = 'good';
                    $this->damageFees[$detail->id] = 0;
                    $this->itemNotes[$detail->id] = 'Unit tidak diambil penyewa hingga lewat tanggal kembali.';
                    // Biarkan kasir meninjau lembar kerja, kasir dapat mengonfirmasi atau klik selesai
                    $this->returnedItems[$detail->id] = false;
                } else {
                    $this->inspectionNotes[$detail->id] = $detail->condition_after ?? 'good';
                    $this->damageFees[$detail->id] = (float) ($detail->damage_fee ?? 0);
                    $this->itemNotes[$detail->id] = $detail->checklist_notes ?? '';
                    if ($detail->is_returned) {
                        $this->returnedItems[$detail->id] = true;
                    }
                }
            }
        }
    }

    public function getIsExpiredUnpickedProperty(): bool
    {
        $order = $this->getActiveOrderProperty();
        if (! $order) {
            return false;
        }

        return $order->status === 'confirmed' && Carbon::today()->gt($order->return_date);
    }

    public function getActiveOrderProperty()
    {
        if (! $this->activeOrderId) {
            return null;
        }

        $vendorId = Auth::user()?->vendor_id;
        $today = Carbon::today();

        return Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where(function ($query) use ($today) {
                $query->where('status', 'picked_up')
                    ->orWhere(function ($subQuery) use ($today) {
                        $subQuery->where('status', 'confirmed')
                            ->whereDate('return_date', '<', $today);
                    });
            })
            ->with(['details.masterItem', 'details.itemUnit', 'customer'])
            ->find($this->activeOrderId);
    }

    public function getFineQrisPaymentProperty(): ?Payment
    {
        $order = $this->getActiveOrderProperty();

        if (! $order) {
            return null;
        }

        $query = $order->payments()
            ->where('payment_type', 'fine')
            ->where('payment_channel', 'qris')
            ->whereIn('status', ['pending', 'paid']);

        if ($this->finePaymentId) {
            $query->whereKey($this->finePaymentId);
        } else {
            $query->latest('id');
        }

        return $query->first();
    }

    public function getFineQrisImageUrlProperty(): ?string
    {
        $payment = $this->fineQrisPayment;

        if (! $payment || $payment->status !== 'pending' || ! $payment->qris_details) {
            return null;
        }

        return URL::temporarySignedRoute(
            'kasir.fine-qris-image',
            now()->addHours(24),
            ['payment' => $payment->getKey()],
        );
    }

    private function hasPendingFineQrisPayment(?Rental $order = null): bool
    {
        $order ??= $this->getActiveOrderProperty();

        return $order?->payments()
            ->where('payment_type', 'fine')
            ->where('payment_channel', 'qris')
            ->where('status', 'pending')
            ->exists() ?? false;
    }

    public function updatedInspectionNotes($value, $key): void
    {
        if ($this->hasPendingFineQrisPayment()) {
            $detail = $this->getActiveOrderProperty()?->details->firstWhere('id', $key);
            $this->inspectionNotes[$key] = $detail?->condition_after ?? 'good';
            $this->damageFees[$key] = (float) ($detail?->damage_fee ?? 0);
            $this->addError('finePayment', 'Tutup modal QRIS atau pilih Tunai agar tagihan dibatalkan sebelum mengubah hasil inspeksi.');

            return;
        }

        $this->resetErrorBag("damageFees.{$key}");
        if ($value === 'good') {
            $this->damageFees[$key] = 0;
        }
    }

    public function updatedDamageFees($value, $key): void
    {
        if (! $this->hasPendingFineQrisPayment()) {
            return;
        }

        $detail = $this->getActiveOrderProperty()?->details->firstWhere('id', $key);
        $this->damageFees[$key] = (float) ($detail?->damage_fee ?? 0);
        $this->addError('finePayment', 'Tutup modal QRIS atau pilih Tunai agar tagihan dibatalkan sebelum mengubah denda inspeksi.');
    }

    public function selectFinePaymentMethod(
        string $method,
        PaymentService $paymentService,
        MidtransService $midtrans,
    ): void {
        if (! in_array($method, ['cash', 'qris'], true)) {
            $this->addError('finePayment', 'Pilih metode pembayaran denda yang valid.');

            return;
        }

        $payment = $this->fineQrisPayment;

        if ($method === 'cash' && $payment?->status === 'paid') {
            $this->addError('finePayment', 'Denda sudah lunas melalui QRIS; selesaikan retur tanpa menerima pembayaran tunai lagi.');

            return;
        }

        if (
            $method === 'cash'
            && $payment?->status === 'pending'
            && ! $this->cancelPendingFineQrisPayment($midtrans)
        ) {
            return;
        }

        $this->finePaymentMethod = $method;
        $this->resetErrorBag('finePayment');
        $this->resetErrorBag('cashTendered');

        if ($method === 'qris') {
            $this->createFineQrisPayment($paymentService, $midtrans);
        }
    }

    public function closeFineModal(MidtransService $midtrans): void
    {
        if (
            $this->fineQrisPayment?->status === 'pending'
            && ! $this->cancelPendingFineQrisPayment($midtrans)
        ) {
            return;
        }

        $this->showFineModal = false;
        $this->resetErrorBag('finePayment');
    }

    public function setCondition($detailId, $condition)
    {
        if ($this->isExpiredUnpicked) {
            return;
        }

        if ($this->hasPendingFineQrisPayment()) {
            $this->addError('finePayment', 'Tutup modal QRIS atau pilih Tunai agar tagihan dibatalkan sebelum mengubah hasil inspeksi.');

            return;
        }

        $this->inspectionNotes[$detailId] = $condition;
        $this->resetErrorBag("damageFees.{$detailId}");

        if ($condition === 'good') {
            $this->damageFees[$detailId] = 0;
        }
    }

    public function markItemReturned($detailId)
    {
        if ($this->hasPendingFineQrisPayment()) {
            $this->addError('finePayment', 'Tutup modal QRIS atau pilih Tunai agar tagihan dibatalkan sebelum mengubah hasil inspeksi.');

            return;
        }

        $condition = $this->inspectionNotes[$detailId] ?? 'good';
        $fee = (float) ($this->damageFees[$detailId] ?? 0);

        // Validasi: jika kondisi rusak atau hilang, denda wajib diisi > 0 agar tidak kecolongan
        if (in_array($condition, ['damaged', 'lost']) && $fee <= 0) {
            $this->addError("damageFees.{$detailId}", 'Denda wajib diisi minimal Rp 1 bila kondisi barang '.($condition === 'damaged' ? 'Rusak' : 'Hilang').'.');

            return;
        }

        $this->resetErrorBag("damageFees.{$detailId}");
        $this->returnedItems[$detailId] = true;

        // Auto update individual detail in DB
        $detail = RentalDetail::find($detailId);
        if ($detail) {
            $lateDays = $this->lateDays;
            $ratePerDay = (float) ($detail->late_fee_per_day_snapshot ?? 0);

            $detail->update([
                'condition_after' => $condition,
                'damage_fee' => $fee,
                'checklist_notes' => $this->itemNotes[$detailId] ?? null,
                'is_returned' => true,
                'returned_at' => Carbon::now(),
                'late_days' => $lateDays,
                'late_fee' => $ratePerDay * $lateDays,
            ]);

            // Jika barang rusak/hilang atau dikembalikan, update item_unit juga
            if ($detail->item_unit_id) {
                $unitStatus = match ($condition) {
                    'lost' => 'lost',
                    'damaged' => 'maintenance',
                    default => 'available',
                };
                $unitCondition = match ($condition) {
                    'lost' => 'lost',
                    'damaged' => 'damaged',
                    default => 'good',
                };
                ItemUnit::withoutGlobalScope('vendor')
                    ->where('id', $detail->item_unit_id)
                    ->update([
                        'status' => $unitStatus,
                        'condition' => $unitCondition,
                    ]);
            }
        }
    }

    private function validateReturnInspection(Rental $order): bool
    {
        $allReturned = $order->details->every(fn (RentalDetail $detail) => ! empty($this->returnedItems[$detail->id]));

        if (! $allReturned) {
            $this->addError('general', 'Semua barang harus diinspeksi dan dikonfirmasi sebelum menyelesaikan return.');

            return false;
        }

        foreach ($order->details as $detail) {
            $condition = $this->inspectionNotes[$detail->id] ?? 'good';
            $fee = (float) ($this->damageFees[$detail->id] ?? 0);

            if (in_array($condition, ['damaged', 'lost'], true) && $fee <= 0) {
                $this->addError(
                    "damageFees.{$detail->id}",
                    'Denda wajib diisi minimal Rp 1 bila kondisi barang '.($condition === 'damaged' ? 'Rusak' : 'Hilang').'.'
                );

                return false;
            }
        }

        return true;
    }

    public function createFineQrisPayment(PaymentService $paymentService, MidtransService $midtrans): void
    {
        $this->showFineModal = true;

        if ($this->finePaymentMethod !== 'qris') {
            $this->addError('finePayment', 'Pilih QRIS sebagai metode pembayaran terlebih dahulu.');

            return;
        }

        $order = $this->getActiveOrderProperty();
        if (! $order || ! $this->validateReturnInspection($order)) {
            return;
        }

        $amount = (int) round($this->totalCharge);
        if ($amount <= 0) {
            $this->addError('finePayment', 'Tidak ada denda yang perlu dibayar.');

            return;
        }

        $payment = DB::transaction(function () use ($order, $paymentService, $amount): ?Payment {
            $lockedOrder = Rental::withoutGlobalScope('vendor')
                ->where('vendor_id', Auth::user()?->vendor_id)
                ->lockForUpdate()
                ->findOrFail($order->id);

            if (
                $lockedOrder->status !== 'picked_up'
                && ! ($lockedOrder->status === 'confirmed' && Carbon::today()->gt($lockedOrder->return_date))
            ) {
                return null;
            }

            $paidPayment = $lockedOrder->payments()
                ->where('payment_type', 'fine')
                ->where('status', 'paid')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($paidPayment) {
                return $paidPayment->payment_channel === 'qris'
                    && (int) $paidPayment->amount === $amount
                    ? $paidPayment
                    : null;
            }

            $pendingPayment = $lockedOrder->payments()
                ->where('payment_type', 'fine')
                ->where('status', 'pending')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($pendingPayment) {
                if (
                    $pendingPayment->payment_channel !== 'qris'
                    || (int) $pendingPayment->amount !== $amount
                ) {
                    return null;
                }

                return $pendingPayment;
            }

            return $paymentService->createOnlineFinePayment($lockedOrder, $amount, 1, 'qris');
        });

        if (! $payment) {
            $this->addError(
                'finePayment',
                'Tagihan denda lain masih aktif atau nominalnya berbeda. Batalkan tagihan sebelumnya sebelum membuat QRIS baru.'
            );

            return;
        }

        $this->finePaymentId = $payment->getKey();
        $this->resetErrorBag('finePayment');

        if ($payment->status === 'paid') {
            return;
        }

        $payment = $this->chargeFineQrisPayment($payment, $order, $amount, $paymentService, $midtrans);
        if (! $payment) {
            $this->addError(
                'finePayment',
                'Midtrans gagal membuat QRIS. Coba lagi atau batalkan tagihan lama terlebih dahulu.'
            );

            return;
        }

        $this->finePaymentId = $payment->getKey();

        if ($payment->status !== 'paid' && ! $payment->fresh()->qris_details) {
            $this->addError('finePayment', 'QRIS belum tersedia dari Midtrans. Silakan coba lagi.');
        }
    }

    private function chargeFineQrisPayment(
        Payment $payment,
        Rental $order,
        int $amount,
        PaymentService $paymentService,
        MidtransService $midtrans,
    ): ?Payment {
        try {
            $midtrans->chargeQris($payment);

            return $payment->fresh();
        } catch (\Exception $exception) {
            Log::error('ReturnFlow: gagal membuat tagihan QRIS denda di Midtrans.', [
                'payment_id' => $payment->getKey(),
                'order_id' => $payment->order_id,
                'error' => $exception->getMessage(),
            ]);

            if ($exception->getCode() !== 406) {
                return null;
            }
        }

        $payment = $midtrans->syncStatus($payment);

        if ($payment->status === 'paid' || $payment->qris_details) {
            return $payment;
        }

        if ($payment->status === 'pending') {
            if (! $midtrans->cancelTransaction($payment)) {
                $payment = $midtrans->syncStatus($payment);

                return $payment->status === 'paid' || $payment->qris_details
                    ? $payment
                    : null;
            }

            $cancelled = DB::transaction(function () use ($payment): bool {
                $lockedPayment = Payment::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->getKey());

                if ($lockedPayment->status !== PaymentStatus::Pending->value) {
                    return false;
                }

                if (! $lockedPayment->transitionTo(PaymentStatus::Cancelled)) {
                    return false;
                }

                $lockedPayment->midtrans_status = 'cancel';
                $lockedPayment->save();

                return true;
            });

            if (! $cancelled) {
                $payment = $payment->fresh();

                return $payment->status === 'paid' || $payment->qris_details
                    ? $payment
                    : null;
            }
        } elseif (! in_array($payment->status, [
            PaymentStatus::Cancelled->value,
            PaymentStatus::Expired->value,
            PaymentStatus::Failed->value,
        ], true)) {
            return null;
        }

        $nextAttempt = $paymentService->orderSequence($order->booking_code, $payment->order_id) + 1;
        $replacement = $paymentService->createOnlineFinePayment($order, $amount, $nextAttempt, 'qris');

        try {
            $midtrans->chargeQris($replacement);

            return $replacement->fresh();
        } catch (\Exception $exception) {
            Log::error('ReturnFlow: gagal membuat ulang QRIS denda dengan order ID baru.', [
                'payment_id' => $replacement->getKey(),
                'order_id' => $replacement->order_id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function cancelPendingFineQrisPayment(MidtransService $midtrans): bool
    {
        $payment = $this->fineQrisPayment;

        if (! $payment || $payment->status !== 'pending') {
            return true;
        }

        if (! $midtrans->cancelTransaction($payment)) {
            $payment = $midtrans->syncStatus($payment);

            if ($payment->status === 'paid') {
                $this->addError('finePayment', 'Pembayaran sudah berhasil dan tidak dapat dibatalkan.');

                return false;
            }

            if ($payment->status === 'pending') {
                $this->addError('finePayment', 'Midtrans belum mengonfirmasi pembatalan QRIS. Tagihan tetap aktif.');

                return false;
            }
        }

        $cancelled = DB::transaction(function () use ($payment): bool {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            if (in_array($lockedPayment->status, [
                PaymentStatus::Cancelled->value,
                PaymentStatus::Expired->value,
                PaymentStatus::Failed->value,
            ], true)) {
                return true;
            }

            if ($lockedPayment->status !== PaymentStatus::Pending->value) {
                return false;
            }

            if ($lockedPayment->transitionTo(PaymentStatus::Cancelled)) {
                $lockedPayment->midtrans_status = 'cancel';
                $lockedPayment->save();

                return true;
            }

            return false;
        });

        if (! $cancelled) {
            $this->addError('finePayment', 'Status QRIS berubah saat dibatalkan. Periksa status pembayaran sebelum melanjutkan.');

            return false;
        }

        $this->finePaymentId = null;
        $this->resetErrorBag('finePayment');

        return true;
    }

    public function completeReturn(PaymentService $paymentService): void
    {
        $order = $this->getActiveOrderProperty();
        if (! $order) {
            return;
        }

        if (! $this->validateReturnInspection($order)) {
            return;
        }

        // Hitung total denda
        $totalLateFee = (float) $this->calculatedLateFee;
        $totalDamageFee = (float) $order->details->sum(
            fn (RentalDetail $detail) => (float) ($this->damageFees[$detail->id] ?? 0)
        );
        $totalFines = (int) round($totalLateFee + $totalDamageFee);

        if ($totalFines > 0) {
            $this->showFineModal = true;

            if (! in_array($this->finePaymentMethod, ['cash', 'qris'], true)) {
                $this->addError('finePayment', 'Pilih metode pembayaran denda yang valid.');

                return;
            }

            $paidFinePayment = $order->payments()
                ->where('payment_type', 'fine')
                ->where('status', 'paid')
                ->latest('id')
                ->first();

            if ($paidFinePayment && (int) $paidFinePayment->amount !== $totalFines) {
                $this->addError('finePayment', 'Nominal denda berbeda dari pembayaran yang sudah tercatat.');

                return;
            }

            if ($this->finePaymentMethod === 'cash' && (float) $this->cashTendered < $totalFines) {
                $this->addError('cashTendered', 'Nominal tunai yang diterima belum mencukupi.');

                return;
            }

            $this->resetErrorBag('cashTendered');
        }

        $pendingQrisPaymentExists = $this->hasPendingFineQrisPayment($order);
        if ($pendingQrisPaymentExists && ($totalFines === 0 || $this->finePaymentMethod === 'cash')) {
            $this->showFineModal = true;
            $this->addError('finePayment', 'Tutup modal QRIS untuk membatalkan tagihan lama sebelum melanjutkan.');

            return;
        }

        if ($totalFines > 0) {
            if ($this->finePaymentMethod === 'qris') {
                $qrisPaymentPaid = $order->payments()
                    ->where('payment_type', 'fine')
                    ->where('payment_channel', 'qris')
                    ->where('status', 'paid')
                    ->where('amount', $totalFines)
                    ->exists();

                if (! $qrisPaymentPaid) {
                    $this->addError('finePayment', 'Pembayaran QRIS belum dikonfirmasi oleh Midtrans.');

                    return;
                }
            }

            $this->resetErrorBag('finePayment');
        }

        // Kumpulkan detail barang hilang sebelum transaksi (untuk notif setelahnya)
        $lostDetails = $order->details->filter(
            fn ($d) => ($this->inspectionNotes[$d->id] ?? 'good') === 'lost'
        );

        // Eksekusi penyelesaian return di Database
        $completed = DB::transaction(function () use ($order, $paymentService, $totalLateFee, $totalDamageFee, $totalFines): bool {
            $lockedOrder = Rental::withoutGlobalScope('vendor')
                ->where('vendor_id', Auth::user()?->vendor_id)
                ->lockForUpdate()
                ->findOrFail($order->id);

            if (
                $lockedOrder->status !== 'picked_up'
                && ! ($lockedOrder->status === 'confirmed' && Carbon::today()->gt($lockedOrder->return_date))
            ) {
                return false;
            }

            $pendingQrisPaymentExists = $lockedOrder->payments()
                ->where('payment_type', 'fine')
                ->where('payment_channel', 'qris')
                ->where('status', 'pending')
                ->lockForUpdate()
                ->exists();

            if ($pendingQrisPaymentExists && ($totalFines === 0 || $this->finePaymentMethod === 'cash')) {
                return false;
            }

            $user = Auth::user();

            if ($totalFines > 0) {
                $paidFinePayment = $lockedOrder->payments()
                    ->where('payment_type', 'fine')
                    ->where('status', 'paid')
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($paidFinePayment && (int) $paidFinePayment->amount !== $totalFines) {
                    return false;
                }

                if ($this->finePaymentMethod === 'qris') {
                    if (! $paidFinePayment || $paidFinePayment->payment_channel !== 'qris') {
                        return false;
                    }
                } elseif (! $paidFinePayment) {
                    $paymentService->createCashFinePayment($lockedOrder, $totalFines, $user->id);
                }
            }

            // 1. Simpan denda & status return pada detail
            foreach ($lockedOrder->details as $detail) {
                $cond = $this->inspectionNotes[$detail->id] ?? 'good';
                $damage = (float) ($this->damageFees[$detail->id] ?? 0);
                $lateDays = $this->lateDays;
                $ratePerDay = (float) ($detail->late_fee_per_day_snapshot ?? 0);

                $detail->update([
                    'condition_after' => $cond,
                    'damage_fee' => $damage,
                    'is_returned' => true,
                    'returned_at' => $detail->returned_at ?? Carbon::now(),
                    'late_days' => $lateDays,
                    'late_fee' => $ratePerDay * $lateDays,
                ]);

                if ($detail->item_unit_id) {
                    $unitStatus = match ($cond) {
                        'lost' => 'lost',
                        'damaged' => 'maintenance',
                        default => 'available',
                    };
                    $unitCondition = match ($cond) {
                        'lost' => 'lost',
                        'damaged' => 'damaged',
                        default => 'good',
                    };
                    ItemUnit::withoutGlobalScope('vendor')
                        ->where('id', $detail->item_unit_id)
                        ->update([
                            'status' => $unitStatus,
                            'condition' => $unitCondition,
                        ]);
                }
            }

            // 2. Update master Rental: KTP returned & status completed
            $lockedOrder->update([
                'status' => 'completed',
                'actual_return_date' => Carbon::today(),
                'returned_at' => Carbon::now(),
                'completed_at' => Carbon::now(),
                'returned_by' => $user->id,
                'ktp_collateral_status' => 'returned',
                'ktp_returned_at' => Carbon::now(),
                'total_late_fee' => $totalLateFee,
                'total_damage_fee' => $totalDamageFee,
                'final_total' => $order->grand_total + $totalFines,
            ]);

            return true;
        });

        if (! $completed) {
            $this->showFineModal = true;
            $this->addError('finePayment', 'Status pembayaran atau transaksi berubah. Periksa kembali sebelum menyelesaikan retur.');

            return;
        }

        // 4. Kirim notifikasi ke owner jika ada barang hilang
        if ($lostDetails->isNotEmpty()) {
            $this->notifyOwnerAboutLostItems($order, $lostDetails);
        }

        // Siapkan struk untuk dicetak
        $this->prepareReceipt($order);

        $msg = $this->isExpiredUnpicked
            ? "Penyelesaian transaksi kedaluwarsa {$order->booking_code} selesai! Semua unit telah dikembalikan ke stok siap pakai."
            : "Pengembalian {$order->booking_code} selesai! KTP telah diserahkan kembali & status transaksi 'completed'.";
        session()->flash('message', $msg);
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $msg,
        ]);

        $this->activeOrderId = null;
        $this->showFineModal = false;
        $this->finePaymentId = null;
        $this->loadDefaultActive();
    }

    public function processFinePayment(PaymentService $paymentService, MidtransService $midtrans): void
    {
        if ($this->finePaymentMethod === 'cash') {
            $this->completeReturn($paymentService);

            return;
        }

        if ($this->finePaymentMethod !== 'qris') {
            $this->addError('finePayment', 'Pilih metode pembayaran denda yang valid.');

            return;
        }

        $payment = $this->fineQrisPayment;
        if (! $payment || (int) $payment->amount !== (int) round($this->totalCharge)) {
            $this->addError('finePayment', 'Tagihan QRIS tidak ditemukan atau nominalnya sudah berubah.');

            return;
        }

        if ($payment->status === 'pending') {
            $payment = $midtrans->syncStatus($payment);
        }

        if ($payment->status !== 'paid') {
            $this->addError('finePayment', 'Midtrans belum mengonfirmasi pembayaran QRIS.');

            return;
        }

        $this->completeReturn($paymentService);
    }

    /**
     * Kirim notifikasi statis in-app (dan opsional WhatsApp) ke owner/vendor
     * bahwa ada unit fisik yang dilaporkan hilang saat pengembalian.
     *
     * @param  Rental  $order
     * @param  Collection  $lostDetails
     */
    private function notifyOwnerAboutLostItems($order, $lostDetails): void
    {
        $owner = User::withoutGlobalScopes()
            ->where('vendor_id', $order->vendor_id)
            ->where('role', 'vendor_owner')
            ->first();

        $itemNames = $lostDetails->map(function ($d) {
            $name = $d->masterItem?->name ?? 'Unit tidak diketahui';
            $unitCode = $d->itemUnit?->unit_code ?? '-';

            return "{$name} ({$unitCode})";
        })->join(', ');

        $message = "🚨 Laporan Barang Hilang — {$order->booking_code}: Unit {$itemNames} dilaporkan hilang oleh kasir saat pengembalian. Silakan periksa inventaris dan hapus unit tersebut jika diperlukan.";

        // Simpan notif statis in-app ke tabel notification_logs
        try {
            NotificationLog::create([
                'vendor_id' => $order->vendor_id,
                'user_id' => $owner?->id,
                'rental_id' => $order->id,
                'channel' => 'in_app',
                'type' => 'lost_unit',
                'message' => $message,
                'status' => 'sent',
                'sent_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ReturnFlow: gagal menyimpan notif in-app barang hilang.', [
                'rental_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Kirim WhatsApp jika owner punya nomor telepon
        if ($owner && $owner->phone) {
            $waMessage = "🚨 *Laporan Barang Hilang — {$order->booking_code}*\n"
                ."Kasir melaporkan barang hilang saat pengembalian:\n"
                ."{$itemNames}\n\n"
                .'Silakan periksa inventaris atau hapus unit tersebut dari toko melalui menu Kelola Unit.';

            try {
                app(WhatsAppService::class)->send($owner->phone, $waMessage);
            } catch (\Throwable $e) {
                Log::error('ReturnFlow: gagal kirim WhatsApp notif barang hilang ke owner.', [
                    'rental_id' => $order->id,
                    'owner_id' => $owner->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function prepareReceipt($order)
    {
        $order->refresh();
        $this->receiptData = [
            'booking_code' => $order->booking_code,
            'customer_name' => $order->customer?->name ?? $order->guest_name,
            'phone' => $order->customer?->phone ?? $order->guest_phone,
            'date' => Carbon::now()->format('d M Y, H:i'),
            'vendor_name' => $order->vendor?->name ?? 'Tendaku Outdoor',
            'vendor_address' => $order->vendor?->address ?? 'Malang, Jawa Timur',
            'items' => $order->details->map(fn ($d) => [
                'name' => $d->masterItem?->name,
                'unit_code' => $d->itemUnit?->unit_code ?? '-',
                'barcode' => $d->itemUnit?->barcode ?? '-',
                'condition' => $d->condition_after ?? 'good',
                'damage_fee' => (float) $d->damage_fee,
            ])->toArray(),
            'grand_total' => (float) $order->grand_total,
            'late_fee' => (float) $order->total_late_fee,
            'damage_fee' => (float) $order->total_damage_fee,
            'final_total' => (float) $order->final_total,
            'ktp_status' => 'Sudah Dikembalikan ke Penyewa',
            'cashier_name' => Auth::user()?->name ?? 'Kasir Tendaku',
        ];

        $this->showReceiptModal = true;
    }

    public function getLateDaysProperty(): int
    {
        $order = $this->getActiveOrderProperty();
        if (! $order || $this->isExpiredUnpicked) {
            return 0;
        }

        if (Carbon::today()->gt($order->return_date)) {
            return (int) Carbon::parse($order->return_date)->diffInDays(Carbon::today());
        }

        return 0;
    }

    public function getIsLateProperty(): bool
    {
        return $this->lateDays > 0;
    }

    public function getCalculatedLateFeeProperty(): float
    {
        $order = $this->getActiveOrderProperty();
        if (! $order) {
            return 0;
        }

        // Jika pesanan kedaluwarsa/tidak pernah diambil oleh penyewa, tidak dikenakan denda keterlambatan retur
        if ($this->isExpiredUnpicked) {
            return 0;
        }

        $lateDays = $this->lateDays;
        if ($lateDays <= 0) {
            return (float) ($order->total_late_fee ?? 0);
        }

        // Hitung denda per detail × rate snapshot masing-masing × jumlah hari terlambat
        $total = 0.0;
        foreach ($order->details as $detail) {
            $ratePerDay = (float) ($detail->late_fee_per_day_snapshot ?? 0);
            $total += $ratePerDay * $lateDays;
        }

        return $total;
    }

    /**
     * Rincian denda keterlambatan per item untuk ditampilkan di view.
     *
     * @return array<int, array{name: string, rate: float, late_days: int, subtotal: float}>
     */
    public function getLateFeeBreakdownProperty(): array
    {
        $order = $this->getActiveOrderProperty();
        if (! $order || ! $this->isLate) {
            return [];
        }

        $lateDays = $this->lateDays;
        $breakdown = [];

        foreach ($order->details as $detail) {
            $rate = (float) ($detail->late_fee_per_day_snapshot ?? 0);
            if ($rate <= 0) {
                continue;
            }

            $breakdown[] = [
                'name' => $detail->masterItem?->name ?? 'Item #'.$detail->id,
                'rate' => $rate,
                'late_days' => $lateDays,
                'subtotal' => $rate * $lateDays,
            ];
        }

        return $breakdown;
    }

    public function getTotalDamageFeeProperty(): float
    {
        return array_sum(array_map('floatval', $this->damageFees));
    }

    public function getTotalChargeProperty(): float
    {
        return $this->calculatedLateFee + $this->totalDamageFee;
    }

    public function render()
    {
        $vendorId = Auth::user()?->vendor_id;

        // 1. Transaksi aktif yang siap diretur (status: picked_up ATAU confirmed yang telah lewat return_date)
        $today = Carbon::today();
        $activeRentals = Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where(function ($q) use ($today) {
                $q->where('status', 'picked_up')
                    ->orWhere(function ($sub) use ($today) {
                        $sub->where('status', 'confirmed')
                            ->whereDate('return_date', '<', $today);
                    });
            })
            ->when($this->searchOrder, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('booking_code', 'like', "%{$this->searchOrder}%")
                        ->orWhere('guest_name', 'like', "%{$this->searchOrder}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$this->searchOrder}%"));
                });
            })
            ->with(['details.masterItem', 'details.itemUnit', 'customer'])
            ->latest('id')
            ->get();

        // 2. Riwayat transaksi yang sudah selesai (status: completed / returned)
        $historyQuery = Rental::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['completed', 'returned'])
            ->when($this->searchHistory, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('booking_code', 'like', "%{$this->searchHistory}%")
                        ->orWhere('guest_name', 'like', "%{$this->searchHistory}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$this->searchHistory}%"));
                });
            })
            ->when($this->statusFilter === 'Denda', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('total_late_fee', '>', 0)
                        ->orWhere('total_damage_fee', '>', 0);
                });
            })
            ->when($this->statusFilter === 'Selesai', function ($q) {
                $q->where('total_late_fee', '<=', 0)
                    ->where('total_damage_fee', '<=', 0);
            })
            ->with(['details.masterItem', 'customer', 'payments'])
            ->latest('returned_at');

        $filteredHistory = $historyQuery->get();

        return view('livewire.kasir.return-flow', [
            'activeRentalsList' => $activeRentals,
            'filteredHistory' => $filteredHistory,
        ]);
    }
}
