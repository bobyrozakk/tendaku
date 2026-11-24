{{--
    Tombol aksi sewa (dipakai di hero dan panel ringkasan).
    Variabel: $isGuest, $canSubmit, $idSuffix ('' untuk panel ringkasan, '-hero' untuk hero).
--}}
@php
    $checkoutAction = $canSubmit ? 'checkoutNow' : 'chooseSchedule';
@endphp

<div class="space-y-2">
    <button
        type="button"
        wire:click="{{ $checkoutAction }}"
        id="{{ $isGuest ? 'btn-checkout' : 'btn-sewa-sekarang' }}{{ $idSuffix }}"
        wire:loading.attr="disabled"
        wire:loading.class="opacity-75 cursor-wait"
        class="btn-tendaku-primary w-full py-3.5 px-6 rounded-xl font-extrabold text-sm shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 cursor-pointer transition-all duration-200"
    >
        <span wire:loading.remove wire:target="checkoutNow,chooseSchedule" class="flex items-center gap-2">
            @if($canSubmit)
                <i data-lucide="arrow-right-circle" class="w-4 h-4"></i>
                <span>Checkout Sekarang</span>
            @else
                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                <span>Pilih Jadwal Lain</span>
            @endif
        </span>

        <span wire:loading wire:target="checkoutNow,chooseSchedule" class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span>Menyiapkan checkout...</span>
        </span>
    </button>

    @unless($isGuest)
        <button
            type="button"
            wire:click="addToCart"
            id="btn-masuk-keranjang{{ $idSuffix }}"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-75 cursor-wait"
            @class([
                'w-full py-3 px-4 rounded-xl font-bold text-xs shadow-sm transition-all duration-150',
                'flex items-center justify-center gap-1.5',
                'btn-tendaku-secondary hover:border-avocado-500 hover:text-avocado-700 cursor-pointer' => $canSubmit,
                'bg-wheat-200 text-darkbrown-400 cursor-not-allowed opacity-60' => ! $canSubmit,
            ])
            @disabled(! $canSubmit)
        >
            <span wire:loading.remove wire:target="addToCart" class="flex items-center gap-1.5">
                <i data-lucide="shopping-cart" class="w-4 h-4 text-avocado-700"></i>
                <span>Masukkan ke Keranjang</span>
            </span>
            <span wire:loading wire:target="addToCart" class="flex items-center gap-1.5">
                <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span>Menyimpan...</span>
            </span>
        </button>
    @endunless
</div>
