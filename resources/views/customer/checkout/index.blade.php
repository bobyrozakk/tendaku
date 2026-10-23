<x-layouts.customer>
    <x-slot name="title">
        Checkout Booking #{{ $booking['booking_code'] }} - Tendaku
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs text-darkbrown-500 mb-1.5">
                    <a href="{{ route('home') }}" class="hover:text-avocado-600 transition">Beranda</a>
                    <span class="text-wheat-400">/</span>
                    <a href="{{ route('customer.catalog.index') }}" class="hover:text-avocado-600 transition">Katalog</a>
                    <span class="text-wheat-400">/</span>
                    <span class="text-darkbrown-800 font-semibold">Booking</span>
                </nav>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-800 tracking-tight">
                        Checkout & Pembayaran
                    </h1>
                </div>
            </div>

            <!-- Expiration Countdown Badge -->
            <div class="flex items-center gap-2.5 px-4 py-2.5 bg-wheat-100/90 rounded-2xl border border-wheat-300/80 shadow-tendaku-sm self-start sm:self-auto">
                <div class="w-8 h-8 rounded-xl bg-sunglow-300 text-darkbrown-800 flex items-center justify-center shrink-0">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-darkbrown-500 block leading-none">Batas Waktu Bayar</span>
                    <span class="text-xs font-bold text-darkbrown-800">{{ $booking['expires_at'] }}</span>
                </div>
            </div>
        </div>
    </x-slot>

    <!-- Interactive Alpine.js Container for Realtime Calculation & Method Switch -->
    <div x-data="{
        isLoggedIn: {{ $isLoggedIn ? 'true' : 'false' }},
        pickupMethod: '{{ $booking['pickup_method'] }}',
        selectedChannel: 'qris',
        subtotal: {{ $booking['subtotal'] }},
        deposit: {{ $booking['deposit'] }},
        deliveryFeeAmount: 25000,
        get deliveryFee() {
            return (this.isLoggedIn && this.pickupMethod === 'delivery') ? this.deliveryFeeAmount : 0;
        },
        get grandTotal() {
            return this.subtotal + this.deposit + this.deliveryFee;
        },
        formatRupiah(num) {
            return 'Rp ' + (num || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },
        payWithMidtrans() {
            // Validasi aturan: Opsi delivery hanya diizinkan untuk user login
            if (this.pickupMethod === 'delivery' && !this.isLoggedIn) {
                window.location.href = '{{ route('login') }}';
                return;
            }

            // Panggil API Gateway Midtrans (Snap Ready)
            window.tendakuMidtrans.pay({
                bookingCode: '{{ $booking['booking_code'] }}',
                grossAmount: this.grandTotal,
                pickupMethod: this.pickupMethod,
                selectedChannel: this.selectedChannel,
                redirectUrl: '{{ route('booking.status', $booking['booking_code']) }}',
                formAction: '{{ route('checkout.store') }}'
            });
        }
    }" class="py-2 sm:py-4">

        <!-- Main Checkout Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">

            <!-- LEFT COLUMN: Booking Details (8 Cols) -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-6">

                <!-- 1. System Rule Notice: Guest vs Logged In Authentication Status -->
                @guest
                <div class="p-4 sm:p-5 rounded-2xl bg-wheat-100 border border-wheat-300 text-darkbrown-800 shadow-tendaku-sm">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-goldenrod-500 text-white flex items-center justify-center shrink-0 font-extrabold text-sm shadow-sm">
                                !
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-sm text-darkbrown-900">Pemesanan Tanpa Login (Mode Tamu)</span>
                                </div>
                                <p class="text-xs text-darkbrown-700 leading-relaxed">
                                    Butuh barang diantar ke lokasi? Masuk atau daftar akun untuk menikmati layanan delivery.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                            <a href="{{ route('login') }}" class="px-3.5 py-2 rounded-xl text-xs font-bold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 transition shadow-sm">
                                Masuk (Login)
                            </a>
                            <a href="{{ route('register') }}" class="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-avocado-500 hover:bg-avocado-600 active:bg-avocado-700 transition shadow-sm">
                                Daftar Akun
                            </a>
                        </div>
                    </div>
                </div>
                @else
                <div class="p-4 rounded-2xl bg-avocado-50 border border-avocado-200 text-xs sm:text-sm text-avocado-950 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-tendaku-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-avocado-100 text-avocado-700 flex items-center justify-center font-bold">
                            ✓
                        </div>
                        <div>
                            <span class="font-bold text-avocado-900 block">Akun Terverifikasi</span>
                            <span class="text-xs text-avocado-800">Login sebagai <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}). Anda dapat memilih Ambil di Tempat maupun Layanan Pengantaran.</span>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-white text-avocado-800 border border-avocado-300 shrink-0 self-start sm:self-center">
                        Delivery Aktif
                    </span>
                </div>

                <!-- Nomor WhatsApp tujuan pengingat pengembalian (diambil dari profil) -->
                <div class="p-4 rounded-2xl bg-white border border-wheat-200/90 shadow-tendaku-sm text-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <span class="font-bold text-darkbrown-900 block">Pengingat Pengembalian via WhatsApp</span>
                            @if (filled(auth()->user()->phone))
                                <span class="text-darkbrown-500">Pengingat H-1 & hari pengembalian dikirim ke nomor profil Anda:</span>
                                <span class="font-mono font-bold text-avocado-700">{{ auth()->user()->phone }}</span>
                            @else
                                <span class="text-red-600 font-semibold">Nomor WhatsApp belum terisi di profil. Pengingat pengembalian otomatis tidak dapat dikirim — lengkapi nomor pada halaman profil.</span>
                            @endif
                        </div>
                        <a href="{{ route('profile') }}" class="shrink-0 self-start sm:self-center px-3 py-1.5 rounded-lg text-[11px] font-bold text-darkbrown-800 bg-white border border-wheat-300 hover:bg-wheat-100 transition">
                            Ubah di Profil
                        </a>
                    </div>
                </div>
                @endguest

                <!-- Ringkasan error validasi -->
                @if ($errors->any())
                    <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-xs text-red-800 shadow-tendaku-sm">
                        <span class="font-bold block mb-1">Checkout belum dapat diproses:</span>
                        <ul class="list-disc pl-4 space-y-0.5">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- 2. Booking Header Identifier -->
                <div class="bg-white rounded-2xl border border-wheat-200/90 p-5 sm:p-6 shadow-tendaku-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-wheat-200/70">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-400">Kode Booking Resmi</span>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="font-mono text-xl sm:text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                                    #{{ $booking['booking_code'] }}
                                </span>
                            </div>
                        </div>

                        <div class="text-left sm:text-right">
                            <span class="text-xs font-medium text-darkbrown-500 block">Waktu Pemesanan:</span>
                            <span class="text-xs font-bold text-darkbrown-800">{{ $booking['created_at'] }}</span>
                        </div>
                    </div>

                    <div class="mt-4 flex items-start gap-3 p-3.5 rounded-xl bg-wheat-50 border border-wheat-200/80 text-xs text-darkbrown-700 leading-relaxed">
                        <i data-lucide="info" class="w-5 h-5 text-goldenrod-600 shrink-0 mt-0.5"></i>
                        <div>
                            Peralatan sewa telah diamankan selama <strong class="text-darkbrown-900">12 jam</strong>. Selesaikan transaksi sebelum batas waktu berakhir agar unit tidak dialihkan ke penyewa lain.
                        </div>
                    </div>
                </div>

                <!-- 3. Metode Pengambilan & Logistik (Business Rule Enforced) -->
                <div class="bg-white rounded-2xl border border-wheat-200/90 p-5 sm:p-6 shadow-tendaku-sm space-y-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-goldenrod-100 text-goldenrod-800 flex items-center justify-center font-bold">
                                <i data-lucide="package" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-darkbrown-800">Metode Pengambilan Barang</h3>
                                <p class="text-xs text-darkbrown-500">Pilih bagaimana peralatan camping akan diserahkan kepada Anda</p>
                            </div>
                        </div>
                    </div>

                    <!-- Method Option Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <!-- OPTION 1: Ambil di Tempat (Self-Pickup) - Available for EVERYONE -->
                        <div @click="pickupMethod = 'self_pickup'"
                            :class="pickupMethod === 'self_pickup' ? 'border-avocado-500 bg-[#FAF6ED] ring-2 ring-avocado-400/40' : 'border-wheat-200 bg-white hover:border-wheat-400'"
                            class="p-4 rounded-2xl border transition duration-200 cursor-pointer flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="ui_pickup_method" value="self_pickup"
                                            :checked="pickupMethod === 'self_pickup'"
                                            @change="pickupMethod = 'self_pickup'"
                                            class="text-avocado-600 focus:ring-avocado-500 border-wheat-400">
                                        <span class="font-extrabold text-sm text-darkbrown-900">Ambil di Tempat</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                        Gratis Ongkir
                                    </span>
                                </div>
                                <p class="text-xs text-darkbrown-600 leading-relaxed">
                                    Ambil sendiri peralatan langsung ke toko/workshop vendor tanpa biaya antar tambahan.
                                </p>
                            </div>

                            <div class="pt-2 border-t border-wheat-200/80 text-[11px] text-darkbrown-500 space-y-1">
                                <span class="font-semibold text-darkbrown-800 block">Tersedia untuk: Tamu & Member</span>
                                <span>Outlet: {{ $booking['vendor']['city'] }}</span>
                            </div>
                        </div>

                        <!-- OPTION 2: Diantar ke Lokasi (Delivery) - Available ONLY FOR LOGGED IN USERS -->
                        @if ($isLoggedIn)
                        <!-- Logged in state: fully active and selectable -->
                        <div @click="pickupMethod = 'delivery'"
                            :class="pickupMethod === 'delivery' ? 'border-goldenrod-500 bg-[#FAF6ED] ring-2 ring-goldenrod-400/40' : 'border-wheat-200 bg-white hover:border-wheat-400'"
                            class="p-4 rounded-2xl border transition duration-200 cursor-pointer flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="ui_pickup_method" value="delivery"
                                            :checked="pickupMethod === 'delivery'"
                                            @change="pickupMethod = 'delivery'"
                                            class="text-goldenrod-600 focus:ring-goldenrod-500 border-wheat-400">
                                        <span class="font-extrabold text-sm text-darkbrown-900">Diantar ke Lokasi</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-goldenrod-100 text-goldenrod-900 border border-goldenrod-200">
                                        Ongkir Rp 25.000
                                    </span>
                                </div>
                                <p class="text-xs text-darkbrown-600 leading-relaxed">
                                    Peralatan diantar oleh kurir resmi vendor langsung ke alamat rumah atau titik kumpul trip Anda.
                                </p>
                            </div>

                            <div class="pt-2 border-t border-wheat-200/80 text-[11px] text-darkbrown-500 space-y-1">
                                <span class="font-semibold text-avocado-700 block">✓ Akun Terverifikasi</span>
                                <span>Area cakupan: Malang Raya & Batu</span>
                            </div>
                        </div>
                        @else
                        <!-- Guest state: locked/restricted with clear login requirement -->
                        <div class="p-4 rounded-2xl border border-dashed border-wheat-300 bg-wheat-100/50 opacity-90 flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" disabled class="text-gray-400 border-gray-300 cursor-not-allowed">
                                        <span class="font-bold text-sm text-darkbrown-700 line-through">Diantar ke Lokasi</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-wheat-200 text-darkbrown-800 border border-wheat-300">
                                        🔒 Wajib Login
                                    </span>
                                </div>
                                <p class="text-xs text-darkbrown-600 leading-relaxed">
                                    Layanan pengantaran (delivery) mengharuskan akun terdaftar untuk verifikasi alamat dan serah terima kurir.
                                </p>
                            </div>

                            <div class="pt-2 border-t border-wheat-200 flex items-center justify-between text-[11px]">
                                <span class="text-darkbrown-500">Ingin diantar?</span>
                                <a href="{{ route('login') }}" class="font-bold text-goldenrod-800 hover:text-goldenrod-900 underline underline-offset-2">
                                    Masuk / Daftar Akun →
                                </a>
                            </div>
                        </div>
                        @endif

                    </div>

                    <!-- Dynamic Details Box Based on Selected Method -->
                    <!-- Detail Ambil di Tempat -->
                    <div x-show="pickupMethod === 'self_pickup'" x-cloak class="p-4 rounded-xl bg-[#FAF6ED] border border-wheat-200/90 space-y-3 text-xs">
                        <div class="flex items-center justify-between border-b border-wheat-200/70 pb-2">
                            <span class="font-bold text-darkbrown-900 text-sm flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-avocado-500"></span>
                                Lokasi Pengambilan Toko Vendor:
                            </span>
                        </div>

                        <div class="space-y-1">
                            <div class="font-extrabold text-darkbrown-900">{{ $booking['vendor']['pickup_location'] }}</div>
                            <div class="text-darkbrown-600">{{ $booking['vendor']['address'] }}</div>
                            <div class="text-darkbrown-500 pt-1">
                                <strong>Jam Operasional:</strong> {{ $booking['vendor']['operational_hours'] }}
                            </div>
                        </div>

                        @guest
                        <!-- Guest Identity Input Form for Store Pickup -->
                        <div class="pt-3 border-t border-wheat-200/80 space-y-2">
                            <span class="font-bold text-darkbrown-900 block">Data Identitas Tamu untuk Serah Terima:</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="guest_name" class="block text-[11px] font-semibold text-darkbrown-600 mb-1">Nama Lengkap Tamu</label>
                                    <input type="text" id="guest_name" name="guest_name" form="checkout-payment-form"
                                        value="{{ old('guest_name') }}"
                                        class="w-full text-xs rounded-lg border-wheat-300 bg-white text-darkbrown-800 focus:border-avocado-500 focus:ring-1 focus:ring-avocado-500 @error('guest_name') border-red-400 @enderror"
                                        placeholder="Nama sesuai KTP" required>
                                    @error('guest_name')
                                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="guest_phone" class="block text-[11px] font-semibold text-darkbrown-600 mb-1">No. WhatsApp Aktif</label>
                                    <input type="tel" id="guest_phone" name="guest_phone" form="checkout-payment-form"
                                        value="{{ old('guest_phone') }}"
                                        class="w-full text-xs rounded-lg border-wheat-300 bg-white text-darkbrown-800 focus:border-avocado-500 focus:ring-1 focus:ring-avocado-500 @error('guest_phone') border-red-400 @enderror"
                                        placeholder="08xxxxxxxxxx" inputmode="numeric" maxlength="20" required>
                                    @error('guest_phone')
                                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div>
                                <label for="guest_id_number" class="block text-[11px] font-semibold text-darkbrown-600 mb-1">NIK (Nomor Induk Kependudukan)</label>
                                <input type="text" id="guest_id_number" name="guest_id_number" form="checkout-payment-form"
                                    value="{{ old('guest_id_number') }}"
                                    class="w-full text-xs rounded-lg border-wheat-300 bg-white text-darkbrown-800 focus:border-avocado-500 focus:ring-1 focus:ring-avocado-500 @error('guest_id_number') border-red-400 @enderror"
                                    placeholder="16 digit sesuai KTP" inputmode="numeric" maxlength="16" required>
                                @error('guest_id_number')
                                    <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <p class="text-[11px] text-darkbrown-500">
                                *Wajib menunjukkan KTP asli dan bukti nomor booking saat pengambilan unit di outlet.
                            </p>
                            <p class="text-[11px] text-avocado-700">
                                Nomor WhatsApp ini dipakai untuk mengirim pengingat pengembalian barang (H-1 & hari pengembalian).
                            </p>
                        </div>
                        @endguest
                    </div>

                    <!-- Detail Diantar ke Lokasi (Hanya muncul jika Logged In dan memilih delivery) -->
                    <div x-show="pickupMethod === 'delivery'" x-cloak class="p-4 rounded-xl bg-[#FAF6ED] border border-wheat-200/90 space-y-3 text-xs">
                        <div class="flex items-center justify-between border-b border-wheat-200/70 pb-2">
                            <span class="font-bold text-darkbrown-900 text-sm flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-goldenrod-500"></span>
                                Alamat Tujuan Pengantaran (Kurir Vendor):
                            </span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-white text-darkbrown-700 border border-wheat-300">
                                Biaya Kurir: Rp 25.000
                            </span>
                        </div>

                        <div class="space-y-1">
                            <p class="text-darkbrown-700 leading-relaxed font-medium">{{ $booking['delivery_address'] }}</p>
                            <p class="text-[11px] text-darkbrown-500 pt-1">
                                <strong class="text-goldenrod-800">Catatan Khusus Pengantaran:</strong> {{ $booking['delivery_notes'] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 4. Vendor Partner Information -->
                <div class="bg-white rounded-2xl border border-wheat-200/90 p-5 sm:p-6 shadow-tendaku-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-avocado-100 text-avocado-700 flex items-center justify-center">
                                <i data-lucide="store" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-darkbrown-800">Vendor Penyedia Alat</h3>
                                <p class="text-xs text-darkbrown-500">Peralatan camping disediakan dan disiapkan oleh mitra resmi</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-[#FAF6ED] border border-wheat-200/80 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-darkbrown-800 text-wheat-200 flex items-center justify-center font-bold text-lg shrink-0">
                                    M
                                </div>
                                <div>
                                    <h4 class="font-extrabold text-darkbrown-900 text-sm sm:text-base">
                                        {{ $booking['vendor']['name'] }}
                                    </h4>
                                    <div class="flex items-center gap-3 text-xs text-darkbrown-600 mt-0.5">
                                        <span class="inline-flex items-center gap-1 font-semibold text-goldenrod-700">
                                            ★ {{ $booking['vendor']['rating'] }} <span class="text-darkbrown-500 font-normal">({{ $booking['vendor']['total_reviews'] }} ulasan)</span>
                                        </span>
                                        <span>•</span>
                                        <span class="text-darkbrown-600 font-medium">{{ $booking['vendor']['city'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="shrink-0 self-start sm:self-center">
                                <a href="tel:{{ $booking['vendor']['phone'] }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-darkbrown-800 bg-white border border-wheat-300 hover:bg-wheat-100 transition shadow-sm">
                                    <i data-lucide="phone" class="w-3.5 h-3.5 text-avocado-600"></i>
                                    {{ $booking['vendor']['phone'] }}
                                </a>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-wheat-300/60 flex items-start gap-2 text-xs text-darkbrown-600">
                            <i data-lucide="map-pin" class="w-4 h-4 text-darkbrown-400 shrink-0 mt-0.5"></i>
                            <span><strong class="text-darkbrown-800">Alamat Workshop/Store:</strong> {{ $booking['vendor']['address'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- 5. Jadwal Rental (Tanggal Ambil & Tanggal Kembali) -->
                <div class="bg-white rounded-2xl border border-wheat-200/90 p-5 sm:p-6 shadow-tendaku-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-goldenrod-100 text-goldenrod-800 flex items-center justify-center">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-darkbrown-800">Jadwal Rental</h3>
                            <p class="text-xs text-darkbrown-500">Waktu pengambilan dan pengembalian unit</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Tanggal Ambil -->
                        <div class="p-4 rounded-xl bg-[#FAF6ED] border border-wheat-200/90">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-avocado-700">Tanggal Ambil</span>
                            </div>
                            <div class="text-base font-extrabold text-darkbrown-900">
                                {{ $booking['pickup_date'] }}
                            </div>
                            <div class="text-xs text-darkbrown-600 font-semibold mt-0.5 flex items-center gap-1.5">
                                <i data-lucide="clock" class="w-3.5 h-3.5 text-darkbrown-400"></i>
                                Pukul {{ $booking['pickup_time'] }}
                            </div>
                        </div>

                        <!-- Tanggal Kembali -->
                        <div class="p-4 rounded-xl bg-[#FAF6ED] border border-wheat-200/90">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-goldenrod-700">Tanggal Kembali</span>
                            </div>
                            <div class="text-base font-extrabold text-darkbrown-900">
                                {{ $booking['return_date'] }}
                            </div>
                            <div class="text-xs text-darkbrown-600 font-semibold mt-0.5 flex items-center gap-1.5">
                                <i data-lucide="clock" class="w-3.5 h-3.5 text-darkbrown-400"></i>
                                Maksimal {{ $booking['return_time'] }}
                            </div>
                        </div>
                    </div>

                    <!-- Rental Duration Pill -->
                    <div class="mt-4 p-3 rounded-xl bg-wheat-100/70 border border-wheat-200 flex items-center justify-between text-xs">
                        <span class="text-darkbrown-600 font-medium">Total Durasi Pemakaian:</span>
                        <span class="font-extrabold text-darkbrown-900 bg-white px-2.5 py-1 rounded-lg border border-wheat-300">
                            {{ $booking['rental_days'] }} Hari (2 Malam Camping)
                        </span>
                    </div>
                </div>

                <!-- 6. Daftar Barang yang Disewa (Equipment List) -->
                <div class="bg-white rounded-2xl border border-wheat-200/90 p-5 sm:p-6 shadow-tendaku-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-sunglow-200 text-darkbrown-800 flex items-center justify-center font-bold">
                                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-darkbrown-800">Daftar Peralatan Camping</h3>
                                <p class="text-xs text-darkbrown-500">Rincian unit yang disewa untuk trip petualangan Anda</p>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table / Stacked Card List -->
                    <div class="divide-y divide-wheat-200/70">
                        @foreach ($booking['items'] as $item)
                        <div class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-3.5 flex-1 min-w-0">
                                <!-- Gear Icon Visual -->
                                <div class="w-12 h-12 rounded-xl bg-wheat-100 border border-wheat-200/90 flex items-center justify-center shrink-0 text-darkbrown-800 shadow-sm">
                                    @if ($item['icon'] === 'tent')
                                    <i data-lucide="tent" class="w-6 h-6 text-avocado-700"></i>
                                    @elseif ($item['icon'] === 'sleeping_bag')
                                    <i data-lucide="bed" class="w-6 h-6 text-goldenrod-700"></i>
                                    @elseif ($item['icon'] === 'stove')
                                    <i data-lucide="flame" class="w-6 h-6 text-sunglow-700"></i>
                                    @else
                                    <i data-lucide="flashlight" class="w-6 h-6 text-avocado-700"></i>
                                    @endif
                                </div>

                                <div class="space-y-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="font-bold text-darkbrown-900 text-sm sm:text-base leading-snug">
                                            {{ $item['name'] }}
                                        </h4>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-wheat-100 text-darkbrown-700 border border-wheat-200">
                                            {{ $item['category'] }}
                                        </span>
                                    </div>

                                    <p class="text-xs text-darkbrown-500 line-clamp-1">
                                        {{ $item['specs'] }}
                                    </p>

                                    <div class="flex flex-wrap items-center gap-2 pt-1 text-xs text-darkbrown-600">
                                        <span class="font-semibold text-darkbrown-800">
                                            Rp {{ number_format($item['price_per_day'], 0, ',', '.') }}<span class="font-normal text-darkbrown-500">/hari</span>
                                        </span>
                                        <span>•</span>
                                        <span class="bg-wheat-100 px-2 py-0.5 rounded font-bold text-darkbrown-800">
                                            {{ $item['qty'] }} Unit
                                        </span>
                                        <span>•</span>
                                        <span class="text-darkbrown-500">
                                            {{ $booking['rental_days'] }} Hari
                                        </span>
                                        @if ($item['deposit_per_unit'] > 0)
                                        <span>•</span>
                                        <span class="text-[11px] font-semibold text-goldenrod-800 bg-goldenrod-50 px-2 py-0.5 rounded border border-goldenrod-200">
                                            Deposit: Rp {{ number_format($item['deposit_per_unit'] * $item['qty'], 0, ',', '.') }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="text-left sm:text-right shrink-0 pt-2 sm:pt-0 self-end sm:self-center">
                                <span class="text-[10px] text-darkbrown-400 block font-bold uppercase">Subtotal Sewa</span>
                                <span class="text-base sm:text-lg font-extrabold text-darkbrown-900">
                                    Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- 7. Deposit & Security Terms Policy -->
                <div class="bg-white rounded-2xl border border-wheat-200/90 p-5 sm:p-6 shadow-tendaku-sm">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-avocado-100 text-avocado-800 flex items-center justify-center shrink-0">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <div class="space-y-1.5 text-xs text-darkbrown-700">
                            <h4 class="font-bold text-darkbrown-900 text-sm">
                                Ketentuan Uang Jaminan (Deposit) 100% Refundable
                            </h4>
                            <p class="leading-relaxed text-darkbrown-600">
                                Deposit sebesar <strong class="text-darkbrown-900">Rp {{ number_format($booking['deposit'], 0, ',', '.') }}</strong> berfungsi sebagai uang jaminan keutuhan fisik unit selama masa penyewaan. Uang jaminan ini akan ditransfer kembali secara utuh ke rekening/e-wallet Anda segera setelah unit diperiksa dan diterima kembali oleh vendor.
                            </p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: Order Summary & Payment Button (4-5 Cols Sticky) -->
            <div class="lg:col-span-5 xl:col-span-4 space-y-6 lg:sticky lg:top-24">

                <!-- Order Summary Card -->
                <div class="bg-white rounded-2xl border border-wheat-200/90 p-5 sm:p-6 shadow-tendaku-sm">
                    <h3 class="text-lg font-extrabold text-darkbrown-800 tracking-tight mb-4 pb-3 border-b border-wheat-200">
                        Ringkasan Pembayaran
                    </h3>

                    <!-- Price Breakdown List with Realtime Alpine.js Reactive Bindings -->
                    <div class="space-y-3 text-xs sm:text-sm">
                        <!-- Subtotal Sewa -->
                        <div class="flex items-center justify-between text-darkbrown-600">
                            <span>Subtotal Sewa Alat ({{ $booking['rental_days'] }} Hari)</span>
                            <span class="font-bold text-darkbrown-800">
                                Rp {{ number_format($booking['subtotal'], 0, ',', '.') }}
                            </span>
                        </div>

                        <!-- Biaya Pengiriman (Dynamically Recalculated) -->
                        <div class="flex items-center justify-between text-darkbrown-600">
                            <div>
                                <span class="flex items-center gap-1 font-medium">
                                    Ongkir Pengambilan
                                </span>
                                <span x-show="pickupMethod === 'self_pickup'" class="block text-[10px] text-avocado-700 font-semibold">
                                    Ambil di Tempat (Gratis)
                                </span>
                                <span x-show="pickupMethod === 'delivery'" class="block text-[10px] text-goldenrod-700 font-semibold">
                                    Kurir Vendor ke Lokasi
                                </span>
                            </div>
                            <span x-text="pickupMethod === 'delivery' ? formatRupiah(deliveryFee) : 'Rp 0'" class="font-bold text-darkbrown-800">
                                {{ $booking['pickup_method'] === 'delivery' ? 'Rp '.number_format($booking['delivery_fee'], 0, ',', '.') : 'Rp 0' }}
                            </span>
                        </div>

                        <!-- Uang Jaminan / Deposit -->
                        <div class="flex items-center justify-between text-darkbrown-600">
                            <div>
                                <span>Uang Jaminan (Deposit)</span>
                                <span class="block text-[10px] text-goldenrod-700 font-semibold">100% Refundable saat retur</span>
                            </div>
                            <span class="font-bold text-darkbrown-800">
                                Rp {{ number_format($booking['deposit'], 0, ',', '.') }}
                            </span>
                        </div>

                        <!-- Diskon Promosi -->
                        @if ($booking['discount'] > 0)
                        <div class="flex items-center justify-between text-avocado-700">
                            <span>Diskon Promosi</span>
                            <span class="font-bold">
                                - Rp {{ number_format($booking['discount'], 0, ',', '.') }}
                            </span>
                        </div>
                        @endif

                        <!-- Grand Total Highlight (Reactive to Method) -->
                        <div class="pt-4 border-t-2 border-wheat-200/90 mt-4">
                            <div class="flex items-baseline justify-between mb-1">
                                <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-darkbrown-700">
                                    Total Pembayaran
                                </span>
                                <span x-text="formatRupiah(grandTotal)" class="text-xl sm:text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                                    Rp {{ number_format($booking['grand_total'], 0, ',', '.') }}
                                </span>
                            </div>
                            <p class="text-[11px] text-darkbrown-500">
                                Termasuk {{ count($booking['items']) }} unit alat, metode penyerahan terpilih, & deposit jaminan.
                            </p>
                        </div>
                    </div>

                    <!-- Payment Method Selector -->
                    <div class="mt-6 pt-5 border-t border-wheat-200">
                        <div class="flex items-center justify-between mb-3">
                            <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700">
                                Pilih Saluran Pembayaran
                            </label>
                            <span class="text-[10px] font-bold text-darkbrown-500 uppercase tracking-widest bg-wheat-100 px-2 py-0.5 rounded border border-wheat-200">
                                Midtrans Gateway
                            </span>
                        </div>

                        <div class="space-y-2.5">
                            @foreach ($booking['payment_methods'] as $index => $channel)
                            <label class="flex items-start gap-3 p-3 rounded-xl border border-wheat-300/80 bg-[#FAF6ED] hover:bg-wheat-100/70 hover:border-goldenrod-400 cursor-pointer transition select-none">
                                <input type="radio" name="checkout_channel" value="{{ $channel['id'] }}" x-model="selectedChannel" class="mt-1 text-avocado-600 focus:ring-avocado-500 border-wheat-400">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="font-bold text-xs sm:text-sm text-darkbrown-900">{{ $channel['name'] }}</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-white text-darkbrown-700 border border-wheat-300 shrink-0">
                                            {{ $channel['badge'] }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-darkbrown-500 mt-0.5 line-clamp-1">{{ $channel['desc'] }}</p>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- FORM & TOMBOL BAYAR SEKARANG -->
                    <div class="mt-6 pt-2">
                        <form id="checkout-payment-form" action="{{ route('checkout.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="booking_code" value="{{ $booking['booking_code'] }}">
                            <input type="hidden" name="pickup_method" :value="pickupMethod">
                            <input type="hidden" name="amount" :value="grandTotal">
                            <input type="hidden" name="payment_channel" :value="selectedChannel">

                            <button
                                type="button"
                                x-on:click="payWithMidtrans()"
                                class="w-full py-4 px-6 rounded-xl font-extrabold text-base text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 shadow-tendaku hover:shadow-tendaku-lg transition duration-200 flex items-center justify-center gap-2 group cursor-pointer focus:outline-none focus:ring-2 focus:ring-goldenrod-500 focus:ring-offset-2"
                            >
                                <i data-lucide="lock" class="w-5 h-5 text-darkbrown-900"></i>
                                <span>Bayar Sekarang</span>
                                <span x-text="formatRupiah(grandTotal)" class="font-mono text-sm font-black px-2 py-0.5 rounded-lg bg-darkbrown-800 text-sunglow-300 ml-1">
                                    Rp {{ number_format($booking['grand_total'], 0, ',', '.') }}
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- CS Support Help Box -->
                <div class="bg-white rounded-2xl border border-wheat-200/90 p-5 shadow-tendaku-sm space-y-2">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-wheat-100 text-darkbrown-800 flex items-center justify-center shrink-0">
                            <i data-lucide="life-buoy" class="w-4 h-4 text-goldenrod-700"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-xs sm:text-sm text-darkbrown-800">Pusat Bantuan Booking</h4>
                            <p class="text-[11px] text-darkbrown-500">Ada kendala pembayaran atau ubah jadwal rental?</p>
                        </div>
                    </div>
                    <div class="pt-2 flex items-center justify-between text-xs text-darkbrown-600 border-t border-wheat-200/70">
                        <span>WhatsApp CS Tendaku:</span>
                        <a href="https://wa.me/6281234567890" target="_blank" class="font-bold text-avocado-700 hover:text-avocado-800 flex items-center gap-1">
                            0812-3456-7890 ↗
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <!-- MODAL SIMULASI MIDTRANS SNAP -->
    <x-midtrans-simulation-modal :booking="$booking" />

    <!--
    ====================================================================================
    ARSITEKTUR JAVASCRIPT MIDTRANS SNAP GATEWAY
    ====================================================================================
    Ketika Server Key dan Client Key Midtrans asli sudah tersedia di project:
    1. Masukkan SDK Midtrans Snap di template:
       <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    2. Ubah `window.tendakuMidtrans.isSimulation = false;`
    3. Controller backend akan mengembalikan `snap_token`, dan fungsi window.tendakuMidtrans.pay()
       akan langsung memicu `window.snap.pay(paymentData.snapToken, ...)` secara otomatis.
    ====================================================================================
    -->
    <script>
        window.tendakuMidtrans = {
            // Ubah menjadi false saat Server Key & Client Key Midtrans asli siap
            isSimulation: true,

            pay: function(paymentData, callbacks = {}) {
                // 1. JIKA SUDAH MENGGUNAKAN SNAP ASLI
                if (!this.isSimulation && typeof window.snap !== 'undefined' && paymentData.snapToken) {
                    window.snap.pay(paymentData.snapToken, {
                        onSuccess: function(result) {
                            if (callbacks.onSuccess) callbacks.onSuccess(result);
                            window.location.href = paymentData.redirectUrl;
                        },
                        onPending: function(result) {
                            if (callbacks.onPending) callbacks.onPending(result);
                            window.location.href = paymentData.redirectUrl;
                        },
                        onError: function(result) {
                            if (callbacks.onError) callbacks.onError(result);
                            alert("Pembayaran belum berhasil: " + (result.status_message || "Dibatalkan"));
                        },
                        onClose: function() {
                            if (callbacks.onClose) callbacks.onClose();
                        }
                    });
                    return;
                }

                // 2. MODE SIMULASI SANDBOX DEMO (DEFAULT SEMENTARA)
                window.dispatchEvent(new CustomEvent('open-midtrans-simulation', {
                    detail: paymentData
                }));
            }
        };
    </script>
</x-layouts.customer>