@include('customer.booking.partials.create.rental-schedule')

                <!-- 2. PERALATAN YANG DISEWA -->
                <section id="section-equipment" class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm scroll-mt-24">
                    @include('customer.booking.partials.create.section-heading', [
                        'step' => 2,
                        'title' => 'Peralatan yang Disewa',
                        'subtitle' => 'Pastikan perlengkapan yang Anda butuhkan.',
                    ])

                    <div class="p-5 sm:p-6">
                        <div class="rounded-xl border border-wheat-200 overflow-hidden">
                            <div class="hidden sm:grid grid-cols-[minmax(0,1fr)_9rem_8rem_8rem] gap-4 bg-wheat-50 border-b border-wheat-200 px-4 py-2.5 text-xs font-semibold text-darkbrown-600">
                                <span>Nama Peralatan</span>
                                <span class="text-right">Harga / Unit</span>
                                <span class="text-center">Jumlah</span>
                                <span class="text-right">Subtotal</span>
                            </div>

                            <ul class="divide-y divide-wheat-200">
                                <template x-for="(item, i) in items" :key="item.id">
                                    <li class="px-4 py-4 grid grid-cols-2 sm:grid-cols-[minmax(0,1fr)_9rem_8rem_8rem] items-center gap-x-4 gap-y-3">
                                        <div class="col-span-2 sm:col-span-1 flex items-center gap-3 min-w-0">
                                            <span class="w-14 h-14 rounded-xl bg-avocado-100 text-avocado-800 flex items-center justify-center shrink-0 overflow-hidden">
                                                <template x-if="item.image_url">
                                                    <img :src="item.image_url" :alt="item.name" loading="lazy" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!item.image_url">
                                                    <i data-lucide="tent" class="w-6 h-6"></i>
                                                </template>
                                            </span>
                                            <div class="min-w-0">
                                                <h4 class="font-bold text-darkbrown-900 leading-snug" x-text="item.name"></h4>
                                                <p x-show="item.category || item.specs" class="text-xs text-darkbrown-500 line-clamp-1" x-text="[item.category, item.specs].filter(Boolean).join(' · ')"></p>
                                            </div>
                                        </div>

                                        <div class="sm:text-right text-sm text-darkbrown-800">
                                            <span class="font-semibold" x-text="formatRupiah(item.price_per_day)"></span>
                                            <span class="text-darkbrown-500">/ unit / hari</span>
                                        </div>

                                        <div class="flex flex-col items-end sm:items-center">
                                            <div class="inline-flex items-center rounded-lg border border-wheat-300 bg-white overflow-hidden">
                                                <button type="button" @click="changeQty(i, -1)" :disabled="item.qty <= 1" aria-label="Kurangi unit"
                                                        class="w-9 h-9 flex items-center justify-center text-darkbrown-800 hover:bg-wheat-100 active:bg-wheat-200 disabled:opacity-30 disabled:cursor-not-allowed transition">
                                                    <i data-lucide="minus" class="h-4 w-4"></i>
                                                </button>
                                                <input type="number" min="1" :max="item.max_qty" x-model.number="item.qty" @change="clampQty(i)" aria-label="Jumlah unit"
                                                       class="w-9 h-9 border-0 p-0 text-center text-sm font-black text-darkbrown-900 focus:ring-0">
                                                <button type="button" @click="changeQty(i, 1)" :disabled="item.qty >= item.max_qty" aria-label="Tambah unit"
                                                        class="w-9 h-9 flex items-center justify-center text-darkbrown-800 hover:bg-wheat-100 active:bg-wheat-200 disabled:opacity-30 disabled:cursor-not-allowed transition">
                                                    <i data-lucide="plus" class="h-4 w-4"></i>
                                                </button>
                                            </div>
                                            <div x-show="item.max_qty < 99" x-cloak class="mt-1 text-[11px] text-darkbrown-500" x-text="'Maks. ' + item.max_qty + ' unit'"></div>
                                        </div>

                                        <div class="col-span-2 sm:col-span-1 flex items-center justify-between sm:justify-end gap-2 border-t border-wheat-100 pt-2 sm:border-0 sm:pt-0">
                                            <span class="sm:hidden text-xs text-darkbrown-500">Subtotal</span>
                                            <span class="text-sm font-extrabold text-darkbrown-900" x-text="formatRupiah((Number(item.qty) || 0) * item.price_per_day * rentalDays)"></span>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <p x-show="errors.items" x-text="errors.items" x-cloak class="mt-2 text-sm text-red-700"></p>
                    </div>
                </section>
