        <!-- ============ TRIP BANNER ============ -->
        <div class="relative overflow-hidden rounded-3xl bg-avocado-900 text-white shadow-tendaku">

            <div class="relative p-5 sm:p-7 flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div class="space-y-4 min-w-0">
                    <div>
                        <span class="text-sm text-avocado-200">Status Pemesanan</span>
                        <div class="text-2xl sm:text-3xl font-extrabold tracking-tight">Pemesanan Baru</div>
                    </div>

                    <div class="flex flex-wrap gap-2 text-sm">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/15 px-3 py-1">
                            <i data-lucide="store" class="w-4 h-4 text-sunglow-300"></i>
                            {{ $booking['vendor']['name'] }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/15 px-3 py-1">
                            <i data-lucide="calendar" class="w-4 h-4 text-sunglow-300"></i>
                            <span x-text="fmtShort(pickupDate) + ' – ' + fmtShort(returnDate)">{{ $booking['pickup_date'] }} – {{ $booking['return_date'] }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
