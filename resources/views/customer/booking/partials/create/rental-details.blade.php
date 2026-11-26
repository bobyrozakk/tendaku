<!-- 1. JADWAL & PERALATAN -->
                <section id="section-rental" class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm">
                    @include('customer.booking.partials.create.section-heading', [
                        'step' => 1,
                        'title' => 'Jadwal & peralatan',
                        'subtitle' => 'Cek tanggal dan jumlah unit. Ketersediaan diperiksa otomatis.',
                    ])

                    <!-- Vendor -->
                    <div class="px-5 sm:px-6 pt-4">
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-wheat-50 border border-wheat-200 px-3.5 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-10 h-10 rounded-xl bg-avocado-800 text-wheat-100 flex items-center justify-center shrink-0">
                                    <i data-lucide="store" class="w-5 h-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="text-xs text-darkbrown-500">Disiapkan oleh</div>
                                    <div class="font-bold text-darkbrown-900 leading-snug truncate">{{ $booking['vendor']['name'] }}</div>
                                    <div class="text-xs text-darkbrown-600 truncate">{{ $booking['vendor']['city'] }}</div>
                                </div>
                            </div>
                            @if (filled($booking['vendor']['phone'] ?? null))
                            <a href="tel:{{ $booking['vendor']['phone'] }}" aria-label="Hubungi vendor {{ $booking['vendor']['phone'] }}"
                               class="inline-flex items-center justify-center gap-2 h-10 px-3 rounded-xl text-sm font-bold text-darkbrown-800 bg-white border border-wheat-300 hover:bg-wheat-100 transition shrink-0">
                                <i data-lucide="phone" class="w-4 h-4 text-avocado-600"></i>
                                <span class="hidden sm:inline">{{ $booking['vendor']['phone'] }}</span>
                                <span class="sm:hidden">Hubungi</span>
                            </a>
                            @endif
                        </div>
                    </div>

                    <!-- Jadwal sewa (Floating Dropdown Popover) -->
                    <div class="p-5 sm:p-6" @click.outside="calOpen = null; timeOpen = false">
                        <h3 class="font-bold text-darkbrown-900 mb-3">Jadwal sewa</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto_1fr] items-stretch gap-3">

                            <!-- Kolom Tanggal Ambil -->
                            <div class="relative rounded-xl bg-avocado-50 border border-avocado-200 p-4">
                                <label class="text-sm font-semibold text-avocado-800">Tanggal ambil</label>

                                <!-- Tombol tanggal -->
                                <button type="button" @click="openCalendar('pickup')"
                                        class="mt-1.5 w-full flex items-center justify-between rounded-lg border border-avocado-300 bg-white px-3 py-2 text-sm font-semibold text-darkbrown-900 hover:border-avocado-500 focus:outline-none focus:ring-2 focus:ring-avocado-500/30 transition text-left shadow-2xs">
                                    <span x-text="pickupDate ? fmtShort(pickupDate) : 'Pilih tanggal'"></span>
                                    <i data-lucide="calendar" class="w-4 h-4 text-avocado-700"></i>
                                </button>

                                <!-- Hari + jam ambil (klik jam untuk buka timepicker) -->
                                <div class="mt-1.5 flex items-center gap-1.5 text-sm text-darkbrown-600 flex-wrap">
                                    <span x-text="fmtLong(pickupDate)"></span>
                                    <span>·</span>
                                    <button type="button" @click.stop="toggleTime()"
                                            class="inline-flex items-center gap-1 font-semibold text-avocado-800 hover:text-avocado-900 bg-avocado-100 hover:bg-avocado-200 border border-avocado-200 rounded-md px-2 py-0.5 transition">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        <span x-text="pickupTime"></span>
                                    </button>
                                </div>

                                <!-- Dropdown Kalender (Ambil) -->
                                <div x-show="calOpen === 'pickup'" x-cloak x-transition.opacity.duration.150ms
                                     class="absolute top-full left-0 mt-2 z-40 w-[20rem] max-w-[calc(100vw-2.5rem)] p-4 rounded-2xl bg-white border border-wheat-200 shadow-tendaku-lg">
                                    <div class="flex items-center justify-between pb-2.5 border-b border-wheat-200 mb-2.5">
                                        <button type="button" @click="prevMonth()" aria-label="Bulan sebelumnya" class="w-9 h-9 rounded-lg hover:bg-wheat-100 flex items-center justify-center font-bold text-darkbrown-700">‹</button>
                                        <span class="text-sm font-bold text-darkbrown-900" x-text="monthNames[calMonth] + ' ' + calYear"></span>
                                        <button type="button" @click="nextMonth()" aria-label="Bulan berikutnya" class="w-9 h-9 rounded-lg hover:bg-wheat-100 flex items-center justify-center font-bold text-darkbrown-700">›</button>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-bold text-darkbrown-500 mb-1.5">
                                        <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center text-xs">
                                        <template x-for="(cell, idx) in calendarDays" :key="idx">
                                            <div>
                                                <template x-if="cell.empty"><span class="block h-9"></span></template>
                                                <template x-if="!cell.empty">
                                                    <button type="button" :disabled="cell.disabled" @click="selectCalDate(cell.iso)"
                                                            :class="[
                                                                cell.unavailable ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                                                (cell.selected ? 'bg-avocado-700 text-white font-bold shadow-xs' :
                                                                (cell.inRange ? 'bg-avocado-100 text-avocado-900 font-semibold' :
                                                                (cell.disabled ? 'text-wheat-400 opacity-40 cursor-not-allowed' : 'text-darkbrown-800 hover:bg-wheat-100 font-medium')))
                                                                , (cell.outside && (!cell.selected || cell.unavailable)) ? 'opacity-50' : ''
                                                            ]"
                                                            class="w-full h-9 rounded-lg inline-flex items-center justify-center transition text-sm"
                                                            x-text="cell.day">
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Timepicker wheel 24H (07.00 - 17.00) -->
                                <div x-show="timeOpen" x-cloak x-transition.opacity.duration.150ms
                                     class="absolute top-full left-0 mt-2 z-50 rounded-2xl bg-white border border-wheat-200 shadow-tendaku-lg overflow-hidden"
                                     style="width: 13rem;">
                                    <div class="px-4 pt-3 pb-1">
                                        <p class="text-xs font-bold text-darkbrown-700">Jam ambil</p>
                                        <p class="text-[10px] text-darkbrown-400 mt-0.5">07.00 – 17.00 · jam kembali mengikuti</p>
                                    </div>

                                    <div class="relative" style="height: 200px;">
                                        <!-- Garis penanda baris terpilih -->
                                        <div class="absolute inset-x-3 pointer-events-none border-y border-wheat-300"
                                             style="top: 80px; height: 40px;"></div>

                                        <div class="flex h-full">
                                            <!-- Kolom Jam -->
                                            <div class="flex-1 h-full overflow-y-auto overscroll-contain snap-y snap-mandatory no-scrollbar"
                                                 x-ref="hourCol"
                                                 style="padding: 80px 0; scrollbar-width: none; -ms-overflow-style: none; -webkit-mask-image: linear-gradient(to bottom, transparent, #000 32%, #000 68%, transparent); mask-image: linear-gradient(to bottom, transparent, #000 32%, #000 68%, transparent);"
                                                 @scroll.passive="onHourScroll()">
                                                <template x-for="h in timeHours" :key="h">
                                                    <div @click="pickHour(h)"
                                                         :class="timeHour === h ? 'text-2xl font-semibold text-darkbrown-900' : 'text-lg text-darkbrown-300'"
                                                         class="snap-center flex items-center justify-center cursor-pointer select-none transition-all"
                                                         style="height: 40px;"
                                                         x-text="String(h).padStart(2,'0')"></div>
                                                </template>
                                            </div>

                                            <!-- Kolom Menit (langkah 15) -->
                                            <div class="flex-1 h-full overflow-y-auto overscroll-contain snap-y snap-mandatory no-scrollbar"
                                                 x-ref="minCol"
                                                 style="padding: 80px 0; scrollbar-width: none; -ms-overflow-style: none; -webkit-mask-image: linear-gradient(to bottom, transparent, #000 32%, #000 68%, transparent); mask-image: linear-gradient(to bottom, transparent, #000 32%, #000 68%, transparent);"
                                                 @scroll.passive="onMinScroll()">
                                                <template x-for="m in timeMinutes" :key="m">
                                                    <div @click="pickMinute(m)"
                                                         :class="timeMinute === m ? 'text-2xl font-semibold text-darkbrown-900' : (timeHour === 17 && m !== 0 ? 'text-lg text-darkbrown-200' : 'text-lg text-darkbrown-300')"
                                                         class="snap-center flex items-center justify-center cursor-pointer select-none transition-all"
                                                         style="height: 40px;"
                                                         x-text="String(m).padStart(2,'0')"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-3 py-2 border-t border-wheat-100 flex justify-end">
                                        <button type="button" @click="timeOpen = false"
                                                class="text-xs font-bold text-avocado-700 hover:text-avocado-900 px-3 py-1 rounded-lg hover:bg-avocado-50 transition">
                                            Selesai
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Pemisah: durasi sewa -->
                            <div class="flex sm:flex-col items-center justify-center gap-2" aria-live="polite">
                                <span class="hidden sm:block w-px flex-1 bg-wheat-300"></span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-darkbrown-900 text-wheat-100 px-3 py-1 text-xs font-bold whitespace-nowrap">
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 rotate-90 sm:rotate-0"></i>
                                    <span x-text="rentalDays + ' hari'"></span>
                                </span>
                                <span class="hidden sm:block w-px flex-1 bg-wheat-300"></span>
                            </div>

                            <!-- Kolom Tanggal Kembali -->
                            <div class="relative rounded-xl bg-sunglow-100/70 border border-sunglow-300/80 p-4">
                                <label class="text-sm font-semibold text-goldenrod-800">Tanggal kembali</label>

                                <button type="button" @click="openCalendar('return')"
                                        class="mt-1.5 w-full flex items-center justify-between rounded-lg border border-sunglow-300 bg-white px-3 py-2 text-sm font-semibold text-darkbrown-900 hover:border-goldenrod-500 focus:outline-none focus:ring-2 focus:ring-goldenrod-400/30 transition text-left shadow-2xs">
                                    <span x-text="returnDate ? fmtShort(returnDate) : 'Pilih tanggal'"></span>
                                    <i data-lucide="calendar" class="w-4 h-4 text-goldenrod-700"></i>
                                </button>

                                <!-- Jam kembali = jam ambil, fixed -->
                                <div class="mt-1.5 flex items-center gap-1.5 text-sm text-darkbrown-600 flex-wrap">
                                    <span x-text="fmtLong(returnDate)"></span>
                                    <span>·</span>
                                    <span class="text-darkbrown-500">paling lambat <strong x-text="pickupTime"></strong></span>
                                </div>

                                <!-- Dropdown Kalender (Kembali) -->
                                <div x-show="calOpen === 'return'" x-cloak x-transition.opacity.duration.150ms
                                     class="absolute top-full right-0 sm:left-auto mt-2 z-40 w-[20rem] max-w-[calc(100vw-2.5rem)] p-4 rounded-2xl bg-white border border-wheat-200 shadow-tendaku-lg">
                                    <div class="flex items-center justify-between pb-2.5 border-b border-wheat-200 mb-2.5">
                                        <button type="button" @click="prevMonth()" aria-label="Bulan sebelumnya" class="w-9 h-9 rounded-lg hover:bg-wheat-100 flex items-center justify-center font-bold text-darkbrown-700">‹</button>
                                        <span class="text-sm font-bold text-darkbrown-900" x-text="monthNames[calMonth] + ' ' + calYear"></span>
                                        <button type="button" @click="nextMonth()" aria-label="Bulan berikutnya" class="w-9 h-9 rounded-lg hover:bg-wheat-100 flex items-center justify-center font-bold text-darkbrown-700">›</button>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-bold text-darkbrown-500 mb-1.5">
                                        <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center text-xs">
                                        <template x-for="(cell, idx) in calendarDays" :key="idx">
                                            <div>
                                                <template x-if="cell.empty"><span class="block h-9"></span></template>
                                                <template x-if="!cell.empty">
                                                    <button type="button" :disabled="cell.disabled" @click="selectCalDate(cell.iso)"
                                                            :class="[
                                                                cell.unavailable ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                                                (cell.selected ? 'bg-goldenrod-600 text-white font-bold shadow-xs' :
                                                                (cell.inRange ? 'bg-sunglow-100 text-darkbrown-900 font-semibold' :
                                                                (cell.disabled ? 'text-wheat-400 opacity-40 cursor-not-allowed' : 'text-darkbrown-800 hover:bg-wheat-100 font-medium')))
                                                                , (cell.outside && (!cell.selected || cell.unavailable)) ? 'opacity-50' : ''
                                                            ]"
                                                            class="w-full h-9 rounded-lg inline-flex items-center justify-center transition text-sm"
                                                            x-text="cell.day">
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <p x-show="errors.dates" x-text="errors.dates" x-cloak class="mt-2 text-sm text-red-700"></p>
                        @if(in_array(null, array_column($booking['items'], 'stock'), true))
                            <p x-show="shippingState !== 'ready'" class="mt-2 text-sm text-darkbrown-600">Ketersediaan belum dapat diperiksa. Pilih jadwal; stok akan diperiksa kembali sebelum booking diproses.</p>
                        @endif
                    </div>

                    <!-- Peralatan -->
                    <div class="p-5 sm:p-6 border-t border-wheat-200">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h3 class="font-bold text-darkbrown-900">Peralatan yang disewa</h3>
                            <span class="text-xs text-darkbrown-500"><span x-text="totalUnits"></span> unit</span>
                        </div>

                        <ul class="divide-y divide-wheat-200">
                            <template x-for="(item, i) in items" :key="item.id">
                                <li class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                                    <div class="min-w-0 space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="font-bold text-darkbrown-900 leading-snug" x-text="item.name"></h4>
                                            <span x-show="item.category" class="rounded-md bg-wheat-100 border border-wheat-200 px-2 py-0.5 text-xs font-semibold text-darkbrown-700" x-text="item.category"></span>
                                        </div>
                                        <p x-show="item.specs" class="text-sm text-darkbrown-500 line-clamp-2" x-text="item.specs"></p>
                                        <p class="text-sm text-darkbrown-700">
                                            <span class="font-semibold" x-text="formatRupiah(item.price_per_day)"></span>
                                            <span class="text-darkbrown-500">/ unit / hari</span>
                                        </p>
                                    </div>

                                    <div class="flex sm:flex-col items-center sm:items-end justify-between gap-2 shrink-0">
                                        <div class="inline-flex items-center rounded-xl border border-wheat-300 bg-white overflow-hidden">
                                            <button type="button" @click="changeQty(i, -1)" :disabled="item.qty <= 1" aria-label="Kurangi unit"
                                                    class="w-10 h-10 flex items-center justify-center text-darkbrown-800 hover:bg-wheat-100 active:bg-wheat-200 disabled:opacity-30 disabled:cursor-not-allowed transition">
                                                <i data-lucide="minus" class="h-4 w-4"></i>
                                            </button>
                                            <input type="number" min="1" :max="item.max_qty" x-model.number="item.qty" @change="clampQty(i)" aria-label="Jumlah unit"
                                                   class="w-10 h-10 border-0 p-0 text-center text-sm font-black text-darkbrown-900 focus:ring-0">
                                            <button type="button" @click="changeQty(i, 1)" :disabled="item.qty >= item.max_qty" aria-label="Tambah unit"
                                                    class="w-10 h-10 flex items-center justify-center text-darkbrown-800 hover:bg-wheat-100 active:bg-wheat-200 disabled:opacity-30 disabled:cursor-not-allowed transition">
                                                <i data-lucide="plus" class="h-4 w-4"></i>
                                            </button>
                                        </div>

                                        <div class="text-right">
                                            <div class="text-sm font-extrabold text-darkbrown-900" x-text="formatRupiah((Number(item.qty) || 0) * item.price_per_day * rentalDays)"></div>
                                            <div x-show="item.max_qty < 99" x-cloak class="text-[11px] text-darkbrown-500" x-text="'Maks. ' + item.max_qty + ' unit'"></div>
                                        </div>
                                    </div>
                                </li>
                            </template>
                        </ul>
                        <p x-show="errors.items" x-text="errors.items" x-cloak class="mt-2 text-sm text-red-700"></p>
                    </div>
                </section>
