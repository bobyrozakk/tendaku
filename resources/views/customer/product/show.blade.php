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

    <!-- Container Utama Detail Produk dengan Alpine State -->
    <div x-data="productBooking({
            dailyRate: {{ $dailyRate }},
            bookedDates: {{ $bookedDatesJson }},
            mainImage: '{{ $mainImage }}'
         })" 
         class="space-y-8 py-2">

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

            <!-- Kolom Kanan: Form Pilihan Tanggal & Kalkulasi Sewa (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku-lg sticky top-6">
                    
                    <!-- Header Harga per Hari -->
                    <div class="border-b border-wheat-200 pb-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-500">Tarif Sewa Peralatan</span>
                        <div class="flex items-baseline gap-1.5 mt-1">
                            <span class="text-3xl font-extrabold text-darkbrown-900 font-heading">
                                Rp {{ number_format($dailyRate, 0, ',', '.') }}
                            </span>
                            <span class="text-sm font-semibold text-darkbrown-600">/ hari</span>
                        </div>
                        <p class="text-[11px] text-avocado-700 mt-1 font-semibold flex items-center gap-1">
                            <span>✓ Tanpa Deposit Uang (Cukup Jaminan KTP Asli)</span>
                        </p>
                    </div>

                    <!-- Vendor Store Badge -->
                    <div class="py-3.5 px-4 rounded-xl bg-wheat-50 border border-wheat-200 my-4 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-lg bg-darkbrown-800 text-sunglow-300 flex items-center justify-center font-bold text-sm">
                                🏬
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-darkbrown-900">{{ $vendorName }}</h4>
                                <p class="text-[11px] text-darkbrown-600">Penyedia Terverifikasi Tendaku</p>
                            </div>
                        </div>
                        <span class="badge-avocado text-[10px]">Active</span>
                    </div>

                    <!-- Form Pemilih Tanggal Sewa -->
                    <form action="{{ url('/checkout') }}" method="GET" @submit="handleSubmit($event)" class="space-y-4">
                        <input type="hidden" name="master_item_id" value="{{ $currentItem->id ?? 1 }}" />

                        <div class="space-y-3">
                            <h3 class="text-xs font-extrabold uppercase text-darkbrown-800 tracking-wider flex items-center gap-1.5">
                                <span>📅 Pilih Tanggal Sewa & Cek Ketersediaan</span>
                            </h3>

                            <!-- Tanggal Mulai (Pickup) -->
                            <div>
                                <label for="pickup_date" class="block text-xs font-semibold text-darkbrown-700 mb-1">
                                    Tanggal Mulai (Pickup):
                                </label>
                                <input type="date" 
                                       id="pickup_date" 
                                       name="pickup_date" 
                                       x-model="pickupDate" 
                                       @change="validateDates()"
                                       :min="minDate"
                                       class="input-tendaku w-full text-sm py-2.5 px-3 rounded-xl border-wheat-300" 
                                       required 
                                />
                            </div>

                            <!-- Tanggal Selesai (Return) -->
                            <div>
                                <label for="return_date" class="block text-xs font-semibold text-darkbrown-700 mb-1">
                                    Tanggal Selesai (Return):
                                </label>
                                <input type="date" 
                                       id="return_date" 
                                       name="return_date" 
                                       x-model="returnDate" 
                                       @change="validateDates()"
                                       :min="pickupDate || minDate"
                                       class="input-tendaku w-full text-sm py-2.5 px-3 rounded-xl border-wheat-300" 
                                       required 
                                />
                            </div>
                        </div>

                        <!-- Panel Alert Real-time Status Validasi Tanggal Overlap -->
                        <div x-show="statusMessage" 
                             x-transition 
                             :class="{
                                 'bg-avocado-50 border-avocado-300 text-avocado-900': isValid && !isOverlapped,
                                 'bg-red-50 border-red-300 text-red-900': !isValid || isOverlapped,
                                 'bg-sunglow-50 border-sunglow-300 text-darkbrown-900': isChecking
                             }"
                             class="p-3.5 rounded-xl border text-xs space-y-1 font-medium">
                            <div class="flex items-center gap-2 font-bold">
                                <template x-if="isValid && !isOverlapped">
                                    <span>✅ Tanggal Tersedia!</span>
                                </template>
                                <template x-if="!isValid || isOverlapped">
                                    <span>⚠️ Perhatian</span>
                                </template>
                            </div>
                            <p x-text="statusMessage"></p>
                        </div>

                        <!-- Ringkasan Biaya & Durasi -->
                        <div x-show="isValid && !isOverlapped && durationDays > 0" x-transition class="p-4 rounded-xl bg-wheat-100 border border-wheat-200 space-y-2">
                            <div class="flex justify-between text-xs text-darkbrown-700">
                                <span>Durasi Sewa</span>
                                <span class="font-bold text-darkbrown-900" x-text="durationDays + ' Hari'"></span>
                            </div>
                            <div class="flex justify-between text-xs text-darkbrown-700">
                                <span>Tarif / Hari</span>
                                <span class="font-semibold text-darkbrown-900" x-text="'Rp ' + formatRupiah(dailyRate)"></span>
                            </div>
                            <div class="border-t border-wheat-300 pt-2 flex justify-between items-baseline">
                                <span class="text-xs font-bold text-darkbrown-900">Total Estimasi</span>
                                <span class="text-xl font-extrabold text-avocado-800 font-heading" x-text="'Rp ' + formatRupiah(totalCost)"></span>
                            </div>
                        </div>

                        <!-- Tombol Sewa Sekarang -->
                        <button type="submit" 
                                :disabled="!isValid || isOverlapped || durationDays <= 0"
                                :class="(!isValid || isOverlapped || durationDays <= 0) ? 'opacity-50 cursor-not-allowed bg-gray-400' : 'btn-tendaku-primary hover:shadow-lg'"
                                class="w-full py-3.5 px-6 rounded-xl font-extrabold text-sm text-center shadow-md transition-all flex items-center justify-center gap-2">
                            <span>🛒 Sewa Sekarang</span>
                        </button>
                    </form>

                    <!-- Jaminan Informasi -->
                    <div class="mt-4 pt-4 border-t border-wheat-200 text-center">
                        <p class="text-[11px] text-darkbrown-500 font-medium">
                            🔒 Pemesanan cepat tanpa deposit tunai. Fisik KTP asli diserahkan saat pengambilan unit di lokasi vendor.
                        </p>
                    </div>

                </div>
            </div>

        </div>

    </div>

    <!-- Script Logika Overlap & Validation Date (Alpine.js Component) -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productBooking', (config) => ({
                dailyRate: config.dailyRate || 45000,
                bookedDates: config.bookedDates || [],
                activeImage: config.mainImage,

                pickupDate: '',
                returnDate: '',
                minDate: new Date().toISOString().split('T')[0],

                isValid: true,
                isOverlapped: false,
                isChecking: false,
                statusMessage: 'Pilih tanggal mulai & selesai untuk mengecek ketersediaan unit.',
                durationDays: 0,
                totalCost: 0,

                setActiveImage(url) {
                    this.activeImage = url;
                },

                formatRupiah(number) {
                    return new Intl.NumberFormat('id-ID').format(number);
                },

                validateDates() {
                    if (!this.pickupDate || !this.returnDate) {
                        this.isValid = false;
                        this.durationDays = 0;
                        this.totalCost = 0;
                        this.statusMessage = 'Pilih kedua tanggal (mulai & selesai) untuk melanjutkan.';
                        return;
                    }

                    const start = new Date(this.pickupDate);
                    const end = new Date(this.returnDate);
                    const today = new Date(this.minDate);

                    if (start < today) {
                        this.isValid = false;
                        this.isOverlapped = false;
                        this.statusMessage = 'Tanggal mulai tidak boleh lebih awal dari hari ini.';
                        this.durationDays = 0;
                        return;
                    }

                    if (end < start) {
                        this.isValid = false;
                        this.isOverlapped = false;
                        this.statusMessage = 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.';
                        this.durationDays = 0;
                        return;
                    }

                    // Hitung durasi hari
                    const diffTime = Math.abs(end - start);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // Termasuk hari pickup & return
                    this.durationDays = diffDays;
                    this.totalCost = this.durationDays * this.dailyRate;

                    // Cek Overlap dengan tanggal yang sudah ter-booking
                    let overlapped = false;
                    let currentDate = new Date(start);

                    while (currentDate <= end) {
                        const dateString = currentDate.toISOString().split('T')[0];
                        if (this.bookedDates.includes(dateString)) {
                            overlapped = true;
                            break;
                        }
                        currentDate.setDate(currentDate.getDate() + 1);
                    }

                    if (overlapped) {
                        this.isValid = false;
                        this.isOverlapped = true;
                        this.statusMessage = 'Maaf, unit ini sudah ter-booking pada salah satu tanggal yang Anda pilih. Silakan pilih tanggal lain.';
                    } else {
                        this.isValid = true;
                        this.isOverlapped = false;
                        this.statusMessage = `Unit TERSEDIA pada tanggal tersebut! Durasi: ${this.durationDays} Hari.`;
                    }
                },

                handleSubmit(e) {
                    if (!this.isValid || this.isOverlapped || this.durationDays <= 0) {
                        e.preventDefault();
                        alert('Harap pilih tanggal sewa yang valid dan tersedia.');
                    }
                }
            }));
        });
    </script>
</x-layouts.customer>
