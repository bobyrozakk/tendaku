<x-layouts.customer>
    <x-slot name="title">Checkout Paket Camping – Tendaku</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-sm text-darkbrown-500 mb-1.5">
                    <a href="{{ route('home') }}" class="hover:text-avocado-700 transition">Beranda</a>
                    <span class="text-wheat-400">/</span>
                    <a href="{{ route('customer.packages.index') }}" class="hover:text-avocado-700 transition">Paket Camping</a>
                    <span class="text-wheat-400">/</span>
                    <a href="{{ route('customer.packages.show', $package) }}" class="hover:text-avocado-700 transition">{{ $package->name }}</a>
                    <span class="text-wheat-400">/</span>
                    <span class="text-darkbrown-800 font-semibold">Booking</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-900 tracking-tight font-heading">
                    Checkout &amp; Pembayaran
                </h1>
            </div>
        </div>
    </x-slot>

    @php
        $price = (float) $package->price;
        $vendorName = $package->vendor?->name ?? 'Tendaku Vendor';
        $vendorCity = $package->vendor?->city ?? '';
        $vendorAddress = $package->vendor?->address ?? '';
        $vendorPhone = $package->vendor?->phone ?? '';
        $vendorOperational = $package->vendor?->operational_hours ?? '';
        $image = $package->image_url
            ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80';
        $user = auth()->user();
        $maskedNik = ($isLoggedIn && filled($user?->nik))
            ? substr($user->nik, 0, 4) . str_repeat('•', max(0, strlen($user->nik) - 8)) . substr($user->nik, -4)
            : null;
        $needsPhone = $isLoggedIn && blank($user?->phone);
        $needsNik = $isLoggedIn && blank($user?->nik);
        $selectedAddrId = old('customer_address_id', $defaultAddress?->id ?? ($addresses->first()?->id ?? null));
        $addressListJson = $addresses->map(fn ($a) => [
            'id' => $a->id,
            'label' => $a->label,
            'recipient_name' => $a->recipient_name,
            'phone_number' => $a->phone_number,
            'full_address' => $a->full_address,
            'province' => $a->province,
            'city' => $a->city,
            'district' => $a->district,
            'village' => $a->village,
            'postal_code' => $a->postal_code,
            'notes' => $a->notes,
            'latitude' => $a->latitude,
            'longitude' => $a->longitude,
            'formatted_address' => $a->formatted_address,
            'delivery_snapshot' => $a->toDeliverySnapshot(),
            'is_default' => (bool) $a->is_default,
        ])->values();
        $packageCheckoutConfig = [
            'packageId' => $package->id, 'method' => old('pickup_method', $pickupMethod),
            'packagePrice' => $price, 'packageName' => $package->name,
            'pickupDate' => old('pickup_date', $pickupDate->format('Y-m-d')),
            'pickupTime' => old('pickup_time', $pickupTime),
            'returnDate' => old('return_date', $returnDate->format('Y-m-d')),
            'returnTime' => old('return_time', $returnTime),
            'durationDays' => $rentalDays, 'totalHours' => $hours, 'subtotal' => $subtotal,
            'csrf' => csrf_token(), 'shippingPreviewUrl' => route('checkout.shipping.preview'),
            'storeAddressUrl' => route('customer.addresses.store'), 'addresses' => $addressListJson,
            'updateAddressUrl' => route('customer.addresses.update', ['address' => '__ADDRESS__']),
            'selectedAddressId' => $selectedAddrId, 'name' => $user?->name ?? '', 'phone' => $user?->phone ?? '',
            'nikInput' => old('nik', $user?->nik ?? ''),
            'requiresApproval' => session()->has('shipping_conflict'),
            'packageItems' => $package->packageItems->map(fn ($item) => ['id' => $item->master_item_id, 'name' => $item->masterItem?->name, 'qty' => $item->quantity])->values(),
            'minDate' => now()->toDateString(),
            'calYear' => (int) $pickupDate->format('Y'),
            'calMonth' => (int) $pickupDate->format('n') - 1,
            'storeUrl' => route('customer.packages.booking.store', $package),
            'vendorCity' => $vendorCity,
            'vendorPickupLocation' => $package->vendor?->pickup_location ?? $vendorName,
            'vendorAddress' => $vendorAddress,
            'vendorOperationalHours' => $vendorOperational,
        ];
    @endphp

    <div x-data="packageCheckout({{ \Illuminate\Support\Js::from($packageCheckoutConfig) }})" class="py-2 sm:py-4 space-y-6">

        {{-- Trip Banner --}}
        <div class="relative overflow-hidden rounded-3xl bg-avocado-900 text-white shadow-tendaku">
            <div class="relative p-5 sm:p-7 flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div class="space-y-4 min-w-0">
                    <div>
                        <span class="text-sm text-avocado-200">Status Pemesanan</span>
                        <div class="text-2xl sm:text-3xl font-extrabold tracking-tight">Pemesanan Baru</div>
                    </div>
                    <div class="flex flex-wrap gap-2 text-sm">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/15 px-3 py-1">
                            <i data-lucide="store" class="w-4 h-4 text-sunglow-300"></i>
                            {{ $vendorName }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/15 px-3 py-1">
                            <i data-lucide="package" class="w-4 h-4 text-sunglow-300"></i>
                            <span x-text="packageName">{{ $package->name }}</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/15 px-3 py-1">
                            <i data-lucide="calendar" class="w-4 h-4 text-sunglow-300"></i>
                            <span x-text="fmtShort(pickupDate) + ' – ' + fmtShort(returnDate)">{{ $pickupDate->format('d M Y') }} – {{ $returnDate->format('d M Y') }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">

            {{-- Left Column --}}
            <div class="lg:col-span-7 xl:col-span-8 space-y-6">

                {{-- Section 1: Data Penyewa --}}
                <section id="section-identity" class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm">
                    <div class="flex items-center gap-3 px-5 sm:px-6 pt-5 sm:pt-6">
                        <span class="w-8 h-8 rounded-full bg-avocado-700 text-white font-bold flex items-center justify-center shrink-0">1</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-darkbrown-900 font-heading leading-tight">Data penyewa</h2>
                            </div>
                            <p class="text-sm text-darkbrown-500">
                                @if ($isLoggedIn)
                                    Terhubung dengan {{ $user->email }}. Dipakai vendor untuk mencocokkan identitas saat serah terima alat.
                                @else
                                    Dipakai vendor untuk mencocokkan identitas saat serah terima alat.
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 space-y-4">
                        @if ($isLoggedIn)
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div class="rounded-xl bg-wheat-50 border border-wheat-200 px-3.5 py-2.5">
                                    <dt class="text-darkbrown-500 text-xs">Nama</dt>
                                    <dd class="font-bold text-darkbrown-900 truncate mt-0.5">{{ $user->name }}</dd>
                                </div>
                                @if (filled($user->phone))
                                    <div class="rounded-xl bg-wheat-50 border border-wheat-200 px-3.5 py-2.5">
                                        <dt class="text-darkbrown-500 text-xs">WhatsApp</dt>
                                        <dd class="font-bold text-darkbrown-900 mt-0.5">{{ $user->phone }}</dd>
                                    </div>
                                @endif
                                @if (filled($user->nik))
                                    <div class="rounded-xl bg-wheat-50 border border-wheat-200 px-3.5 py-2.5 sm:col-span-2">
                                        <dt class="text-darkbrown-500 text-xs">NIK</dt>
                                        <dd class="font-mono font-bold text-darkbrown-900 mt-0.5">{{ $maskedNik }}</dd>
                                    </div>
                                @endif
                            </dl>

                            @if ($needsPhone || $needsNik)
                                <div class="space-y-3">
                                    @if ($needsNik)
                                        <div>
                                            <label for="nik" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">NIK (nomor KTP)</label>
                                            <div class="relative">
                                                <input type="text" id="nik" name="nik" x-model="nikInput"
                                                       @input="nikInput = nikInput.replace(/\D/g, '').slice(0, 16)"
                                                       inputmode="numeric" maxlength="16" autocomplete="off" placeholder="16 digit angka"
                                                       value="{{ old('nik') }}"
                                                       class="input-tendaku w-full py-2.5 px-3 rounded-xl text-sm font-mono tracking-wide border-wheat-300 pr-14" required>
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono"
                                                      :class="nikInput.length === 16 ? 'text-avocado-700 font-bold' : 'text-darkbrown-400'"
                                                      x-text="nikInput.length + '/16'"></span>
                                            </div>
                                            <p x-show="nikInput.length > 0 && nikInput.length < 16" x-cloak class="mt-1 text-sm text-red-700">NIK harus 16 digit angka.</p>
                                            <x-input-error :messages="$errors->get('nik')" class="mt-1" />
                                        </div>
                                    @endif

                                    @if ($needsPhone)
                                        <div>
                                            <label for="phone" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">No. WhatsApp aktif</label>
                                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                                                   inputmode="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" required
                                                   class="input-tendaku w-full py-2.5 px-3 rounded-xl text-sm font-medium border-wheat-300">
                                            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @else
                            {{-- Guest --}}
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
                </section>

                {{-- Section 2: Cara Menerima Alat --}}
                <section id="section-delivery" class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm">
                    <div class="flex items-center gap-3 px-5 sm:px-6 pt-5 sm:pt-6">
                        <span class="w-8 h-8 rounded-full bg-avocado-700 text-white font-bold flex items-center justify-center shrink-0">2</span>
                        <div>
                            <h2 class="text-lg font-bold text-darkbrown-900 font-heading leading-tight">Cara menerima alat</h2>
                            <p class="text-sm text-darkbrown-500">Ambil sendiri di toko, atau diantar kurir vendor.</p>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" role="radiogroup" aria-label="Metode pengambilan">

                            {{-- Ambil di toko --}}
                            <label :class="method === 'self_pickup' ? 'border-avocado-600 bg-avocado-50 ring-2 ring-avocado-600/20' : 'border-wheat-300 bg-white hover:border-avocado-400'"
                                   class="relative flex gap-3 rounded-2xl border p-4 cursor-pointer transition focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-avocado-600">
                                <input type="radio" name="pickup_method" value="self_pickup" x-model="method" class="sr-only">
                                <div class="w-10 h-10 rounded-xl bg-avocado-100 text-avocado-700 flex items-center justify-center shrink-0">
                                    <i data-lucide="store" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0 flex-1 pr-6">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-darkbrown-900">Ambil di toko</span>
                                        <span class="rounded-md bg-avocado-100 border border-avocado-200 px-2 py-0.5 text-xs font-bold text-avocado-800">Gratis</span>
                                    </div>
                                    <p class="mt-1 text-sm text-darkbrown-600 leading-relaxed">Datang ke outlet vendor di {{ $vendorCity }}. Bisa untuk tamu maupun member.</p>
                                </div>
                                <span class="absolute top-3 right-3 w-5 h-5 rounded-full border-2 flex items-center justify-center transition"
                                      :class="method === 'self_pickup' ? 'bg-avocado-600 border-avocado-600' : 'border-wheat-400 bg-white'" aria-hidden="true">
                                    <i data-lucide="check" class="w-3 h-3 text-white"></i>
                                </span>
                            </label>

                            {{-- Diantar (member) --}}
                            @if ($isLoggedIn)
                                <label :class="method === 'delivery' ? 'border-avocado-600 bg-avocado-50 ring-2 ring-avocado-600/20' : 'border-wheat-300 bg-white hover:border-avocado-400'"
                                       class="relative flex gap-3 rounded-2xl border p-4 cursor-pointer transition focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-avocado-600">
                                    <input type="radio" name="pickup_method" value="delivery" x-model="method" class="sr-only">
                                    <div class="w-10 h-10 rounded-xl bg-sunglow-200 text-goldenrod-800 flex items-center justify-center shrink-0">
                                        <i data-lucide="truck" class="w-5 h-5"></i>
                                    </div>
                                    <div class="min-w-0 flex-1 pr-6">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-bold text-darkbrown-900">Diantar ke lokasi</span>
                                            <span class="rounded-md bg-sunglow-100 border border-sunglow-300 px-2 py-0.5 text-xs font-bold text-darkbrown-900">Ongkir otomatis</span>
                                        </div>
                                        <p class="mt-1 text-sm text-darkbrown-600 leading-relaxed">Kurir vendor mengantar ke rumah atau titik kumpul trip.</p>
                                    </div>
                                    <span class="absolute top-3 right-3 w-5 h-5 rounded-full border-2 flex items-center justify-center transition"
                                          :class="method === 'delivery' ? 'bg-avocado-600 border-avocado-600' : 'border-wheat-400 bg-white'" aria-hidden="true">
                                        <i x-show="method === 'delivery'" data-lucide="check" class="w-3 h-3 text-white"></i>
                                    </span>
                                </label>
                            @else
                                <div class="flex gap-3 rounded-2xl border border-dashed border-wheat-400 bg-wheat-50 p-4" aria-disabled="true">
                                    <div class="w-10 h-10 rounded-xl bg-wheat-200 text-darkbrown-500 flex items-center justify-center shrink-0">
                                        <i data-lucide="lock" class="w-5 h-5"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <span class="font-bold text-darkbrown-700">Diantar ke lokasi</span>
                                        <p class="mt-1 text-sm text-darkbrown-600 leading-relaxed">Khusus member, karena alamat dan serah terima kurir perlu akun terverifikasi.</p>
                                        <a href="{{ route('login') }}" class="mt-2 inline-block text-sm font-bold text-avocado-700 hover:text-avocado-800 underline underline-offset-2">Masuk atau daftar dulu</a>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Detail: ambil di toko --}}
                        <div x-show="method === 'self_pickup'" x-cloak class="flex items-start gap-3 rounded-xl bg-wheat-50 border border-wheat-200 p-4 text-sm">
                            <i data-lucide="map-pin" class="w-5 h-5 text-avocado-700 shrink-0 mt-0.5"></i>
                            <div class="space-y-0.5">
                                <div class="font-bold text-darkbrown-900">{{ $package->vendor?->pickup_location ?? $vendorName }}</div>
                                @if ($vendorAddress)
                                    <div class="text-darkbrown-600">{{ $vendorAddress }}</div>
                                @endif
                                @if ($vendorOperational)
                                    <div class="text-darkbrown-600 pt-1">Jam operasional: <strong class="text-darkbrown-800">{{ $vendorOperational }}</strong></div>
                                @endif
                            </div>
                        </div>

                        {{-- Detail: diantar (member) --}}
                        @if ($isLoggedIn)
                            <div x-show="method === 'delivery'" x-cloak class="rounded-xl bg-wheat-50 border border-wheat-200 p-4 space-y-4 text-sm">
                                <div class="flex items-center justify-between gap-3 border-b border-wheat-200/80 pb-3">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="map-pin" class="w-4 h-4 text-avocado-700"></i>
                                        <span class="font-bold text-darkbrown-900">Alamat Pengantaran</span>
                                    </div>
                                    <button type="button" @click="openAddAddressModal()"
                                            class="inline-flex items-center gap-1.5 text-xs font-bold text-avocado-700 hover:text-avocado-800 bg-white border border-wheat-300 hover:border-avocado-500 px-3 py-1.5 rounded-lg shadow-sm transition">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        <span>Tambah Alamat</span>
                                    </button>
                                </div>

                                <template x-if="addresses.length > 0">
                                    <div class="space-y-3">
                                        <div class="rounded-xl bg-white border-2 border-avocado-600/60 p-3.5 shadow-sm space-y-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-wheat-100 text-darkbrown-800"
                                                          x-text="selectedAddress ? selectedAddress.label : 'Alamat'"></span>
                                                    <template x-if="selectedAddress && selectedAddress.is_default">
                                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-avocado-100 text-avocado-800">Utama</span>
                                                    </template>
                                                </div>
                                                <button type="button" x-show="addresses.length > 1" @click="showAddressPicker = !showAddressPicker"
                                                        class="text-xs font-bold text-avocado-700 hover:text-avocado-800 underline underline-offset-2"
                                                        x-text="showAddressPicker ? 'Tutup Pilihan' : 'Pilih Alamat Lain'"></button>
                                            </div>
                                            <div class="text-xs text-darkbrown-900 space-y-0.5">
                                                <p class="font-bold text-sm text-darkbrown-900">
                                                    <span x-text="selectedAddress ? selectedAddress.recipient_name : ''"></span>
                                                    <span class="font-normal text-darkbrown-500" x-text="selectedAddress ? ' (' + selectedAddress.phone_number + ')' : ''"></span>
                                                </p>
                                                <p class="text-darkbrown-700 leading-relaxed" x-text="selectedAddress ? (selectedAddress.formatted_address || selectedAddress.full_address) : ''"></p>
                                            </div>
                                        </div>

                                        <div x-show="showAddressPicker" x-cloak class="rounded-xl bg-white border border-wheat-200 p-3 space-y-2 max-h-60 overflow-y-auto">
                                            <p class="text-xs font-bold text-darkbrown-700">Pilih dari daftar alamat tersimpan:</p>
                                            <div class="space-y-2">
                                                <template x-for="addr in addresses" :key="addr.id">
                                                    <div @click="selectAddress(addr.id)"
                                                         :class="selectedAddressId === addr.id ? 'border-avocado-600 bg-avocado-50/50' : 'border-wheat-200 hover:border-wheat-400 bg-white'"
                                                         class="p-2.5 rounded-lg border cursor-pointer transition flex items-start justify-between gap-3">
                                                        <div class="min-w-0 space-y-0.5">
                                                            <div class="flex items-center gap-1.5">
                                                                <span class="text-[10px] font-bold uppercase tracking-wider bg-wheat-100 text-darkbrown-700 px-1.5 py-0.5 rounded" x-text="addr.label"></span>
                                                                <span class="text-xs font-bold text-darkbrown-900" x-text="addr.recipient_name"></span>
                                                                <template x-if="addr.is_default">
                                                                    <span class="text-[9px] font-bold uppercase bg-avocado-100 text-avocado-800 px-1.5 py-0.5 rounded">Utama</span>
                                                                </template>
                                                            </div>
                                                            <p class="text-xs text-darkbrown-600 truncate" x-text="addr.formatted_address || addr.full_address"></p>
                                                        </div>
                                                        <div class="shrink-0 mt-0.5">
                                                            <span class="w-4 h-4 rounded-full border flex items-center justify-center"
                                                                  :class="selectedAddressId === addr.id ? 'border-avocado-600 bg-avocado-600 text-white' : 'border-wheat-400'">
                                                                <i x-show="selectedAddressId === addr.id" data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="addresses.length === 0">
                                    <div class="rounded-xl border border-dashed border-wheat-300 bg-white p-5 text-center space-y-2.5">
                                        <div class="w-10 h-10 rounded-full bg-wheat-100 text-darkbrown-500 mx-auto flex items-center justify-center">
                                            <i data-lucide="map-pin-off" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-darkbrown-800 text-xs">Belum Ada Alamat Tersimpan</p>
                                            <p class="text-[11px] text-darkbrown-500 mt-0.5">Simpan alamat pengantaran lengkap agar kurir vendor dapat mengirimkan alat ke lokasi Anda.</p>
                                        </div>
                                        <button type="button" @click="openAddAddressModal()"
                                                class="inline-flex items-center gap-1.5 text-xs font-bold text-white bg-avocado-700 hover:bg-avocado-800 px-3.5 py-2 rounded-xl shadow-sm transition">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                            <span>Tambah Alamat Pengantaran</span>
                                        </button>
                                    </div>
                                </template>

                                @include('customer.booking.partials.create.address-location-notice')

                                <x-input-error :messages="$errors->get('customer_address_id')" class="mt-1" />
                                <x-input-error :messages="$errors->get('delivery_address')" class="mt-1" />
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Section 3: Jadwal Sewa --}}
                <section id="section-rental" class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm divide-y divide-wheat-200">

                    {{-- Vendor Info --}}
                    <div class="p-5 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-8 h-8 rounded-full bg-avocado-700 text-white font-bold flex items-center justify-center shrink-0">3</span>
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-12 h-12 rounded-xl bg-avocado-800 text-wheat-100 flex items-center justify-center shrink-0">
                                        <i data-lucide="store" class="w-6 h-6"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-sm text-darkbrown-500">Disiapkan oleh</div>
                                        <h3 class="font-bold text-darkbrown-900 text-base leading-snug">{{ $vendorName }}</h3>
                                        @if ($vendorCity)
                                            <div class="text-sm text-darkbrown-600">{{ $vendorCity }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if ($vendorPhone)
                                <a href="tel:{{ $vendorPhone }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl text-sm font-bold text-darkbrown-800 bg-white border border-wheat-300 hover:bg-wheat-50 transition self-start sm:self-center">
                                    <i data-lucide="phone" class="w-4 h-4 text-avocado-600"></i>
                                    {{ $vendorPhone }}
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Jadwal Sewa --}}
                    <div class="p-5 sm:p-6" @click.outside="calOpen = null; timeOpen = false">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h3 class="font-bold text-darkbrown-900">Jadwal sewa</h3>
                            <span class="text-sm text-darkbrown-500">Tanggal bisa diubah</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto_1fr] items-stretch gap-3">

                            {{-- Tanggal Ambil --}}
                            <div class="relative rounded-xl bg-avocado-50 border border-avocado-200 p-4">
                                <label class="text-sm font-semibold text-avocado-800">Tanggal ambil</label>

                                <button type="button" @click="openCalendar('pickup')"
                                        class="mt-1.5 w-full flex items-center justify-between rounded-lg border border-avocado-300 bg-white px-3 py-2 text-sm font-semibold text-darkbrown-900 hover:border-avocado-500 focus:outline-none focus:ring-2 focus:ring-avocado-500/30 transition text-left shadow-2xs">
                                    <span x-text="pickupDate ? fmtShort(pickupDate) : 'Pilih tanggal'"></span>
                                    <i data-lucide="calendar" class="w-4 h-4 text-avocado-700"></i>
                                </button>

                                <div class="mt-1.5 flex items-center gap-1.5 text-sm text-darkbrown-600 flex-wrap">
                                    <span x-text="fmtLong(pickupDate)"></span>
                                    <span>·</span>
                                    <button type="button" @click.stop="toggleTime()"
                                            class="inline-flex items-center gap-1 font-semibold text-avocado-800 hover:text-avocado-900 bg-avocado-100 hover:bg-avocado-200 border border-avocado-200 rounded-md px-2 py-0.5 transition">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        <span x-text="pickupTime"></span>
                                    </button>
                                </div>

                                {{-- Kalender ambil --}}
                                <div x-show="calOpen === 'pickup'" x-cloak x-transition.opacity.duration.150ms
                                     class="absolute top-full left-0 mt-2 z-40 w-72 sm:w-80 p-4 rounded-2xl bg-white border border-wheat-200 shadow-tendaku-lg">
                                    <div class="flex items-center justify-between pb-2.5 border-b border-wheat-200 mb-2.5">
                                        <button type="button" @click="prevMonth()" class="w-7 h-7 rounded-lg hover:bg-wheat-100 flex items-center justify-center font-bold text-darkbrown-700">‹</button>
                                        <span class="text-xs font-bold text-darkbrown-900" x-text="monthNames[calMonth] + ' ' + calYear"></span>
                                        <button type="button" @click="nextMonth()" class="w-7 h-7 rounded-lg hover:bg-wheat-100 flex items-center justify-center font-bold text-darkbrown-700">›</button>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-darkbrown-400 mb-1.5">
                                        <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center text-xs">
                                        <template x-for="(cell, idx) in calendarDays" :key="idx">
                                            <div>
                                                <template x-if="cell.empty"><span class="w-7 h-7 inline-block"></span></template>
                                                <template x-if="!cell.empty">
                                                    <button type="button" :disabled="cell.disabled" @click="selectCalDate(cell.iso)"
                                                            :class="[
                                                                cell.unavailable ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                                                (cell.selected ? 'bg-avocado-700 text-white font-bold shadow-xs' :
                                                                (cell.inRange ? 'bg-avocado-100 text-avocado-900 font-semibold' :
                                                                (cell.disabled ? 'text-wheat-400 opacity-40 cursor-not-allowed' : 'text-darkbrown-800 hover:bg-wheat-100 font-medium')))
                                                                , (cell.outside && (!cell.selected || cell.unavailable)) ? 'opacity-50' : ''
                                                            ]"
                                                            class="w-7 h-7 rounded-lg inline-flex items-center justify-center transition text-xs"
                                                            x-text="cell.day">
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Timepicker --}}
                                <div x-show="timeOpen" x-cloak x-transition.opacity.duration.150ms
                                     class="absolute top-full left-0 mt-2 z-50 rounded-2xl bg-white border border-wheat-200 shadow-tendaku-lg overflow-hidden"
                                     style="width: 13rem;">
                                    <div class="px-4 pt-3 pb-1">
                                        <p class="text-xs font-bold text-darkbrown-700">Jam ambil</p>
                                        <p class="text-[10px] text-darkbrown-400 mt-0.5">07.00 – 17.00 · jam kembali mengikuti</p>
                                    </div>
                                    <div class="relative" style="height: 200px;">
                                        <div class="absolute inset-x-3 pointer-events-none border-y border-wheat-300"
                                             style="top: 80px; height: 40px;"></div>
                                        <div class="flex h-full">
                                            <div class="flex-1 h-full overflow-y-auto overscroll-contain snap-y snap-mandatory no-scrollbar"
                                                 x-ref="hourCol"
                                                 style="padding: 80px 0; scrollbar-width: none; -ms-overflow-style: none; -webkit-mask-image: linear-gradient(to bottom, transparent, #000 32%, #000 68%, transparent); mask-image: linear-gradient(to bottom, transparent, #000 32%, #000 68%, transparent);"
                                                 @scroll.passive="onHourScroll()">
                                                <template x-for="h in timeHours" :key="h">
                                                    <div @click="pickHour(h)"
                                                         :class="timeHour === h ? 'text-2xl font-semibold text-darkbrown-900' : 'text-lg text-darkbrown-300'"
                                                         class="snap-center flex items-center justify-center cursor-pointer select-none transition-all"
                                                         style="height: 40px;"
                                                         x-text="String(h).padStart(2,'0')"></div>
                                                </template>
                                            </div>
                                            <div class="flex-1 h-full overflow-y-auto overscroll-contain snap-y snap-mandatory no-scrollbar"
                                                 x-ref="minCol"
                                                 style="padding: 80px 0; scrollbar-width: none; -ms-overflow-style: none; -webkit-mask-image: linear-gradient(to bottom, transparent, #000 32%, #000 68%, transparent); mask-image: linear-gradient(to bottom, transparent, #000 32%, #000 68%, transparent);"
                                                 @scroll.passive="onMinScroll()">
                                                <template x-for="m in timeMinutes" :key="m">
                                                    <div @click="pickMinute(m)"
                                                         :class="timeMinute === m ? 'text-2xl font-semibold text-darkbrown-900' : (timeHour === 17 && m !== 0 ? 'text-lg text-darkbrown-200' : 'text-lg text-darkbrown-300')"
                                                         class="snap-center flex items-center justify-center cursor-pointer select-none transition-all"
                                                         style="height: 40px;"
                                                         x-text="String(m).padStart(2,'0')"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="px-3 py-2 border-t border-wheat-100 flex justify-end">
                                        <button type="button" @click="timeOpen = false"
                                                class="text-xs font-bold text-avocado-700 hover:text-avocado-900 px-3 py-1 rounded-lg hover:bg-avocado-50 transition">
                                            Selesai
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Pemisah --}}
                            <div class="flex sm:flex-col items-center justify-center gap-2">
                                <span class="hidden sm:block w-px flex-1 bg-wheat-300"></span>
                                <span class="rounded-full bg-darkbrown-900 text-wheat-100 px-3 py-1 text-xs font-bold whitespace-nowrap">hingga</span>
                                <span class="hidden sm:block w-px flex-1 bg-wheat-300"></span>
                            </div>

                            {{-- Tanggal Kembali --}}
                            <div class="relative rounded-xl bg-sunglow-100/70 border border-sunglow-300/80 p-4">
                                <label class="text-sm font-semibold text-goldenrod-800">Tanggal kembali</label>

                                <button type="button" @click="openCalendar('return')"
                                        class="mt-1.5 w-full flex items-center justify-between rounded-lg border border-sunglow-300 bg-white px-3 py-2 text-sm font-semibold text-darkbrown-900 hover:border-goldenrod-500 focus:outline-none focus:ring-2 focus:ring-goldenrod-400/30 transition text-left shadow-2xs">
                                    <span x-text="returnDate ? fmtShort(returnDate) : 'Pilih tanggal'"></span>
                                    <i data-lucide="calendar" class="w-4 h-4 text-goldenrod-700"></i>
                                </button>

                                <div class="mt-1.5 flex items-center gap-1.5 text-sm text-darkbrown-600 flex-wrap">
                                    <span x-text="fmtLong(returnDate)"></span>
                                    <span>·</span>
                                    <span class="text-darkbrown-500">maksimal <strong x-text="pickupTime"></strong></span>
                                </div>

                                {{-- Kalender kembali --}}
                                <div x-show="calOpen === 'return'" x-cloak x-transition.opacity.duration.150ms
                                     class="absolute top-full right-0 sm:left-auto mt-2 z-40 w-72 sm:w-80 p-4 rounded-2xl bg-white border border-wheat-200 shadow-tendaku-lg">
                                    <div class="flex items-center justify-between pb-2.5 border-b border-wheat-200 mb-2.5">
                                        <button type="button" @click="prevMonth()" class="w-7 h-7 rounded-lg hover:bg-wheat-100 flex items-center justify-center font-bold text-darkbrown-700">‹</button>
                                        <span class="text-xs font-bold text-darkbrown-900" x-text="monthNames[calMonth] + ' ' + calYear"></span>
                                        <button type="button" @click="nextMonth()" class="w-7 h-7 rounded-lg hover:bg-wheat-100 flex items-center justify-center font-bold text-darkbrown-700">›</button>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-darkbrown-400 mb-1.5">
                                        <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center text-xs">
                                        <template x-for="(cell, idx) in calendarDays" :key="idx">
                                            <div>
                                                <template x-if="cell.empty"><span class="w-7 h-7 inline-block"></span></template>
                                                <template x-if="!cell.empty">
                                                    <button type="button" :disabled="cell.disabled" @click="selectCalDate(cell.iso)"
                                                            :class="[
                                                                cell.unavailable ? 'bg-gray-200 text-gray-400 cursor-not-allowed' :
                                                                (cell.selected ? 'bg-goldenrod-600 text-white font-bold shadow-xs' :
                                                                (cell.inRange ? 'bg-sunglow-100 text-darkbrown-900 font-semibold' :
                                                                (cell.disabled ? 'text-wheat-400 opacity-40 cursor-not-allowed' : 'text-darkbrown-800 hover:bg-wheat-100 font-medium')))
                                                                , (cell.outside && (!cell.selected || cell.unavailable)) ? 'opacity-50' : ''
                                                            ]"
                                                            class="w-7 h-7 rounded-lg inline-flex items-center justify-center transition text-xs"
                                                            x-text="cell.day">
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p x-show="errorMessage" x-cloak class="mt-2 text-sm text-red-700" x-text="errorMessage"></p>
                        <x-input-error :messages="$errors->get('pickup_date')" class="mt-2" />
                        <x-input-error :messages="$errors->get('return_date')" class="mt-2" />
                        <x-input-error :messages="$errors->get('pickup_time')" class="mt-2" />
                        <x-input-error :messages="$errors->get('return_time')" class="mt-2" />
                    </div>

                    {{-- Isi Paket --}}
                    <div class="p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h3 class="font-bold text-darkbrown-900">Isi paket camping</h3>
                            <span class="text-sm text-darkbrown-500" x-text="durationDays + ' hari sewa'">{{ $rentalDays }} hari sewa</span>
                        </div>

                        <ul class="divide-y divide-wheat-200">
                            <template x-for="item in packageItems" :key="item.id">
                                <li class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                                    <span class="font-medium text-darkbrown-900 text-sm truncate" x-text="item.name"></span>
                                    <span class="font-bold text-darkbrown-700 shrink-0 text-sm" x-text="item.qty + 'x'"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </section>

            </div>

            {{-- Right Column: Ringkasan --}}
            <div class="lg:col-span-5 xl:col-span-4">
                <div class="space-y-5 lg:sticky lg:top-24">

                    {{-- Payment Summary --}}
                    <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm overflow-hidden">
                        <div class="px-5 sm:px-6 py-4 bg-wheat-100 border-b border-wheat-200">
                            <h3 class="text-lg font-extrabold text-darkbrown-900 font-heading">Ringkasan pembayaran</h3>
                        </div>

                        <div class="p-5 sm:p-6 space-y-5">
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center justify-between text-darkbrown-600">
                                    <span>Harga paket / 24 jam</span>
                                    <span class="font-bold text-darkbrown-900" x-text="formatRupiah(packagePrice)">Rp {{ number_format($price, 0, ',', '.') }}</span>
                                </div>

                                <div class="flex items-center justify-between text-darkbrown-600">
                                    <span>Subtotal (<span x-text="durationDays + ' hari sewa'">{{ $rentalDays }} hari sewa</span>)</span>
                                    <span class="font-bold text-darkbrown-900" x-text="formatRupiah(subtotal)">
                                        Rp {{ number_format($price * $rentalDays, 0, ',', '.') }}
                                    </span>
                                </div>

                                <div class="flex items-start justify-between text-darkbrown-600">
                                    <div>
                                        <span>Ongkos kirim</span>
                                        <span x-show="method === 'delivery'" x-cloak class="block text-xs text-goldenrod-700 font-semibold">Kurir vendor ke lokasi</span>
                                    </div>
                                    <span class="font-bold text-darkbrown-900 text-right" x-text="method === 'self_pickup' ? 'Gratis — Ambil di Toko' : formatRupiah(deliveryFee)">—</span>
                                </div>

                                <div x-show="shippingReady" x-cloak class="flex items-center justify-between text-darkbrown-600">
                                    <span>Berat paket</span>
                                    <span x-text="shipping ? measurement(shipping.total_weight_kg) + ' kg' : '—'"></span>
                                </div>
                                <div x-show="method === 'delivery' && shippingReady" x-cloak class="flex items-center justify-between text-darkbrown-600">
                                    <span>Jarak pengiriman</span>
                                    <span x-text="shipping?.distance_km == null ? '—' : measurement(shipping.distance_km) + ' km'"></span>
                                </div>
                                <div x-show="!shippingReady" x-cloak role="status" aria-live="polite" class="rounded-xl border border-wheat-200 bg-wheat-50 p-3 text-darkbrown-600">
                                    <span x-text="shippingMessage"></span>
                                    <button type="button" x-show="shippingState === 'error'" @click="scheduleShipping()" class="block mt-2 font-bold text-avocado-700">Coba lagi</button>
                                </div>
                            </div>

                            <div class="rounded-xl bg-avocado-900 text-white px-4 py-3.5">
                                <div class="flex items-baseline justify-between gap-3">
                                    <span class="text-sm text-avocado-100">Total pembayaran</span>
                                    <span class="text-2xl font-extrabold tracking-tight font-heading" x-text="formatRupiah(grandTotal)">—</span>
                                </div>
                            </div>

                            {{-- Ketentuan --}}
                            <div class="rounded-xl border border-amber-300 bg-amber-50/80 p-3.5 text-xs text-amber-950 space-y-2">
                                <div class="flex items-center gap-2 font-bold text-amber-900">
                                    <i data-lucide="triangle-alert" class="w-4 h-4 text-amber-600 shrink-0"></i>
                                    <span>Ketentuan Pelunasan</span>
                                </div>
                                <ul class="list-disc list-inside space-y-1 text-amber-900/90 leading-relaxed">
                                    <li><strong>Sisa tagihan sewa:</strong> wajib dilunasi di toko kasir saat serah terima barang (pickup).</li>
                                    <li><strong>KTP asli:</strong> Bawa KTP asli dan nomor booking saat mengambil alat.</li>
                                    <p>KTP ditahan toko selama masa sewa dan dikembalikan setelah alat diperiksa.</p>
                                </ul>
                            </div>

                            <div x-show="formError" x-cloak role="alert" class="flex items-start gap-2 rounded-xl bg-red-50 border border-red-200 px-3.5 py-3 text-sm text-red-800">
                                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 mt-0.5"></i>
                                <span x-text="formError"></span>
                            </div>

                            <div x-show="shippingNeedsApproval" x-cloak class="rounded-xl border border-wheat-300 bg-wheat-50 p-3 text-sm text-darkbrown-700 space-y-2">
                                <p>Periksa rincian paket dan total terbaru sebelum melanjutkan.</p>
                                <button type="button" @click="approveShippingChange()" class="font-bold text-avocado-700">Saya setuju dengan total terbaru</button>
                            </div>

                            <div>
                                <button type="button" @click="submitBooking()"
                                        :disabled="!canSubmit"
                                        :class="!canSubmit ? 'opacity-70 cursor-not-allowed' : 'hover:bg-sunglow-400 active:bg-goldenrod-400 hover:shadow-tendaku-lg'"
                                        class="w-full py-3.5 px-5 rounded-xl font-extrabold text-base text-darkbrown-900 bg-sunglow-300 shadow-tendaku transition duration-200 flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-goldenrod-500 focus:ring-offset-2">
                                    <template x-if="isSubmitting">
                                        <i data-lucide="loader" class="w-5 h-5 animate-spin"></i>
                                    </template>
                                    <span x-text="isSubmitting ? 'Membuat booking...' : 'Buat Booking'"></span>
                                    <span x-show="!isSubmitting && grandTotal" x-text="formatRupiah(grandTotal)"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Butuh Bantuan --}}
                    <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm px-5 py-4 flex items-center justify-between gap-3 text-sm">
                        <div>
                            <div class="font-bold text-darkbrown-900">Butuh bantuan?</div>
                            <div class="text-darkbrown-500">Kendala bayar atau ubah jadwal.</div>
                        </div>
                        <a href="https://wa.me/6281234567890" target="_blank" rel="noopener" class="shrink-0 font-bold text-avocado-700 hover:text-avocado-800">WhatsApp CS</a>
                    </div>

                </div>
            </div>
        </div>

        {{-- Hidden form inputs for submission --}}
        <form id="pkg-booking-form" method="POST" action="{{ route('customer.packages.booking.store', $package) }}" class="hidden">
            @csrf
            <input type="hidden" name="package_id" x-bind:value="packageId" />
            <input type="hidden" name="pickup_date" x-bind:value="pickupDate" />
            <input type="hidden" name="pickup_time" x-bind:value="pickupTime" />
            <input type="hidden" name="return_date" x-bind:value="returnDate" />
            <input type="hidden" name="return_time" x-bind:value="returnTime" />
            <input type="hidden" name="pickup_method" x-bind:value="method" />
            <input type="hidden" name="customer_address_id" x-bind:value="selectedAddressId" />
            <input type="hidden" name="expected_delivery_fee" x-bind:value="shipping ? shipping.delivery_fee : ''" />
            <input type="hidden" name="expected_shipping_context" x-bind:value="shipping ? shipping.shipping_context : ''" />
            <input type="hidden" name="checkout_attempt_id" value="{{ old('checkout_attempt_id', (string) \Illuminate\Support\Str::uuid()) }}" />
            @if ($needsNik)
                <input type="hidden" name="nik" x-bind:value="nikInput" />
            @endif
            @if ($needsPhone)
                <input type="hidden" name="phone" x-bind:value="phoneInput" />
            @endif
        </form>

        @include('customer.booking.partials.create.add-address-modal')
    </div>
</x-layouts.customer>
