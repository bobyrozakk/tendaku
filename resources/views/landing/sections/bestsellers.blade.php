<section class="py-12 sm:py-20 lg:py-24 w-full bg-[#FDFBF7]" id="shop" aria-labelledby="shop-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6 sm:mb-10" data-sr>
            <div>
                <h2 id="shop-heading" class="font-display text-3xl sm:text-4xl text-darkbrown-900 mt-1">Peralatan Terpopuler</h2>
            </div>
            <a href="{{ route('customer.catalog.index') }}"
               class="text-sm font-semibold text-darkbrown-700 hover:text-avocado-700 inline-flex items-center gap-1 min-h-[44px] transition-colors shrink-0">
                Lihat Semua Produk
                <i data-lucide="arrow-right" class="w-4 h-4" aria-hidden="true"></i>
            </a>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
            <form
                x-ref="dateForm"
                method="GET"
                action="{{ route('home') }}#shop"
                x-data="bestsellerDateRange({
                    initialStart: @js(request('start_date', $dateRange['start_date'] ?? '')),
                    initialEnd: @js(request('end_date', $dateRange['end_date'] ?? '')),
                    minDate: @js(now()->toDateString()),
                })"
                @click.outside="closeCalendar()"
                @keydown.escape.window="closeCalendar()"
                class="relative w-full sm:w-80"
            >
                <input type="hidden" name="start_date" :value="pickupDate">
                <input type="hidden" name="end_date" :value="returnDate">

                <button type="button" @click="openCalendar()" :aria-expanded="calOpen ? 'true' : 'false'" aria-haspopup="dialog"
                        class="w-full flex items-center justify-between gap-3 rounded-xl border border-wheat-300 bg-white px-4 py-3 text-left text-sm font-bold text-darkbrown-900 shadow-2xs hover:border-avocado-600 focus:outline-none focus:ring-2 focus:ring-avocado-500/30 transition">
                    <span class="flex items-center gap-3 min-w-0">
                        <i data-lucide="calendar" class="w-5 h-5 text-avocado-800 shrink-0" aria-hidden="true"></i>
                        <span class="truncate" x-text="fmtRange()"></span>
                    </span>
                    <span class="inline-flex shrink-0 transition-transform" :class="calOpen ? 'rotate-180' : ''"><i data-lucide="chevron-down" class="w-5 h-5 text-darkbrown-600" aria-hidden="true"></i></span>
                </button>

                <x-date-range-calendar />
            </form>
            <x-stock-period :date-range="$dateRange" id="bestsellers-stock-period" />
        </div>

        <div id="bestsellers-grid" class="transition-opacity duration-200">
            @include('landing.sections.partials.bestsellers-grid')
        </div>
    </div>
</section>
