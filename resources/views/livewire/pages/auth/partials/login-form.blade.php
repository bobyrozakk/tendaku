<x-auth-session-status class="mb-4" :status="session('status')" />

<form wire:submit="login">
    <div>
        <x-input-label for="email" :value="__('Email')" />
        <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
    </div>

    <div class="mt-4">
        <x-input-label for="password" :value="__('Password')" />

        <x-text-input wire:model="form.password" id="password" class="block mt-1 w-full"
                        type="password"
                        name="password"
                        required autocomplete="current-password" />

        <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
    </div>

    <div class="block mt-4">
        <label for="remember" class="inline-flex items-center">
            <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
            <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
        </label>
    </div>

    <div class="flex items-center justify-end mt-4">
        @if (($showForgotPassword ?? true) && Route::has('password.request'))
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}" wire:navigate>
                {{ __('Forgot your password?') }}
            </a>
        @endif

        <x-primary-button class="ms-3">
            {{ __('Log in') }}
        </x-primary-button>
    </div>
</form>

@include('livewire.pages.auth.partials.google-auth-button', [
    'label' => 'Masuk dengan Google',
    'showGoogleLogin' => $showGoogleLogin ?? true,
])

@if ($showRegisterLink ?? true)
    <p class="mt-5 text-center text-sm text-darkbrown-600">
        Belum punya akun?
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-darkbrown-800 underline decoration-avocado-600 underline-offset-2 focus:outline-none focus:ring-2 focus:ring-avocado-500 focus:ring-offset-2">
            Daftar sebagai customer
        </a>
    </p>
@endif
