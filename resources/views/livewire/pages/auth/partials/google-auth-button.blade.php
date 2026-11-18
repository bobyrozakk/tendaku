@if (($showGoogleLogin ?? true) && filled(config('services.google.client_id')) && filled(config('services.google.client_secret')))
    <div class="mt-5">
        <p class="mb-3 text-center text-sm text-avocado-300">atau</p>

        <a
            href="{{ route('google.redirect') }}"
            aria-label="{{ $label }}"
            title="{{ $label }}"
            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-tendaku-brown/20 bg-white transition hover:bg-tendaku-wheat focus:outline-none focus-visible:ring-2 focus-visible:ring-avocado-300 focus-visible:ring-offset-2"
        >
            <img
                src="{{ asset('images/icons/google.svg') }}"
                alt=""
                aria-hidden="true"
                class="h-6 w-6"
            >
            <span class="sr-only">{{ $label }}</span>
        </a>
    </div>
@endif
