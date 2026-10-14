<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:'.User::class,
                'ends_with:@gmail.com',
            ],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.ends_with' => 'Email harus menggunakan @gmail.com.',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'customer';

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('verification.notice', absolute: false), navigate: true);
    }
}; ?>

<div>
    <x-slot:heading>Buat akun Tendaku</x-slot:heading>
    <x-slot:subheading>Daftar sekali, lalu sewa tenda dan perlengkapan lain kapan saja.</x-slot:subheading>

    <form wire:submit="register" class="space-y-5">
        @csrf

        <x-auth.field name="name" label="Nama lengkap"
                      wire:model="name"
                      placeholder="Nama Lengkap" autocomplete="name" required autofocus />

        <x-auth.field name="email" label="Email" type="email"
                      wire:model="email"
                      placeholder="nama@gmail.com" autocomplete="username" required />

        <x-auth.field name="password" label="Password" type="password"
                      wire:model="password"
                      autocomplete="new-password" hint="Minimal 8 karakter." required />

        <x-auth.field name="password_confirmation" label="Ulangi password" type="password"
                      wire:model="password_confirmation"
                      autocomplete="new-password" required />

        <x-auth.button class="w-full">Buat akun</x-auth.button>
    </form>

    @include('livewire.pages.auth.partials.google-auth-button', [
        'label' => 'Daftar dengan Google',
    ])

    <p class="mt-8 text-sm text-tendaku-brown/70">
        Sudah punya akun?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-tendaku-moss underline-offset-4 hover:underline">Masuk</a>
    </p>
</div>
