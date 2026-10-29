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

    public string $phone = '';

    public string $nik = '';

    public ?string $address = null;

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
            'phone' => ['required', 'regex:/^08[0-9]{8,11}$/'],
            'nik' => ['required', 'digits:16'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.ends_with' => 'Email harus menggunakan @gmail.com.',
            'phone.regex' => 'Nomor telepon harus diawali 08 dan terdiri dari 10 hingga 13 angka.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
        ]);

        $validated['address'] = filled($validated['address'] ?? null) ? trim($validated['address']) : null;
        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'customer';

        event(new Registered($user = User::create($validated)));

        Auth::login($user);
        app(\App\Services\CartService::class)->syncOnLogin($user);

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

        <x-auth.field name="phone" label="Nomor telepon" type="tel"
                      wire:model="phone"
                      placeholder="08"
                      pattern="08[0-9]{8,11}"
                      maxlength="13"
                      autocomplete="tel" required />

        <x-auth.field name="nik" label="NIK (Nomor Induk Kependudukan)"
                      wire:model="nik"
                      placeholder="Masukkan NIK"
                      inputmode="numeric"
                      maxlength="16"
                      pattern="[0-9]{16}" required />

        <x-auth.field name="address" label="Alamat"
                      wire:model="address"
                      placeholder="Alamat lengkap (opsional)" />

        <x-auth.field name="password" label="Password" type="password"
                      wire:model="password" placeholder="Password"
                      autocomplete="new-password" required />

        <x-auth.field name="password_confirmation" label="Ulangi password" type="password"
                      wire:model="password_confirmation" placeholder="Password"
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
