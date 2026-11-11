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
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
        <!-- Kolom Kiri: Detail Paket -->
        <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
            <div class="card-tendaku rounded-2xl bg-white p-2 border border-wheat-200 overflow-hidden shadow-tendaku">
                <div class="relative aspect-[3/2] md:aspect-auto md:h-56 rounded-xl overflow-hidden bg-wheat-100 border border-wheat-200">
                    <img src="{{ $image }}" alt="{{ $package->name }}" class="w-full h-full object-cover" />
                    <div class="absolute top-3 left-3 right-3 flex flex-wrap gap-2">
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
            <div class="space-y-3 min-w-0">
                <div class="flex flex-col gap-2 border-b border-wheat-200 pb-2">
                    <h2 class="text-base font-extrabold text-darkbrown-900 font-heading">Tentang Paket</h2>
                    @if($package->vendor)
                        <a href="{{ route('customer.stores.show', $package->vendor->slug ?? $package->vendor_id) }}" 
                           class="inline-flex flex-wrap items-center gap-1.5 text-xs font-semibold text-goldenrod-800 hover:text-avocado-700 transition"
                           title="Kunjungi Toko {{ $vendorName }}">
                            <i data-lucide="store" class="w-3.5 h-3.5 text-goldenrod-600"></i>
                            <span class="underline-offset-2 hover:underline">{{ $vendorName }}</span>
                            <span class="text-[10px] bg-avocado-100 text-avocado-800 font-bold px-2 py-0.5 rounded-md ml-1">Kunjungi Toko</span>
                        </a>
                    @else
                        <div class="flex items-center gap-1.5 text-xs font-semibold text-goldenrod-800">
                            <i data-lucide="store" class="w-3.5 h-3.5"></i>
                            <span>{{ $vendorName }}</span>
                        </div>
                    @endif
                </div>

                <p class="text-sm text-darkbrown-700 leading-relaxed">
                    {{ $package->description ?: 'Paket camping lengkap berisi perlengkapan pilihan vendor, siap pakai untuk petualangan Anda.' }}
                </p>

                <div class="grid grid-cols-3 gap-2">
                    <div class="p-2 rounded-lg bg-avocado-50 border border-avocado-200">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-avocado-700">Durasi Sewa</p>
                        <p class="text-sm font-extrabold text-darkbrown-900">{{ $package->duration_days }} hari</p>
                    </div>
                    <div class="p-2 rounded-lg bg-wheat-50 border border-wheat-200">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Isi Paket</p>
                        <p class="text-sm font-extrabold text-darkbrown-900">{{ $package->total_items_count }} item</p>
                    </div>
                    <div class="p-2 rounded-lg bg-sunglow-50 border border-sunglow-200">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-goldenrod-700">Kapasitas</p>
                        <p class="text-sm font-extrabold text-darkbrown-900">
                            {{ $package->max_people ? $package->max_people.' orang' : 'Fleksibel' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Isi Paket -->
            <div class="md:col-span-2 card-tendaku rounded-2xl bg-white p-4 border border-wheat-200 shadow-tendaku space-y-3">
                <h2 class="text-base font-extrabold text-darkbrown-900 border-b border-wheat-200 pb-2 font-heading">
                    Perlengkapan Dalam Paket
                </h2>

                <ul class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2">
                    @foreach ($package->packageItems as $packageItem)
                        @php
                            $item = $packageItem->masterItem;
                            $availableUnits = $item ? $item->units->where('status', 'available')->count() : 0;
                            $itemImage = $item?->image_url
                                ?: 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=200&q=80';
                        @endphp
                        <li class="py-2 flex items-center gap-2 border-b border-wheat-100">
                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-wheat-100 border border-wheat-200 shrink-0">
                                <img src="{{ $itemImage }}" alt="{{ $item?->name }}" class="w-full h-full object-cover" loading="lazy" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-darkbrown-900 break-words">
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
        <div class="lg:col-span-4 lg:sticky lg:top-24">
            <div class="space-y-3">
                <div class="card-tendaku rounded-2xl bg-white p-4 border border-wheat-200 shadow-tendaku space-y-3">
                    <div>
                        <p class="text-xs text-darkbrown-500 font-medium">Harga Paket per Hari</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-2xl font-extrabold text-darkbrown-900 font-heading">
                                Rp {{ number_format($price, 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-darkbrown-500 font-medium">/ hari</span>
                        </div>
                        @if ($normalPrice > $price)
                            <p class="text-[11px] text-darkbrown-400 mt-1">
                                Harga normal bila disewa satuan:
                                <span class="line-through">Rp {{ number_format($normalPrice, 0, ',', '.') }}</span>
                            </p>
                        @endif
                    </div>

                    <p class="text-sm text-darkbrown-600 leading-relaxed">
                        Pilih jadwal sewa dan metode pengambilan di halaman booking paket.
                    </p>
                    <a href="{{ route('customer.packages.checkout', ['id' => $package->id]) }}"
                       class="btn-tendaku-primary flex items-center justify-center w-full py-3 rounded-xl text-sm font-bold">
                        Sewa Paket
                    </a>

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
