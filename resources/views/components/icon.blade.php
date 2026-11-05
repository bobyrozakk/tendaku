@props([
    'name',
])

@php
    $path = base_path("node_modules/lucide-static/icons/{$name}.svg");

    if (! preg_match('/^[a-z0-9-]+$/', $name) || ! is_file($path)) {
        throw new \InvalidArgumentException("Ikon Lucide '{$name}' tidak ditemukan.");
    }
@endphp

<span
    data-lucide="{{ $name }}"
    {{ $attributes->merge(['class' => 'inline-block shrink-0']) }}
    aria-hidden="true"
></span>
