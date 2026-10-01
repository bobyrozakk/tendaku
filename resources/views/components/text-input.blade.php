@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-wheat-300 bg-white/90 text-darkbrown-800 shadow-sm focus:border-avocado-500 focus:ring-2 focus:ring-avocado-400/40 text-sm placeholder:text-darkbrown-400 transition duration-150 disabled:bg-wheat-100 disabled:opacity-60']) }}>
