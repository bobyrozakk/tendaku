{{-- Bar CTA tetap di bawah layar untuk mobile/tablet. Muncul hanya saat fase form. --}}
<div x-show="phase === 'form'" x-cloak :inert="isProcessing"
     class="co-mobile-bar lg:hidden fixed inset-x-0 bottom-0 z-30 bg-white/95 backdrop-blur border-t border-wheat-200 shadow-[0_-4px_16px_rgba(43,34,2,0.08)]">
    <div class="mx-auto max-w-3xl px-4 pt-3 flex items-center gap-3">
        <button type="button" @click="document.getElementById('checkout-summary')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                class="min-w-0 text-left" aria-label="Lihat rincian pembayaran">
            <span class="block text-xs text-darkbrown-500" x-text="paymentOption === 'dp' ? 'Bayar DP sekarang' : 'Total bayar'"></span>
            <span class="block text-lg font-extrabold text-darkbrown-900 leading-tight truncate" x-text="formatRupiah(payableAmount)"></span>
            <span class="block text-xs font-semibold text-avocado-700 underline underline-offset-2">Lihat rincian</span>
        </button>
        <button type="button" x-on:click="submitBooking()" :disabled="!canSubmitBooking"
                :class="!canSubmitBooking ? 'opacity-70 cursor-not-allowed' : 'hover:bg-sunglow-400 active:bg-goldenrod-400'"
                class="ml-auto shrink-0 inline-flex items-center justify-center gap-2 h-12 px-6 rounded-xl font-extrabold text-base text-darkbrown-900 bg-sunglow-300 shadow-tendaku transition focus:outline-none focus:ring-2 focus:ring-goldenrod-500 focus:ring-offset-2">
            <i x-show="isProcessing" x-cloak data-lucide="loader" class="w-5 h-5 animate-spin"></i>
            <span x-text="isProcessing ? 'Memproses...' : 'Buat booking'"></span>
        </button>
    </div>
</div>
