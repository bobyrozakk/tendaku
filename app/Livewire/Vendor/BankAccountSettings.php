<?php

namespace App\Livewire\Vendor;

use App\Models\Vendor;
use App\Models\VendorPaymentSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BankAccountSettings extends Component
{
    public string $bankName = '';

    public string $bankAccountNumber = '';

    public string $bankAccountName = '';

    public bool $showModal = false;

    public ?string $savedMessage = null;

    public function mount(): void
    {
        $this->fillFromSetting($this->vendor()->paymentSetting);
    }

    public function openModal(): void
    {
        $this->fillFromSetting($this->vendor()->paymentSetting);
        $this->resetValidation();
        $this->savedMessage = null;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->fillFromSetting($this->vendor()->paymentSetting);
        $this->resetValidation();
        $this->showModal = false;
    }

    public function save(): void
    {
        $vendor = $this->vendor();

        $this->bankName = trim($this->bankName);
        $this->bankAccountNumber = preg_replace('/\s+/', '', $this->bankAccountNumber) ?? '';
        $this->bankAccountName = trim($this->bankAccountName);

        $validated = $this->validate([
            'bankName' => ['required', 'string', 'max:50'],
            'bankAccountNumber' => ['required', 'string', 'regex:/^[0-9]{6,30}$/'],
            'bankAccountName' => ['required', 'string', 'max:150'],
        ], [
            'bankName.required' => 'Nama bank wajib diisi.',
            'bankName.max' => 'Nama bank maksimal 50 karakter.',
            'bankAccountNumber.required' => 'Nomor rekening wajib diisi.',
            'bankAccountNumber.regex' => 'Nomor rekening harus terdiri dari 6-30 angka.',
            'bankAccountName.required' => 'Nama pemilik rekening wajib diisi.',
            'bankAccountName.max' => 'Nama pemilik rekening maksimal 150 karakter.',
        ]);

        $bankAccount = [
            'bank_name' => $validated['bankName'],
            'bank_account_number' => $validated['bankAccountNumber'],
            'bank_account_name' => $validated['bankAccountName'],
        ];

        DB::transaction(function () use ($vendor, $bankAccount): void {
            VendorPaymentSetting::query()->updateOrCreate(
                ['vendor_id' => $vendor->getKey()],
                $bankAccount,
            );

            // Settlement yang belum dibayar harus ikut memakai rekening terbaru.
            // Settlement paid/cancelled tetap menyimpan snapshot historisnya.
            $vendor->settlements()
                ->whereIn('status', ['draft', 'approved'])
                ->update($bankAccount);
        });

        $this->showModal = false;
        $this->savedMessage = 'Rekening tujuan berhasil disimpan.';

        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Rekening disimpan',
            'message' => 'Settlement yang belum dibayar akan memakai rekening tujuan ini.',
        ]);
    }

    public function render(): View
    {
        $vendor = $this->vendor();

        return view('livewire.vendor.bank-account-settings', [
            'vendor' => $vendor,
            'bankSetting' => $vendor->paymentSetting,
        ]);
    }

    private function vendor(): Vendor
    {
        $user = Auth::user();

        abort_unless($user?->isVendorOwner() && $user->vendor_id !== null, 403);

        return $user->vendor()->firstOrFail();
    }

    private function fillFromSetting(?VendorPaymentSetting $setting): void
    {
        $this->bankName = $setting?->bank_name ?? '';
        $this->bankAccountNumber = $setting?->bank_account_number ?? '';
        $this->bankAccountName = $setting?->bank_account_name ?? '';
    }
}
