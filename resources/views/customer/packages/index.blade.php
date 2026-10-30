<x-layouts.customer>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="font-extrabold text-2xl md:text-3xl text-darkbrown-900 tracking-tight font-heading">
                    Paket Camping Siap Sewa
                </h1>
                <p class="text-sm text-darkbrown-600 mt-1">
                    Perlengkapan lengkap dalam satu paket hemat dari vendor terverifikasi. Pilih paket, tentukan tanggal, langsung sewa.
                </p>
            </div>
            <div class="flex items-center gap-2 bg-avocado-50 border border-avocado-200 px-3.5 py-2 rounded-xl text-xs font-semibold text-avocado-900 shadow-sm self-start md:self-auto">
                <span class="w-2 h-2 rounded-full bg-avocado-500 animate-pulse"></span>
                <span>{{ $packages->total() }} Paket Tersedia</span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8 py-2">
        <!-- Filter & Pencarian -->
        <section class="card-tendaku p-5 sm:p-6 rounded-2xl bg-white shadow-tendaku border border-wheat-200">
            <form method="GET" action="{{ route('customer.packages.index') }}"
                  class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end">
                <div class="lg:col-span-5">
                    <label for="search" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                        Cari Paket
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-4 h-4 text-darkbrown-400"></i>
                        </div>
                        <input type="text" id="search" name="search" value="{{ $search }}"
                               placeholder="Cari paket camping, mis. Paket 2 Hari 1 Malam..."
                               class="input-tendaku w-full pl-9 pr-4 py-2.5 rounded-xl text-xs font-medium border-wheat-300" />
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <label for="vendor_id" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                        Vendor
                    </label>
                    <select id="vendor_id" name="vendor_id"
                            class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                        <option value="">Semua Vendor</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) $vendorId === (string) $vendor->id)>
                                {{ $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label for="sort" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                        Urutkan
                    </label>
                    <select id="sort" name="sort"
                            class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                        <option value="latest" @selected($sort === 'latest')>Terbaru</option>
                        <option value="price_asc" @selected($sort === 'price_asc')>Harga Terendah</option>
                        <option value="price_desc" @selected($sort === 'price_desc')>Harga Tertinggi</option>
                        <option value="duration_asc" @selected($sort === 'duration_asc')>Durasi Terpendek</option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit"
                            class="w-full py-2.5 px-3 rounded-xl bg-avocado-600 hover:bg-avocado-700 text-white font-bold text-xs transition">
                        Terapkan
                    </button>
                    @if (! empty($search) || ! empty($vendorId) || ($sort && $sort !== 'latest'))
                        <a href="{{ route('customer.packages.index') }}"
                           class="py-2.5 px-3 rounded-xl bg-wheat-100 hover:bg-wheat-200 text-darkbrown-700 font-bold text-xs transition text-center"
                           title="Reset filter">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- Grid Paket -->
        @if ($packages->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($packages as $package)
                    @php
                        $image = $package->image_url
                            ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80';
                        $savings = (float) $package->savings;
                        $normalPrice = (float) $package->normal_price;
                    @endphp

                    <article class="card-tendaku rounded-2xl overflow-hidden bg-white border border-wheat-200 flex flex-col justify-between hover:shadow-tendaku-glow transition-all duration-300 group">
                        <div>
                            <!-- Foto -->
                            <div class="relative aspect-[4/3] bg-wheat-100 overflow-hidden">
                                <img src="{{ $image }}" alt="{{ $package->name }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                     loading="lazy" />
                                <div class="absolute top-3 left-3 right-3 flex items-start justify-between gap-2 pointer-events-none">
                                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-darkbrown-900/80 text-wheat-200 backdrop-blur-md border border-wheat-300/30">
                                        {{ $package->duration_days }} Hari
                                    </span>
                                    @if ($savings > 0)
                                        <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-sunglow-300 text-darkbrown-900 shadow-md">
                                            Hemat Rp {{ number_format($savings, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="p-5 space-y-3">
                                @if($package->vendor)
                                    <a href="{{ route('customer.stores.show', $package->vendor->slug ?? $package->vendor_id) }}" 
                                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-goldenrod-800 hover:text-avocado-700 transition max-w-full"
                                       title="Kunjungi Toko {{ $package->vendor->name }}">
                                        <i data-lucide="store" class="w-3.5 h-3.5 text-goldenrod-600 shrink-0"></i>
                                        <span class="truncate underline-offset-2 hover:underline">{{ $package->vendor->name }}</span>
                                    </a>
                                @else
                                    <div class="flex items-center gap-1.5 text-xs font-semibold text-goldenrod-800">
                                        <i data-lucide="store" class="w-3.5 h-3.5 text-goldenrod-600 shrink-0"></i>
                                        <span class="truncate">{{ $package->vendor?->name ?? 'Tendaku Vendor' }}</span>
                                    </div>
                                @endif

                                <h3 class="font-extrabold text-base text-darkbrown-900 line-clamp-2 leading-snug group-hover:text-avocado-700 transition-colors font-heading">
                                    {{ $package->name }}
                                </h3>

                                <p class="text-xs text-darkbrown-600 line-clamp-2 leading-relaxed">
                                    {{ $package->description ?: 'Paket camping lengkap, siap pakai untuk petualangan akhir pekan Anda.' }}
                                </p>

                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                        <i data-lucide="package" class="w-3 h-3"></i>
                                        {{ $package->total_items_count }} item
                                    </span>
                                    @if ($package->max_people)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-wheat-100 text-darkbrown-800 border border-wheat-300">
                                            <i data-lucide="users" class="w-3 h-3"></i>
                                            {{ $package->max_people }} orang
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Harga & Aksi -->
                        <div class="p-5 pt-0 mt-2 border-t border-wheat-100">
                            <div class="flex items-end justify-between py-3">
                                <div>
                                    <span class="text-xs text-darkbrown-500 block font-medium">Harga Paket</span>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-lg font-extrabold text-darkbrown-900 font-heading">
                                            Rp {{ number_format((float) $package->price, 0, ',', '.') }}
                                        </span>
                                        <span class="text-xs text-darkbrown-500 font-medium">/ paket</span>
                                    </div>
                                </div>
                                @if ($normalPrice > (float) $package->price)
                                    <span class="text-[11px] text-darkbrown-400 line-through">
                                        Rp {{ number_format($normalPrice, 0, ',', '.') }}
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('customer.packages.show', $package) }}"
                                   class="py-2.5 px-3 rounded-xl text-xs font-bold text-center text-darkbrown-800 bg-wheat-100 hover:bg-wheat-200 transition">
                                    Lihat Detail
                                </a>
                                <a href="{{ route('checkout.index', $package) }}"
                                   class="btn-tendaku-primary py-2.5 px-3 rounded-xl text-xs font-bold text-center shadow-md hover:shadow-lg transition-all">
                                    Sewa Paket
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $packages->links() }}
            </div>
        @else
            <div class="card-tendaku rounded-2xl bg-white border border-wheat-200 p-12 text-center shadow-tendaku">
                <div class="flex flex-col items-center gap-3">
                    <div class="p-4 rounded-2xl bg-wheat-100 text-wheat-500">
                        <i data-lucide="tent-tree" class="w-10 h-10"></i>
                    </div>
                    <h3 class="font-extrabold text-lg text-darkbrown-900 font-heading">Belum ada paket camping</h3>
                    <p class="text-sm text-darkbrown-600 max-w-md">
                        @if (! empty($search) || ! empty($vendorId))
                            Tidak ada paket yang cocok dengan filter Anda. Coba ubah kata kunci atau reset filter.
                        @else
                            Vendor belum menambahkan paket camping. Sementara itu, Anda tetap bisa menyewa alat satuan di katalog.
                        @endif
                    </p>
                    <div class="flex items-center gap-2 pt-1">
                        @if (! empty($search) || ! empty($vendorId))
                            <a href="{{ route('customer.packages.index') }}" class="btn-tendaku-secondary px-4 py-2.5 rounded-xl text-xs font-bold">
                                Reset Filter
                            </a>
                        @endif
                        <a href="{{ route('customer.catalog.index') }}" class="btn-tendaku-primary px-4 py-2.5 rounded-xl text-xs font-bold">
                            Lihat Katalog Alat
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.customer>
