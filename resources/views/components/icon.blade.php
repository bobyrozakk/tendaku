@props([
    'name',
])

@php
    $validName = preg_match('/^[a-z0-9-]+$/', $name) ? $name : 'circle-help';
@endphp

<i
    data-lucide="{{ $validName }}"
    {{ $attributes->merge(['class' => 'inline-block shrink-0']) }}
    aria-hidden="true"
></i>
