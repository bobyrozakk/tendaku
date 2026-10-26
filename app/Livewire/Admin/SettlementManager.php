<?php

namespace App\Livewire\Admin;

use App\Models\Settlement;
use App\Models\Vendor;
use App\Services\SettlementService;
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

    public mixed $proof = null;

    public string $referenceNumber = '';

    public string $paymentNotes = '';

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(): void
    {
        $this->ensureSuperAdmin();
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
    }

    public function closeDetail(): void
    {
        $this->selectedSettlementId = null;
        $this->showPayModal = false;
        $this->resetPaymentForm();
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
        $this->flashSuccess = 'Batch settlement #'.$settlement->getKey().' disetujui. Escrow akan dilepas saat pembayaran ditandai lunas.';
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

        $this->selectedSettlementId = $settlement->getKey();
        $this->resetPaymentForm();
        $this->showPayModal = true;
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
        $this->flashSuccess = 'Pembayaran batch dicatat. Invoice '.$invoice->number.' telah diterbitkan.';
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
            'statusLabels' => [
                'draft' => 'Draft',
                'approved' => 'Disetujui',
                'paid' => 'Dibayar',
                'cancelled' => 'Dibatalkan',
            ],
            'itemTypeLabels' => [
                'payment' => 'Pembayaran',
                'fine' => 'Denda',
                'cash_commission' => 'Komisi Tunai',
                'adjustment' => 'Penyesuaian',
            ],
        ]);
    }

    protected function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    protected function resetPaymentForm(): void
    {
        $this->reset(['proof', 'referenceNumber', 'paymentNotes']);
        $this->resetValidation();
    }
}
