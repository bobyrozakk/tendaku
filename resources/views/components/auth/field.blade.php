@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null])

@php
    $id = $attributes->get('id', $name);
    $isPassword = $type === 'password';
    $hasError = $errors->has($name);
    $describedBy = $hasError ? $id.'-error' : ($hint ? $id.'-hint' : null);

    $inputClass = 'block w-full rounded-full border bg-white px-5 py-3 text-base text-tendaku-brown '
        .'placeholder:text-tendaku-brown/45 focus:border-tendaku-avocado focus:outline-none focus:ring-4 focus:ring-tendaku-avocado/20 '
        .($hasError ? 'border-red-600' : 'border-tendaku-brown/15')
        .($isPassword ? ' pr-14' : '');
@endphp

<div @if ($isPassword) x-data="{ show: false }" @endif>
    <div class="flex items-baseline justify-between gap-3 px-1">
        <label for="{{ $id }}" class="text-sm font-medium text-tendaku-brown">{{ $label }}</label>
        @isset($aside)
            <div class="text-sm">{{ $aside }}</div>
        @endisset
    </div>

    <div class="relative mt-1.5">
        @if ($isPassword)
            <input id="{{ $id }}" name="{{ $name }}" type="password" x-bind:type="show ? 'text' : 'password'"
                   {{ $attributes->except('id')->merge(['class' => $inputClass]) }}
                   @if ($hasError) aria-invalid="true" @endif
                   @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>

            <button type="button" x-on:click="show = !show" x-bind:aria-pressed="show" x-bind:aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'" class="absolute inset-y-0 right-3 flex items-center pr-2 text-tendaku-brown/50 transition-colors hover:text-tendaku-brown focus:outline-none">
                {{-- password disembunyikan --}}
                <svg x-show="!show" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-.722-3.25"/>
                    <path d="M2 8a10.645 10.645 0 0 0 20 0"/>
                    <path d="m20 15-1.726-2.05"/>
                    <path d="m4 15 1.726-2.05"/>
                    <path d="m9 18 .722-3.25"/>
                </svg>
                {{-- password terlihat --}}
                <svg x-show="show" x-cloak class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
        @else
            <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
                   {{ $attributes->except('id')->merge(['class' => $inputClass]) }}
                   @if ($hasError) aria-invalid="true" @endif
                   @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
        @endif
    </div>

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 px-1 text-sm text-red-700">{{ $message }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 px-1 text-sm text-tendaku-brown/70">{{ $hint }}</p>
    @enderror
</div>
