<!-- 1. JADWAL & PERKIRAAN CUACA -->
                <section id="section-rental" class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm scroll-mt-24">
                    @include('customer.booking.partials.create.section-heading', [
                        'step' => 1,
                        'title' => $isLoggedIn ? 'Jadwal & Perkiraan Cuaca' : 'Jadwal Sewa',
                        'subtitle' => $isLoggedIn
                            ? 'Pilih tanggal sewa dan lihat prakiraan cuaca di lokasi.'
                            : 'Pilih tanggal ambil dan kembali. Ketersediaan diperiksa otomatis.',
                    ])

                    <div class="px-5 sm:px-6 pt-4 pb-5 sm:pb-6" @click.outside="calOpen = null; timeOpen = false">
                        <div @class(['grid grid-cols-1 gap-3', 'md:grid-cols-12 md:items-start md:gap-x-4 md:gap-y-4' => $isLoggedIn])>

                            <div @class(['relative', 'md:col-span-6' => $isLoggedIn, 'sm:max-w-md' => ! $isLoggedIn])>
                                <span class="sr-only">Tanggal sewa</span>

                                <button type="button" @click="openCalendar()" :aria-expanded="calOpen ? 'true' : 'false'" aria-haspopup="dialog"
                                        class="w-full flex items-center justify-between gap-3 rounded-xl border border-wheat-300 bg-white px-4 py-3 text-left text-sm font-bold text-darkbrown-900 shadow-2xs hover:border-avocado-600 focus:outline-none focus:ring-2 focus:ring-avocado-500/30 transition">
                                    <span class="flex items-center gap-3 min-w-0">
                                        <i data-lucide="calendar" class="w-5 h-5 text-avocado-800 shrink-0"></i>
                                        <span class="truncate" x-text="fmtRange()"></span>
                                    </span>
                                    <span class="inline-flex shrink-0 transition-transform" :class="calOpen ? 'rotate-180' : ''"><i data-lucide="chevron-down" class="w-5 h-5 text-darkbrown-600"></i></span>
                                </button>

                                <!-- Ringkas: durasi, jam ambil, jam kembali -->
                                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2" aria-live="polite">
                                    <span class="inline-flex items-center rounded-full bg-darkbrown-900 text-wheat-100 px-3 py-1 text-xs font-bold whitespace-nowrap"
                                          x-text="rentalDays + ' hari'"></span>

                                    <!-- Jam ambil (klik untuk buka timepicker) -->
                                    <span class="relative inline-flex">
                                        <button type="button" @click.stop="toggleTime()"
                                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-avocado-900 bg-avocado-100 hover:bg-avocado-200 border border-avocado-200 rounded-full px-3 py-1 transition">
                                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                            Jam ambil
                                            <span class="font-extrabold" x-text="pickupTime"></span>
                                        </button>

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
                                    </span>

                                    <span class="text-xs text-darkbrown-500">Kembali paling lambat <strong class="text-darkbrown-800" x-text="pickupTime"></strong></span>
                                </div>

                                <!-- Dropdown kalender rentang (Senin - Minggu) -->
                                <div x-show="calOpen" x-cloak x-transition.opacity.duration.150ms role="dialog" aria-label="Pilih rentang tanggal sewa"
                                     class="absolute top-full left-0 mt-2 z-40 w-[21rem] max-w-[calc(100vw-2.5rem)] p-4 rounded-2xl bg-white border border-wheat-200 shadow-tendaku-lg">
                                    <div class="flex items-center justify-between mb-1">
                                        <button type="button" @click="prevMonth()" aria-label="Bulan sebelumnya" class="w-9 h-9 rounded-full hover:bg-wheat-100 flex items-center justify-center text-darkbrown-800">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                        </button>
                                        <span class="text-sm font-extrabold text-darkbrown-900" x-text="calendarTitle"></span>
                                        <button type="button" @click="nextMonth()" aria-label="Bulan berikutnya" class="w-9 h-9 rounded-full hover:bg-wheat-100 flex items-center justify-center text-darkbrown-800">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                        </button>
                                    </div>
                                    <p class="text-center text-xs font-bold text-darkbrown-900 bg-sunglow-200 border border-sunglow-300 rounded-lg px-3 py-1.5 mb-3"
                                       x-text="calOpen === 'return' ? 'Pilih tanggal kembali' : 'Pilih tanggal ambil'"></p>
                                    <div class="grid grid-cols-7 text-center text-[11px] font-semibold text-darkbrown-500 mb-1.5">
                                        <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
                                    </div>
                                    <div class="grid grid-cols-7 gap-y-1 text-center">
                                        <template x-for="(cell, idx) in calendarDays" :key="idx">
                                            <div class="h-10 flex items-center justify-center"
                                                 :class="[cell.inRange ? 'bg-avocado-100' : '', cell.start ? 'rounded-l-full' : '', cell.end ? 'rounded-r-full' : '']">
                                                <template x-if="cell.empty"><span class="block h-9"></span></template>
                                                <template x-if="!cell.empty">
                                                    <button type="button" :disabled="cell.disabled" @click="selectCalDate(cell.iso)"
                                                            :class="[
                                                                (cell.start || cell.end) ? 'bg-avocado-900 text-white font-bold shadow-xs' :
                                                                (cell.unavailable ? 'bg-gray-100 text-gray-400 cursor-not-allowed' :
                                                                (cell.disabled ? 'text-wheat-400 opacity-40 cursor-not-allowed' :
                                                                (cell.inRange ? 'text-avocado-900 font-semibold' : 'text-darkbrown-800 hover:bg-wheat-100 font-medium')))
                                                                , (cell.outside && !cell.start && !cell.end) ? 'opacity-50' : ''
                                                            ]"
                                                            class="w-9 h-9 rounded-full inline-flex items-center justify-center transition text-sm"
                                                            x-text="cell.day">
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            @if ($isLoggedIn)
                                <livewire:customer.booking-weather
                                    :vendor-city="$booking['vendor']['city'] ?? ''"
                                    :pickup-date="$initialPickupDate ?? ''"
                                    :return-date="$initialReturnDate ?? ''"
                                    :compact="true"
                                    key="checkout-booking-weather"
                                />
                            @endif
                        </div>

                        <p x-show="errors.dates" x-text="errors.dates" x-cloak class="mt-2 text-sm text-red-700"></p>
                        @if(in_array(null, array_column($booking['items'], 'stock'), true))
                            <p x-show="shippingState !== 'ready'" class="mt-2 text-sm text-darkbrown-600">Ketersediaan belum dapat diperiksa. Pilih jadwal; stok akan diperiksa kembali sebelum booking diproses.</p>
                        @endif
                    </div>
                </section>
