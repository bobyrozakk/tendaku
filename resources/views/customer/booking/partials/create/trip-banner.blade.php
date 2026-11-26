        <!-- ============ TRIP BANNER ============ -->
        <div class="relative overflow-hidden rounded-2xl bg-avocado-900 text-white shadow-tendaku">
            <div class="relative p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="min-w-0 space-y-3">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-avocado-200">Checkout</span>
                        <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight font-heading leading-tight">Lengkapi pemesanan</h2>
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
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/15 px-3 py-1">
                            <i data-lucide="moon" class="w-4 h-4 text-sunglow-300"></i>
                            <span x-text="rentalDays + ' hari'">{{ (int) $booking['rental_days'] }} hari</span>
                        </span>
                    </div>
                </div>

                {{-- Progres: orientasi user, sekarang di langkah 1 --}}
                <ol class="flex items-center gap-2 text-xs font-semibold shrink-0" aria-label="Tahapan sewa">
                    <li class="inline-flex items-center gap-1.5 rounded-full bg-sunglow-300 text-darkbrown-900 pl-1.5 pr-3 py-1" aria-current="step">
                        <span class="w-5 h-5 rounded-full bg-darkbrown-900 text-sunglow-300 flex items-center justify-center text-[11px] font-bold">1</span>
                        Booking
                    </li>
                    <span class="w-4 h-px bg-white/30" aria-hidden="true"></span>
                    <li class="inline-flex items-center gap-1.5 text-avocado-100 pl-1.5 pr-2 py-1">
                        <span class="w-5 h-5 rounded-full border border-white/30 flex items-center justify-center text-[11px]">2</span>
                        Bayar
                    </li>
                    <span class="w-4 h-px bg-white/30" aria-hidden="true"></span>
                    <li class="inline-flex items-center gap-1.5 text-avocado-100 pl-1.5 pr-2 py-1">
                        <span class="w-5 h-5 rounded-full border border-white/30 flex items-center justify-center text-[11px]">3</span>
                        Ambil alat
                    </li>
                </ol>
            </div>
        </div>
