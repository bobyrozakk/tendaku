<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $nik = '';
    public ?string $address = null;
    public bool $isEditing = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->nik = (string) ($user->nik ?? '');
        $this->address = $user->address;
        $this->isEditing = false;
    }

    /**
     * Aktifkan mode edit profil.
     */
    public function startEditing(): void
    {
        $this->resetErrorBag();
        $this->isEditing = true;
    }

    /**
     * Batalkan mode edit dan pulihkan nilai asal dari database.
     */
    public function cancelEditing(): void
    {
        $user = Auth::user();
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->nik = (string) ($user->nik ?? '');
        $this->address = $user->address;
        $this->resetErrorBag();
        $this->isEditing = false;
    }

    /**
     * Simpan perubahan informasi profil user.
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
            'email.required' => 'Alamat email wajib diisi.',
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

        $this->isEditing = false;

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

<section>
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-wheat-200 pb-4">
        <div>
            <h2 class="text-lg font-extrabold text-darkbrown-900 font-heading">
                {{ __('Biodata Profil') }}
            </h2>
            <p class="mt-1 text-xs sm:text-sm text-darkbrown-600">
                {{ __('Informasi identitas akun Anda untuk keperluan persewaan alat camping.') }}
            </p>
        </div>

        @if(! $isEditing)
            <button 
                type="button" 
                wire:click="startEditing"
                class="btn-tendaku-primary inline-flex items-center justify-center gap-2 py-2 px-4 rounded-xl text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all self-start sm:self-auto"
            >
                <i data-lucide="edit-3" class="w-4 h-4"></i>
                <span>{{ __('Edit Profil') }}</span>
            </button>
        @endif
    </header>

    @if (session('status') === 'profile-updated')
        <div class="mt-4 p-4 rounded-xl bg-avocado-50 border border-avocado-200 text-xs sm:text-sm font-semibold text-avocado-900 flex items-center gap-2">
            <span class="text-base">✅</span>
            <span>Biodata profil berhasil diperbarui!</span>
        </div>
    @endif

    {{-- ============================================================
         1. TAMPILAN PREVIEW BIODATA (DEFAULT)
         ============================================================ --}}
    @if(! $isEditing)
        @php
            $currentUser = Auth::user();
            $maskedNik = $currentUser->nik 
                ? (strlen($currentUser->nik) === 16 ? substr($currentUser->nik, 0, 4) . ' **** **** ' . substr($currentUser->nik, -4) : $currentUser->nik)
                : null;
        @endphp

        <div class="mt-6 space-y-6">
            <!-- Header Kartu Identitas Pengguna -->
            <div class="flex items-center gap-4 p-4 rounded-2xl bg-wheat-50 border border-wheat-200">
                <div class="w-14 h-14 rounded-2xl bg-avocado-600 text-white font-extrabold text-xl flex items-center justify-center shadow-md font-heading shrink-0">
                    {{ strtoupper(substr($currentUser->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-base sm:text-lg font-extrabold text-darkbrown-900 font-heading truncate">
                            {{ $currentUser->name }}
                        </h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                            {{ ucfirst($currentUser->role ?? 'Customer') }}
                        </span>
                        @if($currentUser->hasVerifiedEmail())
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800 border border-green-200">
                                Email Terverifikasi
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-darkbrown-600 truncate mt-0.5">
                        {{ $currentUser->email }}
                    </p>
                </div>
            </div>

            <!-- Grid Rincian Biodata -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nama Lengkap -->
                <div class="p-4 rounded-xl bg-white border border-wheat-200 space-y-1">
                    <span class="text-[11px] font-bold uppercase text-darkbrown-500 tracking-wider block">
                        👤 Nama Lengkap
                    </span>
                    <p class="text-sm font-bold text-darkbrown-900">
                        {{ $currentUser->name ?: '-' }}
                    </p>
                </div>

                <!-- Email -->
                <div class="p-4 rounded-xl bg-white border border-wheat-200 space-y-1">
                    <span class="text-[11px] font-bold uppercase text-darkbrown-500 tracking-wider block">
                        ✉️ Alamat Email
                    </span>
                    <p class="text-sm font-bold text-darkbrown-900">
                        {{ $currentUser->email ?: '-' }}
                    </p>
                </div>

                <!-- Nomor Telepon / WA -->
                <div class="p-4 rounded-xl bg-white border border-wheat-200 space-y-1">
                    <span class="text-[11px] font-bold uppercase text-darkbrown-500 tracking-wider block">
                        📱 Nomor WhatsApp / Telepon
                    </span>
                    <p class="text-sm font-bold text-darkbrown-900">
                        {{ $currentUser->phone ?: '-' }}
                    </p>
                    <span class="text-[10px] text-darkbrown-500">Nomor aktif untuk konfirmasi sewa dan notifikasi.</span>
                </div>

                <!-- NIK / No. KTP -->
                <div class="p-4 rounded-xl bg-white border border-wheat-200 space-y-1">
                    <span class="text-[11px] font-bold uppercase text-darkbrown-500 tracking-wider block">
                        🪪 NIK (Nomor Induk Kependudukan)
                    </span>
                    <p class="text-sm font-bold text-darkbrown-900 font-mono">
                        {{ $maskedNik ?: 'Belum diisi' }}
                    </p>
                    <span class="text-[10px] text-darkbrown-500">Untuk jaminan penahanan KTP fisik saat pickup alat camping.</span>
                </div>

                <!-- Alamat Domisili -->
                <div class="p-4 rounded-xl bg-white border border-wheat-200 space-y-1 md:col-span-2">
                    <span class="text-[11px] font-bold uppercase text-darkbrown-500 tracking-wider block">
                        🏠 Alamat Lengkap
                    </span>
                    <p class="text-sm font-semibold text-darkbrown-800 leading-relaxed">
                        {{ $currentUser->address ?: 'Alamat belum dilengkapi.' }}
                    </p>
                </div>
            </div>

            <!-- Tombol Edit Profil di Bawah Biodata -->
            <div class="pt-2 flex justify-start">
                <button 
                    type="button" 
                    wire:click="startEditing"
                    class="btn-tendaku-primary inline-flex items-center gap-2 py-2.5 px-6 rounded-xl text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all"
                >
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                    <span>Edit Profil Biodata</span>
                </button>
            </div>
        </div>

    {{-- ============================================================
         2. TAMPILAN FORM EDIT PROFIL (MUNCUL SETELAH KLIK EDIT)
         ============================================================ --}}
    @else
        <form wire:submit="updateProfileInformation" class="mt-6 space-y-5">
            <div class="p-4 rounded-xl bg-sunglow-50 border border-sunglow-200 text-xs font-medium text-darkbrown-800 flex items-center gap-2">
                <span>✏️</span>
                <span>Silakan ubah data biodata di bawah ini lalu tekan <strong>Simpan Perubahan</strong>.</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Nama Lengkap -->
                <div>
                    <label for="name" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1.5 tracking-wider">
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>
                    <input 
                        wire:model="name" 
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
                                {{ __('Kirim ulang link verifikasi') }}
                            </button>
                            @if (session('status') === 'verification-link-sent')
                                <p class="mt-1 text-xs font-bold text-green-700">
                                    {{ __('Link verifikasi baru telah dikirimkan ke email Anda.') }}
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

                <!-- Alamat Lengkap -->
                <div class="sm:col-span-2">
                    <label for="address" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1.5 tracking-wider">
                        Alamat Domisili Lengkap
                    </label>
                    <textarea 
                        wire:model="address" 
                        id="address" 
                        rows="3"
                        placeholder="Tuliskan nama jalan, RT/RW, kelurahan, kecamatan, dan kota..."
                        class="input-tendaku w-full py-2.5 px-3.5 rounded-xl text-sm font-medium border-wheat-300 focus:ring-2 focus:ring-avocado-500"
                    ></textarea>
                    <x-input-error class="mt-1.5" :messages="$errors->get('address')" />
                </div>
            </div>

            <!-- Tombol Aksi Form -->
            <div class="flex items-center gap-3 pt-3 border-t border-wheat-200">
                <button 
                    type="submit" 
                    class="btn-tendaku-primary py-2.5 px-6 rounded-xl text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all"
                >
                    {{ __('Simpan Perubahan') }}
                </button>

                <button 
                    type="button" 
                    wire:click="cancelEditing"
                    class="btn-tendaku-secondary py-2.5 px-5 rounded-xl text-xs sm:text-sm font-bold hover:bg-wheat-100 transition-all"
                >
                    {{ __('Batal') }}
                </button>
            </div>
        </form>
    @endif
</section>
