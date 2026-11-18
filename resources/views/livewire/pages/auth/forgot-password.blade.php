<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div>
    <x-slot:heading>Lupa password?</x-slot:heading>
    <x-slot:subheading>Masukkan email akunmu. Kami kirim tautan untuk membuat password baru.</x-slot:subheading>

    @if (session('status'))
        <div role="status" class="mb-6 rounded-lg border border-white/20 bg-tendaku-avocado/20 px-4 py-3 text-sm text-white">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="sendPasswordResetLink" class="space-y-5">
        @csrf

        <x-auth.field name="email" label="Email" type="email"
                      wire:model="email"
                      placeholder="nama@gmail.com" autocomplete="username" required autofocus />

        <x-auth.button class="w-full">Kirim tautan reset</x-auth.button>
    </form>

    <p class="mt-8 text-sm text-white/80">
        Ingat passwordnya?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-avocado-300 underline-offset-4 hover:text-avocado-200 hover:underline">Kembali masuk</a>
    </p>
</div>
