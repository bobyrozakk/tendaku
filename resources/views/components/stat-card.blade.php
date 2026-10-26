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

<div {{ $attributes->merge(['class' => "bg-white p-5 rounded-2xl border border-wheat-200 shadow-tendaku-sm transition duration-200 hover:shadow-tendaku-md flex flex-col justify-between {$theme['border']}"]) }}>
    {{-- Header: Judul di kiri, Icon di kanan --}}
    <div class="flex items-start justify-between gap-3">
        <p class="text-[11px] font-bold uppercase tracking-wider text-darkbrown-500 leading-tight">{{ $title }}</p>
        @if ($icon)
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $theme['iconBg'] }}">
                {{ $icon }}
            </div>
        @endif
    </div>

    {{-- Value: Angka utama berukuran besar, ruang penuh tanpa terhimpit icon --}}
    <div class="mt-2.5">
        <div class="text-2xl sm:text-[28px] font-extrabold tracking-tight text-darkbrown-900 whitespace-nowrap overflow-hidden text-ellipsis">
            {{ $value }}
        </div>
    </div>

    {{-- Footer: Trend Badge & Deskripsi --}}
    @if ($trend || $description)
        <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1.5 text-xs">
            @if ($trend)
                <span class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-[11px] font-bold border {{ $trendUp ? 'bg-avocado-50 text-avocado-700 border-avocado-200' : 'bg-red-50 text-red-700 border-red-200' }}">
                    @if ($trendUp)
                        <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    @else
                        <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" />
                        </svg>
                    @endif
                    <span class="whitespace-nowrap">{{ $trend }}</span>
                </span>
            @endif
            @if ($description)
                <span class="text-xs text-darkbrown-400 font-medium leading-tight truncate">{{ $description }}</span>
            @endif
        </div>
    @endif
</div>
