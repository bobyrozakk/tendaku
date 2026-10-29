<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasVerifiedEmail()) {
            return;
        }

        $destination = match (true) {
            $user->isVendorOwner() => route(
                'vendor.owner.dashboard',
                absolute: false
            ),
            $user->isVendorAdmin() => route(
                'kasir.dashboard',
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
            default => route('dashboard', absolute: false),
        };

        $intended = session('url.intended');

        if (
            $intended &&
            str_contains($intended, 'verify-email')
        ) {
            session()->forget('url.intended');
        }

        $this->redirectIntended(
            default: $destination,
            navigate: false
        );
    }

    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $user = Auth::user();

            $destination = match (true) {
                $user->isVendorOwner() => route(
                    'vendor.owner.dashboard',
                    absolute: false
                ),
                $user->isVendorAdmin() => route(
                    'kasir.dashboard',
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
                default => route('dashboard', absolute: false),
            };

            $this->redirectIntended(default: $destination, navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::put('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <x-slot:heading>Cek emailmu</x-slot:heading>
    <x-slot:subheading>Kami sudah mengirim tautan verifikasi. Klik tautan itu untuk mengaktifkan akun sebelum mulai menyewa.</x-slot:subheading>

    @if (session('status') === 'verification-link-sent')
        <div role="status" class="mb-6 rounded-lg border border-tendaku-brown/15 bg-tendaku-avocado/10 px-4 py-3 text-sm text-tendaku-brown">
            Tautan verifikasi baru sudah dikirim ke email yang kamu daftarkan.
        </div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form wire:submit="sendVerification">
            @csrf
            <x-auth.button class="w-full sm:w-auto">Kirim ulang email</x-auth.button>
        </form>

        <a href="{{ route('login') }}"
           wire:navigate
           class="rounded text-sm font-medium text-tendaku-brown/70 underline-offset-4 hover:text-tendaku-brown hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-tendaku-brown/40">
            Kembali
        </a>
    </div>

    <p class="mt-8 text-sm text-tendaku-brown/70">Email belum masuk? Cek juga folder spam.</p>
</div>
