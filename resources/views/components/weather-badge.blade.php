@props([
    'condition' => 'Cerah',
    'icon' => 'sun',
    'temp' => null,
    'extreme' => false,
])

<span @class([
    'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold border whitespace-nowrap',
    'bg-avocado-50 border-avocado-200 text-avocado-900' => ! $extreme,
    'bg-red-50 border-red-300 text-red-900' => $extreme,
])>
    <x-icon :name="$icon" class="w-4 h-4" :stroke-width="$extreme ? 2.25 : 2" />
    <span>{{ $condition }}</span>

    @if (filled($temp))
        <span class="pl-2 font-extrabold border-l {{ $extreme ? 'border-red-300' : 'border-avocado-200' }}">{{ $temp }}</span>
    @endif
</span>
