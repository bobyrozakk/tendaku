
<x-mail::message>
{{-- Intro --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
# Halo!
@endif

@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>

<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

@foreach ($outroLines as $line)
{{ $line }}

@endforeach

Salam hangat,

**Tim Tendaku**

<x-slot:subcopy>
Email ini dikirim secara otomatis oleh Tendaku. Mohon jangan membalas email ini jika alamat pengirim tidak menerima balasan.

@isset($actionText)
Jika tombol di atas tidak berfungsi, salin dan buka tautan berikut di browser:

<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
@endisset
</x-slot:subcopy>
</x-mail::message>
