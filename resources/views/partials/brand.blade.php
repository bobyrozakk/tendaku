@php($dark = $dark ?? false)

<a href="{{ url('/') }}"
   class="inline-flex items-center gap-2.5 rounded font-display text-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 {{ $dark ? 'text-tendaku-wheat focus-visible:ring-tendaku-wheat focus-visible:ring-offset-transparent' : 'text-tendaku-brown focus-visible:ring-tendaku-brown focus-visible:ring-offset-tendaku-wheat' }}">
    <svg viewBox="0 0 32 32" class="h-8 w-8" aria-hidden="true">
        <path d="M3 27 16 5l13 22Z" fill="#D5A007"/>
        <path d="M12.5 27 16 16l3.5 11Z" fill="#6C8B08"/>
    </svg>
    Tendaku
</a>
