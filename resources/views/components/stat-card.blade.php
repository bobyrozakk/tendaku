@props([
    'title',
    'value',
    'icon' => null,
    'trend' => null,
    'trendUp' => true,
    'description' => null,
    'color' => 'avocado', // 'avocado' | 'goldenrod' | 'sunglow' | 'dark'
])

@php
    $colorThemes = [
        'avocado' => [
            'iconBg' => 'bg-avocado-100 text-avocado-700',
            'border' => 'hover:border-avocado-300',
            'glow' => 'hover:shadow-tendaku-avocado-glow',
        ],
        'goldenrod' => [
            'iconBg' => 'bg-goldenrod-100 text-goldenrod-700',
            'border' => 'hover:border-goldenrod-300',
            'glow' => 'hover:shadow-tendaku-glow',
        ],
        'sunglow' => [
            'iconBg' => 'bg-sunglow-100 text-sunglow-800',
            'border' => 'hover:border-sunglow-300',
            'glow' => '',
        ],
        'dark' => [
            'iconBg' => 'bg-darkbrown-800 text-wheat-200',
            'border' => 'hover:border-darkbrown-600',
            'glow' => '',
        ],
    ];

    $theme = $colorThemes[$color] ?? $colorThemes['avocado'];
@endphp

<div {{ $attributes->merge(['class' => "bg-white p-5 rounded-2xl border border-wheat-200 shadow-tendaku-sm transition duration-200 {$theme['border']}"]) }}>
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-darkbrown-500">{{ $title }}</p>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-extrabold tracking-tight text-darkbrown-800">{{ $value }}</span>
                @if ($trend)
                    <span class="inline-flex items-center text-xs font-bold {{ $trendUp ? 'text-avocado-600' : 'text-red-500' }}">
                        @if ($trendUp)
                            <svg class="w-3.5 h-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        @else
                            <svg class="w-3.5 h-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" />
                            </svg>
                        @endif
                        {{ $trend }}
                    </span>
                @endif
            </div>
            @if ($description)
                <p class="mt-1 text-xs text-darkbrown-400">{{ $description }}</p>
            @endif
        </div>

        @if ($icon)
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $theme['iconBg'] }}">
                {{ $icon }}
            </div>
        @endif
    </div>
</div>
