@props([
    'variant' => 'avocado', // 'avocado' | 'goldenrod' | 'dark' | 'sunglow'
    'size' => 'md',
])

@php
    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-xs rounded-lg',
        'md' => 'px-4 py-2.5 text-sm rounded-xl',
        'lg' => 'px-5 py-3 text-base rounded-xl',
    ][$size] ?? 'px-4 py-2.5 text-sm rounded-xl';

    $variantClasses = [
        'avocado' => 'bg-avocado-500 text-white hover:bg-avocado-600 active:bg-avocado-700 focus:ring-avocado-500 shadow-sm hover:shadow-tendaku',
        'goldenrod' => 'bg-goldenrod-500 text-white hover:bg-goldenrod-600 active:bg-goldenrod-700 focus:ring-goldenrod-500 shadow-sm hover:shadow-tendaku',
        'sunglow' => 'bg-sunglow-300 text-darkbrown-800 hover:bg-sunglow-400 active:bg-goldenrod-400 focus:ring-sunglow-400 shadow-sm hover:shadow-tendaku font-bold',
        'dark' => 'bg-darkbrown-800 text-wheat-100 hover:bg-darkbrown-900 active:bg-darkbrown-950 focus:ring-darkbrown-800 shadow-sm',
    ][$variant] ?? 'bg-avocado-500 text-white hover:bg-avocado-600 active:bg-avocado-700 focus:ring-avocado-500 shadow-sm hover:shadow-tendaku';
@endphp

<button {{ $attributes->merge(['type' => 'submit', 'class' => "inline-flex items-center justify-center gap-2 font-semibold transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed select-none {$sizeClasses} {$variantClasses}"]) }}>
    {{ $slot }}
</button>
