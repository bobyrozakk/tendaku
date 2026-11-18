@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null])

@php
    $id = $attributes->get('id', $name);
    $isPassword = $type === 'password';
    $isTextarea = $type === 'textarea';
    $hasError = $errors->has($name);
    $describedBy = $hasError ? $id . '-error' : ($hint ? $id . '-hint' : null);

    $inputClass = 'block w-full border bg-white px-5 py-3 text-base text-tendaku-brown '
        . ($isTextarea ? 'min-h-24 resize-y rounded-2xl ' : 'rounded-full ')
        . 'placeholder:text-tendaku-brown/45 focus:border-tendaku-avocado focus:outline-none focus:ring-4 focus:ring-tendaku-avocado/20 '
        . ($hasError ? 'border-red-600' : 'border-tendaku-brown/15')
        . ($isPassword ? ' pr-14' : '');
@endphp

<div @if ($isPassword) x-data="{ show: false }" @endif>
    <div class="flex items-baseline justify-between gap-3 px-1">
        <label for="{{ $id }}" class="text-sm font-medium text-white">
            {{ $label }}
        </label>

        @isset($aside)
            <div class="text-sm text-white">{{ $aside }}</div>
        @endisset
    </div>

    <div class="relative mt-1.5">
        @if ($isTextarea)
            <textarea
                id="{{ $id }}"
                name="{{ $name }}"
                {{ $attributes->except('id')->merge(['class' => $inputClass]) }}
                @if ($hasError) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            >{{ old($name, $value) }}</textarea>
        @elseif ($isPassword)
            <input
                id="{{ $id }}"
                name="{{ $name }}"
                type="password"
                x-bind:type="show ? 'text' : 'password'"
                {{ $attributes->except('id')->merge(['class' => $inputClass]) }}
                @if ($hasError) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            >

            <button
                type="button"
                x-on:click="show = !show"
                x-bind:aria-pressed="show"
                x-bind:aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                class="absolute inset-y-0 right-3 flex items-center pr-2 text-tendaku-brown/60 transition-colors hover:text-tendaku-brown focus:outline-none"
            >
                <i
                    x-show="!show"
                    data-lucide="eye-closed"
                    class="h-5 w-5"
                ></i>

                <i
                    x-show="show"
                    x-cloak
                    data-lucide="eye"
                    class="h-5 w-5"
                ></i>
            </button>
        @else
            <input
                id="{{ $id }}"
                name="{{ $name }}"
                type="{{ $type }}"
                value="{{ old($name, $value) }}"
                {{ $attributes->except('id')->merge(['class' => $inputClass]) }}
                @if ($hasError) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            >
        @endif
    </div>

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 px-1 text-sm text-red-300">
            {{ $message }}
        </p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 px-1 text-sm text-white/70">
            {{ $hint }}
        </p>
    @enderror
</div>
