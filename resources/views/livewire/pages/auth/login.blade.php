
<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $user = auth()->user();

        // Jika email belum diverifikasi, arahkan ke halaman verifikasi email (resend email)
        if (! $user->hasVerifiedEmail()) {
            $this->redirect(route('verification.notice', absolute: false), navigate: true);

            return;
        }

        // Tentukan halaman tujuan berdasarkan role pengguna.
        $destination = match (true) {
            $user->isVendorOwner() => route(
                'vendor.owner.dashboard',
                absolute: false
            ),

            $user->isVendorAdmin() => route(
                'vendor.dashboard',
                absolute: false
            ),

            $user->isVendorStaff() => route(
                'dashboard',
                absolute: false
            ),

            $user->isCustomer() => route(
                'home',
                absolute: false
            ),

            default => route(
                'dashboard',
                absolute: false
            ),
        };

        // Pesan selamat datang.
        $welcomeMessage = match (true) {
            $user->isVendorOwner() =>
                "Selamat datang Owner {$user->name}!",

            $user->isVendorAdmin() =>
                "Selamat datang Vendor {$user->name}!",

            $user->isSuperAdmin() =>
                "Halo Superadmin {$user->name}!",

            default =>
                "Selamat datang, {$user->name}!",
        };

        Session::flash('status', $welcomeMessage);
        Session::flash('success', $welcomeMessage);

        // Redirect cukup dilakukan satu kali.
        $this->redirectIntended(
            default: $destination,
            navigate: false
        );
    }
};
?>

<div>
    <x-slot:heading>Masuk ke Tendaku</x-slot:heading>

    <x-slot:subheading>
        Lanjutkan sewa perlengkapan campingmu.
    </x-slot:subheading>

    @if (session('status'))
        <div
            role="status"
            class="mb-6 rounded-lg border border-tendaku-brown/15 bg-tendaku-avocado/10 px-4 py-3 text-sm text-tendaku-brown"
        >
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="login" class="space-y-5">
        @csrf

        <x-auth.field
            name="form.email"
            label="Email"
            type="email"
            wire:model="form.email"
            placeholder="nama@gmail.com"
            autocomplete="username"
            required
            autofocus
        />

        <x-auth.field
            name="form.password"
            label="Password"
            type="password"
            wire:model="form.password"
            autocomplete="current-password"
            required
        >
            @if (Route::has('password.request'))
                <x-slot:aside>
                    <a
                        href="{{ route('password.request') }}"
                        wire:navigate
                        class="rounded font-medium text-tendaku-moss underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-tendaku-avocado"
                    >
                        Lupa password?
                    </a>
                </x-slot:aside>
            @endif
        </x-auth.field>

        <label
            for="remember_me"
            class="flex items-center gap-2.5 text-sm text-tendaku-brown"
        >
            <input
                wire:model="form.remember"
                id="remember_me"
                name="remember"
                type="checkbox"
                class="h-4 w-4 rounded border-tendaku-brown/15 text-tendaku-moss focus:ring-2 focus:ring-tendaku-moss"
            >

            Tetap masuk di perangkat ini
        </label>

        <x-auth.button class="w-full">
            Masuk
        </x-auth.button>
    </form>

    @include('livewire.pages.auth.partials.google-auth-button', [
        'label' => 'Masuk dengan Google',
        'showGoogleLogin' => $showGoogleLogin ?? true,
    ])

    @if (Route::has('register'))
        <p class="mt-8 text-sm text-tendaku-brown/70">
            Belum punya akun?

            <a
                href="{{ route('register') }}"
                wire:navigate
                class="font-semibold text-tendaku-moss underline-offset-4 hover:underline"
            >
                Daftar
            </a>
        </p>
    @endif
</div>
