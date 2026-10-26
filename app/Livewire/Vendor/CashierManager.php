<?php

namespace App\Livewire\Vendor;

use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Component;
use Livewire\WithPagination;

class CashierManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all'; // 'all', 'active', 'inactive'

    // Modal state
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    // Form fields
    public ?int $cashierId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_active = true;

    // Delete modal states
    public string $deletingCashierName = '';

    public bool $hasTransactionsWarning = false;

    // Alert banner
    public ?string $feedbackMessage = null;

    public string $feedbackType = 'success'; // 'success' | 'error'

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function closeModals(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->resetValidation();
    }

    public function createCashier(): void
    {
        $owner = Auth::user();

        if (! $owner || ! $owner->vendor_id) {
            abort(403, 'Akses ditolak. Toko vendor tidak ditemukan.');
        }

        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'phone' => ['required', 'regex:/^08[0-9]{8,11}$/'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.unique' => 'Email ini sudah terdaftar di sistem.',
            'phone.regex' => 'Nomor telepon harus diawali 08 dan terdiri dari 10 hingga 13 angka.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $cashier = User::create([
            'vendor_id' => $owner->vendor_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_VENDOR_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(), // Akun dibuat oleh owner toko resmi sehingga langsung diverifikasi
        ]);

        $this->closeModals();
        $this->resetForm();

        $this->feedbackMessage = "Akun kasir '{$cashier->name}' berhasil ditambahkan ke toko Anda!";
        $this->feedbackType = 'success';
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $this->feedbackMessage,
        ]);
    }

    public function openEditModal(int $id): void
    {
        $cashier = $this->getCashierForOwner($id);

        $this->resetValidation();
        $this->cashierId = $cashier->id;
        $this->name = $cashier->name;
        $this->email = $cashier->email;
        $this->phone = $cashier->phone ?? '';
        $this->is_active = (bool) $cashier->is_active;
        $this->password = '';
        $this->password_confirmation = '';

        $this->showEditModal = true;
    }

    public function updateCashier(): void
    {
        if (! $this->cashierId) {
            return;
        }

        $cashier = $this->getCashierForOwner($this->cashierId);

        $this->email = Str::lower(trim($this->email));

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($cashier->id),
            ],
            'phone' => ['required', 'regex:/^08[0-9]{8,11}$/'],
            'is_active' => ['boolean'],
        ];

        if (filled($this->password)) {
            $rules['password'] = ['required', 'string', 'confirmed', Rules\Password::defaults()];
        }

        $validated = $this->validate($rules, [
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'phone.regex' => 'Nomor telepon harus diawali 08 dan terdiri dari 10 hingga 13 angka.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'is_active' => $this->is_active,
        ];

        if (filled($this->password)) {
            $updateData['password'] = Hash::make($this->password);
        }

        $cashier->update($updateData);

        $this->closeModals();
        $this->resetForm();

        $this->feedbackMessage = "Data kasir '{$cashier->name}' berhasil diperbarui!";
        $this->feedbackType = 'success';
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $this->feedbackMessage,
        ]);
    }

    public function toggleStatus(int $id): void
    {
        $cashier = $this->getCashierForOwner($id);

        $newStatus = ! $cashier->is_active;
        $cashier->update(['is_active' => $newStatus]);

        $statusLabel = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        $this->feedbackMessage = "Status kasir '{$cashier->name}' berhasil {$statusLabel}.";
        $this->feedbackType = 'success';
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $this->feedbackMessage,
        ]);
    }

    public function confirmDelete(int $id): void
    {
        $cashier = $this->getCashierForOwner($id);

        $this->cashierId = $cashier->id;
        $this->deletingCashierName = $cashier->name;

        // Cek apakah kasir memiliki riwayat transaksi sewa atau pembayaran
        $hasRentals = Rental::where(function ($q) use ($cashier) {
            $q->where('created_by', $cashier->id)
                ->orWhere('picked_up_by', $cashier->id)
                ->orWhere('returned_by', $cashier->id);
        })->exists();

        $hasPayments = Payment::where('received_by', $cashier->id)->exists();

        $this->hasTransactionsWarning = $hasRentals || $hasPayments;
        $this->showDeleteModal = true;
    }

    public function deleteCashier(): void
    {
        if (! $this->cashierId) {
            return;
        }

        $cashier = $this->getCashierForOwner($this->cashierId);
        $name = $cashier->name;

        // Gunakan Soft Delete untuk menjaga integritas data riwayat transaksi POS
        $cashier->delete();

        $this->closeModals();
        $this->resetForm();

        $this->feedbackMessage = "Akun kasir '{$name}' berhasil dihapus dari toko Anda.";
        $this->feedbackType = 'success';
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => $this->feedbackMessage,
        ]);
    }

    protected function resetForm(): void
    {
        $this->cashierId = null;
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->is_active = true;
        $this->deletingCashierName = '';
        $this->hasTransactionsWarning = false;
    }

    protected function getCashierForOwner(int $id): User
    {
        $owner = Auth::user();

        if (! $owner || ! $owner->vendor_id) {
            abort(403, 'Akses ditolak.');
        }

        return User::where('vendor_id', $owner->vendor_id)
            ->where('role', User::ROLE_VENDOR_ADMIN)
            ->findOrFail($id);
    }

    public function render()
    {
        $owner = Auth::user();
        $vendorId = (int) ($owner?->vendor_id ?? 0);

        $baseQuery = User::where('vendor_id', $vendorId)
            ->where('role', User::ROLE_VENDOR_ADMIN);

        $cashiers = (clone $baseQuery)
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when(filled($this->search), function ($q) {
                $term = trim($this->search);
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->latest('id')
            ->paginate(10);

        return view('livewire.vendor.cashier-manager', [
            'cashiers' => $cashiers,
        ]);
    }
}
