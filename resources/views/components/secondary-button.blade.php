@props([
    'size' => 'md',
])

@php
    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-xs rounded-lg',
        'md' => 'px-4 py-2.5 text-sm rounded-xl',
        'lg' => 'px-5 py-3 text-base rounded-xl',
    ][$size] ?? 'px-4 py-2.5 text-sm rounded-xl';
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => "inline-flex items-center justify-center gap-2 font-medium bg-wheat-100/90 text-darkbrown-800 border border-wheat-300 hover:bg-wheat-200 active:bg-wheat-300 focus:outline-none focus:ring-2 focus:ring-wheat-400 focus:ring-offset-2 disabled:opacity-50 transition-all duration-150 select-none {$sizeClasses}"]) }}>
    {{ $slot }}
</button>
