<x-layouts.customer>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-xs font-semibold text-darkbrown-600 mb-2">
            <a href="{{ route('customer.packages.show', $package) }}" class="hover:text-avocado-700 transition flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Kembali ke Detail Paket</span>
            </a>
            <span>/</span>
            <span class="text-darkbrown-900 font-bold">Konfirmasi Sewa</span>
        </div>
        <h1 class="font-extrabold text-2xl md:text-3xl text-darkbrown-900 tracking-tight font-heading">
            Sewa Paket Camping
        </h1>
        <p class="text-sm text-darkbrown-600 mt-1">
            Lengkapi data penyewaan, lalu lanjutkan ke pembayaran.
        </p>
    </x-slot>

    @php
        $price = (float) $package->price;
        $vendorName = $package->vendor?->name ?? 'Tendaku Vendor';
        $deliveryFeeAmount = (int) \App\Http\Controllers\Customer\PackageCheckoutController::DELIVERY_FEE;
        $image = $package->image_url
            ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80';
    @endphp

    <form method="POST" action="{{ route('customer.packages.checkout.store', $package) }}"
          class="grid grid-cols-1 lg:grid-cols-12 gap-8 py-2"
          x-data="{ method: '{{ $pickupMethod }}', deliveryFee: {{ $deliveryFeeAmount }} }">
        @csrf
        <input type="hidden" name="package_id" value="{{ $package->id }}" />

        <!-- Kolom Kiri: Form Data Penyewa -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Jadwal Sewa -->
            <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku space-y-4">
                <h2 class="text-base font-extrabold text-darkbrown-900 border-b border-wheat-200 pb-3 font-heading flex items-center gap-2">
                    <i data-lucide="calendar-days" class="w-4 h-4 text-avocado-600"></i>
                    Jadwal Sewa
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="pickup_date" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                            Tanggal Mulai Sewa *
                        </label>
                        <input type="date" id="pickup_date" name="pickup_date"
                               value="{{ old('pickup_date', $pickupDate->format('Y-m-d')) }}"
                               min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                               required
                               class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300" />
                        <x-input-error :messages="$errors->get('pickup_date')" class="mt-1" />
                    </div>

                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                            Tanggal Pengembalian
                        </span>
                        <div class="w-full py-2.5 px-3 rounded-xl text-xs font-bold text-darkbrown-800 bg-wheat-100 border border-wheat-200">
                            {{ $returnDate->format('d/m/Y') }}
                        </div>
                        <p class="text-[11px] text-darkbrown-400 mt-1">
                            Otomatis {{ $package->duration_days }} hari dari tanggal mulai.
                        </p>
                    </div>
                </div>

                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                        Metode Pengambilan
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                               :class="method === 'self_pickup' ? 'border-avocado-500 bg-avocado-50' : 'border-wheat-300 bg-white hover:bg-wheat-50'">
                            <input type="radio" name="pickup_method" value="self_pickup" x-model="method"
                                   class="mt-0.5 text-avocado-600 focus:ring-avocado-500" />
                            <span>
                                <span class="block text-xs font-bold text-darkbrown-900">Ambil di Toko</span>
                                <span class="block text-[11px] text-darkbrown-500">Gratis, ambil langsung di {{ $vendorName }}.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                               :class="method === 'delivery' ? 'border-avocado-500 bg-avocado-50' : 'border-wheat-300 bg-white hover:bg-wheat-50'">
                            <input type="radio" name="pickup_method" value="delivery" x-model="method"
                                   @disabled(! $isLoggedIn)
                                   class="mt-0.5 text-avocado-600 focus:ring-avocado-500 disabled:opacity-40" />
                            <span>
                                <span class="block text-xs font-bold text-darkbrown-900">
                                    Diantar (+Rp {{ number_format($deliveryFeeAmount, 0, ',', '.') }})
                                </span>
                                <span class="block text-[11px] text-darkbrown-500">
                                    @if ($isLoggedIn)
                                        Dikirim ke alamat pada profil Anda.
                                    @else
                                        Wajib masuk ke akun terlebih dahulu.
                                    @endif
                                </span>
                            </span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('pickup_method')" class="mt-1" />
                </div>
            </div>

            <!-- Data Penyewa -->
            <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku space-y-4">
                <h2 class="text-base font-extrabold text-darkbrown-900 border-b border-wheat-200 pb-3 font-heading flex items-center gap-2">
                    <i data-lucide="user-round" class="w-4 h-4 text-avocado-600"></i>
                    Data Penyewa
                </h2>

                @if ($isLoggedIn)
                    <div class="p-3 rounded-xl bg-avocado-50 border border-avocado-200">
                        <p class="text-xs font-bold text-avocado-900">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-avocado-800 mt-0.5">
                            {{ auth()->user()->email }}
                            @if (auth()->user()->phone)
                                &middot; {{ auth()->user()->phone }}
                            @endif
                        </p>
                        <p class="text-[11px] text-avocado-700 mt-1">
                            Data penyewa mengikuti profil akun Anda.
                        </p>
                    </div>
                @else
                    <div class="p-3 rounded-xl bg-sunglow-50 border border-sunglow-200">
                        <p class="text-xs font-bold text-darkbrown-900">Pemesanan tanpa akun (guest)</p>
                        <p class="text-[11px] text-darkbrown-600 mt-0.5">
                            Sudah punya akun?
                            <a href="{{ route('login') }}" class="font-bold text-avocado-700 underline">Masuk di sini</a>
                            agar data terisi otomatis dan riwayat sewa tersimpan.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="guest_name" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                                Nama Lengkap *
                            </label>
                            <input type="text" id="guest_name" name="guest_name" value="{{ old('guest_name') }}"
                                   required placeholder="Sesuai KTP"
                                   class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300" />
                            <x-input-error :messages="$errors->get('guest_name')" class="mt-1" />
                        </div>

                        <div>
                            <label for="guest_phone" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                                Nomor WhatsApp *
                            </label>
                            <input type="text" id="guest_phone" name="guest_phone" value="{{ old('guest_phone') }}"
                                   required placeholder="08xxxxxxxxxx"
                                   class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300" />
                            <x-input-error :messages="$errors->get('guest_phone')" class="mt-1" />
                        </div>

                        <div>
                            <label for="guest_id_number" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                                NIK (KTP) *
                            </label>
                            <input type="text" id="guest_id_number" name="guest_id_number" value="{{ old('guest_id_number') }}"
                                   required placeholder="16 digit NIK"
                                   class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300" />
                            <x-input-error :messages="$errors->get('guest_id_number')" class="mt-1" />
                        </div>
                    </div>
                @endif
            </div>

            <!-- Metode Pembayaran -->
            <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku space-y-4">
                <h2 class="text-base font-extrabold text-darkbrown-900 border-b border-wheat-200 pb-3 font-heading flex items-center gap-2">
                    <i data-lucide="credit-card" class="w-4 h-4 text-avocado-600"></i>
                    Metode Pembayaran
                </h2>

                <div>
                    <label for="payment_channel" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                        Pilih Kanal Pembayaran
                    </label>
                    <select id="payment_channel" name="payment_channel"
                            class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                        <option value="bca_va" @selected(old('payment_channel') === 'bca_va')>BCA Virtual Account</option>
                        <option value="bri_va" @selected(old('payment_channel') === 'bri_va')>BRI Virtual Account (BRIVA)</option>
                    </select>
                    <p class="text-[11px] text-darkbrown-400 mt-1">
                        Instruksi pembayaran tampil pada halaman status setelah pesanan dibuat.
                    </p>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Ringkasan Pesanan -->
        <div class="lg:col-span-5">
            <div class="lg:sticky lg:top-24 space-y-4">
                <div class="card-tendaku rounded-2xl bg-white border border-wheat-200 shadow-tendaku overflow-hidden">
                    <div class="relative aspect-[16/9] bg-wheat-100">
                        <img src="{{ $image }}" alt="{{ $package->name }}" class="w-full h-full object-cover" />
                        <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-darkbrown-900/85 text-wheat-200 backdrop-blur-md">
                            {{ $package->duration_days }} Hari
                        </span>
                    </div>

                    <div class="p-5 space-y-4">
                        <div>
                            <p class="text-[11px] font-semibold text-goldenrod-800 flex items-center gap-1.5">
                                <i data-lucide="store" class="w-3.5 h-3.5"></i>
                                {{ $vendorName }}
                            </p>
                            <h2 class="text-base font-extrabold text-darkbrown-900 font-heading mt-1">
                                {{ $package->name }}
                            </h2>
                        </div>

                        <ul class="text-[11px] text-darkbrown-600 space-y-1 max-h-40 overflow-y-auto pr-1">
                            @foreach ($package->packageItems as $packageItem)
                                <li class="flex items-center justify-between gap-2 py-1 border-b border-wheat-100 last:border-0">
                                    <span class="truncate">{{ $packageItem->masterItem?->name ?? 'Produk' }}</span>
                                    <span class="font-bold text-darkbrown-900 shrink-0">{{ $packageItem->quantity }}x</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="pt-3 border-t border-wheat-200 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-darkbrown-600">Harga paket</span>
                                <span class="font-bold text-darkbrown-900">Rp {{ number_format($price, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between" x-show="method === 'delivery'" x-cloak>
                                <span class="text-darkbrown-600">Biaya pengantaran</span>
                                <span class="font-bold text-darkbrown-900">Rp {{ number_format($deliveryFeeAmount, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-wheat-200">
                                <span class="font-bold text-darkbrown-900">Total Bayar</span>
                                <span class="text-lg font-extrabold text-avocado-800 font-heading"
                                      x-text="'Rp ' + ({{ $price }} + (method === 'delivery' ? deliveryFee : 0)).toLocaleString('id-ID')">
                                    Rp {{ number_format($pickupMethod === 'delivery' ? $price + $deliveryFeeAmount : $price, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <button type="submit"
                                class="btn-tendaku-primary w-full py-3 rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition-all">
                            Buat Pesanan & Lanjut Bayar
                        </button>

                        <p class="text-[10px] text-darkbrown-500 text-center leading-relaxed">
                            Dengan menekan tombol di atas, Anda menyetujui syarat sewa Tendaku.
                            Pembayaran dilunasi sebelum pengambilan unit.
                        </p>
                    </div>
                </div>

                <div class="card-tendaku rounded-2xl bg-white p-4 border border-wheat-200 text-[11px] text-darkbrown-600 space-y-2">
                    <p class="flex items-center gap-2">
                        <i data-lucide="calendar-check" class="w-3.5 h-3.5 text-avocado-600 shrink-0"></i>
                        Mulai {{ $pickupDate->format('d/m/Y') }} &middot; kembali {{ $returnDate->format('d/m/Y') }}
                    </p>
                    <p class="flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-avocado-600 shrink-0"></i>
                        Jaminan KTP asli, tanpa deposit uang tunai.
                    </p>
                </div>
            </div>
        </div>
    </form>
</x-layouts.customer>
