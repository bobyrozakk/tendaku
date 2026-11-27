<x-layouts.customer :full-width="true">
    <x-slot name="title">Tendaku</x-slot>

    @push('styles')
        @vite('resources/css/pages/landing/landing.css')
    @endpush

    @include('landing.sections.hero')

    @include('landing.sections.categories')

    @include('landing.sections.bestsellers')

    @include('landing.sections.customer-features')

    @include('landing.sections.why-tendaku')

    @include('landing.sections.ktp-guarantee')

    @include('landing.sections.vendor-benefits')

    @include('landing.sections.how-to-rent')

    @include('landing.sections.final-cta')

    {{-- Floating Back-to-Top Button (kanan bawah) --}}
    <div x-data="{ showTop: false }"
         x-init="showTop = window.scrollY > 360"
         @scroll.window.passive="showTop = window.scrollY > 360"
         class="fixed bottom-5 right-5 sm:bottom-8 sm:right-8 z-40">
        <button type="button"
                id="back-to-top-btn"
                x-show="showTop"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-3 scale-95"
                @click="window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })"
                aria-label="Kembali ke atas"
                title="Kembali ke atas"
                class="flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-full bg-darkbrown-900/90 text-sunglow-300 border border-sunglow-300/35 shadow-xl backdrop-blur-md hover:bg-darkbrown-800 hover:text-sunglow-200 hover:-translate-y-0.5 active:translate-y-0 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sunglow-400 focus-visible:ring-offset-2">
            <i data-lucide="arrow-up" class="w-5 h-5" aria-hidden="true"></i>
        </button>
    </div>

    @push('scripts')
        @vite('resources/js/pages/landing/landing.js')
    @endpush

</x-layouts.customer>
