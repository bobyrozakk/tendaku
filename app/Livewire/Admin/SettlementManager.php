<?php

namespace App\Livewire\Admin;

use App\Exceptions\IrisPayoutException;
use App\Models\Settlement;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Services\Finance\SettlementPayoutService;
use App\Services\Finance\SettlementService;
use App\Services\Payment\MidtransIrisService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class SettlementManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $status = '';

    public ?int $vendorId = null;

    public ?int $selectedSettlementId = null;

    public bool $showPayModal = false;

    public bool $showIrisModal = false;

    public string $irisOtp = '';

    public ?string $irisError = null;

    public mixed $proof = null;

    public string $referenceNumber = '';

    public string $paymentNotes = '';

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(): void
    {
        $this->ensureSuperAdmin();

        $settlementId = (int) request()->query('settlement', 0);

        if ($settlementId > 0 && Settlement::query()->whereKey($settlementId)->exists()) {
            $this->selectedSettlementId = $settlementId;
        }
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedVendorId(): void
    {
        $this->resetPage();
    }

    public function generateDraft(SettlementService $service): void
    {
        $this->ensureSuperAdmin();

        $created = $service->buildWeeklyDraft(Carbon::today()->subWeek());

        $this->flashError = null;
        $this->flashSuccess = $created->isEmpty()
            ? 'Tidak ada pembayaran baru yang perlu dibuatkan batch settlement.'
            : $created->count().' draft settlement berhasil dibuat.';

        $this->dispatch('toast', [
            'type' => $created->isEmpty() ? 'info' : 'success',
            'title' => $created->isEmpty() ? 'Informasi' : 'Berhasil!',
            'message' => $this->flashSuccess,
        ]);
    }

    public function openSettlement(int $settlementId): void
    {
        $this->ensureSuperAdmin();

        $settlement = Settlement::query()->findOrFail($settlementId);
        $this->selectedSettlementId = $settlement->getKey();
        $this->showPayModal = false;
        $this->showIrisModal = false;
        $this->resetPaymentForm();
        $this->resetIrisForm();
    }

    public function closeDetail(): void
    {
        $this->selectedSettlementId = null;
        $this->showPayModal = false;
        $this->showIrisModal = false;
        $this->resetPaymentForm();
        $this->resetIrisForm();
    }

    public function approve(int $settlementId, SettlementService $service): void
    {
        $this->ensureSuperAdmin();

        $settlement = Settlement::query()->findOrFail($settlementId);
        $approved = $service->approve($settlement, auth()->user());

        if (! $approved) {
            $this->flashSuccess = null;
            $this->flashError = 'Batch tidak dapat disetujui. Pastikan statusnya masih draft.';
            $this->dispatch('toast', [
                'type' => 'danger',
                'title' => 'Gagal',
                'message' => $this->flashError,
            ]);

            return;
        }

        $this->selectedSettlementId = $settlement->getKey();
        $this->flashError = null;
        $this->flashSuccess = 'Batch settlement #'.$settlement->getKey().' disetujui. Invoice vendor sudah diterbitkan; escrow dilepas saat pembayaran ditandai lunas.';
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $this->flashSuccess,
        ]);
    }

    public function openPayModal(int $settlementId): void
    {
        $this->ensureSuperAdmin();

        $settlement = Settlement::query()->findOrFail($settlementId);

        if (! $settlement->isApproved()) {
            $this->flashSuccess = null;
            $this->flashError = 'Batch harus berstatus approved sebelum ditandai sudah dibayar.';
            $this->dispatch('toast', [
                'type' => 'warning',
                'title' => 'Perhatian',
                'message' => $this->flashError,
            ]);

            return;
        }

        if ($settlement->hasActiveIrisPayout()) {
            $this->flashSuccess = null;
            $this->flashError = 'Payout Midtrans Iris untuk batch ini masih berjalan atau belum terkonfirmasi. Periksa status Iris sebelum mencatat transfer manual.';
            $this->dispatch('toast', [
                'type' => 'warning',
                'title' => 'Perhatian',
                'message' => $this->flashError,
            ]);

            return;
        }

        $this->selectedSettlementId = $settlement->getKey();
        $this->resetPaymentForm();
        $this->showIrisModal = false;
        $this->showPayModal = true;
    }

    public function openIrisModal(int $settlementId): void
    {
        $this->ensureSuperAdmin();

        $settlement = Settlement::query()->findOrFail($settlementId);

        if (! $settlement->isApproved()) {
            $this->flashSuccess = null;
            $this->flashError = 'Batch harus berstatus approved sebelum dicairkan melalui Midtrans Iris.';
            $this->dispatch('toast', [
                'type' => 'warning',
                'title' => 'Perhatian',
                'message' => $this->flashError,
            ]);

            return;
        }

        if ((float) $settlement->net_payout <= 0) {
            $this->flashSuccess = null;
            $this->flashError = 'Net payout harus lebih dari nol sebelum dicairkan.';
            $this->dispatch('toast', [
                'type' => 'warning',
                'title' => 'Perhatian',
                'message' => $this->flashError,
            ]);

            return;
        }

        $this->selectedSettlementId = $settlement->getKey();
        $this->resetIrisForm();
        $this->showPayModal = false;
        $this->showIrisModal = true;
    }

    public function closeIrisModal(): void
    {
        $this->showIrisModal = false;
        $this->resetIrisForm();
    }

    public function startIrisPayout(SettlementPayoutService $payoutService): void
    {
        $this->ensureSuperAdmin();

        $this->validate([
            'irisOtp' => ['nullable', 'digits:6'],
        ], [
            'irisOtp.digits' => 'OTP Midtrans harus terdiri dari 6 digit.',
        ]);

        $settlement = Settlement::query()->findOrFail($this->selectedSettlementId);

        try {
            $result = $payoutService->start(
                $settlement,
                auth()->user(),
                $this->irisOtp !== '' ? $this->irisOtp : null,
            );
        } catch (IrisPayoutException $exception) {
            $this->flashSuccess = null;
            $this->irisError = $exception->getMessage();
            $this->flashError = $exception->getMessage();
            $this->dispatch('toast', [
                'type' => 'danger',
                'title' => 'Payout Iris gagal',
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        $this->handleIrisResult($result);
    }

    public function syncIrisPayout(int $settlementId, SettlementPayoutService $payoutService): void
    {
        $this->ensureSuperAdmin();

        $settlement = Settlement::query()->findOrFail($settlementId);

        try {
            $result = $payoutService->sync($settlement, auth()->user());
        } catch (IrisPayoutException $exception) {
            $this->flashSuccess = null;
            $this->irisError = $exception->getMessage();
            $this->flashError = $exception->getMessage();
            $this->dispatch('toast', [
                'type' => 'danger',
                'title' => 'Sinkronisasi Iris gagal',
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        $this->selectedSettlementId = $settlement->getKey();
        $this->handleIrisResult($result);
    }

    public function closePayModal(): void
    {
        $this->showPayModal = false;
        $this->resetPaymentForm();
    }

    public function markPaid(SettlementService $service): void
    {
        $this->ensureSuperAdmin();

        $this->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'referenceNumber' => ['required', 'string', 'max:100'],
            'paymentNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $settlement = Settlement::query()->findOrFail($this->selectedSettlementId);

        if (! $settlement->isApproved()) {
            $this->flashSuccess = null;
            $this->flashError = 'Batch tidak lagi berstatus approved.';
            $this->dispatch('toast', [
                'type' => 'danger',
                'title' => 'Gagal',
                'message' => $this->flashError,
            ]);

            return;
        }

        if ($settlement->hasActiveIrisPayout()) {
            $this->flashSuccess = null;
            $this->flashError = 'Payout Midtrans Iris untuk batch ini masih berjalan atau belum terkonfirmasi. Periksa status Iris sebelum mencatat transfer manual.';
            $this->dispatch('toast', [
                'type' => 'warning',
                'title' => 'Perhatian',
                'message' => $this->flashError,
            ]);

            return;
        }

        $path = $this->proof->store('settlements/proofs', 'public');

        $invoice = $service->markPaid(
            $settlement,
            auth()->user(),
            $this->referenceNumber,
            Storage::url($path),
            $this->paymentNotes !== '' ? $this->paymentNotes : null,
        );

        if ($invoice === null) {
            $this->flashSuccess = null;
            $this->flashError = 'Batch gagal ditandai lunas.';
            $this->dispatch('toast', [
                'type' => 'danger',
                'title' => 'Gagal',
                'message' => $this->flashError,
            ]);
            Storage::disk('public')->delete($path);

            return;
        }

        $this->showPayModal = false;
        $this->selectedSettlementId = $settlement->getKey();
        $this->resetPaymentForm();
        $this->flashError = null;
        $this->flashSuccess = 'Pembayaran batch dicatat. Invoice '.$invoice->number.' telah diterbitkan dan sekarang ditandai lunas.';
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $this->flashSuccess,
        ]);
    }

    public function render(): View
    {
        $this->ensureSuperAdmin();

        $query = Settlement::query()
            ->with('vendor')
            ->when($this->status !== '', fn ($builder) => $builder->where('status', $this->status))
            ->when($this->vendorId !== null, fn ($builder) => $builder->where('vendor_id', $this->vendorId))
            ->orderByDesc('period_start')
            ->orderByDesc('id');

        $settlements = (clone $query)->paginate(10);

        $selectedSettlement = $this->selectedSettlementId !== null
            ? Settlement::query()
                ->with(['vendor', 'items.payment.rental', 'items.commission', 'invoices'])
                ->find($this->selectedSettlementId)
            : null;

        return view('livewire.admin.settlement-manager', [
            'settlements' => $settlements,
            'selectedSettlement' => $selectedSettlement,
            'vendors' => Vendor::orderBy('name')->get(['id', 'name']),
            'draftCount' => Settlement::where('status', 'draft')->count(),
            'approvedCount' => Settlement::where('status', 'approved')->count(),
            'paidThisMonth' => Settlement::where('status', 'paid')
                ->whereBetween('paid_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->sum('net_payout'),
            'irisConfigured' => app(MidtransIrisService::class)->isConfigured(),
            'statusLabels' => [
                'draft' => 'Draft',
                'approved' => 'Disetujui',
                'paid' => 'Dibayar',
                'cancelled' => 'Dibatalkan',
            ],
            'irisStatusLabels' => [
                'requested' => 'Diminta',
                'queued' => 'Menunggu Midtrans',
                'approved' => 'Disetujui Midtrans',
                'processed' => 'Diproses bank',
                'completed' => 'Selesai',
                'unknown' => 'Belum terkonfirmasi',
                'failed' => 'Gagal',
                'rejected' => 'Ditolak',
            ],
            'itemTypeLabels' => [
                'payment' => 'Pembayaran',
                'fine' => 'Denda',
                'cash_commission' => 'Komisi Tunai',
                'adjustment' => 'Penyesuaian',
            ],
        ]);
    }

    /**
     * @param  array{status: string, settlement: Settlement, invoice: ?VendorInvoice}  $result
     */
    protected function handleIrisResult(array $result): void
    {
        $settlement = $result['settlement'];
        $this->selectedSettlementId = $settlement->getKey();
        $this->irisError = null;
        $this->showIrisModal = false;
        $this->resetIrisForm();

        if ($result['status'] === 'completed') {
            $this->flashError = null;
            $this->flashSuccess = 'Payout Midtrans Iris selesai. Invoice '.($result['invoice']?->number ?? '-').' sudah ditandai lunas.';
            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Payout selesai',
                'message' => $this->flashSuccess,
            ]);

            return;
        }

        $statusLabel = $this->irisStatusLabel($result['status']);
        $this->flashError = null;
        $this->flashSuccess = 'Payout Midtrans Iris dibuat. Status saat ini: '.$statusLabel.'. Gunakan tombol Cek Status Iris setelah beberapa saat.';
        $this->dispatch('toast', [
            'type' => 'info',
            'title' => 'Payout diproses',
            'message' => $this->flashSuccess,
        ]);
    }

    protected function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    protected function irisStatusLabel(string $status): string
    {
        return match ($status) {
            'requested' => 'Diminta',
            'queued' => 'Menunggu Midtrans',
            'approved' => 'Disetujui Midtrans',
            'processed' => 'Diproses bank',
            'completed' => 'Selesai',
            'unknown' => 'Belum terkonfirmasi',
            'failed' => 'Gagal',
            'rejected' => 'Ditolak',
            default => ucfirst($status),
        };
    }

    protected function resetPaymentForm(): void
    {
        $this->reset(['proof', 'referenceNumber', 'paymentNotes']);
        $this->resetValidation();
    }

    protected function resetIrisForm(): void
    {
        $this->reset(['irisOtp', 'irisError']);
        $this->resetValidation();
    }
}
