            @if($hasList)
            <!-- ============ DAFTAR BOOKING (riwayat) ============ -->
            <aside class="status-list" x-data="{ listOpen: false }">
                @if($hasRental)
                    <button type="button" class="bk-toggle w-full items-center justify-between gap-3 rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm px-4 py-3 text-left"
                            @click="listOpen = !listOpen" :aria-expanded="listOpen.toString()">
                        <span>
                            <span class="block text-xs text-darkbrown-500">Booking dipilih</span>
                            <span class="font-mono text-sm font-extrabold text-darkbrown-900">#{{ $rental->booking_code }}</span>
                        </span>
                        <span class="text-sm font-bold text-avocado-700" x-text="listOpen ? 'Tutup daftar' : 'Ganti booking'">Ganti booking</span>
                    </button>
                @endif

                <div class="bk-body {{ $hasRental ? '' : 'is-open' }} space-y-3" :class="{ 'is-open': listOpen }">

                    <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm p-4 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="font-extrabold text-darkbrown-900 font-heading">Riwayat sewa</h2>
                            <span class="text-xs text-darkbrown-500">{{ method_exists($rentals, 'total') ? $rentals->total() : $rentals->count() }} transaksi</span>
                        </div>
                        <div>
                            <label for="bk-filter" class="text-xs font-semibold text-darkbrown-600">Filter status</label>
                            <select id="bk-filter" onchange="window.location.href = this.value"
                                    class="mt-1 w-full rounded-xl border border-wheat-300 bg-white px-3 py-2 text-sm font-semibold text-darkbrown-900 focus:border-avocado-600 focus:ring-2 focus:ring-avocado-500/30">
                                @foreach($statusTabs as $tabKey => $tabLabel)
                                    <option value="{{ route('customer.booking.index', $tabKey ? ['status' => $tabKey] : []) }}" {{ $filterStatus === $tabKey ? 'selected' : '' }}>{{ $tabLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @forelse($rentals as $r)
                        @php
                            $isSel = $hasRental && $r->booking_code === $rental->booking_code;
                        @endphp
                        <a href="{{ route('booking.status', $r->booking_code) }}{{ $filterStatus ? '?status=' . $filterStatus : '' }}"
                           @if($isSel) aria-current="page" @endif
                           class="block rounded-2xl border p-4 space-y-2.5 transition {{ $isSel ? 'bg-avocado-50 border-avocado-600 ring-2 ring-avocado-600/20' : 'bg-white border-wheat-200 hover:border-wheat-400 shadow-tendaku-sm' }}">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-sm font-extrabold text-darkbrown-900">#{{ $r->booking_code }}</span>
                                <x-rental-status-badge :status="$r->status" :label="$r->displayStatusLabel()" />
                            </div>
                            <div class="text-xs text-darkbrown-500 font-medium">
                                Dipesan pada {{ $r->created_at->translatedFormat('d M Y, H:i') }} WIB
                            </div>
                            <div class="text-xs font-bold text-darkbrown-900">
                                {{ $r->vendor?->name }}
                                <span class="text-wheat-500">•</span>
                                <span class="text-darkbrown-600 font-medium">{{ $r->vendor?->city }}</span>
                            </div>
                            <div class="text-xs text-darkbrown-700 space-y-1">
                                <p class="font-medium">
                                    Periode: <strong>{{ \Carbon\Carbon::parse($r->pickup_date)->translatedFormat('d M Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($r->return_date)->translatedFormat('d M Y') }}</strong> ({{ $r->rental_days }} hari)
                                </p>
                                <p class="text-darkbrown-600">
                                    Barang: {{ $r->details->count() }} unit alat
                                    @if($r->details->first()?->masterItem)
                                        ({{ $r->details->first()->masterItem->name }}{{ $r->details->count() > 1 ? ', dll' : '' }})
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-end justify-between gap-3 pt-2 border-t border-wheat-100">
                                <div>
                                    <span class="text-[11px] text-darkbrown-500 block">Total Tagihan</span>
                                    <span class="text-base font-extrabold text-darkbrown-900 font-heading">Rp {{ number_format($r->final_total ?: $r->grand_total, 0, ',', '.') }}</span>
                                </div>
                                <span class="text-xs font-bold {{ $isSel ? 'text-avocado-800' : 'text-avocado-700' }}">{{ $isSel ? 'Sedang dilihat' : 'Lihat status →' }}</span>
                            </div>
                        </a>
                    @empty
                        <p class="rounded-2xl bg-white border border-wheat-200 p-4 text-sm text-darkbrown-600">Tidak ada transaksi untuk status ini.</p>
                    @endforelse

                    <div>
                        {{ $rentals->links() }}
                    </div>
                </div>
            </aside>
            @endif
