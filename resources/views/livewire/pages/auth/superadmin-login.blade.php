<?php

use App\Livewire\Forms\LoginForm;
use App\Services\Auth\AuthRedirectService;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.superadmin-guest')] class extends Component
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

<div class="space-y-6" x-data="{ capsLockOn: false, showPassword: false }">
    {{-- Header Section --}}
    <div>
        <h1 class="font-display text-3xl sm:text-4xl font-normal text-tendaku-brown tracking-tight">
            Super Admin Login
        </h1>
        <p class="mt-2 text-sm text-tendaku-brown/70 leading-relaxed">
            Otorisasi kredensial administrator untuk akses konsol platform Tendaku.
        </p>
    </div>

    {{-- Live JavaScript Caps Lock Alert --}}
    <div
        x-show="capsLockOn"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        role="alert"
        class="flex items-center gap-2.5 rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-2.5 text-xs font-medium text-amber-900 shadow-sm"
    >
        <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span><strong>Caps Lock Aktif:</strong> Perhatikan penulisan huruf besar dan kecil pada kredensial Anda.</span>
    </div>

    {{-- Session Status / Error Notice --}}
    @if (session('status'))
        <div role="status" class="rounded-xl border border-tendaku-brown/15 bg-tendaku-avocado/10 px-4 py-3 text-sm text-tendaku-brown">
            {{ session('status') }}
        </div>
    @endif

    {{-- Authentication Form --}}
    <form wire:submit="login" class="space-y-4" id="superadmin-login-form">
        @csrf

        {{-- Email Field --}}
        <div>
            <label for="superadmin-email" class="block text-xs font-semibold text-tendaku-brown uppercase tracking-wider">
                Email Administrator
            </label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-tendaku-brown/40">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </span>
                <input
                    id="superadmin-email"
                    name="form.email"
                    type="email"
                    wire:model="form.email"
                    placeholder="admin@tendaku.com"
                    autocomplete="username"
                    required
                    autofocus
                    @keyup="capsLockOn = $event.getModifierState && $event.getModifierState('CapsLock')"
                    @keydown="capsLockOn = $event.getModifierState && $event.getModifierState('CapsLock')"
                    class="block w-full rounded-xl border {{ $errors->has('form.email') ? 'border-red-600 bg-red-50/20' : 'border-tendaku-brown/15 bg-white' }} py-3 pl-11 pr-4 text-sm text-tendaku-brown placeholder:text-tendaku-brown/40 focus:border-tendaku-avocado focus:outline-none focus:ring-4 focus:ring-tendaku-avocado/20 transition"
                    @if ($errors->has('form.email')) aria-invalid="true" aria-describedby="superadmin-email-error" @endif
                >
            </div>
            @error('form.email')
                <p id="superadmin-email-error" class="mt-1.5 px-1 text-xs font-medium text-red-700">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password Field with Interactive Toggle & Caps Lock Detection --}}
        <div>
            <div class="flex items-center justify-between">
                <label for="superadmin-password" class="block text-xs font-semibold text-tendaku-brown uppercase tracking-wider">
                    Kata Sandi
                </label>
            </div>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-tendaku-brown/40">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
                <input
                    id="superadmin-password"
                    name="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    wire:model="form.password"
                    placeholder="••••••••••••"
                    autocomplete="current-password"
                    required
                    @keyup="capsLockOn = $event.getModifierState && $event.getModifierState('CapsLock')"
                    @keydown="capsLockOn = $event.getModifierState && $event.getModifierState('CapsLock')"
                    class="block w-full rounded-xl border {{ $errors->has('form.password') ? 'border-red-600 bg-red-50/20' : 'border-tendaku-brown/15 bg-white' }} py-3 pl-11 pr-12 text-sm text-tendaku-brown placeholder:text-tendaku-brown/40 focus:border-tendaku-avocado focus:outline-none focus:ring-4 focus:ring-tendaku-avocado/20 transition"
                    @if ($errors->has('form.password')) aria-invalid="true" aria-describedby="superadmin-password-error" @endif
                >
                {{-- Show/Hide Password Interactive Button --}}
                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'"
                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-tendaku-brown/45 hover:text-tendaku-brown focus:outline-none transition-colors"
                >
                    <span x-show="!showPassword" class="flex">
                        <i data-lucide="eye-closed" class="h-5 w-5"></i>
                    </span>
                    <span x-show="showPassword" x-cloak class="flex">
                        <i data-lucide="eye" class="h-5 w-5"></i>
                    </span>
                </button>
            </div>
            @error('form.password')
                <p id="superadmin-password-error" class="mt-1.5 px-1 text-xs font-medium text-red-700">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember Device (Auto-fill removed) --}}
        <div class="pt-1 text-xs">
            <label for="superadmin-remember" class="flex items-center gap-2 cursor-pointer text-tendaku-brown select-none">
                <input
                    id="superadmin-remember"
                    name="form.remember"
                    type="checkbox"
                    wire:model="form.remember"
                    class="h-4 w-4 rounded border-tendaku-brown/20 text-tendaku-moss focus:ring-2 focus:ring-tendaku-avocado transition"
                >
                <span>Ingat sesi pada perangkat ini</span>
            </label>
        </div>

        {{-- Submit Button with Loading State --}}
        <div class="pt-2">
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="group relative flex w-full items-center justify-center gap-2.5 overflow-hidden rounded-xl bg-gradient-to-r from-avocado-700 via-avocado-600 to-tendaku-avocado px-6 py-3.5 text-base font-semibold text-white shadow-[0_4px_16px_rgba(108,139,8,0.35)] transition-all duration-200 hover:shadow-[0_6px_20px_rgba(108,139,8,0.45)] hover:from-avocado-800 hover:to-avocado-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-tendaku-avocado/30 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-75 disabled:shadow-none"
            >
                <span wire:loading.remove wire:target="login" class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-tendaku-wheat transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>Autentikasi & Masuk Konsol</span>
                </span>

                <span wire:loading wire:target="login" class="flex items-center gap-2">
                    <svg class="h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memverifikasi Hak Akses...</span>
                </span>
            </button>
        </div>
    </form>
</div>
