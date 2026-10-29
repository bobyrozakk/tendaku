<x-layouts.customer>
    <x-slot name="title">Keranjang Sewa — Tendaku</x-slot>

    <div class="py-6 min-h-[65vh]">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs text-darkbrown-500 mb-3" aria-label="Breadcrumb">
            <a href="{{ url('/') }}" class="hover:text-avocado-700 transition">Beranda</a>
            <span>/</span>
            <span class="font-semibold text-darkbrown-800">Keranjang Sewa</span>
        </nav>

        {{-- Header Halaman --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-900 tracking-tight font-heading">
                    Keranjang Sewa
                </h1>
                <p class="text-xs sm:text-sm text-darkbrown-600 mt-1">
                    Kelola perlengkapan camping dari vendor pilihan Anda sebelum melanjutkan ke pembayaran.
                </p>
            </div>
            @if (!empty($items))
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                    <span class="w-2 h-2 rounded-full bg-avocado-500 animate-pulse"></span>
                    <span>{{ count($items) }} Jenis Alat ({{ $cartSummary['total_qty'] ?? count($items) }} Unit)</span>
                </span>
            @endif
        </div>

        {{-- Flash Messages --}}
        @if (session('warning'))
            <div class="mb-5 p-4 rounded-xl bg-sunglow-50 border border-goldenrod-300 text-darkbrown-900 text-xs sm:text-sm flex items-center gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-goldenrod-700 shrink-0"></i>
                <div class="font-medium">{{ session('warning') }}</div>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-5 p-4 rounded-xl bg-avocado-50 border border-avocado-300 text-darkbrown-900 text-xs sm:text-sm flex items-center gap-3 shadow-sm">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-avocado-700 shrink-0"></i>
                <div class="font-medium">{{ session('success') }}</div>
            </div>
        @endif

        @if (empty($items))
            {{-- State Keranjang Kosong --}}
            <div class="bg-white rounded-2xl border border-wheat-200 shadow-tendaku flex flex-col items-center justify-center py-20 px-4 text-center space-y-5">
                <div class="w-20 h-20 rounded-full bg-wheat-100 border border-wheat-200 flex items-center justify-center text-darkbrown-400">
                    <i data-lucide="shopping-cart" class="w-10 h-10"></i>
                </div>
                <div class="max-w-md space-y-1">
                    <h3 class="text-lg font-bold text-darkbrown-900">Keranjang Sewamu Masih Kosong</h3>
                    <p class="text-xs sm:text-sm text-darkbrown-600 leading-relaxed">
                        Belum ada perlengkapan camping yang dipilih. Jelajahi katalog alat Tendaku dan masukkan perlengkapan yang Anda butuhkan ke keranjang!
                    </p>
                </div>
                <div class="pt-2">
                    <a href="{{ route('customer.catalog.index') }}" class="btn-tendaku-primary inline-flex items-center gap-2 text-sm px-6 py-3">
                        <i data-lucide="tent" class="w-4 h-4"></i>
                        <span>Jelajahi Katalog Alat</span>
                    </a>
                </div>
            </div>
        @else
            {{-- Konten Keranjang Ada Isi (Multi-Item Vendor Sama) --}}
            <div class="space-y-6">
                {{-- Header Kolom Desktop --}}
                <div class="hidden md:grid grid-cols-12 gap-4 bg-wheat-50/70 border border-wheat-200 rounded-xl px-5 py-3 text-xs font-bold text-darkbrown-600 uppercase tracking-wider">
                    <div class="col-span-5">Produk Alat Camping</div>
                    <div class="col-span-2 text-center">Tarif / Hari</div>
                    <div class="col-span-2 text-center">Kuantitas</div>
                    <div class="col-span-2 text-right">Subtotal</div>
                    <div class="col-span-1 text-center">Aksi</div>
                </div>

                {{-- Card Toko Vendor --}}
                <div class="bg-white rounded-2xl border border-wheat-200 shadow-tendaku overflow-hidden">
                    {{-- Header Vendor --}}
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-wheat-100 bg-wheat-50/40">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[11px] font-extrabold text-white uppercase tracking-wider bg-avocado-700">
                                Official Partner
                            </span>
                            <span class="font-extrabold text-darkbrown-900 text-sm">
                                {{ $vendor['name'] ?? 'Tendaku Vendor' }}
                            </span>
                            <span class="text-darkbrown-300">·</span>
                            <span class="inline-flex items-center gap-1 text-xs text-darkbrown-600">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-goldenrod-600"></i>
                                {{ $vendor['city'] ?? 'Malang' }}
                            </span>
                        </div>
                        @if (!empty($vendor['id']))
                            <a href="{{ route('customer.catalog.index', ['vendor_id' => $vendor['id']]) }}" class="text-xs font-bold text-avocado-700 hover:text-avocado-800 transition">
                                + Tambah Alat Dari Toko Ini →
                            </a>
                        @endif
                    </div>

                    {{-- Daftar Baris Barang --}}
                    <div class="divide-y divide-wheat-100">
                        @foreach ($items as $item)
                            <div class="p-5 hover:bg-wheat-50/30 transition">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-center">
                                    {{-- Info Produk --}}
                                    <div class="col-span-1 md:col-span-5 flex gap-4">
                                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden bg-wheat-100 border border-wheat-200 shrink-0">
                                            <img src="{{ $item['image'] }}"
                                                 alt="{{ $item['name'] }}"
                                                 class="w-full h-full object-cover"
                                                 loading="lazy">
                                        </div>
                                        <div class="min-w-0 flex-1 space-y-1">
                                            <span class="inline-block text-[10px] font-bold uppercase tracking-wider bg-wheat-100 text-darkbrown-700 px-2 py-0.5 rounded border border-wheat-200">
                                                {{ $item['category'] }}
                                            </span>
                                            <h4 class="font-extrabold text-sm sm:text-base text-darkbrown-900 leading-snug line-clamp-2">
                                                {{ $item['name'] }}
                                            </h4>
                                            <div class="inline-flex items-center gap-1 text-xs text-darkbrown-600 bg-wheat-50 px-2 py-0.5 rounded border border-wheat-200/80">
                                                <i data-lucide="calendar" class="w-3 h-3 text-avocado-700"></i>
                                                <span class="text-[11px]">
                                                    {{ \Carbon\Carbon::parse($item['pickup_date'])->format('d M') }} – {{ \Carbon\Carbon::parse($item['return_date'])->format('d M Y') }}
                                                    ({{ $item['duration_days'] }} hari)
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Tarif per Hari --}}
                                    <div class="col-span-1 md:col-span-2 text-left md:text-center">
                                        <span class="md:hidden text-xs text-darkbrown-500 block mb-0.5 font-medium">Tarif / Hari:</span>
                                        <span class="text-sm font-semibold text-darkbrown-800">
                                            Rp {{ number_format($item['price_per_day'], 0, ',', '.') }}
                                        </span>
                                    </div>

                                    {{-- Stepper Kuantitas --}}
                                    <div class="col-span-1 md:col-span-2 flex flex-col md:items-center">
                                        <span class="md:hidden text-xs text-darkbrown-500 block mb-1 font-medium">Jumlah Unit:</span>
                                        <form method="POST" action="{{ route('customer.cart.update') }}" class="flex items-center border border-wheat-300 rounded-lg overflow-hidden bg-white w-fit shadow-sm">
                                            @csrf
                                            <input type="hidden" name="master_item_id" value="{{ $item['master_item_id'] }}">
                                            <button type="submit" name="qty" value="{{ max(0, $item['qty'] - 1) }}"
                                                    class="w-8 h-8 flex items-center justify-center font-bold text-darkbrown-700 hover:bg-wheat-100 transition"
                                                    title="Kurangi kuantitas">
                                                −
                                            </button>
                                            <span class="w-10 text-center font-bold text-xs text-darkbrown-900">
                                                {{ $item['qty'] }}
                                            </span>
                                            <button type="submit" name="qty" value="{{ $item['qty'] + 1 }}"
                                                    class="w-8 h-8 flex items-center justify-center font-bold text-darkbrown-700 hover:bg-wheat-100 transition"
                                                    title="Tambah kuantitas">
                                                +
                                            </button>
                                        </form>
                                    </div>

                                    {{-- Subtotal --}}
                                    <div class="col-span-1 md:col-span-2 text-left md:text-right">
                                        <span class="md:hidden text-xs text-darkbrown-500 block font-medium">Subtotal:</span>
                                        <span class="text-base sm:text-lg font-extrabold text-goldenrod-700 font-heading">
                                            Rp {{ number_format($item['total_price'], 0, ',', '.') }}
                                        </span>
                                    </div>

                                    {{-- Aksi Hapus --}}
                                    <div class="col-span-1 md:col-span-1 flex items-center justify-start md:justify-center">
                                        <form method="POST" action="{{ route('customer.cart.remove') }}" onsubmit="return confirm('Hapus {{ addslashes($item['name']) }} dari keranjang?');">
                                            @csrf
                                            <input type="hidden" name="master_item_id" value="{{ $item['master_item_id'] }}">
                                            <button type="submit" class="p-2 text-darkbrown-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition" title="Hapus alat">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Bar Checkout & Ringkasan Total --}}
                <div class="bg-white rounded-2xl border border-wheat-200 shadow-tendaku p-5 sm:p-6 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('customer.catalog.index', !empty($vendor['id']) ? ['vendor_id' => $vendor['id']] : []) }}" class="btn-tendaku-secondary text-xs sm:text-sm py-2.5 px-4 flex items-center gap-1.5">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Tambah Alat Lain</span>
                        </a>
                        <form method="POST" action="{{ route('customer.cart.clear') }}" onsubmit="return confirm('Kosongkan semua alat dari keranjang sewa?');">
                            @csrf
                            <button type="submit" class="text-xs text-darkbrown-500 hover:text-red-600 font-semibold transition px-2 py-1">
                                Kosongkan Keranjang
                            </button>
                        </form>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-5">
                        <div class="text-right">
                            <span class="text-xs text-darkbrown-500 block">Total Sewa ({{ $cartSummary['total_qty'] ?? count($items) }} Unit):</span>
                            <span class="text-xl sm:text-2xl font-extrabold text-goldenrod-700 font-heading">
                                Rp {{ number_format($cartSummary['total_price'] ?? 0, 0, ',', '.') }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('customer.cart.checkout') }}">
                            @csrf
                            <button type="submit" class="btn-tendaku-primary !py-3.5 !px-8 text-sm font-extrabold shadow-md hover:shadow-lg flex items-center gap-2">
                                <span>Lanjut ke Checkout</span>
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.customer>
