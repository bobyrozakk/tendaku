                <!-- 2. ISI PAKET -->
                <section id="section-equipment" class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm scroll-mt-24">
                    @include('customer.booking.partials.create.section-heading', [
                        'step' => 2,
                        'title' => 'Isi Paket',
                        'subtitle' => 'Perlengkapan yang sudah termasuk dalam paket ini.',
                    ])

                    <div class="p-5 sm:p-6">
                        <div class="rounded-xl border border-wheat-200 overflow-hidden">
                            <div class="hidden sm:grid grid-cols-[minmax(0,1fr)_9rem_8rem_8rem] gap-4 bg-wheat-50 border-b border-wheat-200 px-4 py-2.5 text-xs font-semibold text-darkbrown-600">
                                <span>Nama Paket</span>
                                <span class="text-right">Harga / Paket</span>
                                <span class="text-center">Jumlah</span>
                                <span class="text-right">Subtotal</span>
                            </div>

                            <div class="px-4 py-4 grid grid-cols-2 sm:grid-cols-[minmax(0,1fr)_9rem_8rem_8rem] items-center gap-x-4 gap-y-3">
                                <div class="col-span-2 sm:col-span-1 flex items-center gap-3 min-w-0">
                                    <span class="w-14 h-14 rounded-xl bg-avocado-100 text-avocado-800 flex items-center justify-center shrink-0 overflow-hidden">
                                        <img src="{{ $image }}" alt="{{ $package->name }}" loading="lazy" class="w-full h-full object-cover">
                                    </span>
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-darkbrown-900 leading-snug" x-text="packageName">{{ $package->name }}</h4>
                                        <p class="text-xs text-darkbrown-500 line-clamp-1">Paket Camping · {{ $vendorName }}</p>
                                    </div>
                                </div>

                                <div class="sm:text-right text-sm text-darkbrown-800">
                                    <span class="font-semibold" x-text="formatRupiah(packagePrice)"></span>
                                    <span class="text-darkbrown-500">/ paket / hari</span>
                                </div>

                                <div class="flex flex-col items-end sm:items-center">
                                    <span class="inline-flex items-center justify-center h-9 min-w-9 px-3 rounded-lg border border-wheat-300 bg-wheat-50 text-sm font-black text-darkbrown-900">1</span>
                                </div>

                                <div class="col-span-2 sm:col-span-1 flex items-center justify-between sm:justify-end gap-2 border-t border-wheat-100 pt-2 sm:border-0 sm:pt-0">
                                    <span class="sm:hidden text-xs text-darkbrown-500">Subtotal</span>
                                    <span class="text-sm font-extrabold text-darkbrown-900" x-text="formatRupiah(packagePrice * rentalDays)"></span>
                                </div>
                            </div>

                            <div class="border-t border-wheat-200 bg-wheat-50/60 px-4 py-3">
                                <p class="text-xs font-semibold text-darkbrown-600 mb-2">Termasuk di dalam paket</p>
                                <ul class="divide-y divide-wheat-200">
                                    <template x-for="item in packageItems" :key="item.id">
                                        <li class="py-2 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                                            <span class="flex items-center gap-2.5 min-w-0">
                                                <span class="w-8 h-8 rounded-lg bg-avocado-100 text-avocado-800 flex items-center justify-center shrink-0 overflow-hidden">
                                                    <template x-if="item.image_url">
                                                        <img :src="item.image_url" :alt="item.name" loading="lazy" class="w-full h-full object-cover">
                                                    </template>
                                                    <template x-if="!item.image_url">
                                                        <i data-lucide="tent" class="w-4 h-4"></i>
                                                    </template>
                                                </span>
                                                <span class="text-sm font-semibold text-darkbrown-900 truncate" x-text="item.name"></span>
                                            </span>
                                            <span class="text-sm font-bold text-darkbrown-700 shrink-0" x-text="item.qty + ' unit'"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>
