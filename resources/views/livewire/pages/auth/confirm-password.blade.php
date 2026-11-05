<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <x-slot:heading>Konfirmasi password</x-slot:heading>
    <x-slot:subheading>Ini area yang aman. Masukkan passwordmu untuk melanjutkan.</x-slot:subheading>

    <form wire:submit="confirmPassword" class="space-y-5">
        @csrf

        <x-auth.field name="password" label="Password" type="password"
                      wire:model="password"
                      autocomplete="current-password" required autofocus />

        <x-auth.button class="w-full">Konfirmasi</x-auth.button>
    </form>
</div>

