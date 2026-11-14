@props([
    'pickerId',
    'activeExpression' => 'true',
    'contextExpression' => 'null',
    'asForm' => false,
])

<{{ $asForm ? 'form' : 'div' }}
    x-data="addressAutofill(@js(['url' => route('locations.reverse-geocode'), 'pickerId' => $pickerId]))"
    x-effect="syncAutofillContext({{ $activeExpression }}, {{ $contextExpression }})"
    @location-picker:change="scheduleAddressAutofill($event.detail)"
    @input="trackAddressEdit($event)"
    @change="trackAddressEdit($event)"
    @submit.capture="cancelAddressAutofill()"
    {{ $attributes }}
>
    {{ $slot }}

    <p x-cloak x-show="autofillMessage" x-text="autofillMessage" role="status" aria-live="polite" class="text-xs text-darkbrown-600"></p>
</{{ $asForm ? 'form' : 'div' }}>
