{{-- Sidebar aktivitas dan informasi penyewa/vendor. Variabel berasal dari view induk. --}}
<aside class="status-right">
                <section class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm overflow-hidden">
                    <h3 class="px-5 py-4 font-extrabold text-darkbrown-900 font-heading border-b border-wheat-200">Aktivitas</h3>
                    <ul class="divide-y divide-wheat-200 max-h-[32rem] overflow-y-auto">
                        <template x-for="(a, i) in activities" :key="a.title + a.time + i">
                            <li class="flex items-start gap-3 px-5 py-3.5 border-l-4"
                                :class="[i === 0 ? 'border-avocado-500 bg-avocado-50/60' : 'border-transparent', a.fresh ? 'act-in' : '']">
                                <svg class="w-4 h-4 mt-1 shrink-0" :class="toneCls(a.tone)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" :d="iconPath[a.type]" /></svg>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-bold text-darkbrown-900 leading-snug" x-text="a.title"></div>
                                    <div x-show="a.sub" class="text-sm text-darkbrown-600" x-text="a.sub"></div>
                                    <div class="text-xs text-darkbrown-500 mt-0.5" x-text="a.time"></div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </section>

                <div class="space-y-5">

                <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm p-5 text-center">
                    <div class="mx-auto w-20 h-20 rounded-full bg-avocado-800 text-wheat-100 flex items-center justify-center text-3xl font-extrabold font-heading">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($renterName, 0, 1)) }}
                    </div>
                    <h2 class="mt-3 font-extrabold text-darkbrown-900 leading-tight">{{ $renterName }}</h2>
                    <p class="text-sm text-darkbrown-500">Penyewa</p>

                    @if($rental->vendor?->phone)
                        <a href="tel:{{ $rental->vendor->phone }}" class="mt-4 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-bold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 border border-goldenrod-400 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            Hubungi vendor
                        </a>
                    @endif
                </div>

                <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm p-5 space-y-4 text-sm">
                    @if($renterPhone)
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-darkbrown-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            <div class="min-w-0">
                                <div class="text-xs text-darkbrown-500">WhatsApp penyewa</div>
                                <div class="font-semibold text-darkbrown-900">{{ $renterPhone }}</div>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-darkbrown-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        <div class="min-w-0">
                            <div class="text-xs text-darkbrown-500">Vendor</div>
                            <div class="font-semibold text-darkbrown-900">{{ $rental->vendor?->name }}</div>
                            <div class="text-darkbrown-600">{{ $rental->vendor?->address }}{{ $rental->vendor?->city ? ', ' . $rental->vendor->city : '' }}</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-darkbrown-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v9H3zM14 10h4l3 3v3h-7z" /><circle cx="7" cy="17.5" r="1.8" /><circle cx="17" cy="17.5" r="1.8" /></svg>
                        <div>
                            <div class="text-xs text-darkbrown-500">Cara menerima alat</div>
                            <div class="font-semibold text-darkbrown-900">{{ $rental->pickup_method === 'delivery' ? 'Diantar kurir vendor' : 'Ambil di toko' }}</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-darkbrown-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div>
                            <div class="text-xs text-darkbrown-500">Durasi sewa</div>
                            <div class="font-semibold text-darkbrown-900">{{ $rental->rental_days }} hari</div>
                        </div>
                    </div>
                </div>
                </div>
                <div class="mt-5 rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm px-5 py-4 text-sm">
                    <div class="font-bold text-darkbrown-900">Butuh bantuan?</div>
                    <div class="text-darkbrown-500">Hubungi vendor untuk kendala bayar atau jadwal.</div>
                </div>
            </aside>
