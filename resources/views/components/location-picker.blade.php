@props([
    'id' => null,
    'latitude' => null,
    'longitude' => null,
    'latitudeName' => 'latitude',
    'longitudeName' => 'longitude',
    'wireLatitudeModel' => null,
    'wireLongitudeModel' => null,
    'defaultLatitude' => -7.9797,
    'defaultLongitude' => 112.6304,
    'defaultZoom' => 13,
    'selectedZoom' => 16,
    'label' => 'Titik Lokasi pada Peta',
    'helpText' => 'Klik pada peta atau geser pin untuk menentukan titik lokasi yang presisi.',
    'heightClass' => 'h-64 sm:h-72',
    'required' => false,
    'disabled' => false,
])

@php
    $parseCoordinate = static function (mixed $value): ?float {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
        }

        if (! is_numeric($value)) {
            return null;
        }

        $floatValue = (float) $value;

        return is_finite($floatValue) ? round($floatValue, 7) : null;
    };

    $parsedLat = $parseCoordinate($latitude);
    $parsedLng = $parseCoordinate($longitude);

    $hasValidInitialCoordinates = $parsedLat !== null
        && $parsedLng !== null
        && $parsedLat >= -90.0
        && $parsedLat <= 90.0
        && $parsedLng >= -180.0
        && $parsedLng <= 180.0;

    $initialLatitude = $hasValidInitialCoordinates ? $parsedLat : null;
    $initialLongitude = $hasValidInitialCoordinates ? $parsedLng : null;

    $parsedDefaultLat = $parseCoordinate($defaultLatitude);
    $parsedDefaultLng = $parseCoordinate($defaultLongitude);

    $hasValidDefaultCenter = $parsedDefaultLat !== null
        && $parsedDefaultLng !== null
        && $parsedDefaultLat >= -90.0
        && $parsedDefaultLat <= 90.0
        && $parsedDefaultLng >= -180.0
        && $parsedDefaultLng <= 180.0;

    $safeDefaultLatitude = $hasValidDefaultCenter ? $parsedDefaultLat : -7.9797;
    $safeDefaultLongitude = $hasValidDefaultCenter ? $parsedDefaultLng : 112.6304;

    $pickerId = filled($id) ? (string) $id : 'location-picker-' . \Illuminate\Support\Str::lower((string) \Illuminate\Support\Str::ulid());

    $pickerConfig = [
        'pickerId' => $pickerId,
        'initialLatitude' => $initialLatitude,
        'initialLongitude' => $initialLongitude,
        'defaultLatitude' => $safeDefaultLatitude,
        'defaultLongitude' => $safeDefaultLongitude,
        'defaultZoom' => (int) $defaultZoom,
        'selectedZoom' => (int) $selectedZoom,
        'disabled' => (bool) $disabled,
        'wireLatitudeModel' => $wireLatitudeModel,
        'wireLongitudeModel' => $wireLongitudeModel,
    ];

    $initialLatitudeInputValue = $initialLatitude !== null ? number_format($initialLatitude, 7, '.', '') : '';
    $initialLongitudeInputValue = $initialLongitude !== null ? number_format($initialLongitude, 7, '.', '') : '';
@endphp

<div
    id="{{ $pickerId }}"
    x-data="locationPicker(@js($pickerConfig))"
    {{ $attributes->merge(['class' => 'tendaku-location-picker space-y-2.5']) }}
>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-0.5">
            @if (filled($label))
                <label class="block text-sm font-semibold text-darkbrown-800">
                    {{ $label }}
                    @if ($required)
                        <span class="text-red-600" aria-hidden="true">*</span>
                    @endif
                </label>
            @endif

            @if (filled($helpText))
                <p class="text-xs text-darkbrown-500">
                    {{ $helpText }}
                </p>
            @endif
        </div>

        @if (! $disabled)
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button
                    type="button"
                    @click="useCurrentLocation()"
                    :disabled="isLocating"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-avocado-700 bg-avocado-50 hover:bg-avocado-100 border border-avocado-600/30 transition duration-150 focus:outline-none focus:ring-2 focus:ring-avocado-500 focus:ring-offset-1 disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <svg class="w-3.5 h-3.5 text-avocado-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 2v3"></path>
                        <path d="M12 19v3"></path>
                        <path d="M2 12h3"></path>
                        <path d="M19 12h3"></path>
                    </svg>
                    <span x-text="isLocating ? 'Mendeteksi lokasi...' : 'Gunakan Lokasi Saya'">Gunakan Lokasi Saya</span>
                </button>

                <button
                    type="button"
                    x-cloak
                    x-show="hasSelectedCoordinates"
                    @click="clearLocation()"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium text-darkbrown-700 bg-white hover:bg-wheat-100 border border-wheat-300 transition duration-150 focus:outline-none focus:ring-2 focus:ring-wheat-400 focus:ring-offset-1"
                >
                    <svg class="w-3.5 h-3.5 text-darkbrown-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                    <span>Hapus Titik</span>
                </button>
            </div>
        @endif
    </div>

    <div
        x-cloak
        x-show="feedbackMessage !== ''"
        role="status"
        aria-live="polite"
        :class="feedbackType === 'error'
            ? 'border-red-200 bg-red-50 text-red-700'
            : 'border-avocado-200 bg-avocado-50 text-avocado-800'"
        class="px-3 py-2 rounded-lg border text-xs font-medium flex items-start justify-between gap-2"
    >
        <span x-text="feedbackMessage"></span>
        <button
            type="button"
            @click="clearFeedback()"
            class="shrink-0 text-current opacity-70 hover:opacity-100 focus:outline-none"
            aria-label="Tutup pesan"
        >
            &times;
        </button>
    </div>

    <div class="tendaku-location-picker__frame">
        <div wire:ignore>
            <div
                x-ref="mapCanvas"
                class="tendaku-location-picker__canvas {{ $heightClass }}"
                role="application"
                aria-label="{{ $label ?: 'Peta pemilih lokasi' }}"
                tabindex="0"
            ></div>
        </div>

        <div class="px-3.5 py-2.5 bg-wheat-50/70 border-t border-wheat-200 flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between text-xs">
            <div class="flex items-center gap-2">
                <span
                    :class="hasSelectedCoordinates ? 'bg-avocado-600' : 'bg-wheat-500'"
                    class="w-2 h-2 rounded-full shrink-0"
                    aria-hidden="true"
                ></span>
                <span
                    x-text="hasSelectedCoordinates ? 'Titik lokasi dipilih' : 'Belum ada titik lokasi yang dipilih (peta menampilkan area Malang Raya)'"
                    :class="hasSelectedCoordinates ? 'font-semibold text-darkbrown-800' : 'text-darkbrown-500'"
                >
                    {{ $hasValidInitialCoordinates ? 'Titik lokasi dipilih' : 'Belum ada titik lokasi yang dipilih (peta menampilkan area Malang Raya)' }}
                </span>
            </div>

            <div
                x-cloak
                x-show="hasSelectedCoordinates"
                class="flex flex-wrap items-center gap-3 font-mono text-xs text-darkbrown-700"
            >
                <span>
                    Lat: <strong class="font-semibold text-darkbrown-900" x-text="formattedLatitude">{{ $initialLatitudeInputValue }}</strong>
                </span>
                <span>
                    Lng: <strong class="font-semibold text-darkbrown-900" x-text="formattedLongitude">{{ $initialLongitudeInputValue }}</strong>
                </span>
            </div>
        </div>
    </div>

    <input
        type="hidden"
        x-ref="latitudeInput"
        name="{{ $latitudeName }}"
        value="{{ $initialLatitudeInputValue }}"
        data-location-picker-input="latitude"
    >
    <input
        type="hidden"
        x-ref="longitudeInput"
        name="{{ $longitudeName }}"
        value="{{ $initialLongitudeInputValue }}"
        data-location-picker-input="longitude"
    >

    @if (isset($errors) && $errors->has($latitudeName))
        <p class="text-xs text-red-600 mt-1">{{ $errors->first($latitudeName) }}</p>
    @endif

    @if (isset($errors) && $errors->has($longitudeName))
        <p class="text-xs text-red-600 mt-1">{{ $errors->first($longitudeName) }}</p>
    @endif
</div>
