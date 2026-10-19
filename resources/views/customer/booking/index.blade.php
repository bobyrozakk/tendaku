<x-layouts.customer>
    <x-slot name="title">
        Riwayat Sewa Saya - Tendaku
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs text-darkbrown-500 mb-1.5">
                    <a href="{{ route('home') }}" class="hover:text-avocado-600 transition">Beranda</a>
                    <span class="text-wheat-400">/</span>
                    <span class="text-darkbrown-800 font-semibold">Riwayat Sewa</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-900 tracking-tight font-heading">
                    📦 Riwayat Transaksi Sewa
                </h1>
                <p class="text-xs text-darkbrown-600 mt-1">
                    Daftar seluruh pesanan peralatan camping dan status sewa Anda di Tendaku.
                </p>
            </div>
            <a href="{{ route('customer.catalog.index') }}" class="btn-tendaku-primary py-2.5 px-4 rounded-xl text-xs font-bold self-start sm:self-auto">
                + Sewa Alat Baru
            </a>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6 space-y-6">

        <!-- FILTER STATUS TABS -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-wheat-200">
            @php
                $statusTabs = [
                    '' => 'Semua Transaksi',
                    'pending' => 'Menunggu Pembayaran',
                    'confirmed' => 'Dikonfirmasi',
                    'picked_up' => 'Sedang Digunakan',
                    'returned' => 'Dikembalikan',
                    'completed' => 'Selesai',
                    'cancelled' => 'Dibatalkan',
                    'expired' => 'Kedaluwarsa',
                ];
            @endphp
            @foreach($statusTabs as $tabKey => $tabLabel)
                <a href="{{ route('customer.booking.index', $tabKey ? ['status' => $tabKey] : []) }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition border {{ ($status ?? '') === $tabKey ? 'bg-darkbrown-900 text-wheat-200 border-darkbrown-900 shadow-sm' : 'bg-white text-darkbrown-700 border-wheat-200 hover:bg-wheat-100' }}">
                    {{ $tabLabel }}
                </a>
            @endforeach
        </div>

        <!-- LIST RENTALS -->
        @if($rentals->count() > 0)
            <div class="space-y-4">
                @foreach($rentals as $rental)
                    @php
                        $statusBadgeClass = match($rental->status) {
                            'pending' => 'bg-sunglow-100 text-sunglow-900 border-sunglow-300',
                            'confirmed' => 'bg-sky-100 text-sky-900 border-sky-300',
                            'picked_up' => 'bg-avocado-100 text-avocado-900 border-avocado-300',
                            'returned' => 'bg-indigo-100 text-indigo-900 border-indigo-300',
                            'completed' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                            'cancelled', 'expired' => 'bg-red-100 text-red-900 border-red-300',
                            default => 'bg-wheat-100 text-darkbrown-700 border-wheat-300',
                        };

                        $statusLabel = match($rental->status) {
                            'pending' => 'Menunggu Pembayaran',
                            'confirmed' => 'Dikonfirmasi (Siap Pickup)',
                            'picked_up' => 'Sedang Dipakai (Disewa)',
                            'returned' => 'Telah Dikembalikan',
                            'completed' => 'Selesai',
                            'cancelled' => 'Dibatalkan',
                            'expired' => 'Kedaluwarsa',
                            default => ucfirst($rental->status),
                        };
                    @endphp
                    <div class="card-tendaku rounded-2xl bg-white border border-wheat-200 p-5 sm:p-6 shadow-tendaku-sm hover:border-wheat-400 transition space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-wheat-100">
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-sm font-extrabold text-darkbrown-900">
                                    #{{ $rental->booking_code }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $statusBadgeClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                            <span class="text-xs text-darkbrown-500 font-medium">
                                Dipesan pada {{ $rental->created_at->translatedFormat('d M Y, H:i') }} WIB
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                            <div class="md:col-span-8 space-y-2">
                                <div class="text-xs font-bold text-darkbrown-900 flex items-center gap-1.5">
                                    <span>🏬 {{ $rental->vendor?->name }}</span>
                                    <span class="text-wheat-500">•</span>
                                    <span class="text-darkbrown-600 font-medium">{{ $rental->vendor?->city }}</span>
                                </div>
                                
                                <div class="text-xs text-darkbrown-700 space-y-1">
                                    <p class="font-medium">
                                        📅 Periode: <strong>{{ \Carbon\Carbon::parse($rental->pickup_date)->translatedFormat('d M Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($rental->return_date)->translatedFormat('d M Y') }}</strong> ({{ $rental->rental_days }} hari)
                                    </p>
                                    <p class="text-darkbrown-600">
                                        📦 Barang: {{ $rental->details->count() }} unit alat
                                        @if($rental->details->first()?->masterItem)
                                            ({{ $rental->details->first()->masterItem->name }}{{ $rental->details->count() > 1 ? ', dll' : '' }})
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="md:col-span-4 flex flex-col sm:items-end justify-between gap-3 pt-2 md:pt-0 border-t md:border-t-0 border-wheat-100">
                                <div>
                                    <span class="text-[11px] text-darkbrown-500 block">Total Tagihan</span>
                                    <span class="text-xl font-extrabold text-darkbrown-900 font-heading">
                                        Rp {{ number_format($rental->final_total ?: $rental->grand_total, 0, ',', '.') }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <a href="{{ route('booking.status', $rental->booking_code) }}"
                                       class="btn-tendaku-primary py-2 px-4 rounded-xl text-xs font-bold shadow-sm">
                                        Detail Booking & Status →
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $rentals->links() }}
            </div>
        @else
            <div class="card-tendaku-warm p-12 text-center rounded-2xl border border-wheat-300">
                <div class="w-16 h-16 bg-wheat-200 text-darkbrown-700 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                    ⛺
                </div>
                <h3 class="text-lg font-bold text-darkbrown-900 font-heading">Belum Ada Transaksi Sewa</h3>
                <p class="text-sm text-darkbrown-600 max-w-md mx-auto mt-2">
                    Anda belum memiliki riwayat rental untuk kategori status yang dipilih. Jelajahi katalog dan sewa peralatan petualangan Anda sekarang!
                </p>
                <div class="mt-6">
                    <a href="{{ route('customer.catalog.index') }}" class="btn-tendaku-primary py-2.5 px-6 rounded-xl text-sm font-bold inline-block">
                        Lihat Katalog Alat Camping
                    </a>
                </div>
            </div>
        @endif

    </div>
</x-layouts.customer>
