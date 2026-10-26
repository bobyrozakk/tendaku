<div class="space-y-6" x-data="{ showHistoryModal: false, selectedHistory: null }">

    @if(session()->has('message'))
        <div class="p-4 rounded-2xl bg-avocado-50 border border-avocado-300 text-avocado-900 flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-avocado-600 text-white flex items-center justify-center font-bold">
                    ✓
                </div>
                <p class="text-xs font-bold">{{ session('message') }}</p>
            </div>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        {{-- Pill Navigation --}}
        <div class="inline-flex p-1.5 bg-white/80 backdrop-blur-md rounded-2xl border border-wheat-300/80 shadow-sm">
            <button wire:click="$set('activeTab', 'return')"
                class="flex items-center gap-2.5 py-2 px-5 rounded-xl text-xs font-bold transition-all duration-200 cursor-pointer {{ $activeTab === 'return' ? 'text-white shadow-md' : 'text-darkbrown-600 hover:text-darkbrown-900 hover:bg-wheat-100/70 font-semibold' }}"
                style="{{ $activeTab === 'return' ? 'background: linear-gradient(135deg, #4e6506 0%, #5c7608 60%, #6c8b08 100%); box-shadow: 0 4px 14px rgba(78,101,6,0.3);' : '' }}">
                <i data-lucide="clipboard-check" class="w-4 h-4 {{ $activeTab === 'return' ? 'text-white' : 'text-darkbrown-400' }}"></i>
                <span>Inspeksi & Retur</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $activeTab === 'return' ? 'bg-white/20 text-white' : 'bg-wheat-200 text-darkbrown-800' }}">
                    {{ $activeRentalsList->count() }}
                </span>
            </button>

            <button wire:click="$set('activeTab', 'history')"
                class="flex items-center gap-2.5 py-2 px-5 rounded-xl text-xs font-bold transition-all duration-200 cursor-pointer {{ $activeTab === 'history' ? 'text-white shadow-md' : 'text-darkbrown-600 hover:text-darkbrown-900 hover:bg-wheat-100/70 font-semibold' }}"
                style="{{ $activeTab === 'history' ? 'background: linear-gradient(135deg, #2b2202 0%, #3d3000 60%, #4a3c00 100%); box-shadow: 0 4px 14px rgba(43,34,2,0.3);' : '' }}">
                <i data-lucide="history" class="w-4 h-4 {{ $activeTab === 'history' ? 'text-white' : 'text-darkbrown-400' }}"></i>
                <span>Riwayat Transaksi</span>
            </button>
        </div>

        {{-- Helper Information pill --}}
        <div class="hidden sm:flex items-center gap-2 text-xs font-semibold text-darkbrown-600 bg-wheat-100/60 px-4 py-2 rounded-xl border border-wheat-200">
            <span class="w-2 h-2 rounded-full bg-avocado-500 animate-pulse"></span>
            <span>Verifikasi fisik barang, hitung denda, dan serahkan kembali KTP jaminan.</span>
        </div>
    </div>

    @if($activeTab === 'return')
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- KOLOM KIRI: DAFTAR TRANSAKSI SEWA AKTIF (Picked Up) --}}
            <div class="lg:col-span-5 space-y-4">
                <div class="bg-white rounded-2xl border border-wheat-200 shadow-sm p-4 space-y-3">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-avocado-100 text-avocado-800 flex items-center justify-center">
                                <i data-lucide="list" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-xs text-darkbrown-900 uppercase tracking-wider">Antrean Retur</h3>
                                <p class="text-[10px] text-darkbrown-500 font-medium">Transaksi sedang disewa & siap kembali</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-black bg-wheat-100 text-darkbrown-800 border border-wheat-200">
                            {{ $activeRentalsList->count() }} Transaksi
                        </span>
                    </div>

                    {{-- Search Input by booking_code / customer --}}
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-darkbrown-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                        <input type="text"
                               wire:model.live.debounce.250ms="searchOrder"
                               placeholder="Cari Kode Booking atau Nama..."
                               class="w-full text-xs pl-10 pr-4 py-2.5 bg-wheat-50/50 border border-wheat-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-500 text-darkbrown-900 placeholder-darkbrown-400 transition" />
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse($activeRentalsList as $rental)
                        @php
                            $isSelected = $this->activeOrder && $this->activeOrder->id === $rental->id;
                            $custName = $rental->customer?->name ?? $rental->guest_name;
                            $isLate = \Carbon\Carbon::today()->gt($rental->return_date);
                        @endphp
                        <div wire:click="selectRental({{ $rental->id }})"
                             class="group relative overflow-hidden bg-white rounded-2xl border p-4 cursor-pointer transition-all duration-200 {{ $isSelected ? 'border-avocado-500 shadow-md ring-2 ring-avocado-500/20' : 'border-wheat-200/90 hover:border-wheat-300 hover:shadow-sm hover:translate-x-0.5' }}">

                            <div class="absolute left-0 top-0 bottom-0 w-1.5 transition-colors duration-200 {{ $isSelected ? 'bg-avocado-600' : ($isLate ? 'bg-red-500' : 'bg-wheat-300 group-hover:bg-avocado-400') }}"></div>

                            <div class="pl-2 space-y-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs {{ $isSelected ? 'bg-avocado-600 text-white' : 'bg-wheat-100 text-darkbrown-700' }}">
                                            {{ strtoupper(substr($custName, 0, 1)) }}
                                        </div>
                                        <div>
                                            <h4 class="font-extrabold text-sm text-darkbrown-900 leading-tight group-hover:text-avocado-700 transition-colors">
                                                {{ $custName }}
                                            </h4>
                                            <span class="inline-flex items-center gap-1 text-[11px] font-mono font-bold text-darkbrown-500">
                                                {{ $rental->booking_code }}
                                            </span>
                                        </div>
                                    </div>

                                    @if($rental->ktp_collateral_status === 'held')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-200 shrink-0">
                                            <i data-lucide="id-card" class="w-3 h-3 text-amber-700"></i>
                                            KTP Ditahan
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between text-xs pt-1 border-t border-wheat-100">
                                    <div class="flex items-center gap-1.5 text-darkbrown-600 text-[11px]">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-darkbrown-400"></i>
                                        <span>Jatuh Tempo: <strong class="text-darkbrown-800">{{ $rental->return_date->format('d M Y') }}</strong></span>
                                    </div>
                                    <span class="text-[11px] font-bold text-darkbrown-500 bg-wheat-100 px-2 py-0.5 rounded-md">
                                        {{ $rental->details->count() }} Item
                                    </span>
                                </div>

                                <div class="flex items-center justify-between pt-1">
                                    @if($isLate)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                            Terlambat Kembalikan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[10px] font-bold bg-avocado-50 text-avocado-700 border border-avocado-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-avocado-500"></span>
                                            Tepat Waktu
                                        </span>
                                    @endif

                                    <span class="text-xs font-bold flex items-center gap-1 {{ $isSelected ? 'text-avocado-700' : 'text-darkbrown-400 group-hover:text-darkbrown-700' }}">
                                        <span>Inspeksi</span>
                                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center bg-white rounded-2xl border border-wheat-200 space-y-2">
                            <div class="w-12 h-12 mx-auto rounded-2xl bg-wheat-100 flex items-center justify-center text-darkbrown-400">
                                <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                            </div>
                            <p class="font-bold text-xs text-darkbrown-800">Tidak ada sewa aktif (Picked Up)</p>
                            <p class="text-[11px] text-darkbrown-500">Semua pesanan sewa telah dikembalikan atau belum ada transaksi yang diserahterimakan.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- KOLOM KANAN: LEMBAR KERJA INSPEKSI & PROSES RETURN --}}
            <div class="lg:col-span-7">
                @if($this->activeOrder)
                    @php
                        $activeCust = $this->activeOrder->customer?->name ?? $this->activeOrder->guest_name;
                    @endphp
                    <div class="bg-white rounded-2xl border border-wheat-200 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-wheat-200 bg-gradient-to-r from-wheat-50/70 via-white to-wheat-50/30">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-mono font-black bg-wheat-100 text-darkbrown-800 border border-wheat-200">
                                            {{ $this->activeOrder->booking_code }}
                                        </span>
                                        <span class="text-[11px] font-bold text-darkbrown-500">• Lembar Kerja Inspeksi</span>
                                    </div>
                                    <h3 class="text-xl sm:text-2xl font-black text-darkbrown-900 tracking-tight">
                                        {{ $activeCust }}
                                    </h3>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($this->activeOrder->ktp_collateral_status === 'held')
                                        <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-50 text-amber-900 border border-amber-200/90 shadow-sm">
                                            <span class="text-base">🪪</span>
                                            <div>
                                                <div class="text-[10px] uppercase font-bold text-amber-700 leading-none">Jaminan Kasir</div>
                                                <div class="leading-tight font-extrabold">KTP Fisik Sedang Ditahan</div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold bg-wheat-100 text-darkbrown-700 border border-wheat-200">
                                            <span>KTP Sudah Dikembalikan</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="p-6 space-y-6">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-black uppercase tracking-wider text-darkbrown-700 flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-lg bg-avocado-100 text-avocado-800 flex items-center justify-center">
                                        <i data-lucide="scan-barcode" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <span>Pencocokan & Kondisi Unit Fisik (Database)</span>
                                </h4>
                                <span class="text-[11px] font-semibold text-darkbrown-500">
                                    {{ count(array_filter($returnedItems)) }} / {{ $this->activeOrder->details->count() }} Diperiksa
                                </span>
                            </div>

                            <div class="space-y-4">
                                @foreach($this->activeOrder->details as $detail)
                                    @php
                                        $isDetailReturned = !empty($returnedItems[$detail->id]) || $detail->is_returned;
                                        $unit = $detail->itemUnit;
                                    @endphp
                                    <div class="rounded-2xl border transition-all duration-200 overflow-hidden {{ $isDetailReturned ? 'bg-avocado-50/40 border-avocado-200' : 'bg-white border-wheat-200 shadow-sm' }}">
                                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 {{ $isDetailReturned ? 'bg-avocado-50/60' : 'bg-wheat-50/50' }} border-b border-wheat-200/70">
                                            <div class="flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 {{ $isDetailReturned ? 'bg-avocado-600 text-white' : 'bg-wheat-200 text-darkbrown-800' }}">
                                                    {{ $loop->iteration }}
                                                </div>
                                                <div>
                                                    <h5 class="font-extrabold text-sm text-darkbrown-900 leading-tight">
                                                        {{ $detail->masterItem?->name }}
                                                    </h5>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <span class="inline-flex items-center gap-1 text-[11px] font-mono font-bold text-darkbrown-700 bg-white px-2 py-0.5 rounded-md border border-wheat-200">
                                                            <i data-lucide="scan-barcode" class="w-3 h-3 text-darkbrown-400"></i>
                                                            {{ $unit?->barcode ?? 'Belum ada barcode' }}
                                                        </span>
                                                        @if($unit)
                                                            <span class="text-[10px] font-mono text-darkbrown-500">Unit: {{ $unit->unit_code }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="self-end sm:self-center">
                                                @if($isDetailReturned)
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-avocado-100 text-avocado-800 border border-avocado-300">
                                                        <i data-lucide="check" class="w-3.5 h-3.5 text-avocado-700"></i>
                                                        <span>Selesai Diinspeksi ({{ $inspectionNotes[$detail->id] ?? 'good' }})</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-900 border border-amber-200">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                        <span>Menunggu Cek Kasir</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        @if(!$isDetailReturned)
                                            <div class="p-4 space-y-4 bg-white">
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    {{-- Radio Badge / Pill Tombol Pilihan Kondisi --}}
                                                    <div>
                                                        <label class="text-xs font-bold text-darkbrown-800 block mb-2">
                                                            Kondisi Fisik Saat Kembali
                                                        </label>
                                                        @php
                                                            $currentCondition = $inspectionNotes[$detail->id] ?? 'good';
                                                        @endphp
                                                        <div class="grid grid-cols-3 gap-2">
                                                            {{-- Tombol Pill: Baik --}}
                                                            <button type="button"
                                                                    wire:click="setCondition({{ $detail->id }}, 'good')"
                                                                    class="py-2 px-2.5 rounded-xl text-xs font-bold transition-all duration-150 flex flex-col items-center justify-center gap-1 border cursor-pointer select-none {{ $currentCondition === 'good' ? 'bg-emerald-500 text-white border-emerald-600 shadow-sm shadow-emerald-500/25 ring-2 ring-emerald-400/40' : 'bg-wheat-50/60 text-darkbrown-700 border-wheat-200 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-800' }}">
                                                                <i data-lucide="check" class="w-4 h-4"></i>
                                                                <span class="text-[11px] leading-tight">Baik</span>
                                                            </button>

                                                            {{-- Tombol Pill: Rusak --}}
                                                            <button type="button"
                                                                    wire:click="setCondition({{ $detail->id }}, 'damaged')"
                                                                    class="py-2 px-2.5 rounded-xl text-xs font-bold transition-all duration-150 flex flex-col items-center justify-center gap-1 border cursor-pointer select-none {{ $currentCondition === 'damaged' ? 'bg-amber-500 text-white border-amber-600 shadow-sm shadow-amber-500/25 ring-2 ring-amber-400/40' : 'bg-wheat-50/60 text-darkbrown-700 border-wheat-200 hover:bg-amber-50 hover:border-amber-300 hover:text-amber-800' }}">
                                                                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                                                                <span class="text-[11px] leading-tight">Rusak</span>
                                                            </button>

                                                            {{-- Tombol Pill: Hilang --}}
                                                            <button type="button"
                                                                    wire:click="setCondition({{ $detail->id }}, 'lost')"
                                                                    class="py-2 px-2.5 rounded-xl text-xs font-bold transition-all duration-150 flex flex-col items-center justify-center gap-1 border cursor-pointer select-none {{ $currentCondition === 'lost' ? 'bg-rose-600 text-white border-rose-700 shadow-sm shadow-rose-600/25 ring-2 ring-rose-400/40' : 'bg-wheat-50/60 text-darkbrown-700 border-wheat-200 hover:bg-rose-50 hover:border-rose-300 hover:text-rose-800' }}">
                                                                <i data-lucide="x-circle" class="w-4 h-4"></i>
                                                                <span class="text-[11px] leading-tight">Hilang</span>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    {{-- Denda Kerusakan Input --}}
                                                    @php
                                                        $isFeeRequired = in_array($currentCondition, ['damaged', 'lost']);
                                                        $currentFee = (float)($damageFees[$detail->id] ?? 0);
                                                        $isFeeInvalid = $isFeeRequired && $currentFee <= 0;
                                                    @endphp
                                                    <div>
                                                        <div class="flex items-center justify-between mb-2">
                                                            <label class="text-xs font-bold text-darkbrown-800 flex items-center gap-1.5">
                                                                <span>Denda Kerusakan / Ganti Rugi</span>
                                                                @if($isFeeRequired)
                                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-rose-100 text-rose-700 border border-rose-300 animate-pulse">
                                                                        Wajib Diisi
                                                                    </span>
                                                                @endif
                                                            </label>
                                                        </div>
                                                        <div class="relative">
                                                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-xs font-bold {{ $isFeeInvalid ? 'text-rose-600' : 'text-darkbrown-400' }}">
                                                                Rp
                                                            </span>
                                                            <input type="number"
                                                                   min="0"
                                                                   wire:model.live="damageFees.{{ $detail->id }}"
                                                                   placeholder="{{ $isFeeRequired ? 'Masukkan nominal denda...' : '0' }}"
                                                                   class="w-full text-xs font-bold pl-10 pr-3.5 py-2.5 rounded-xl transition duration-150 shadow-sm {{ $isFeeInvalid ? 'bg-rose-50/70 border-rose-400 text-rose-900 focus:ring-2 focus:ring-rose-500/30 focus:border-rose-600 ring-1 ring-rose-400' : 'bg-wheat-50/50 border-wheat-300 text-darkbrown-900 focus:bg-white focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-500' }}" />
                                                        </div>

                                                        @error('damageFees.' . $detail->id)
                                                            <p class="text-[11px] font-bold text-rose-600 mt-1.5 flex items-center gap-1">
                                                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                                                                <span>{{ $message }}</span>
                                                            </p>
                                                        @elseif($isFeeInvalid)
                                                            <p class="text-[10px] font-medium text-rose-600 mt-1.5 flex items-center gap-1">
                                                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 shrink-0"></i>
                                                                <span>Wajib mengisi denda agar tidak terjadi kerugian.</span>
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="flex items-center justify-between pt-2 border-t border-wheat-100">
                                                    <div>
                                                        @if($isFeeInvalid)
                                                            <span class="text-[11px] font-bold text-rose-600 flex items-center gap-1">
                                                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                                                <span>Isi nominal denda untuk melanjutkan</span>
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <button wire:click="markItemReturned({{ $detail->id }})"
                                                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 {{ $isFeeInvalid ? 'bg-gray-200 text-gray-400 border border-gray-300 cursor-not-allowed opacity-80' : 'text-white shadow-sm cursor-pointer active:scale-95 hover:brightness-105' }}"
                                                            style="{{ !$isFeeInvalid ? 'background: linear-gradient(135deg, #4e6506 0%, #5c7608 60%, #6c8b08 100%); box-shadow: 0 4px 12px rgba(78,101,6,0.25);' : '' }}"
                                                            {{ $isFeeInvalid ? 'disabled' : '' }}>
                                                        <i data-lucide="check" class="w-4 h-4"></i>
                                                        <span>Konfirmasi Fisik Item</span>
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Ringkasan Biaya Denda & Total (Teks Bersih & Angka Positif) --}}
                            @php
                                $totalItemsInOrder = $this->activeOrder->details->count();
                                $confirmedItemsCount = count(array_filter($returnedItems));
                                $isAllItemsInspected = ($totalItemsInOrder > 0 && $confirmedItemsCount >= $totalItemsInOrder);
                            @endphp

                            <div class="rounded-2xl p-5 border border-wheat-200 space-y-3 bg-gradient-to-br from-wheat-50/80 via-white to-wheat-100/40 shadow-sm">
                                <div class="flex items-center justify-between pb-2 border-b border-wheat-200">
                                    <span class="text-xs font-bold text-darkbrown-700 uppercase tracking-wider">
                                        Ringkasan Biaya Akhir & Denda
                                    </span>
                                    <span class="text-[11px] font-semibold text-darkbrown-500">
                                        Perhitungan otomatis
                                    </span>
                                </div>

                                <div class="space-y-2 text-xs">
                                    <div class="flex justify-between items-center text-darkbrown-700">
                                        <span class="font-medium">Denda Keterlambatan:</span>
                                        <span class="font-bold {{ (float)$this->calculatedLateFee > 0 ? 'text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200' : 'text-darkbrown-900' }}">
                                            Rp {{ number_format(abs((float)$this->calculatedLateFee), 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center text-darkbrown-700">
                                        <span class="font-medium">Total Denda Kerusakan:</span>
                                        <span class="font-bold {{ (float)$this->totalDamageFee > 0 ? 'text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200' : 'text-darkbrown-900' }}">
                                            Rp {{ number_format(abs((float)$this->totalDamageFee), 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center pt-3 border-t border-wheat-200">
                                    <div>
                                        <span class="text-xs font-black uppercase tracking-wider text-darkbrown-900 block">
                                            Total Biaya Denda Tambahan
                                        </span>
                                        <span class="text-[10px] text-darkbrown-500 font-medium">
                                            Wajib dilunasi sebelum status selesai & pengembalian KTP
                                        </span>
                                    </div>
                                    <span class="text-xl font-black {{ (float)$this->totalCharge > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                                        Rp {{ number_format(abs((float)$this->totalCharge), 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>

                            @error('general')
                                <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 font-bold flex items-center gap-3">
                                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0"></i>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror

                            {{-- Tombol SELESAI RETUR Terkunci (Disabled) sampai semua item diinspeksi --}}
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                                <div class="text-[11px] text-darkbrown-500 font-medium">
                                    @if(!$isAllItemsInspected)
                                        <span class="inline-flex items-center gap-1.5 text-amber-800 bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-200">
                                            <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 shrink-0"></i>
                                            Konfirmasi semua fisik item di atas ({{ $confirmedItemsCount }}/{{ $totalItemsInOrder }}) untuk mengaktifkan tombol retur.
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-300 font-bold">
                                            <i data-lucide="check-check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                            Semua item fisik telah diinspeksi. Siap menyelesaikan retur!
                                        </span>
                                    @endif
                                </div>

                                <button wire:click="completeReturn"
                                        class="w-full sm:w-auto py-3.5 px-8 rounded-xl font-black text-xs tracking-wider uppercase transition-all duration-200 flex items-center justify-center gap-2.5 {{ $isAllItemsInspected ? 'text-white cursor-pointer shadow-lg active:scale-95' : 'bg-gray-100 text-gray-400 border border-gray-300 cursor-not-allowed opacity-80' }}"
                                        style="{{ $isAllItemsInspected ? 'background: linear-gradient(135deg, #4e6506 0%, #5c7608 60%, #6c8b08 100%); box-shadow: 0 4px 18px rgba(78,101,6,0.35);' : '' }}"
                                        {{ !$isAllItemsInspected ? 'disabled' : '' }}>
                                    @if(!$isAllItemsInspected)
                                        <i data-lucide="lock" class="w-4 h-4 text-amber-600 shrink-0"></i>
                                        <span>Selesai Retur & Serahkan KTP</span>
                                    @else
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                        <span>Selesai Retur & Serahkan KTP (Completed)</span>
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-2xl border border-wheat-200 shadow-sm flex flex-col items-center justify-center p-12 text-center text-darkbrown-500 min-h-[460px]">
                        <div class="w-16 h-16 rounded-2xl bg-wheat-100 flex items-center justify-center mb-4 border border-wheat-200">
                            <i data-lucide="clipboard-list" class="w-8 h-8 text-wheat-600"></i>
                        </div>
                        <h4 class="text-base font-extrabold text-darkbrown-900">Pilih Transaksi di Antrean Kiri</h4>
                        <p class="text-xs text-darkbrown-500 max-w-sm mt-1.5 leading-relaxed">
                            Pilih transaksi penyewa yang berstatus 'Picked Up' untuk memulai pencocokan barcode, kalkulasi denda, dan pelepasan KTP.
                        </p>
                    </div>
                @endif
            </div>

        </div>
    @else
        {{-- KONTEN TAB 2: RIWAYAT TRANSAKSI SELESAI DARI DATABASE --}}
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-wheat-200 shadow-sm p-4">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="relative w-full sm:w-1/2">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-4 h-4 text-darkbrown-400"></i>
                        </div>
                        <input type="text"
                               wire:model.live.debounce.250ms="searchHistory"
                               placeholder="Cari Kode Booking atau Nama Pelanggan..."
                               class="w-full text-xs pl-10 pr-4 py-2.5 bg-wheat-50/50 border border-wheat-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-500 text-darkbrown-900 placeholder-darkbrown-400 transition" />
                    </div>

                    <div class="w-full sm:w-auto">
                        <select wire:model.live="statusFilter"
                                class="w-full text-xs font-bold bg-white border border-wheat-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-500 text-darkbrown-800 shadow-sm cursor-pointer">
                            <option value="all">Semua Status Riwayat</option>
                            <option value="Selesai">✓ Selesai Tanpa Denda</option>
                            <option value="Denda">⚠️ Selesai Dengan Denda</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-wheat-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-wheat-200 text-white"
                                style="background: linear-gradient(135deg, #2b2202 0%, #3d3000 60%, #4a3c00 100%);">
                                <th class="py-3.5 px-5 font-black uppercase tracking-wider text-[11px]" style="color: #f5e9c8;">Kode Booking</th>
                                <th class="py-3.5 px-5 font-black uppercase tracking-wider text-[11px]" style="color: #f5e9c8;">Pelanggan</th>
                                <th class="py-3.5 px-5 font-black uppercase tracking-wider text-[11px]" style="color: #f5e9c8;">Periode Sewa</th>
                                <th class="py-3.5 px-5 font-black uppercase tracking-wider text-[11px]" style="color: #f5e9c8;">Total Akhir</th>
                                <th class="py-3.5 px-5 font-black uppercase tracking-wider text-[11px]" style="color: #f5e9c8;">Status & KTP</th>
                                <th class="py-3.5 px-5 font-black uppercase tracking-wider text-[11px]" style="color: #f5e9c8;">Tanggal Selesai</th>
                                <th class="py-3.5 px-5 font-black uppercase tracking-wider text-[11px] text-right" style="color: #f5e9c8;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-wheat-200/70 bg-white">
                            @forelse($filteredHistory as $hist)
                                @php
                                    $hasFine = ($hist->total_late_fee > 0 || $hist->total_damage_fee > 0);
                                    $custH = $hist->customer?->name ?? $hist->guest_name;
                                    $histArray = [
                                        'id' => $hist->booking_code,
                                        'customer' => $custH,
                                        'rental_period' => $hist->pickup_date->format('d M') . ' - ' . $hist->return_date->format('d M Y'),
                                        'total' => (float) ($hist->final_total ?: $hist->grand_total),
                                        'status' => $hasFine ? 'Selesai (Ada Denda)' : 'Selesai & Dikembalikan',
                                        'date' => $hist->returned_at ? $hist->returned_at->format('d M Y, H:i') : $hist->updated_at->format('d M Y'),
                                        'items' => $hist->details->map(fn($d) => $d->masterItem?->name)->join(', '),
                                        'late_fee' => (float) $hist->total_late_fee,
                                        'damage_fee' => (float) $hist->total_damage_fee,
                                        'ktp_status' => $hist->ktp_collateral_status === 'returned' ? 'KTP Sudah Dikembalikan' : 'Ditahan',
                                    ];
                                @endphp
                                <tr class="hover:bg-wheat-50/70 transition-colors">
                                    <td class="py-4 px-5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-wheat-100 text-darkbrown-900 border border-wheat-200">
                                            {{ $hist->booking_code }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 font-extrabold text-darkbrown-900 text-sm">
                                        {{ $custH }}
                                    </td>
                                    <td class="py-4 px-5 text-darkbrown-600 font-medium">
                                        {{ $hist->pickup_date->format('d M') }} - {{ $hist->return_date->format('d M Y') }}
                                    </td>
                                    <td class="py-4 px-5 font-black text-darkbrown-900 text-sm">
                                        Rp {{ number_format($hist->final_total ?: $hist->grand_total, 0, ',', '.') }}
                                    </td>
                                    <td class="py-4 px-5">
                                        @if($hasFine)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Selesai (+Denda)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-avocado-50 text-avocado-800 border border-avocado-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-avocado-500"></span>
                                                Completed (KTP Rilis)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-5 text-darkbrown-600 font-medium">
                                        {{ $hist->returned_at ? $hist->returned_at->format('d M Y') : $hist->updated_at->format('d M Y') }}
                                    </td>
                                    <td class="py-4 px-5 text-right">
                                        <button @click="selectedHistory = {{ json_encode($histArray) }}; showHistoryModal = true"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-avocado-800 bg-avocado-100 hover:bg-avocado-200 border border-avocado-300 transition cursor-pointer">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span>Detail</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-darkbrown-500">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <div class="w-12 h-12 rounded-full bg-wheat-100 flex items-center justify-center text-wheat-500">
                                                <i data-lucide="clock" class="w-6 h-6"></i>
                                            </div>
                                            <p class="font-bold text-sm text-darkbrown-700">Tidak ada riwayat transaksi</p>
                                            <p class="text-xs text-darkbrown-400">Belum ada transaksi rental yang berstatus selesai.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL PELUNASAN DENDA (Tunai & QRIS Realtime Simulator) --}}
    @if($showFineModal)
        <div class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-darkbrown-950/70 backdrop-blur-sm animate-fade-in">
            <div class="bg-white w-full max-w-lg rounded-3xl border border-wheat-300 shadow-2xl overflow-hidden">
                <div class="p-5 text-white flex justify-between items-center"
                     style="background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 60%, #b91c1c 100%);">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-white text-base">
                            ⚠️
                        </div>
                        <div>
                            <h3 class="text-base font-black tracking-tight text-white">Pelunasan Denda Retur</h3>
                            <p class="text-xs text-red-200">Total Denda: Rp {{ number_format($this->totalCharge, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <button wire:click="$set('showFineModal', false)" class="text-white/80 hover:text-white p-1 rounded-lg">✕</button>
                </div>

                <div class="p-6 space-y-5 text-xs">
                    {{-- Pilihan Metode Pembayaran Denda --}}
                    <div>
                        <label class="block text-xs font-bold text-darkbrown-800 mb-2">Pilih Cara Pembayaran Denda:</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button"
                                    wire:click="$set('finePaymentMethod', 'cash')"
                                    class="p-3.5 rounded-2xl border text-left flex items-center gap-3 transition cursor-pointer {{ $finePaymentMethod === 'cash' ? 'border-avocado-600 bg-avocado-50 ring-2 ring-avocado-500/20' : 'border-wheat-200 bg-white hover:bg-wheat-50' }}">
                                <span class="text-xl">💵</span>
                                <div>
                                    <h4 class="font-bold text-darkbrown-900 text-xs">Tunai (Cash)</h4>
                                    <p class="text-[10px] text-darkbrown-500">Terima uang tunai di kasir</p>
                                </div>
                            </button>

                            <button type="button"
                                    wire:click="$set('finePaymentMethod', 'qris')"
                                    class="p-3.5 rounded-2xl border text-left flex items-center gap-3 transition cursor-pointer {{ $finePaymentMethod === 'qris' ? 'border-avocado-600 bg-avocado-50 ring-2 ring-avocado-500/20' : 'border-wheat-200 bg-white hover:bg-wheat-50' }}">
                                <span class="text-xl">📱</span>
                                <div>
                                    <h4 class="font-bold text-darkbrown-900 text-xs">QRIS Dinamis</h4>
                                    <p class="text-[10px] text-darkbrown-500">Scan QRIS Realtime</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    @if($finePaymentMethod === 'cash')
                        <div class="p-4 rounded-2xl bg-wheat-50 border border-wheat-200 space-y-3">
                            <label class="block font-bold text-darkbrown-800">Nominal Uang Diterima (Rp):</label>
                            <input type="number" wire:model.live="cashTendered" class="w-full text-base font-black py-2.5 px-3 rounded-xl border border-wheat-300 bg-white" placeholder="{{ $this->totalCharge }}" />
                            <div class="flex justify-between font-bold text-darkbrown-800 pt-2 border-t border-wheat-200">
                                <span>Kembalian:</span>
                                <span class="text-avocado-700 font-black">
                                    Rp {{ number_format(max(0, (float)$cashTendered - $this->totalCharge), 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @else
                        {{-- QRIS Simulation Modal / Interface --}}
                        <div class="p-4 rounded-2xl bg-wheat-50 border border-wheat-200 text-center space-y-3">
                            <div class="inline-block p-3 bg-white rounded-2xl shadow-sm border border-wheat-200">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode('TENDAKU-FINE-'.$this->activeOrder?->booking_code.'-'.$this->totalCharge) }}"
                                     alt="QRIS Pelunasan Denda"
                                     class="w-36 h-36 mx-auto rounded-lg" />
                                <span class="text-[10px] font-mono font-bold text-darkbrown-600 mt-2 block">NMID: ID102003920199</span>
                            </div>
                            <div class="text-[11px] text-darkbrown-600">
                                Scan dengan aplikasi e-wallet / mobile banking pelanggan untuk melunasi denda sebesar <strong>Rp {{ number_format($this->totalCharge, 0, ',', '.') }}</strong>.
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button wire:click="$set('showFineModal', false)"
                                class="py-2.5 px-5 rounded-xl font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 transition cursor-pointer">
                            Batal
                        </button>
                        <button wire:click="processFinePayment"
                                class="py-2.5 px-6 rounded-xl font-black text-white bg-avocado-600 hover:bg-avocado-700 shadow-md transition cursor-pointer active:scale-95">
                            Konfirmasi Pembayaran Denda & Selesai
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL STRUK BUKTI PENGEMBALIAN BARANG & KTP --}}
    @if($showReceiptModal && $receiptData)
        <div class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-darkbrown-950/70 backdrop-blur-sm animate-fade-in">
            <div class="bg-white w-full max-w-sm rounded-3xl border border-wheat-300 shadow-2xl p-6 space-y-4">
                <div class="text-center pb-3 border-b border-dashed border-wheat-300">
                    <h3 class="font-black text-lg text-darkbrown-900 tracking-tight">{{ $receiptData['vendor_name'] }}</h3>
                    <p class="text-[10px] text-darkbrown-500">{{ $receiptData['vendor_address'] }}</p>
                    <p class="text-[10px] font-mono text-darkbrown-400 mt-1">STRUK PENGEMBALIAN & SERAH KTP</p>
                </div>

                <div class="text-xs space-y-1.5 text-darkbrown-700">
                    <div class="flex justify-between">
                        <span>Kode Booking:</span>
                        <span class="font-mono font-black text-darkbrown-900">{{ $receiptData['booking_code'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Pelanggan:</span>
                        <span class="font-bold text-darkbrown-900">{{ $receiptData['customer_name'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Waktu Retur:</span>
                        <span>{{ $receiptData['date'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kasir:</span>
                        <span>{{ $receiptData['cashier_name'] }}</span>
                    </div>
                </div>

                <div class="pt-2 border-t border-dashed border-wheat-300 space-y-2 text-xs">
                    <span class="font-bold text-[10px] uppercase text-darkbrown-500 block">Daftar Barang Kembali:</span>
                    @foreach($receiptData['items'] as $it)
                        <div class="flex justify-between text-[11px]">
                            <div>
                                <p class="font-bold text-darkbrown-900">{{ $it['name'] }}</p>
                                <p class="text-[10px] text-darkbrown-500 font-mono">{{ $it['barcode'] }} ({{ $it['condition'] }})</p>
                            </div>
                            <span class="font-semibold text-darkbrown-800">
                                {{ $it['damage_fee'] > 0 ? 'Denda: Rp ' . number_format($it['damage_fee'], 0, ',', '.') : '✓ Baik' }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="pt-3 border-t border-dashed border-wheat-300 space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span>Biaya Sewa Lunas:</span>
                        <span>Rp {{ number_format($receiptData['grand_total'], 0, ',', '.') }}</span>
                    </div>
                    @if($receiptData['late_fee'] > 0)
                        <div class="flex justify-between text-red-700 font-semibold">
                            <span>Denda Terlambat:</span>
                            <span>Rp {{ number_format($receiptData['late_fee'], 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($receiptData['damage_fee'] > 0)
                        <div class="flex justify-between text-red-700 font-semibold">
                            <span>Denda Kerusakan:</span>
                            <span>Rp {{ number_format($receiptData['damage_fee'], 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-black text-sm text-darkbrown-900 pt-2 border-t border-wheat-200">
                        <span>Total Transaksi Akhir:</span>
                        <span class="text-avocado-800">Rp {{ number_format($receiptData['final_total'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="p-2.5 rounded-xl bg-avocado-50 border border-avocado-200 text-center text-[11px] font-bold text-avocado-800">
                    🪪 Status KTP: {{ $receiptData['ktp_status'] }}
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <button onclick="window.print()" class="w-1/2 py-2.5 rounded-xl font-bold text-xs bg-darkbrown-900 text-white hover:bg-darkbrown-800 transition cursor-pointer">
                        🖨️ Cetak Struk
                    </button>
                    <button wire:click="$set('showReceiptModal', false)" class="w-1/2 py-2.5 rounded-xl font-bold text-xs bg-wheat-100 hover:bg-wheat-200 text-darkbrown-800 transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DETAIL RIWAYAT TRANSAKSI (Alpine.js Teleport) --}}
    <template x-teleport="body">
        <div x-show="showHistoryModal"
             x-cloak
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 animate-fade-in"
             style="background: rgba(43, 34, 2, 0.6); backdrop-filter: blur(4px); display: none;"
             @keydown.escape.window="showHistoryModal = false">

            <div class="w-full max-w-md bg-white rounded-3xl border border-wheat-300 shadow-2xl overflow-hidden"
                 @click.away="showHistoryModal = false">

                <div class="p-5 text-white flex justify-between items-center"
                     style="background: linear-gradient(135deg, #2b2202 0%, #3d3000 60%, #4a3c00 100%);">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-emerald-300">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black tracking-tight" style="color: #f5e9c8;">Detail Transaksi Selesai</h3>
                            <p class="text-[10px]" style="color: rgba(245,233,200,0.6);">Data arsip pengembalian unit & jaminan KTP</p>
                        </div>
                    </div>
                    <button @click="showHistoryModal = false"
                            class="p-1.5 rounded-xl hover:bg-white/10 transition-colors text-wheat-300 hover:text-white cursor-pointer">
                        ✕
                    </button>
                </div>

                <template x-if="selectedHistory">
                    <div class="p-6 space-y-4 text-xs">
                        <div class="p-4 rounded-2xl bg-wheat-50 border border-wheat-200/80 space-y-2.5">
                            <div class="flex justify-between items-center">
                                <span class="text-darkbrown-500 font-semibold">Kode Booking:</span>
                                <span class="font-mono font-black text-darkbrown-900 px-2 py-0.5 rounded-md bg-white border border-wheat-200" x-text="selectedHistory.id"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-darkbrown-500 font-semibold">Pelanggan:</span>
                                <span class="font-extrabold text-darkbrown-900" x-text="selectedHistory.customer"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-darkbrown-500 font-semibold">Periode Sewa:</span>
                                <span class="font-medium text-darkbrown-800" x-text="selectedHistory.rental_period"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-darkbrown-500 font-semibold">Tanggal Selesai:</span>
                                <span class="font-medium text-darkbrown-800" x-text="selectedHistory.date"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-darkbrown-500 font-semibold">Status KTP:</span>
                                <span class="font-bold text-avocado-800" x-text="selectedHistory.ktp_status"></span>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-wheat-50 border border-wheat-200/80 space-y-1.5">
                            <span class="text-darkbrown-500 font-black block text-[10px] uppercase tracking-wider">Item Disewa:</span>
                            <p class="font-bold text-darkbrown-900 text-xs" x-text="selectedHistory.items"></p>
                        </div>

                        <div class="flex justify-between items-center p-4 rounded-2xl border border-avocado-200 text-xs"
                             style="background: linear-gradient(135deg, rgba(78,101,6,0.08) 0%, rgba(92,118,8,0.03) 100%);">
                            <div>
                                <span class="font-bold text-darkbrown-800 block text-xs">Total Transaksi Akhir:</span>
                                <span class="text-[10px] text-darkbrown-500">Termasuk sewa + denda (jika ada)</span>
                            </div>
                            <span class="font-black text-base text-avocado-800"
                                  x-text="'Rp ' + Number(selectedHistory.total).toLocaleString('id-ID')"></span>
                        </div>

                        <div class="pt-2">
                            <button @click="showHistoryModal = false"
                                    class="w-full py-2.5 px-4 rounded-xl font-bold text-xs text-darkbrown-800 bg-wheat-100 hover:bg-wheat-200 border border-wheat-300 transition cursor-pointer">
                                Tutup
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>
