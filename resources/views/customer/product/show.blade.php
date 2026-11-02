<x-layouts.customer>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-xs font-semibold text-darkbrown-600 mb-2">
            <a href="{{ route('customer.catalog.index') }}" class="hover:text-avocado-700 transition flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Kembali ke Katalog</span>
            </a>
            <span>/</span>
            <span class="text-darkbrown-900 font-bold truncate">{{ $item->name }}</span>
        </div>
        <h1 class="font-extrabold text-2xl md:text-3xl text-darkbrown-900 tracking-tight font-heading">
            {{ $item->name }}
        </h1>
    </x-slot>

    @php
        $dailyRate = (float) $item->daily_rate;
        $vendorName = $item->vendor?->name ?? 'Tendaku Vendor';
        $categoryName = $item->category?->name ?? 'Alat Outdoor';
        
        $itemImages = $item->images->pluck('image_url')->filter()->values();
        if ($itemImages->isEmpty() && $item->image_url) {
            $itemImages = collect([$item->image_url]);
        }
        $fallbackPlaceholder = 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1000&q=80';
        $mainImage = $itemImages->first() ?: $fallbackPlaceholder;
        $galleryImages = $itemImages->isNotEmpty() ? $itemImages->all() : [$fallbackPlaceholder];

        $bookedDatesJson = json_encode($bookedDates ?? []);
    @endphp

    <!-- Container Utama Detail Produk -->
    <div x-data="{ activeImage: '{{ $mainImage }}', setActiveImage(url) { this.activeImage = url; }, showGuestStoreModal: false }" 
         @open-guest-store-modal.window="showGuestStoreModal = true"
         class="space-y-8 py-2">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Kolom Kiri: Galeri Foto & Spesifikasi Peralatan (7 cols) -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Galeri Foto Interaktif -->
                <div class="card-tendaku rounded-2xl bg-white p-4 border border-wheat-200 overflow-hidden shadow-tendaku">
                    <!-- Foto Utama -->
                    <div class="relative aspect-[4/3] rounded-xl overflow-hidden bg-wheat-100 border border-wheat-200">
                        <img :src="activeImage" 
                             alt="{{ $item->name }}" 
                             class="w-full h-full object-cover transition-all duration-300" 
                             id="mainProductImage"
                        />
                        <div class="absolute top-4 left-4 flex flex-wrap gap-2">
                            <span class="px-3 py-1 rounded-lg text-xs font-extrabold bg-darkbrown-900/90 text-wheat-200 backdrop-blur-md">
                                {{ $categoryName }}
                            </span>
                            <span class="px-3 py-1 rounded-lg text-xs font-bold bg-avocado-600 text-white shadow-md">
                                Jaminan KTP (Tanpa Deposit Uang)
                            </span>
                        </div>
                    </div>

                    <!-- Thumbnail Switcher -->
                    @if (count($galleryImages) > 1)
                        <div class="grid grid-cols-4 sm:grid-cols-5 gap-3 mt-4">
                            @foreach($galleryImages as $index => $imgUrl)
                                <button type="button" 
                                        @click="setActiveImage('{{ $imgUrl }}')" 
                                        :class="activeImage === '{{ $imgUrl }}' ? 'ring-2 ring-avocado-500 border-transparent scale-[1.02]' : 'opacity-70 hover:opacity-100 border-wheat-200'"
                                        class="relative aspect-[4/3] rounded-lg overflow-hidden border bg-wheat-50 transition-all">
                                    <img src="{{ $imgUrl }}" alt="Thumbnail {{ $index + 1 }}" class="w-full h-full object-cover"/>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Deskripsi & Fitur Utama -->
                <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku space-y-4">
                    <h2 class="text-lg font-extrabold text-darkbrown-900 border-b border-wheat-200 pb-3 font-heading flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <i data-lucide="file-text" class="w-5 h-5 text-darkbrown-800"></i>
                            <span>Deskripsi Peralatan</span>
                        </span>
                        <span class="text-xs font-medium text-darkbrown-500">Kondisi: {{ $item->dominant_condition }}</span>
                    </h2>
                    <p class="text-sm text-darkbrown-700 leading-relaxed">
                        {{ $item->description ?: 'Peralatan outdoor kualitas standar pendakian gunung, steril & siap pakai.' }}
                    </p>

                    <!-- Jaminan Kebersihan & Layanan Vendor -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                        <div class="p-3 rounded-xl bg-avocado-50 border border-avocado-200 flex items-start gap-2.5">
                            <i data-lucide="sparkles" class="w-5 h-5 text-avocado-700 shrink-0 mt-0.5"></i>
                            <div>
                                <h4 class="text-xs font-bold text-avocado-900">Steril & Higienis</h4>
                                <p class="text-[11px] text-avocado-800">Dibersihkan dan disinfeksi pasca setiap penyewaan.</p>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-sunglow-50 border border-sunglow-200 flex items-start gap-2.5">
                            <i data-lucide="credit-card" class="w-5 h-5 text-goldenrod-700 shrink-0 mt-0.5"></i>
                            <div>
                                <h4 class="text-xs font-bold text-darkbrown-900">Jaminan KTP Asli</h4>
                                <p class="text-[11px] text-darkbrown-700">Tanpa deposit uang tunai, cukup KTP fisik.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profil Toko Penyedia (ala Shopee) -->
                @if($item->vendor)
                    <div class="card-tendaku rounded-2xl bg-white p-5 border border-wheat-200 shadow-tendaku flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="relative shrink-0">
                                @if(!empty($item->vendor->logo_url))
                                    <img src="{{ $item->vendor->logo_url }}" alt="{{ $vendorName }}" class="w-12 h-12 rounded-xl object-cover border border-wheat-200 shadow-sm" />
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-darkbrown-800 text-sunglow-300 font-extrabold flex items-center justify-center font-heading text-sm">
                                        {{ strtoupper(substr($vendorName, 0, 2)) }}
                                    </div>
                                @endif
                                <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-avocado-500 border border-white flex items-center justify-center text-white" title="Terverifikasi">
                                    <i data-lucide="check" class="w-2.5 h-2.5"></i>
                                </span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-extrabold text-sm text-darkbrown-900 font-heading">
                                        @auth
                                            <a href="{{ route('customer.stores.show', $item->vendor->slug ?? $item->vendor_id) }}" class="hover:text-avocado-700 transition">
                                                {{ $vendorName }}
                                            </a>
                                        @else
                                            <button type="button" 
                                                    @click="showGuestStoreModal = true"
                                                    class="hover:text-avocado-700 transition cursor-pointer font-bold text-left">
                                                {{ $vendorName }}
                                            </button>
                                        @endauth
                                    </h3>
                                    <span class="badge-avocado text-[10px]">Official</span>
                                </div>
                                <p class="text-[11px] text-darkbrown-500 flex items-center gap-1 mt-0.5">
                                    <i data-lucide="map-pin" class="w-3 h-3 text-sunglow-500"></i>
                                    <span>{{ $item->vendor->city ?: 'Kota Penyedia' }}</span>
                                    @if($item->vendor->items_count ?? false)
                                        <span>&bull; {{ $item->vendor->items_count }} alat disewakan</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        @auth
                            <a href="{{ route('customer.stores.show', $item->vendor->slug ?? $item->vendor_id) }}" 
                               class="btn-tendaku-secondary !py-2 !px-3.5 text-xs font-bold inline-flex items-center justify-center gap-1.5 self-start sm:self-auto shadow-sm hover:border-avocado-500 hover:text-avocado-700 transition">
                                <i data-lucide="store" class="w-3.5 h-3.5 text-darkbrown-600"></i>
                                <span>Kunjungi Toko</span>
                                <i data-lucide="chevron-right" class="w-3 h-3 text-darkbrown-400"></i>
                            </a>
                        @else
                            <button type="button" 
                                    @click="showGuestStoreModal = true"
                                    class="btn-tendaku-secondary !py-2 !px-3.5 text-xs font-bold inline-flex items-center justify-center gap-1.5 self-start sm:self-auto shadow-sm hover:border-avocado-500 hover:text-avocado-700 transition cursor-pointer">
                                <i data-lucide="store" class="w-3.5 h-3.5 text-darkbrown-600"></i>
                                <span>Kunjungi Toko</span>
                                <i data-lucide="chevron-right" class="w-3 h-3 text-darkbrown-400"></i>
                            </button>
                        @endauth
                    </div>
                @endif

                <!-- Spesifikasi Teknis & Informasi Unit -->
                <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku space-y-4">
                    <h2 class="text-lg font-extrabold text-darkbrown-900 border-b border-wheat-200 pb-3 font-heading flex items-center gap-2">
                        <i data-lucide="info" class="w-5 h-5 text-darkbrown-800"></i>
                        <span>Informasi Peralatan & Unit</span>
                    </h2>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-xs">
                        <div class="flex justify-between py-1.5 border-b border-wheat-100">
                            <span class="text-darkbrown-600 font-medium">Kategori Alat</span>
                            <span class="font-bold text-darkbrown-900">{{ $categoryName }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-wheat-100">
                            <span class="text-darkbrown-600 font-medium">Penyedia / Toko</span>
                            @if($item->vendor)
                                @auth
                                    <a href="{{ route('customer.stores.show', $item->vendor->slug ?? $item->vendor_id) }}" 
                                       class="font-bold text-darkbrown-900 hover:text-avocado-700 transition inline-flex items-center gap-1">
                                        <span>{{ $vendorName }}</span>
                                        <i data-lucide="external-link" class="w-3 h-3 text-darkbrown-400"></i>
                                    </a>
                                @else
                                    <button type="button"
                                            @click="showGuestStoreModal = true"
                                            class="font-bold text-darkbrown-900 hover:text-avocado-700 transition inline-flex items-center gap-1 cursor-pointer">
                                        <span>{{ $vendorName }}</span>
                                        <i data-lucide="external-link" class="w-3 h-3 text-darkbrown-400"></i>
                                    </button>
                                @endauth
                            @else
                                <span class="font-bold text-darkbrown-900">{{ $vendorName }}</span>
                            @endif
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-wheat-100">
                            <span class="text-darkbrown-600 font-medium">Kondisi Unit Fisik</span>
                            <span class="font-bold text-avocado-800">{{ $item->dominant_condition }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-wheat-100">
                            <span class="text-darkbrown-600 font-medium">Umur / Masa Pakai</span>
                            <span class="font-bold text-darkbrown-900">{{ $item->item_age }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-wheat-100 sm:col-span-2">
                            <span class="text-darkbrown-600 font-medium">Denda Keterlambatan</span>
                            <span class="font-bold text-red-700">Rp {{ number_format($item->late_fee_per_day, 0, ',', '.') }} / hari</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Kolom Kanan: Livewire ProductBooking Component (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                <livewire:customer.product-booking :product="$item" />
            </div>

        </div>

        @guest
            <!-- Modal Pop-up "Login untuk Melanjutkan" di Halaman Detail Produk -->
            <div x-show="showGuestStoreModal" 
                 x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 aria-labelledby="modal-guest-store-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showGuestStoreModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 transition-opacity bg-darkbrown-950/75 backdrop-blur-sm"
                         @click="showGuestStoreModal = false"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div x-show="showGuestStoreModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="inline-block w-full max-w-sm sm:max-w-md p-6 sm:p-8 my-8 text-center align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-wheat-200 sm:my-8 relative overflow-hidden">
                        
                        <!-- Aksen Garis Emas di Atas -->
                        <div class="absolute top-0 inset-x-0 h-2 bg-gradient-to-r from-sunglow-300 via-avocado-500 to-goldenrod-400"></div>

                        <!-- Tombol Tutup X -->
                        <button type="button" 
                                @click="showGuestStoreModal = false"
                                class="absolute top-4 right-4 text-darkbrown-400 hover:text-darkbrown-700 p-1.5 rounded-xl hover:bg-wheat-100 transition"
                                aria-label="Tutup modal">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>

                        <div class="w-16 h-16 mx-auto rounded-2xl bg-sunglow-100 text-goldenrod-800 flex items-center justify-center shadow-inner mt-1 mb-4">
                            <i data-lucide="store" class="w-8 h-8 text-goldenrod-700"></i>
                        </div>

                        <h3 id="modal-guest-store-title" class="text-xl font-extrabold text-darkbrown-900 font-heading">
                            Lanjut sebagai Tamu atau Masuk
                        </h3>

                        <p class="text-xs sm:text-sm text-darkbrown-600 mt-2 leading-relaxed">
                            Anda bisa checkout langsung tanpa membuat akun di <strong>{{ $vendorName }}</strong>. Masuk jika ingin menyimpan riwayat sewa dan memakai keranjang untuk booking berikutnya.
                        </p>

                        <div class="mt-6 flex flex-col gap-2.5">
                            <a href="{{ route('login') }}" 
                               class="btn-tendaku-primary w-full py-3 px-4 rounded-xl text-xs sm:text-sm font-bold text-center shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                                <i data-lucide="log-in" class="w-4 h-4"></i>
                                <span>Masuk</span>
                            </a>

                            <button type="button" 
                                    @click="showGuestStoreModal = false" 
                                    class="btn-tendaku-secondary w-full py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold text-center text-darkbrown-700 hover:bg-wheat-100 transition">
                                Lanjut Tanpa Akun
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endguest
    </div>

</x-layouts.customer>
