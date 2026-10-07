<x-layouts.customer>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-xs font-semibold text-darkbrown-600 mb-2">
            <a href="{{ route('customer.catalog.index') }}" class="hover:text-avocado-700 transition flex items-center gap-1">
                <span>← Kembali ke Katalog</span>
            </a>
            <span>/</span>
            <span class="text-darkbrown-900 font-bold truncate">{{ $item->name ?? 'Detail Peralatan Outdoor' }}</span>
        </div>
        <h1 class="font-extrabold text-2xl md:text-3xl text-darkbrown-900 tracking-tight font-heading">
            {{ $item->name ?? 'Tenda Consina Magnum 4 Person Ultra' }}
        </h1>
    </x-slot>

    @php
        // Fallback data jika item tidak ditemukan di database
        $defaultItem = (object)[
            'id' => 1,
            'name' => 'Tenda Consina Magnum 4 Person Ultra',
            'description' => 'Tenda kemping kapasitas 4 orang dengan spesifikasi tinggi. Menggunakan konstruksi double layer berbahan Polyester PU 3000mm yang sangat efektif menahan hujan badai di pegunungan Indonesia. Dilengkapi vestibule (teras) luas untuk menyimpan perlengkapan memasak dan tas carrier.',
            'daily_rate' => 45000,
            'image_url' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1000&q=80',
            'category' => (object)['name' => 'Tenda & Canopy'],
            'vendor' => (object)[
                'store_name' => 'Tendaku Store Central',
                'address' => 'Jl. Outdoor Adventure No. 45, Malang',
                'phone' => '0812-3456-7890'
            ],
            'units' => collect([1, 2, 3, 4]),
            'specifications' => [
                'Kapasitas' => '4 - 5 Orang',
                'Material Layer Outer' => 'Polyester 210T PU3000mm Waterproof',
                'Material Floor' => 'PE Sheet 120g/m² Heavy Duty',
                'Dimensi' => '240cm x (210cm + 100cm Teras) x 135cm',
                'Berat Total' => '3.8 kg',
                'Kelengkapan' => 'Tas Tenda, 1 Outer, 1 Inner, Frame Alumunium, 12 Pasak, 4 Guyline',
            ]
        ];

        $currentItem = $item ?? $defaultItem;
        $dailyRate = is_numeric($currentItem->daily_rate) ? $currentItem->daily_rate : 45000;
        $vendorName = is_object($currentItem->vendor) ? $currentItem->vendor->store_name : 'Tendaku Store Central';
        $categoryName = is_object($currentItem->category) ? $currentItem->category->name : 'Tenda & Canopy';
        
        $mainImage = $currentItem->image_url ?? 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1000&q=80';
        $galleryImages = [
            $mainImage,
            'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1526772662000-3f88f10405ff?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1517824806704-9040b037703b?auto=format&fit=crop&w=800&q=80',
        ];

        $bookedDatesJson = json_encode($bookedDates ?? []);
    @endphp

    <!-- Container Utama Detail Produk -->
    <div x-data="{ activeImage: '{{ $mainImage }}', setActiveImage(url) { this.activeImage = url; } }" class="space-y-8 py-2">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Kolom Kiri: Galeri Foto & Spesifikasi Peralatan (8 cols) -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Galeri Foto Interaktif -->
                <div class="card-tendaku rounded-2xl bg-white p-4 border border-wheat-200 overflow-hidden shadow-tendaku">
                    <!-- Foto Utama -->
                    <div class="relative aspect-[4/3] rounded-xl overflow-hidden bg-wheat-100 border border-wheat-200">
                        <img :src="activeImage" 
                             alt="{{ $currentItem->name }}" 
                             class="w-full h-full object-cover transition-all duration-300" 
                             id="mainProductImage"
                        />
                        <div class="absolute top-4 left-4 flex gap-2">
                            <span class="px-3 py-1 rounded-lg text-xs font-extrabold bg-darkbrown-900/90 text-wheat-200 backdrop-blur-md">
                                {{ $categoryName }}
                            </span>
                            <span class="px-3 py-1 rounded-lg text-xs font-bold bg-avocado-600 text-white shadow-md">
                                Jaminan KTP (Tanpa Deposit)
                            </span>
                        </div>
                    </div>

                    <!-- Thumbnail Switcher -->
                    <div class="grid grid-cols-4 gap-3 mt-4">
                        @foreach($galleryImages as $index => $imgUrl)
                            <button type="button" 
                                    @click="setActiveImage('{{ $imgUrl }}')" 
                                    :class="activeImage === '{{ $imgUrl }}' ? 'ring-2 ring-avocado-500 border-transparent scale-[1.02]' : 'opacity-70 hover:opacity-100 border-wheat-200'"
                                    class="relative aspect-[4/3] rounded-lg overflow-hidden border bg-wheat-50 transition-all">
                                <img src="{{ $imgUrl }}" alt="Thumbnail {{ $index + 1 }}" class="w-full h-full object-cover"/>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Deskripsi & Fitur Utama -->
                <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku space-y-4">
                    <h2 class="text-lg font-extrabold text-darkbrown-900 border-b border-wheat-200 pb-3 font-heading flex items-center gap-2">
                        <span>📝 Deskripsi Peralatan</span>
                    </h2>
                    <p class="text-sm text-darkbrown-700 leading-relaxed">
                        {{ $currentItem->description ?? $defaultItem->description }}
                    </p>

                    <!-- Jaminan Kebersihan & Layanan Vendor -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                        <div class="p-3 rounded-xl bg-avocado-50 border border-avocado-200 flex items-start gap-2.5">
                            <span class="text-lg">✨</span>
                            <div>
                                <h4 class="text-xs font-bold text-avocado-900">Steril & Higienis</h4>
                                <p class="text-[11px] text-avocado-800">Dicuci dan disinfeksi pasca setiap penyewaan.</p>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-sunglow-50 border border-sunglow-200 flex items-start gap-2.5">
                            <span class="text-lg">🪪</span>
                            <div>
                                <h4 class="text-xs font-bold text-darkbrown-900">Jaminan KTP Asli</h4>
                                <p class="text-[11px] text-darkbrown-700">Tidak memerlukan uang deposit jaminan.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Spesifikasi Detail -->
                <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku space-y-4">
                    <h2 class="text-lg font-extrabold text-darkbrown-900 border-b border-wheat-200 pb-3 font-heading flex items-center gap-2">
                        <span>📐 Spesifikasi Teknis</span>
                    </h2>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-xs">
                        <div class="flex justify-between py-1.5 border-b border-wheat-100">
                            <span class="text-darkbrown-600 font-medium">Kapasitas Orang</span>
                            <span class="font-bold text-darkbrown-900">4 - 5 Orang</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-wheat-100">
                            <span class="text-darkbrown-600 font-medium">Ketahanan Air (Outer)</span>
                            <span class="font-bold text-avocado-800">PU 3000mm Waterproof</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-wheat-100">
                            <span class="text-darkbrown-600 font-medium">Konstruksi Layer</span>
                            <span class="font-bold text-darkbrown-900">Double Layer + Vestibule</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-wheat-100">
                            <span class="text-darkbrown-600 font-medium">Berat Total Paket</span>
                            <span class="font-bold text-darkbrown-900">3.8 kg</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-wheat-100 sm:col-span-2">
                            <span class="text-darkbrown-600 font-medium">Kelengkapan</span>
                            <span class="font-bold text-darkbrown-900 text-right">Tas, Outer, Inner, Frame Alumunium, 12 Pasak</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Kolom Kanan: Livewire ProductBooking Component -->
            <div class="lg:col-span-5 space-y-6">
                <livewire:customer.product-booking :product="$currentItem" />
            </div>

        </div>

    </div>


</x-layouts.customer>
