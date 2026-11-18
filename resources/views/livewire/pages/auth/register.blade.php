<?php

use App\Livewire\Forms\RegisterForm;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public RegisterForm $form;

    public function setAccountType(string $type): void
    {
        $this->form->account_type = in_array($type, ['customer', 'vendor'], true)
            ? $type
            : 'customer';

        $this->resetErrorBag();
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $this->form->store();

        $this->redirect(
            route('verification.notice', absolute: false),
            navigate: true
        );
    }
}; ?>

<div x-data="{ type: $wire.entangle('form.account_type') }">
    {{-- Sinkronkan tipe akun awal ke teks panel kiri --}}
    <div
        x-data
        x-init="$dispatch('account-type-changed', { type: '{{ $form->account_type }}' })"
        class="hidden"
        aria-hidden="true"
    ></div>

    {{-- Heading & subheading dirender di layout (di luar komponen), jadi diganti lewat event account-type-changed --}}
    <x-slot:heading>
        <span
            x-data="{ t: '{{ $form->account_type }}' }"
            x-on:account-type-changed.window="t = $event.detail.type"
        >
            <span
                x-show="t !== 'vendor'"
                @if ($form->account_type === 'vendor') style="display: none" @endif
            >Buat akun Tendaku</span>
            <span
                x-show="t === 'vendor'"
                @if ($form->account_type !== 'vendor') style="display: none" @endif
            >Mulai bersama Tendaku</span>
        </span>
    </x-slot:heading>

    <x-slot:subheading>
        <span
            x-data="{ t: '{{ $form->account_type }}' }"
            x-on:account-type-changed.window="t = $event.detail.type"
        >
            <span
                x-show="t !== 'vendor'"
                @if ($form->account_type === 'vendor') style="display: none" @endif
            >Daftar sekali, lalu sewa tenda dan perlengkapan lain kapan saja.</span>
            <span
                x-show="t === 'vendor'"
                @if ($form->account_type !== 'vendor') style="display: none" @endif
            >Daftarkan usaha rentalmu dan jangkau lebih banyak pelanggan melalui Tendaku.</span>
        </span>
    </x-slot:subheading>

    {{-- Switch Tipe Akun --}}
    <div class="mb-5 flex justify-center">
        <div
            class="relative inline-flex items-center rounded-full border border-white/15 bg-black/10 p-1 backdrop-blur-xl"
            x-data
        >
            {{-- Sliding background --}}
            <div
                class="absolute inset-y-1 left-1 w-[calc(50%-0.25rem)] rounded-full
                       bg-tendaku-avocado/70 backdrop-blur-xl
                       ring-1 ring-white/15
                       shadow-[0_3px_15px_rgba(91,106,52,0.3)]
                       transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)]"
                x-bind:class="type === 'vendor' ? 'translate-x-full' : 'translate-x-0'"
            ></div>

            <button
                type="button"
                x-on:click="type = 'customer'; $dispatch('account-type-changed', { type: 'customer' })"
                class="relative z-10 w-28 rounded-full px-4 py-1.5 text-xs font-semibold
                       transition-colors duration-300 sm:text-sm"
                x-bind:class="
                    type === 'customer'
                        ? 'text-white'
                        : 'text-white/45 hover:text-white/75'
                "
            >
                Customer
            </button>

            <button
                type="button"
                x-on:click="type = 'vendor'; $dispatch('account-type-changed', { type: 'vendor' })"
                class="relative z-10 w-28 rounded-full px-4 py-1.5 text-xs font-semibold
                       transition-colors duration-300 sm:text-sm"
                x-bind:class="
                    type === 'vendor'
                        ? 'text-white'
                        : 'text-white/45 hover:text-white/75'
                "
            >
                Partner
            </button>
        </div>
    </div>

    @include('livewire.pages.auth.partials.register-form')

    <div x-show="type === 'customer'">
        @include('livewire.pages.auth.partials.google-auth-button', [
            'label' => 'Daftar dengan Google',
        ])
    </div>

    <p class="mt-8 text-sm text-white/80 lg:mt-5">
        Sudah punya akun?

        <a  href="{{ route('login') }}"
            wire:navigate.hover
            class="font-semibold text-avocado-300 underline-offset-4 hover:text-avocado-200 hover:underline" >
            Masuk
        </a>
    </p>
</div>
