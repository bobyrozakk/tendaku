{{-- Form ubah kata sandi. Dipakai lewat @include dari update-password-form. --}}
<form wire:submit="updatePassword" class="space-y-4">
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

        @if ($variant === 'row')
            <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'change-password')">
                {{ __('Batal') }}
            </x-secondary-button>
        @endif

        <x-action-message class="me-3 !text-avocado-700 font-semibold" on="password-updated">
            {{ __('Kata sandi berhasil diperbarui.') }}
        </x-action-message>
    </div>
</form>
