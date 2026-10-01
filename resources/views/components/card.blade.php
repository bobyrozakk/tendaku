@props([
    'title' => null,
    'subtitle' => null,
    'variant' => 'default', // 'default' | 'warm' | 'dark' | 'glass'
    'headerBorder' => false,
    'padding' => 'p-6',
])

@php
    $variants = [
        'default' => 'bg-white border-wheat-200/90 text-darkbrown-800 shadow-tendaku-sm',
        'warm' => 'bg-[#FAF6ED]/80 border-wheat-300/80 text-darkbrown-800 shadow-tendaku-sm',
        'dark' => 'bg-darkbrown-800 border-darkbrown-700 text-wheat-100 shadow-tendaku',
        'glass' => 'bg-white/70 backdrop-blur-md border-wheat-200 text-darkbrown-800 shadow-tendaku-sm',
    ];

    $cardClass = $variants[$variant] ?? $variants['default'];
@endphp

<div {{ $attributes->merge(['class' => "rounded-2xl border transition-all duration-200 {$cardClass}"]) }}>
    @if ($title || isset($header) || isset($action))
        <div class="px-6 pt-5 pb-4 flex items-start justify-between gap-4 {{ $headerBorder ? 'border-b border-wheat-200/60' : '' }}">
            <div class="space-y-0.5">
                @if ($title)
                    <h3 class="text-base sm:text-lg font-bold tracking-tight {{ $variant === 'dark' ? 'text-wheat-100' : 'text-darkbrown-800' }}">
                        {{ $title }}
                    </h3>
                @endif
                @if ($subtitle)
                    <p class="text-xs sm:text-sm {{ $variant === 'dark' ? 'text-wheat-300' : 'text-darkbrown-500' }}">
                        {{ $subtitle }}
                    </p>
                @endif
                @if (isset($header))
                    {{ $header }}
                @endif
            </div>

            @if (isset($action))
                <div class="shrink-0 flex items-center gap-2">
                    {{ $action }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="px-6 py-3.5 bg-wheat-50/50 rounded-b-2xl border-t border-wheat-200/60 flex items-center justify-between text-xs sm:text-sm">
            {{ $footer }}
        </div>
    @endif
</div>
