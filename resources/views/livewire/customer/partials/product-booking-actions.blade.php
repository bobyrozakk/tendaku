@php
    $checkoutAction = $canSubmit ? 'checkoutNow' : 'chooseSchedule';
    $compact = $compact ?? false;
@endphp

<div
    class="flex items-stretch gap-2"
    x-data="{ redirecting: false }"
    x-on:checkout-redirecting.window="redirecting = true"
    x-on:pageshow.window="if ($event.persisted) redirecting = false"
>
    <button
        type="button"
        wire:click="{{ $checkoutAction }}"
        id="{{ $isGuest ? 'btn-checkout' : 'btn-sewa-sekarang' }}{{ $idSuffix }}"
        wire:loading.attr="disabled"
        wire:loading.class="opacity-75 cursor-wait"
        x-on:click="redirecting = true"
        x-bind:disabled="redirecting"
        class="btn-tendaku-primary min-w-0 flex-[0.8] sm:flex-1 rounded-xl font-extrabold text-sm shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 cursor-pointer transition-all duration-200 {{ $compact ? 'py-3 px-3' : 'py-3 px-3' }}"
    >
        <span
            x-show="!redirecting"
            x-cloak
            class="flex items-center justify-center gap-2"
        >
            <span>Checkout</span>
        </span>

        <span
            x-show="redirecting"
            x-cloak
            class="flex items-center justify-center gap-2"
        >
            <i data-lucide="loader" class="w-4 h-4 animate-spin"></i>
        </span>
    </button>

    @unless($isGuest)
        <button
            type="button"
            wire:click="addToCart"
            id="btn-masuk-keranjang{{ $idSuffix }}"
            title="Masukkan ke Keranjang"
            aria-label="Masukkan ke Keranjang"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-75 cursor-wait"
            @class([
                'w-14 shrink-0 rounded-xl shadow-sm transition-all duration-150',
                'flex items-center justify-center',
                'btn-tendaku-secondary hover:border-avocado-500 hover:text-avocado-700 cursor-pointer' => $canSubmit,
                'bg-wheat-200 text-darkbrown-400 cursor-not-allowed opacity-60' => ! $canSubmit,
            ])
            @disabled(! $canSubmit)
        >
            <span wire:loading.remove wire:target="addToCart" class="flex items-center gap-1.5">
                <i data-lucide="shopping-cart" class="w-4 h-4 text-avocado-700"></i>
            </span>

            <span wire:loading wire:target="addToCart" class="flex items-center gap-1.5">
                <i data-lucide="loader" class="w-4 h-4 animate-spin"></i>
                <span>Menyimpan...</span>
            </span>
        </button>
    @endunless
</div>
