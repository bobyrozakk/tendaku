<?php

use App\Livewire\Forms\LoginForm;
use App\Services\Auth\AuthRedirectService;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(AuthRedirectService $redirectService): void
    {
        $this->validate();

        $this->form->authenticate(true);

        Session::regenerate();

        $user = auth()->user();
        Session::flash('success', $redirectService->getWelcomeMessage($user));

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: false);
    }
}; ?>

<div class="space-y-6">
    <x-slot:heading>Super Admin Login</x-slot:heading>
    <x-slot:subheading>Akses terbatas untuk pengelola platform Tendaku.</x-slot:subheading>

    <div class="flex items-start gap-3 rounded-2xl border border-tendaku-brown/10 bg-white/70 p-4 shadow-[0_2px_8px_-1px_rgba(43,34,2,0.06)] backdrop-blur">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-tendaku-avocado/10 text-tendaku-moss">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-tendaku-brown">Portal pengelola platform</p>
            <p class="mt-1 text-xs leading-relaxed text-tendaku-brown/70">
                Gunakan kredensial super admin. Aktivitas operasional tercatat pada log audit platform.
            </p>
        </div>
    </div>

    @include('livewire.pages.auth.partials.login-form', [
        'showForgotPassword' => false,
        'showGoogleLogin' => false,
        'showRegisterLink' => false,
    ])

    <p class="flex items-center justify-center gap-2 text-[11px] font-medium text-tendaku-brown/80">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        Koneksi aman &middot; Sesi diawasi
    </p>
</div>
