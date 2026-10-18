<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex items-center justify-center rounded-full bg-tendaku-avocado px-6 py-3.5 text-lg font-semibold text-white shadow-[0_4px_0_0_#566F06] transition hover:brightness-105 focus:outline-none focus-visible:ring-2 focus-visible:ring-tendaku-brown focus-visible:ring-offset-2 focus-visible:ring-offset-tendaku-wheat active:translate-y-[3px] active:shadow-none',
]) }}>
    {{ $slot }}
</button>
