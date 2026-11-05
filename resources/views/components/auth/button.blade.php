<button
    wire:loading.attr="disabled"
    {{ $attributes->merge([
        'type' => 'submit',
        'class' => 'inline-flex items-center justify-center gap-2 rounded-full bg-tendaku-avocado px-6 py-3.5 text-lg font-semibold text-white shadow-[0_4px_0_0_#566F06] transition hover:brightness-105 focus:outline-none focus-visible:ring-2 focus-visible:ring-tendaku-brown focus-visible:ring-offset-2 focus-visible:ring-offset-tendaku-wheat active:translate-y-[3px] active:shadow-none disabled:cursor-not-allowed disabled:opacity-75 disabled:shadow-none',
    ]) }}
>
    <svg wire:loading class="h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <span>{{ $slot }}</span>
</button>
