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
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ], [
                'current_password.required' => 'Kata sandi saat ini wajib diisi.',
                'current_password.string' => 'Kata sandi saat ini harus berupa teks.',
                'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
                'password.required' => 'Kata sandi baru wajib diisi.',
                'password.string' => 'Kata sandi baru harus berupa teks.',
                'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
                'password.min' => 'Kata sandi baru minimal :min karakter.',
                'password.letters' => 'Kata sandi baru harus mengandung huruf.',
                'password.mixed' => 'Kata sandi baru harus mengandung huruf besar dan huruf kecil.',
                'password.numbers' => 'Kata sandi baru harus mengandung angka.',
                'password.symbols' => 'Kata sandi baru harus mengandung simbol.',
                'password.uncompromised' => 'Kata sandi ini pernah muncul dalam kebocoran data. Silakan pilih kata sandi lain.',
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

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Ubah Kata Sandi') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Gunakan kata sandi yang panjang dan unik untuk menjaga keamanan akun Anda.') }}
        </p>
    </header>

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        <div>
            <x-input-label for="update_password_current_password" :value="__('Kata Sandi Saat Ini')" />
            <x-password-input wire:model="current_password" id="update_password_current_password" name="current_password" wrapper-class="mt-1 w-full" class="block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('Kata Sandi Baru')" />
            <x-password-input wire:model="password" id="update_password_password" name="password" wrapper-class="mt-1 w-full" class="block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Konfirmasi Kata Sandi Baru')" />
            <x-password-input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" wrapper-class="mt-1 w-full" class="block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Simpan') }}</x-primary-button>

            <x-action-message class="me-3" on="password-updated">Kata sandi berhasil diperbarui.</x-action-message>
        </div>
    </form>
</section>
