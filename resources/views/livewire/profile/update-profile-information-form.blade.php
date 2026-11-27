<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $nik = '';
    public ?string $address = null;

    /** Tampilan: 'form' (lebar penuh, default) atau 'compact' (kartu dua kolom di profil customer). */
    #[Locked]
    public string $variant = 'form';

    /**
     * Mount the component.
     */
    public function mount(string $variant = 'form'): void
    {
        $this->variant = $variant === 'compact' ? 'compact' : 'form';
        $user = Auth::user();
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        try {
            $this->nik = (string) ($user->nik ?? '');
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            $this->nik = '';
        }
        $this->address = $user->address;
    }

    public function with(): array
    {
        return ['compact' => $this->variant === 'compact'];
    }

    /**
     * Pulihkan isian form ke nilai di database. Mode edit sendiri diatur di
     * sisi browser (Alpine), jadi membuka/menutup form tidak butuh request.
     */
    public function cancelEditing(): void
    {
        $user = Auth::user();
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        try {
            $this->nik = (string) ($user->nik ?? '');
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            $this->nik = '';
        }
        $this->address = $user->address;
        $this->resetErrorBag();
    }

    /**
     * Simpan perubahan biodata pelanggan.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'phone' => ['required', 'regex:/^08[0-9]{8,11}$/'],
            'nik' => ['nullable', 'digits:16'],
            'address' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama lengkap maksimal 255 karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.max' => 'Alamat email maksimal 255 karakter.',
            'email.unique' => 'Alamat email ini sudah digunakan.',
            'phone.required' => 'Nomor WhatsApp / telepon wajib diisi.',
            'phone.regex' => 'Nomor telepon harus diawali 08 dan terdiri dari 10 hingga 13 angka.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
        ]);

        $validated['address'] = filled($validated['address'] ?? null) ? trim($validated['address']) : null;
        $validated['nik'] = filled($validated['nik'] ?? null) ? trim($validated['nik']) : null;

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Event ini juga menutup form edit di browser (lihat x-on:profile-updated).
        $this->dispatch('profile-updated', name: $user->name);
        session()->flash('status', 'profile-updated');
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section
    id="biodata"
    @class(['pf-bio' => $compact])
    x-data="{ editing: false }"
    x-on:edit-biodata.window="editing = true; $el.scrollIntoView({ behavior: 'smooth', block: 'start' })"
    x-on:profile-updated="editing = false"
>
    @if ($compact)
        <header class="pf-head pf-head--split">
            <div class="pf-head-main">
                <span class="pf-icon"><i data-lucide="user-round"></i></span>
                <div class="min-w-0">
                    <h2 class="pf-title font-heading">{{ __('Biodata Profil') }}</h2>
                    <p class="pf-sub">{{ __('Data identitas Anda untuk keperluan sewa.') }}</p>
                </div>
            </div>

            <button
                type="button"
                x-show="! editing"
                x-on:click="editing = true; $nextTick(() => $refs.nameInput?.focus())"
                class="pf-btn pf-btn--outline"
            >
                <i data-lucide="edit-3"></i>
                <span>{{ __('Edit Profil') }}</span>
            </button>
        </header>
    @else
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-wheat-200 pb-4">
        <div>
            <h2 class="text-lg font-extrabold text-darkbrown-900 font-heading flex items-center gap-2">
                <i data-lucide="user-round" class="w-5 h-5 text-avocado-600"></i>
                <span>{{ __('Biodata Profil') }}</span>
            </h2>
            <p class="mt-1 text-xs sm:text-sm text-darkbrown-600">
                {{ __('Data identitas Anda untuk keperluan sewa perlengkapan camping.') }}
            </p>
        </div>

        <button
            type="button"
            x-show="! editing"
            x-on:click="editing = true; $nextTick(() => $refs.nameInput?.focus())"
            class="btn-tendaku-primary inline-flex items-center justify-center gap-2 py-2 px-4 rounded-xl text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all self-start sm:self-auto"
        >
            <i data-lucide="edit-3" class="w-4 h-4"></i>
            <span>{{ __('Edit Biodata') }}</span>
        </button>
    </header>
    @endif

    @if (session('status') === 'profile-updated')
        <div class="mt-4 p-4 rounded-xl bg-avocado-50 border border-avocado-200 text-xs sm:text-sm font-semibold text-avocado-900 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-avocado-600 shrink-0"></i>
            <span>Biodata berhasil diperbarui.</span>
        </div>
    @endif

    {{-- ============================================================
         1. TAMPILAN RINGKASAN BIODATA (DEFAULT)
         ============================================================ --}}
    @php
        $currentUser = Auth::user();
        try {
            $rawNik = $currentUser->nik;
            $maskedNik = $rawNik
                ? (strlen($rawNik) === 16 ? substr($rawNik, 0, 4) . ' **** **** ' . substr($rawNik, -4) : $rawNik)
                : null;
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            $maskedNik = null;
        }
    @endphp

    @if ($compact)
        <ul x-show="! editing" class="pf-field-list">
            <li class="pf-field">
                <i data-lucide="user"></i>
                <div class="pf-field-body">
                    <p class="pf-field-label">Nama Lengkap</p>
                    <p class="pf-field-value">{{ $currentUser->name ?: '-' }}</p>
                </div>
            </li>

            <li class="pf-field">
                <i data-lucide="mail"></i>
                <div class="pf-field-body">
                    <p class="pf-field-label">Alamat Email</p>
                    <p class="pf-field-value">
                        {{ $currentUser->email ?: '-' }}
                        @if ($currentUser->hasVerifiedEmail())
                            <span class="pf-verify" data-state="yes">Terverifikasi</span>
                        @else
                            <span class="pf-verify" data-state="no">Belum diverifikasi</span>
                        @endif
                    </p>
                </div>
            </li>

            <li class="pf-field">
                <i data-lucide="phone"></i>
                <div class="pf-field-body">
                    <p class="pf-field-label">Nomor WhatsApp</p>
                    <p class="pf-field-value">{{ $currentUser->phone ?: '-' }}</p>
                </div>
            </li>

            <li class="pf-field">
                <i data-lucide="credit-card"></i>
                <div class="pf-field-body">
                    <p class="pf-field-label">NIK</p>
                    <p class="pf-field-value">{{ $maskedNik ?: 'Belum diisi' }}</p>
                </div>
            </li>
        </ul>
    @else
    <div x-show="! editing" class="mt-6 space-y-6">
        <!-- Grid Rincian Biodata -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Nama Lengkap -->
            <div class="p-4 rounded-xl bg-white border border-wheat-200 space-y-1">
                <span class="text-[11px] font-bold uppercase text-darkbrown-500 tracking-wider flex items-center gap-1.5">
                    <i data-lucide="user" class="w-3.5 h-3.5 text-darkbrown-500"></i>
                    Nama Lengkap
                </span>
                <p class="text-sm font-bold text-darkbrown-900">
                    {{ $currentUser->name ?: '-' }}
                </p>
            </div>

            <!-- Email -->
            <div class="p-4 rounded-xl bg-white border border-wheat-200 space-y-1">
                <span class="text-[11px] font-bold uppercase text-darkbrown-500 tracking-wider flex items-center gap-1.5">
                    <i data-lucide="mail" class="w-3.5 h-3.5 text-darkbrown-500"></i>
                    Alamat Email
                </span>
                <p class="text-sm font-bold text-darkbrown-900">
                    {{ $currentUser->email ?: '-' }}
                </p>
            </div>

            <!-- Nomor Telepon / WA -->
            <div class="p-4 rounded-xl bg-white border border-wheat-200 space-y-1">
                <span class="text-[11px] font-bold uppercase text-darkbrown-500 tracking-wider flex items-center gap-1.5">
                    <i data-lucide="phone" class="w-3.5 h-3.5 text-darkbrown-500"></i>
                    Nomor WhatsApp / Telepon
                </span>
                <p class="text-sm font-bold text-darkbrown-900">
                    {{ $currentUser->phone ?: '-' }}
                </p>
                <span class="text-[10px] text-darkbrown-500">Nomor aktif untuk konfirmasi sewa dan notifikasi.</span>
            </div>

            <!-- NIK / No. KTP -->
            <div class="p-4 rounded-xl bg-white border border-wheat-200 space-y-1">
                <span class="text-[11px] font-bold uppercase text-darkbrown-500 tracking-wider flex items-center gap-1.5">
                    <i data-lucide="credit-card" class="w-3.5 h-3.5 text-darkbrown-500"></i>
                    NIK (Nomor Induk Kependudukan)
                </span>
                <p class="text-sm font-bold text-darkbrown-900 font-mono">
                    {{ $maskedNik ?: 'Belum diisi' }}
                </p>
                <span class="text-[10px] text-darkbrown-500">Untuk jaminan KTP saat pengambilan perlengkapan camping.</span>
            </div>
        </div>
    </div>
    @endif

    <form x-show="editing" x-cloak wire:submit="updateProfileInformation" @class(['space-y-5', 'mt-5' => $compact, 'mt-6' => ! $compact])>
        <div class="p-4 rounded-xl bg-sunglow-50 border border-sunglow-200 text-xs font-medium text-darkbrown-800 flex items-center gap-2">
            <i data-lucide="edit-2" class="w-4 h-4 text-goldenrod-700 shrink-0"></i>
            <span>Ubah biodata di bawah ini.</span>
        </div>

        <div @class(['grid gap-4', 'grid-cols-1' => $compact, 'grid-cols-1 sm:grid-cols-2' => ! $compact])>
            <!-- Nama Lengkap -->
            <div>
                <label for="name" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1.5 tracking-wider">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input
                    wire:model="name"
                    x-ref="nameInput"
                    id="name"
                    type="text"
                    class="input-tendaku w-full py-2.5 px-3.5 rounded-xl text-sm font-medium border-wheat-300 focus:ring-2 focus:ring-avocado-500"
                    required
                    autocomplete="name"
                />
                <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1.5 tracking-wider">
                    Alamat Email <span class="text-red-500">*</span>
                </label>
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    class="input-tendaku w-full py-2.5 px-3.5 rounded-xl text-sm font-medium border-wheat-300 focus:ring-2 focus:ring-avocado-500"
                    required
                    autocomplete="username"
                />
                <x-input-error class="mt-1.5" :messages="$errors->get('email')" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                    <div class="mt-2 text-xs text-darkbrown-600">
                        {{ __('Email Anda belum terverifikasi.') }}
                        <button wire:click.prevent="sendVerification" class="underline text-avocado-700 hover:text-avocado-800 font-bold ml-1">
                            {{ __('Kirim ulang tautan verifikasi') }}
                        </button>
                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-1 text-xs font-bold text-green-700">
                                {{ __('Tautan verifikasi baru telah dikirim ke email Anda.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Nomor Telepon / WA -->
            <div>
                <label for="phone" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1.5 tracking-wider">
                    Nomor WhatsApp / Telepon <span class="text-red-500">*</span>
                </label>
                <input
                    wire:model="phone"
                    id="phone"
                    type="text"
                    placeholder="Contoh: 081234567890"
                    class="input-tendaku w-full py-2.5 px-3.5 rounded-xl text-sm font-medium border-wheat-300 focus:ring-2 focus:ring-avocado-500"
                    required
                />
                <x-input-error class="mt-1.5" :messages="$errors->get('phone')" />
            </div>

            <!-- NIK (16 Digit) -->
            <div>
                <label for="nik" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1.5 tracking-wider">
                    NIK (Nomor Induk Kependudukan - 16 Digit)
                </label>
                <input
                    wire:model="nik"
                    id="nik"
                    type="text"
                    maxlength="16"
                    placeholder="Contoh: 350704xxxxxxxxxx"
                    class="input-tendaku w-full py-2.5 px-3.5 rounded-xl text-sm font-medium border-wheat-300 focus:ring-2 focus:ring-avocado-500"
                />
                <x-input-error class="mt-1.5" :messages="$errors->get('nik')" />
            </div>
        </div>

        <!-- Tombol Aksi Form -->
        <div class="flex items-center gap-3 pt-3 border-t border-wheat-200">
            <button
                type="submit"
                class="btn-tendaku-primary inline-flex items-center gap-2 py-2.5 px-2.5 rounded-xl text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all"
            >
                <i data-lucide="check" class="w-4 h-4"></i>
            </button>

            <button
                type="button"
                x-on:click="editing = false"
                wire:click="cancelEditing"
                class="btn-tendaku-secondary inline-flex items-center gap-2 py-2.5 px-2.5 rounded-xl text-xs sm:text-sm font-bold hover:bg-wheat-100 transition-all"
            >
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </form>
</section>
