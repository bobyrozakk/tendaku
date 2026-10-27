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
    <div x-data="{ activeImage: '{{ $mainImage }}', setActiveImage(url) { this.activeImage = url; } }" class="space-y-8 py-2">

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
                            <span class="font-bold text-darkbrown-900">{{ $vendorName }}</span>
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

    </div>

</x-layouts.customer>
