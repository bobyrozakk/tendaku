                <!-- RINCIAN PESANAN -->
                <section class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-5 sm:px-6 py-4 bg-wheat-100 border-b border-wheat-200">
                        <h3 class="font-extrabold text-darkbrown-900 font-heading">Rincian pesanan</h3>
                        <span class="text-sm text-darkbrown-600">{{ $rental->details->count() }} unit alat</span>
                    </div>

                    <ul class="divide-y divide-wheat-200">
                        @foreach($rental->details as $detail)
                            @php
                                $mItem = $detail->masterItem;
                                $unit  = $detail->itemUnit;
                            @endphp
                            <li class="px-5 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-wheat-100 border border-wheat-200 flex items-center justify-center shrink-0 text-avocado-700">
                                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 20L10 4 1 20h18z" /><path d="m14 20-4-8-4 8" /></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="font-bold text-darkbrown-900 text-sm">{{ $mItem?->name ?? 'Peralatan camping' }}</h4>
                                            @if($unit)
                                                <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-wheat-100 text-darkbrown-700 border border-wheat-300">{{ $unit->unit_code }}</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-sunglow-100 text-darkbrown-800 border border-sunglow-300">Unit dipilih saat serah terima</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-darkbrown-500 mt-0.5">
                                            {{ $rupiah($detail->daily_rate_snapshot) }}/hari × {{ $rental->rental_days }} hari
                                            @if($detail->condition_before)
                                                · Kondisi awal: <strong class="text-darkbrown-700">{{ $detail->condition_before }}</strong>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <span class="font-extrabold text-darkbrown-900 sm:text-right shrink-0">{{ $rupiah($detail->daily_rate_snapshot * $rental->rental_days) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="px-5 sm:px-6 py-5 bg-wheat-50/70 border-t border-wheat-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-darkbrown-700">Rincian biaya</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                  :class="current === 'payment' ? 'bg-sunglow-100 text-darkbrown-900 border-sunglow-300' : (current ? 'bg-avocado-100 text-avocado-800 border-avocado-300' : 'bg-red-50 text-red-800 border-red-200')"
                                  x-text="current === 'payment' ? 'Menunggu bayar' : (current ? 'Pembayaran diterima' : statusLabels[status])"></span>
                        </div>

                        <div class="space-y-2 text-sm text-darkbrown-600">
                            <div class="flex justify-between"><span>Subtotal sewa</span><span class="font-bold text-darkbrown-800">{{ $rupiah($rental->subtotal) }}</span></div>
                            @php
                                $adminFee = (int) $rental->adminFee();
                            @endphp
                            @if($adminFee > 0)
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span>Biaya layanan & admin</span>
                                        <span class="block text-[11px] text-darkbrown-400">Pemeliharaan platform & jaminan transaksi</span>
                                    </div>
                                    <span class="font-bold text-darkbrown-800">{{ $rupiah($adminFee) }}</span>
                                </div>
                            @endif
                            @if($rental->delivery_fee > 0)
                                <div class="flex justify-between"><span>Biaya pengantaran</span><span class="font-bold text-darkbrown-800">{{ $rupiah($rental->delivery_fee) }}</span></div>
                            @endif
                            @if($rental->discount_amount > 0)
                                <div class="flex justify-between text-avocado-700 font-semibold"><span>Diskon</span><span>- {{ $rupiah($rental->discount_amount) }}</span></div>
                            @endif
                            @if($hasDenda)
                                <div class="pt-2 border-t border-red-200 text-red-700 space-y-1">
                                    @if($rental->total_late_fee > 0)
                                        <div class="flex justify-between"><span>Denda keterlambatan</span><span class="font-bold">+ {{ $rupiah($rental->total_late_fee) }}</span></div>
                                    @endif
                                    @if($rental->total_damage_fee > 0)
                                        <div class="flex justify-between"><span>Denda kerusakan unit</span><span class="font-bold">+ {{ $rupiah($rental->total_damage_fee) }}</span></div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="pt-3 border-t-2 border-wheat-200 flex items-baseline justify-between">
                            <span class="text-sm font-bold text-darkbrown-700">Total akhir</span>
                            <span class="text-2xl font-black tracking-tight font-heading {{ $hasDenda ? 'text-red-700' : 'text-darkbrown-900' }}">{{ $rupiah($totalFinal) }}</span>
                        </div>
                    </div>
                </section>
