@props([
    'type' => 'info', // 'info' | 'success' | 'warning' | 'danger'
    'title' => null,
])

@php
    $types = [
        'info' => [
            'bg' => 'bg-wheat-100/90 border-wheat-300 text-darkbrown-800',
            'icon' => 'text-goldenrod-600',
            'dot' => 'bg-goldenrod-500',
        ],
        'success' => [
            'bg' => 'bg-avocado-50 border-avocado-200 text-avocado-950',
            'icon' => 'text-avocado-600',
            'dot' => 'bg-avocado-500',
        ],
        'warning' => [
            'bg' => 'bg-sunglow-50 border-sunglow-200 text-sunglow-950',
            'icon' => 'text-sunglow-600',
            'dot' => 'bg-sunglow-500',
        ],
        'danger' => [
            'bg' => 'bg-red-50 border-red-200 text-red-950',
            'icon' => 'text-red-600',
            'dot' => 'bg-red-500',
        ],
    ];

    $alert = $types[$type] ?? $types['info'];
@endphp

<div {{ $attributes->merge(['class' => "p-4 rounded-xl border flex items-start gap-3 {$alert['bg']}"]) }}>
    <div class="shrink-0 mt-0.5 {{ $alert['icon'] }}">
        @if ($type === 'success')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        @elseif ($type === 'warning')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        @elseif ($type === 'danger')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        @else
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        @endif
    </div>

    <div class="flex-1 text-sm">
        @if ($title)
            <h4 class="font-bold mb-1">{{ $title }}</h4>
        @endif
        <div>
            {{ $slot }}
        </div>
    </div>
</div>
