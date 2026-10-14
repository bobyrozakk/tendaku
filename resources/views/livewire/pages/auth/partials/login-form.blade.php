<x-auth-session-status class="mb-4" :status="session('status')" />

<form wire:submit="login">
    <div>
        <x-input-label for="email" :value="__('Email')" />
        <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
    </div>

    <div class="mt-4">
        <x-input-label for="password" :value="__('Password')" />

        <div class="relative mt-1" x-data="{ showPassword: false }">
            <input wire:model="form.password"
                   id="password"
                   name="password"
                   x-bind:type="showPassword ? 'text' : 'password'"
                   required
                   autocomplete="current-password"
                   class="block w-full pr-10 rounded-xl border-wheat-300 bg-white/90 text-darkbrown-800 shadow-sm focus:border-avocado-500 focus:ring-2 focus:ring-avocado-400/40 text-sm placeholder:text-darkbrown-400 transition duration-150" />
            <button type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600 focus:outline-none"
                    :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'">
                {{-- Eye open --}}
                <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                {{-- Eye slash --}}
                <svg x-show="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
            </button>
        </div>

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
