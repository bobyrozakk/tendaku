@props([
    'variant' => 'avocado', // 'avocado' | 'goldenrod' | 'sunglow' | 'wheat' | 'dark' | 'danger'
    'size' => 'md',
    'dot' => false,
])

@php
    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-[11px]',
        'md' => 'px-2.5 py-1 text-xs',
        'lg' => 'px-3 py-1.5 text-sm',
    ][$size] ?? 'px-2.5 py-1 text-xs';

    $variants = [
        'avocado' => [
            'badge' => 'bg-avocado-100/90 text-avocado-800 border-avocado-300',
            'dot' => 'bg-avocado-500',
        ],
        'goldenrod' => [
            'badge' => 'bg-goldenrod-100 text-goldenrod-900 border-goldenrod-300',
            'dot' => 'bg-goldenrod-500',
        ],
        'sunglow' => [
            'badge' => 'bg-sunglow-100 text-sunglow-900 border-sunglow-300',
            'dot' => 'bg-sunglow-500',
        ],
        'wheat' => [
            'badge' => 'bg-wheat-200/80 text-darkbrown-800 border-wheat-300',
            'dot' => 'bg-wheat-600',
        ],
        'dark' => [
            'badge' => 'bg-darkbrown-800 text-wheat-200 border-darkbrown-700',
            'dot' => 'bg-wheat-300',
        ],
        'danger' => [
            'badge' => 'bg-red-100 text-red-800 border-red-200',
            'dot' => 'bg-red-500',
        ],
    ];

    $currentVariant = $variants[$variant] ?? $variants['avocado'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full font-semibold border select-none {$sizeClasses} {$currentVariant['badge']}"]) }}>
    @if ($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $currentVariant['dot'] }}"></span>
    @endif
    {{ $slot }}
</span>
