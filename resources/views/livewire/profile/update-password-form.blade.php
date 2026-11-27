<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /** Tampilan: 'form' (inline, default) atau 'row' (baris + modal di kartu Keamanan Akun). */
    #[Locked]
    public string $variant = 'form';

    public function mount(string $variant = 'form'): void
    {
        $this->variant = $variant === 'row' ? 'row' : 'form';
    }

    /**
     * Perbarui kata sandi pengguna yang sedang login.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ], [
                'current_password.required' => 'Kata sandi saat ini wajib diisi.',
                'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
                'password.required' => 'Kata sandi baru wajib diisi.',
                'password.min' => 'Kata sandi baru minimal :min karakter.',
                'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            ], [
                'current_password' => 'kata sandi saat ini',
                'password' => 'kata sandi baru',
                'password_confirmation' => 'konfirmasi kata sandi baru',
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

@if ($variant === 'row')
    <section id="keamanan">
        <button
            type="button"
            x-data
            x-on:click.prevent="$dispatch('open-modal', 'change-password')"
            class="pf-sec-row"
        >
            <i data-lucide="lock-keyhole"></i>
            <span>{{ __('Ubah Kata Sandi') }}</span>
            <i data-lucide="chevron-right"></i>
        </button>

        <x-modal name="change-password" :show="$errors->isNotEmpty()" max-width="md" focusable>
            <div
                class="p-6"
                x-data
                x-on:password-updated.window="setTimeout(() => $dispatch('close-modal', 'change-password'), 900)"
            >
                <h2 class="text-lg font-extrabold text-darkbrown-900 font-heading">
                    {{ __('Ubah Kata Sandi') }}
                </h2>

                <p class="mt-1 mb-5 text-sm text-darkbrown-600">
                    {{ __('Gunakan kata sandi yang panjang dan sulit ditebak agar akun tetap aman.') }}
                </p>

                @include('livewire.profile.partials.password-form')
            </div>
        </x-modal>
    </section>
@else
<section id="keamanan">
    <header>
        <h2 class="text-lg font-extrabold text-darkbrown-900 font-heading flex items-center gap-2">
            <i data-lucide="lock-keyhole" class="w-5 h-5 text-avocado-600"></i>
            <span>{{ __('Perbarui Kata Sandi') }}</span>
        </h2>

        <p class="mt-1 text-xs sm:text-sm text-darkbrown-600">
            {{ __('Gunakan kata sandi yang panjang dan sulit ditebak agar akun tetap aman.') }}
        </p>
    </header>

    <div class="mt-5">
        @include('livewire.profile.partials.password-form')
    </div>
</section>
@endif
