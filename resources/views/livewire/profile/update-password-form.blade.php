<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

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

    <form wire:submit="updatePassword" class="mt-5 space-y-4">
        <div>
            <x-input-label for="update_password_current_password" :value="__('Kata Sandi Saat Ini')" class="!text-darkbrown-800 !font-bold" />
            <x-password-input wire:model="current_password" id="update_password_current_password" name="current_password" wrapper-class="mt-1 w-full" class="block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('Kata Sandi Baru')" class="!text-darkbrown-800 !font-bold" />
            <x-password-input wire:model="password" id="update_password_password" name="password" wrapper-class="mt-1 w-full" class="block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Konfirmasi Kata Sandi Baru')" class="!text-darkbrown-800 !font-bold" />
            <x-password-input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" wrapper-class="mt-1 w-full" class="block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-primary-button>{{ __('Simpan Kata Sandi') }}</x-primary-button>

            <x-action-message class="me-3 !text-avocado-700 font-semibold" on="password-updated">
                {{ __('Kata sandi berhasil diperbarui.') }}
            </x-action-message>
        </div>
    </form>
</section>
