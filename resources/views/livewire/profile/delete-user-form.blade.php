<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Hapus akun pengguna yang sedang login.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ], [
            'password.required' => 'Kata sandi wajib diisi untuk menghapus akun.',
            'password.current_password' => 'Kata sandi tidak sesuai.',
        ], [
            'password' => 'kata sandi',
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-extrabold text-red-700 font-heading flex items-center gap-2">
            <i data-lucide="trash-2" class="w-5 h-5"></i>
            <span>{{ __('Hapus Akun') }}</span>
        </h2>

        <p class="mt-1 text-xs sm:text-sm text-darkbrown-600">
            {{ __('Setelah akun dihapus, seluruh data Anda akan dihapus secara permanen. Simpan data yang Anda perlukan sebelum menghapus akun.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Hapus Akun') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">

            <h2 class="text-lg font-extrabold text-darkbrown-900 font-heading">
                {{ __('Yakin ingin menghapus akun Anda?') }}
            </h2>

            <p class="mt-1 text-sm text-darkbrown-600">
                {{ __('Setelah akun dihapus, seluruh data Anda akan dihapus secara permanen. Masukkan kata sandi Anda untuk mengonfirmasi penghapusan akun.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Kata Sandi') }}" class="sr-only" />

                <x-password-input
                    wire:model="password"
                    id="password"
                    name="password"
                    wrapper-class="mt-1 w-3/4"
                    class="block w-full"
                    placeholder="{{ __('Kata Sandi') }}"
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Batal') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Hapus Akun') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
