@if (($showGoogleLogin ?? true) && filled(config('services.google.client_id')) && filled(config('services.google.client_secret')))
    <div class="mt-5">
        <p class="mb-3 text-center text-sm text-darkbrown-600">atau</p>
        <a href="{{ route('google.redirect') }}" class="flex min-h-[44px] w-full items-center justify-center rounded-xl border border-darkbrown-400 px-4 py-2.5 text-sm font-semibold text-darkbrown-800 transition hover:bg-wheat-100 focus:outline-none focus:ring-2 focus:ring-avocado-500 focus:ring-offset-2">
            {{ $label }}
        </a>
    </div>
@endif
