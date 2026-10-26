@props([
    'status',
    'label' => null,
])

@php
    $badgeClass = match ($status) {
        'pending'            => 'bg-sunglow-100 text-sunglow-900 border-sunglow-300',
        'confirmed'          => 'bg-sky-100 text-sky-900 border-sky-300',
        'picked_up'          => 'bg-avocado-100 text-avocado-900 border-avocado-300',
        'returned'           => 'bg-indigo-100 text-indigo-900 border-indigo-300',
        'completed'          => 'bg-emerald-100 text-emerald-900 border-emerald-300',
        'cancelled', 'expired' => 'bg-red-100 text-red-900 border-red-300',
        default              => 'bg-wheat-100 text-darkbrown-700 border-wheat-300',
    };
@endphp

<span {{ $attributes->merge(['class' => "px-2.5 py-0.5 rounded-full text-[11px] font-bold border {$badgeClass}"]) }}>{{ $label ?? ucfirst($status) }}</span>
