<!-- 1. JADWAL & PERKIRAAN CUACA -->
                <section id="section-rental" class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm scroll-mt-24">
                    @include('customer.booking.partials.create.section-heading', [
                        'step' => 1,
                        'title' => 'Jadwal & Perkiraan Cuaca',
                        'subtitle' => 'Pilih tanggal sewa dan lihat prakiraan cuaca di lokasi.',
                    ])

                    <div class="px-5 sm:px-6 pt-4 pb-5 sm:pb-6" @click.outside="calOpen = null; timeOpen = false" @keydown.escape.window="calOpen = null; timeOpen = false">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-start md:gap-x-4 md:gap-y-4">

                            <div class="relative md:col-span-6">
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
                                                class="inline-flex min-h-[44px] sm:min-h-0 items-center gap-1.5 text-xs font-semibold text-avocado-900 bg-avocado-100 hover:bg-avocado-200 border border-avocado-200 rounded-full px-3 py-1 transition">
                                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                            Jam ambil
                                            <span class="font-extrabold" x-text="pickupTime"></span>
                                        </button>

                                        <!-- Timepicker wheel 24H (07.00 - 17.00) -->
                                        <div x-show="timeOpen" x-cloak x-transition.opacity.duration.150ms
                                             class="absolute top-full left-1/2 -translate-x-1/2 sm:left-0 sm:translate-x-0 mt-2 z-50 max-w-[calc(100vw-2rem)] rounded-2xl bg-white border border-wheat-200 shadow-tendaku-lg overflow-hidden"
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
                                                        class="inline-flex min-h-[44px] sm:min-h-0 items-center text-xs font-bold text-avocado-700 hover:text-avocado-900 px-3 py-1 rounded-lg hover:bg-avocado-50 transition">
                                                    Selesai
                                                </button>
                                            </div>
                                        </div>
                                    </span>

                                    <span class="text-xs text-darkbrown-500">Kembali paling lambat <strong class="text-darkbrown-800" x-text="pickupTime"></strong></span>
                                </div>

                                <x-date-range-calendar />
                            </div>

                            @if (! $isGuest)
                                <livewire:customer.booking-weather
                                    :vendor-city="$booking['vendor']['city'] ?? ''"
                                    :pickup-date="$initialPickupDate ?? ''"
                                    :return-date="$initialReturnDate ?? ''"
                                    :compact="true"
                                    lazy="on-load"
                                    key="checkout-booking-weather"
                                />
                            @else
                                <div class="rounded-2xl border border-wheat-200 bg-gradient-to-r from-wheat-50/80 to-white p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                    <div class="flex items-start sm:items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-wheat-200/60 border border-wheat-300/80 text-darkbrown-600 flex items-center justify-center shrink-0">
                                            <x-icon name="cloud-sun" class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-sm font-extrabold text-darkbrown-900">Prakiraan Cuaca Masa Sewa</h3>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                    <x-icon name="lock" class="w-3 h-3" />
                                                    Khusus Member
                                                </span>
                                            </div>
                                            <p class="text-xs text-darkbrown-600 mt-1">
                                                Prakiraan cuaca harian dan peringatan cuaca ekstrem hanya dapat diakses oleh akun terdaftar.
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ route('login') }}" class="btn-tendaku-secondary min-h-[44px] sm:min-h-0 inline-flex items-center justify-center text-xs font-bold px-4 py-2 shrink-0">
                                        Masuk Akun
                                    </a>
                                </div>
                            @endif
                        </div>

                        <p x-show="errors.dates" x-text="errors.dates" x-cloak class="mt-2 text-sm text-red-700"></p>
                        @if(in_array(null, array_column($booking['items'], 'stock'), true))
                            <p x-show="shippingState !== 'ready'" class="mt-2 text-sm text-darkbrown-600">Ketersediaan belum dapat diperiksa. Pilih jadwal; stok akan diperiksa kembali sebelum booking diproses.</p>
                        @endif
                    </div>
                </section>
