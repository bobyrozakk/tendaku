@if (($showGoogleLogin ?? true) && filled(config('services.google.client_id')) && filled(config('services.google.client_secret')))
    <div class="mt-5">
        <p class="mb-3 text-center text-sm text-tendaku-brown/60">atau</p>
        <a href="{{ route('google.redirect') }}" class="flex min-h-[44px] w-full items-center justify-center rounded-full border border-tendaku-brown/20 px-4 py-2.5 text-sm font-medium text-tendaku-brown transition hover:bg-tendaku-wheat focus:outline-none focus-visible:ring-2 focus-visible:ring-tendaku-avocado focus-visible:ring-offset-2">
            {{ $label }}
        </a>
    </div>
@endif
