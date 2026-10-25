<x-layouts.customer>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-xs font-semibold text-darkbrown-600 mb-2">
            <a href="{{ route('customer.packages.index') }}" class="hover:text-avocado-700 transition flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Kembali ke Paket Camping</span>
            </a>
            <span>/</span>
            <span class="text-darkbrown-900 font-bold truncate">{{ $package->name }}</span>
        </div>
        <h1 class="font-extrabold text-2xl md:text-3xl text-darkbrown-900 tracking-tight font-heading">
            {{ $package->name }}
        </h1>
    </x-slot>

    @php
        $image = $package->image_url
            ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1000&q=80';
        $price = (float) $package->price;
        $normalPrice = (float) $package->normal_price;
        $savings = (float) $package->savings;
        $vendorName = $package->vendor?->name ?? 'Tendaku Vendor';
        $defaultPickupDate = \Carbon\Carbon::tomorrow()->format('Y-m-d');
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 py-2">
        <!-- Kolom Kiri: Detail Paket -->
        <div class="lg:col-span-7 space-y-6">
            <div class="card-tendaku rounded-2xl bg-white p-4 border border-wheat-200 overflow-hidden shadow-tendaku">
                <div class="relative aspect-[4/3] rounded-xl overflow-hidden bg-wheat-100 border border-wheat-200">
                    <img src="{{ $image }}" alt="{{ $package->name }}" class="w-full h-full object-cover" />
                    <div class="absolute top-4 left-4 flex flex-wrap gap-2">
                        <span class="px-3 py-1 rounded-lg text-xs font-extrabold bg-darkbrown-900/90 text-wheat-200 backdrop-blur-md">
                            Paket {{ $package->duration_days }} Hari
                        </span>
                        @if ($savings > 0)
                            <span class="px-3 py-1 rounded-lg text-xs font-bold bg-sunglow-300 text-darkbrown-900 shadow-md">
                                Hemat Rp {{ number_format($savings, 0, ',', '.') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Vendor & Deskripsi -->
            <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-wheat-200 pb-3">
                    <h2 class="text-lg font-extrabold text-darkbrown-900 font-heading">Tentang Paket</h2>
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-goldenrod-800">
                        <i data-lucide="store" class="w-3.5 h-3.5"></i>
                        <span>{{ $vendorName }}</span>
                    </div>
                </div>

                <p class="text-sm text-darkbrown-700 leading-relaxed">
                    {{ $package->description ?: 'Paket camping lengkap berisi perlengkapan pilihan vendor, siap pakai untuk petualangan Anda.' }}
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                    <div class="p-3 rounded-xl bg-avocado-50 border border-avocado-200">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-avocado-700">Durasi Sewa</p>
                        <p class="text-sm font-extrabold text-darkbrown-900">{{ $package->duration_days }} hari</p>
                    </div>
                    <div class="p-3 rounded-xl bg-wheat-50 border border-wheat-200">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Isi Paket</p>
                        <p class="text-sm font-extrabold text-darkbrown-900">{{ $package->total_items_count }} item</p>
                    </div>
                    <div class="p-3 rounded-xl bg-sunglow-50 border border-sunglow-200">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-goldenrod-700">Kapasitas</p>
                        <p class="text-sm font-extrabold text-darkbrown-900">
                            {{ $package->max_people ? $package->max_people.' orang' : 'Fleksibel' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Isi Paket -->
            <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku space-y-4">
                <h2 class="text-lg font-extrabold text-darkbrown-900 border-b border-wheat-200 pb-3 font-heading">
                    Perlengkapan Dalam Paket
                </h2>

                <ul class="divide-y divide-wheat-100">
                    @foreach ($package->packageItems as $packageItem)
                        @php
                            $item = $packageItem->masterItem;
                            $availableUnits = $item ? $item->units->where('status', 'available')->count() : 0;
                            $itemImage = $item?->image_url
                                ?: 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=200&q=80';
                        @endphp
                        <li class="py-3 flex items-center gap-3">
                            <div class="w-14 h-14 rounded-xl overflow-hidden bg-wheat-100 border border-wheat-200 shrink-0">
                                <img src="{{ $itemImage }}" alt="{{ $item?->name }}" class="w-full h-full object-cover" loading="lazy" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-darkbrown-900 truncate">
                                    {{ $item?->name ?? 'Produk tidak tersedia' }}
                                </p>
                                <p class="text-[11px] text-darkbrown-500">
                                    {{ $item?->category?->name ?? 'Alat Outdoor' }}
                                    @if ($item)
                                        &middot; Rp {{ number_format((float) $item->daily_rate, 0, ',', '.') }}/hari
                                    @endif
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="badge-wheat">{{ $packageItem->quantity }}x</span>
                                @if ($availableUnits > 0)
                                    <p class="text-[10px] font-semibold text-avocado-700 mt-1">Stok tersedia</p>
                                @else
                                    <p class="text-[10px] font-semibold text-red-600 mt-1">Stok habis</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                <p class="text-[11px] text-darkbrown-500 leading-relaxed">
                    Ketersediaan unit dikonfirmasi otomatis saat Anda menyelesaikan pemesanan paket.
                </p>
            </div>
        </div>

        <!-- Kolom Kanan: Ringkasan & Sewa -->
        <div class="lg:col-span-5">
            <div class="lg:sticky lg:top-24 space-y-4">
                <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku space-y-4">
                    <div>
                        <p class="text-xs text-darkbrown-500 font-medium">Harga Paket</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-2xl font-extrabold text-darkbrown-900 font-heading">
                                Rp {{ number_format($price, 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-darkbrown-500 font-medium">/ paket</span>
                        </div>
                        @if ($normalPrice > $price)
                            <p class="text-[11px] text-darkbrown-400 mt-1">
                                Harga normal bila disewa satuan:
                                <span class="line-through">Rp {{ number_format($normalPrice, 0, ',', '.') }}</span>
                            </p>
                        @endif
                    </div>

                    <form method="GET" action="{{ route('customer.packages.checkout', $package) }}" class="space-y-3">
                        <div>
                            <label for="pickup_date" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                                Tanggal Mulai Sewa
                            </label>
                            <input type="date" id="pickup_date" name="pickup_date"
                                   value="{{ $defaultPickupDate }}"
                                   min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                                   required
                                   class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300" />
                            <p class="text-[11px] text-darkbrown-400 mt-1">
                                Pengembalian: {{ $package->duration_days }} hari setelah tanggal mulai.
                            </p>
                        </div>

                        <div>
                            <label for="pickup_method" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                                Metode Pengambilan
                            </label>
                            <select id="pickup_method" name="pickup_method"
                                    class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                                <option value="self_pickup">Ambil di Toko (Gratis)</option>
                                <option value="delivery">Diantar (+Rp 25.000, wajib login)</option>
                            </select>
                        </div>

                        <button type="submit"
                                class="btn-tendaku-primary w-full py-3 rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition-all">
                            Sewa Paket Ini
                        </button>
                    </form>

                    <div class="pt-3 border-t border-wheat-200 space-y-2 text-[11px] text-darkbrown-600">
                        <p class="flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-avocado-600 shrink-0"></i>
                            Jaminan KTP asli saat pengambilan, tanpa deposit uang.
                        </p>
                        <p class="flex items-center gap-2">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-avocado-600 shrink-0"></i>
                            Batas pembayaran 12 jam setelah pemesanan dibuat.
                        </p>
                        <p class="flex items-center gap-2">
                            <i data-lucide="package-check" class="w-3.5 h-3.5 text-avocado-600 shrink-0"></i>
                            Seluruh item dalam paket dipinjamkan sekaligus.
                        </p>
                    </div>
                </div>

                <a href="{{ route('customer.catalog.index') }}"
                   class="block text-center text-xs font-bold text-avocado-700 hover:text-avocado-800 bg-avocado-50 border border-avocado-200 rounded-xl py-2.5 transition">
                    Ingin menyewa alat satuan? Lihat Katalog
                </a>
            </div>
        </div>
    </div>
</x-layouts.customer>
