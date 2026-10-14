<x-auth-session-status class="mb-5" :status="session('status')" />

<form wire:submit="login" class="space-y-5">
    @csrf

    <x-auth.field name="form.email" label="Email" type="email"
                  wire:model="form.email"
                  placeholder="nama@email.com" autocomplete="username" required autofocus />

    <x-auth.field name="form.password" label="Password" type="password"
                  wire:model="form.password"
                  autocomplete="current-password" required>
        @if (($showForgotPassword ?? true) && Route::has('password.request'))
            <x-slot:aside>
                <a href="{{ route('password.request') }}"
                   wire:navigate
                   class="rounded font-medium text-tendaku-moss underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-tendaku-avocado">
                    Lupa password?
                </a>
            </x-slot:aside>
        @endif
    </x-auth.field>

    <label for="remember_me" class="flex items-center gap-2.5 text-sm text-tendaku-brown">
        <input wire:model="form.remember" id="remember_me" name="remember" type="checkbox"
               class="h-4 w-4 rounded border-tendaku-brown/15 text-tendaku-moss focus:ring-2 focus:ring-tendaku-moss">
        Tetap masuk di perangkat ini
    </label>

    <x-auth.button class="w-full">Masuk</x-auth.button>
</form>

@include('livewire.pages.auth.partials.google-auth-button', [
    'label' => 'Masuk dengan Google',
    'showGoogleLogin' => $showGoogleLogin ?? true,
])

@if ($showRegisterLink ?? true)
    <p class="mt-8 text-sm text-tendaku-brown/70">
        Belum punya akun?
        <a href="{{ route('register') }}"
           wire:navigate
           class="font-semibold text-tendaku-moss underline-offset-4 hover:underline">
            Daftar
        </a>
    </p>
@endif
