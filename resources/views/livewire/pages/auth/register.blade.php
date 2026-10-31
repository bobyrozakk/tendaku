<?php

use App\Livewire\Forms\RegisterForm;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public RegisterForm $form;

    public function setAccountType(string $type): void
    {
        $this->form->account_type = in_array($type, ['customer', 'vendor'], true) ? $type : 'customer';
        $this->resetErrorBag();
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $this->form->store();

        $this->redirect(route('verification.notice', absolute: false), navigate: true);
    }
}; ?>

<div>
    <x-slot:heading>
        {{ $form->account_type === 'vendor' ? 'Daftar Mitra Vendor' : 'Buat akun Tendaku' }}
    </x-slot:heading>

    <x-slot:subheading>
        {{ $form->account_type === 'vendor'
            ? 'Buka rental peralatan camping Anda dan jangkau ribuan penyewa.'
            : 'Daftar sekali, lalu sewa tenda dan perlengkapan lain kapan saja.' }}
    </x-slot:subheading>

    <!-- Switch Tipe Akun (Customer vs Vendor Owner) -->
    <div class="mb-6 flex rounded-full bg-tendaku-wheat/60 p-1 border border-tendaku-brown/15 shadow-inner">
        <button
            type="button"
            wire:click="setAccountType('customer')"
            class="flex-1 rounded-full py-2.5 text-xs sm:text-sm font-bold transition-all {{ $form->account_type === 'customer' ? 'bg-tendaku-avocado text-white shadow-sm' : 'text-tendaku-brown/70 hover:text-tendaku-brown' }}"
        >
            Penyewa (Customer)
        </button>

        <button
            type="button"
            wire:click="setAccountType('vendor')"
            class="flex-1 rounded-full py-2.5 text-xs sm:text-sm font-bold transition-all {{ $form->account_type === 'vendor' ? 'bg-tendaku-avocado text-white shadow-sm' : 'text-tendaku-brown/70 hover:text-tendaku-brown' }}"
        >
            Mitra Rental (Vendor)
        </button>
    </div>

    @include('livewire.pages.auth.partials.register-form')

    @if ($form->account_type === 'customer')
        @include('livewire.pages.auth.partials.google-auth-button', [
            'label' => 'Daftar dengan Google',
        ])
    @endif

    <p class="mt-8 text-sm text-tendaku-brown/70">
        Sudah punya akun?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-tendaku-moss underline-offset-4 hover:underline">Masuk</a>
    </p>
</div>
