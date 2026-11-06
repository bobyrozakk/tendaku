@php
    $vendor = $vendor ?? auth()->user()?->vendor;
    $shippingSetting = $shippingSetting ?? $vendor?->shippingSetting;
    $isConfigured = $shippingSetting !== null;
    $hasCoordinates = $vendor !== null && $vendor->hasCoordinates();

    $hasOldInput = session()->hasOldInput();
    $hasTariffErrors = $errors->hasAny(['rate_per_km', 'weight_threshold_kg', 'extra_weight_fee_per_kg']);
    $hasLocationErrors = $errors->hasAny(['address', 'city', 'latitude', 'longitude']);

    $ratePerKmValue = $hasOldInput && $hasTariffErrors ? old('rate_per_km') : $shippingSetting?->rate_per_km;
    $weightThresholdKgValue = $hasOldInput && $hasTariffErrors ? old('weight_threshold_kg') : $shippingSetting?->weight_threshold_kg;
    $extraWeightFeePerKgValue = $hasOldInput && $hasTariffErrors ? old('extra_weight_fee_per_kg') : $shippingSetting?->extra_weight_fee_per_kg;

    $storeAddressValue = $hasOldInput && $hasLocationErrors ? old('address', $vendor?->address) : $vendor?->address;
    $storeCityValue = $hasOldInput && $hasLocationErrors ? old('city', $vendor?->city) : $vendor?->city;
    $storeLatitudeValue = $hasOldInput && $hasLocationErrors ? old('latitude', $vendor?->latitude) : $vendor?->latitude;
    $storeLongitudeValue = $hasOldInput && $hasLocationErrors ? old('longitude', $vendor?->longitude) : $vendor?->longitude;
@endphp

<x-layouts.vendor title="Tendaku - Pengaturan Pengiriman Owner">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Pengaturan Pengiriman
                </h1>
                <p class="text-xs text-darkbrown-500 mt-0.5">
                    Konfigurasi titik lokasi toko dan tarif pengantaran pesanan berdasarkan jarak kilometer serta batas berat muatan.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                @if ($hasCoordinates)
                    <x-badge variant="avocado" :dot="true">Lokasi Toko Tersimpan</x-badge>
                @else
                    <x-badge variant="goldenrod" :dot="true">Titik Lokasi Toko Belum Diatur</x-badge>
                @endif

                @if ($isConfigured)
                    <x-badge variant="avocado" :dot="true">Tarif Terkonfigurasi</x-badge>
                @else
                    <x-badge variant="goldenrod" :dot="true">Belum Dikonfigurasi</x-badge>
                @endif
            </div>
        </div>
    </x-slot>

    <div
        class="max-w-4xl space-y-6"
        x-data="{ showEditModal: {{ $isConfigured && $hasTariffErrors ? 'true' : 'false' }} }"
        @keydown.escape.window="showEditModal = false"
    >
        @if (session('success'))
            <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-xs font-semibold text-emerald-900 flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-xs font-semibold text-red-900 flex items-start gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0 mt-0.5"></i>
                <div>
                    <p class="font-bold">Gagal menyimpan pengaturan pengiriman:</p>
                    <ul class="mt-1 list-disc list-inside space-y-0.5 font-normal">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- ==============================================================
             SECTION 1: LOKASI TOKO (KOORDINAT ASAL PENGIRIMAN)
             ============================================================== --}}
        <section
            aria-labelledby="section-store-location-heading"
            x-data="{
                showLocationModal: {{ $hasCoordinates && $hasLocationErrors ? 'true' : 'false' }},
                openLocationModal() {
                    this.showLocationModal = true;
                    this.$nextTick(() => {
                        window.dispatchEvent(new CustomEvent('location-picker:refresh'));
                    });
                },
                closeLocationModal() {
                    this.showLocationModal = false;
                }
            }"
            @keydown.escape.window="closeLocationModal()"
            class="space-y-4"
        >
            @if ($hasCoordinates)
                {{-- Ringkasan Lokasi Toko Tersimpan --}}
                <x-card class="border-wheat-200 bg-white">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-wheat-200/80">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-avocado-600 text-white shrink-0">
                                <i data-lucide="map-pin" class="w-5 h-5"></i>
                            </span>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 id="section-store-location-heading" class="text-base font-extrabold text-darkbrown-900">
                                        Lokasi Toko
                                    </h2>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                        Lokasi Toko Tersimpan
                                    </span>
                                </div>
                                <p class="text-xs text-darkbrown-500 mt-0.5">
                                    Titik koordinat asal perhitungan jarak pengantaran untuk toko <strong>{{ $vendor?->name ?? 'Vendor' }}</strong>.
                                </p>
                            </div>
                        </div>

                        <div class="self-start sm:self-auto">
                            <button
                                type="button"
                                @click="openLocationModal()"
                                class="btn-tendaku-outline !px-4 !py-2 text-xs font-bold cursor-pointer"
                            >
                                <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                <span>Edit Lokasi</span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <div class="sm:col-span-2 rounded-xl border border-wheat-200 bg-wheat-50/40 p-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">
                                Alamat & Kota Toko
                            </p>
                            <p class="mt-1.5 text-sm font-bold text-darkbrown-900 leading-relaxed">
                                {{ filled($vendor?->address) ? $vendor->address : 'Alamat toko belum diisi' }}
                            </p>
                            <p class="mt-1 text-xs font-semibold text-darkbrown-600">
                                Kota: <span class="text-darkbrown-900">{{ filled($vendor?->city) ? $vendor->city : '-' }}</span>
                            </p>
                        </div>

                        <div class="rounded-xl border border-wheat-200 bg-wheat-50/40 p-4 flex flex-col justify-between">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">
                                    Koordinat Peta
                                </p>
                                <div class="mt-1.5 space-y-1 font-mono text-xs text-darkbrown-800">
                                    <p>
                                        <span class="text-darkbrown-500">Lat:</span>
                                        <strong class="font-bold text-darkbrown-900">{{ number_format((float) $vendor->latitude, 7, '.', '') }}</strong>
                                    </p>
                                    <p>
                                        <span class="text-darkbrown-500">Lng:</span>
                                        <strong class="font-bold text-darkbrown-900">{{ number_format((float) $vendor->longitude, 7, '.', '') }}</strong>
                                    </p>
                                </div>
                            </div>
                            <p class="mt-2 text-[11px] text-avocado-700 font-medium">
                                Siap digunakan untuk perhitungan rute.
                            </p>
                        </div>
                    </div>
                </x-card>

                {{-- Modal Edit Lokasi Toko --}}
                <div
                    x-show="showLocationModal"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click.self="closeLocationModal()"
                    class="fixed inset-0 z-[80] flex items-center justify-center bg-darkbrown-950/60 p-4 overflow-y-auto"
                    style="display: none;"
                >
                    <div
                        x-show="showLocationModal"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                        class="w-full max-w-2xl rounded-xl border border-wheat-200 bg-white shadow-tendaku my-8 overflow-hidden"
                    >
                        <div class="flex items-start justify-between gap-4 border-b border-wheat-200 px-6 py-4 bg-wheat-50/60">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-avocado-100 text-avocado-700 border border-avocado-200 shrink-0">
                                    <i data-lucide="map-pin" class="w-4 h-4"></i>
                                </span>
                                <div>
                                    <h3 class="text-base font-extrabold text-darkbrown-900">
                                        Edit Lokasi Toko
                                    </h3>
                                    <p class="mt-0.5 text-xs text-darkbrown-500">
                                        Perbarui detail alamat atau geser pin pada peta untuk menyesuaikan titik koordinat toko.
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="closeLocationModal()"
                                class="rounded-lg p-1.5 text-darkbrown-400 transition hover:bg-wheat-200/60 hover:text-darkbrown-700 cursor-pointer"
                                title="Tutup"
                            >
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>

                        <form action="{{ route('vendor.shipping.location.update') }}" method="POST" class="space-y-4 px-6 py-5">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="sm:col-span-2">
                                    <x-input-label for="modal_vendor_address" value="Alamat Lengkap Toko" class="font-bold text-xs text-darkbrown-900" />
                                    <x-text-input
                                        id="modal_vendor_address"
                                        name="address"
                                        type="text"
                                        class="mt-1.5 w-full text-sm bg-white"
                                        :value="$storeAddressValue"
                                        placeholder="Contoh: Jl. Soekarno Hatta No. 9, Kec. Lowokwaru"
                                    />
                                    <x-input-error :messages="$errors->get('address')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="modal_vendor_city" value="Kota / Kabupaten" class="font-bold text-xs text-darkbrown-900" />
                                    <x-text-input
                                        id="modal_vendor_city"
                                        name="city"
                                        type="text"
                                        class="mt-1.5 w-full text-sm bg-white"
                                        :value="$storeCityValue"
                                        placeholder="Contoh: Kota Malang"
                                    />
                                    <x-input-error :messages="$errors->get('city')" class="mt-1" />
                                </div>
                            </div>

                            <div>
                                <x-location-picker
                                    id="vendor-store-location-modal-picker"
                                    :latitude="$storeLatitudeValue"
                                    :longitude="$storeLongitudeValue"
                                    latitude-name="latitude"
                                    longitude-name="longitude"
                                    label="Titik Koordinat Lokasi Toko"
                                    help-text="Klik pada peta atau geser pin ke posisi toko Anda. Menggeser pin tidak akan mengubah teks alamat di atas."
                                    :required="true"
                                />
                            </div>

                            <div class="flex items-center justify-end gap-2.5 border-t border-wheat-200 pt-4">
                                <button
                                    type="button"
                                    @click="closeLocationModal()"
                                    class="btn-tendaku-secondary !px-4 !py-2.5 text-xs font-bold cursor-pointer"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="btn-tendaku-primary !px-5 !py-2.5 text-xs font-extrabold cursor-pointer"
                                >
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                    <span>Simpan Lokasi Toko</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                {{-- State Belum Ada Koordinat Lokasi Toko --}}
                <x-card
                    title="Lokasi Toko"
                    subtitle="Tentukan titik koordinat lokasi toko Anda sebagai titik asal perhitungan jarak pengiriman."
                    :headerBorder="true"
                >
                    <x-slot name="action">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-goldenrod-100 text-goldenrod-900 border border-goldenrod-300">
                            Titik Lokasi Toko Belum Diatur
                        </span>
                    </x-slot>

                    <form action="{{ route('vendor.shipping.location.update') }}" method="POST" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="rounded-xl border border-wheat-200 bg-wheat-50/50 p-4">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="sm:col-span-2">
                                    <x-input-label for="vendor_address" value="Alamat Lengkap Toko" class="font-bold text-xs text-darkbrown-900" />
                                    <x-text-input
                                        id="vendor_address"
                                        name="address"
                                        type="text"
                                        class="mt-1.5 w-full text-sm bg-white"
                                        :value="$storeAddressValue"
                                        placeholder="Contoh: Jl. Soekarno Hatta No. 9, Kec. Lowokwaru"
                                    />
                                    <p class="mt-1 text-[11px] text-darkbrown-500">
                                        Alamat tertulis toko Anda saat ini: <strong>{{ filled($vendor?->address) ? $vendor->address : 'Belum diisi' }}</strong>
                                    </p>
                                    <x-input-error :messages="$errors->get('address')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="vendor_city" value="Kota / Kabupaten" class="font-bold text-xs text-darkbrown-900" />
                                    <x-text-input
                                        id="vendor_city"
                                        name="city"
                                        type="text"
                                        class="mt-1.5 w-full text-sm bg-white"
                                        :value="$storeCityValue"
                                        placeholder="Contoh: Kota Malang"
                                    />
                                    <p class="mt-1 text-[11px] text-darkbrown-500">
                                        Kota tersimpan: <strong>{{ filled($vendor?->city) ? $vendor->city : 'Belum diisi' }}</strong>
                                    </p>
                                    <x-input-error :messages="$errors->get('city')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <x-location-picker
                            id="vendor-store-location-picker"
                            :latitude="$storeLatitudeValue"
                            :longitude="$storeLongitudeValue"
                            latitude-name="latitude"
                            longitude-name="longitude"
                            label="Pilih Titik Koordinat Lokasi Toko"
                            help-text="Klik pada peta, geser pin, atau gunakan tombol Gunakan Lokasi Saya untuk menandai lokasi fisik toko Anda."
                            :required="true"
                        />

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-wheat-200">
                            <p class="text-[11px] text-darkbrown-500">
                                Titik koordinat hanya disimpan ketika Anda memilih lokasi pada peta dan menekan tombol simpan.
                            </p>

                            <button
                                type="submit"
                                class="btn-tendaku-primary !px-6 !py-2.5 text-xs font-extrabold cursor-pointer w-full sm:w-auto"
                            >
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>Simpan Lokasi Toko</span>
                            </button>
                        </div>
                    </form>
                </x-card>
            @endif
        </section>

        {{-- ==============================================================
             SECTION 2: KONFIGURASI TARIF PENGIRIMAN
             ============================================================== --}}
        @if ($isConfigured)
            {{-- Ringkasan Tarif Pengiriman Aktif + Tombol Edit Pop-up --}}
            <x-card class="border-avocado-300/80 bg-white">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-wheat-200/80">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-avocado-600 text-white shadow-sm shrink-0">
                            <i data-lucide="truck" class="w-5 h-5"></i>
                        </span>
                        <div>
                            <h2 class="text-base font-extrabold text-darkbrown-900">
                                Ringkasan Tarif Pengiriman Aktif
                            </h2>
                            <p class="text-xs text-darkbrown-500">
                                Konfigurasi tarif pengiriman yang berlaku untuk toko <strong>{{ $vendor?->name ?? 'Vendor' }}</strong>.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 self-start sm:self-auto">
                        @if ($shippingSetting->updated_at)
                            <span class="text-[11px] font-medium text-darkbrown-500">
                                Diperbarui {{ $shippingSetting->updated_at->format('d M Y, H:i') }}
                            </span>
                        @endif

                        <button
                            type="button"
                            @click="showEditModal = true"
                            class="btn-tendaku-primary !px-4 !py-2 text-xs font-extrabold cursor-pointer"
                        >
                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                            <span>Edit Tarif</span>
                        </button>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div class="rounded-xl border border-wheat-200 bg-white p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">
                            Tarif per Kilometer
                        </p>
                        <p class="mt-1.5 text-xl font-extrabold text-avocado-800">
                            Rp {{ number_format((float) $shippingSetting->rate_per_km, 0, ',', '.') }}
                            <span class="text-xs font-semibold text-darkbrown-500">/ km</span>
                        </p>
                        <p class="mt-1 text-[11px] text-darkbrown-400">
                            Biaya pengantaran per 1 km jarak tempuh.
                        </p>
                    </div>

                    <div class="rounded-xl border border-wheat-200 bg-white p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">
                            Batas Berat Normal
                        </p>
                        <p class="mt-1.5 text-xl font-extrabold text-darkbrown-900">
                            {{ rtrim(rtrim(number_format((float) $shippingSetting->weight_threshold_kg, 2, ',', '.'), '0'), ',') }}
                            <span class="text-xs font-semibold text-darkbrown-500">kg</span>
                        </p>
                        <p class="mt-1 text-[11px] text-darkbrown-400">
                            Batas berat pesanan tanpa biaya tambahan.
                        </p>
                    </div>

                    <div class="rounded-xl border border-wheat-200 bg-white p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">
                            Biaya Tambahan Berat
                        </p>
                        <p class="mt-1.5 text-xl font-extrabold text-avocado-800">
                            Rp {{ number_format((float) $shippingSetting->extra_weight_fee_per_kg, 0, ',', '.') }}
                            <span class="text-xs font-semibold text-darkbrown-500">/ kg</span>
                        </p>
                        <p class="mt-1 text-[11px] text-darkbrown-400">
                            Biaya ekstra tiap 1 kg di atas batas normal.
                        </p>
                    </div>
                </div>

                {{-- Panduan Skema Perhitungan --}}
                <div class="mt-4 rounded-xl border border-avocado-200/80 bg-avocado-50/60 p-3.5">
                    <div class="flex items-start gap-2.5">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-avocado-600/15 text-avocado-800 shrink-0 mt-0.5">
                            <i data-lucide="info" class="w-3.5 h-3.5"></i>
                        </span>
                        <p class="text-xs text-darkbrown-700 leading-relaxed">
                            <strong class="text-darkbrown-900">Skema Perhitungan Ongkir:</strong>
                            (Jarak km × Tarif per Kilometer) + (Kelebihan Berat kg × Biaya Tambahan per KG) apabila berat pesanan melebihi Batas Berat Normal. Tanpa biaya dasar tetap (<em>base fee</em>).
                        </p>
                    </div>
                </div>
            </x-card>

            {{-- Modal Pop-up Edit Tarif Pengiriman --}}
            <template x-teleport="body">
                <div
                    x-show="showEditModal"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click.self="showEditModal = false"
                    class="fixed inset-0 z-[80] flex items-center justify-center bg-darkbrown-950/60 p-4"
                    style="display: none;"
                >
                    <div
                        x-show="showEditModal"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                        class="w-full max-w-lg rounded-2xl border border-wheat-200 bg-white shadow-tendaku-lg overflow-hidden"
                    >
                        <div class="flex items-start justify-between gap-4 border-b border-wheat-200 px-6 py-4 bg-wheat-50/60">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-avocado-100 text-avocado-700 border border-avocado-200 shrink-0">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </span>
                                <div>
                                    <h3 class="text-base font-extrabold text-darkbrown-900">
                                        Edit Tarif Pengiriman Toko
                                    </h3>
                                    <p class="mt-0.5 text-xs text-darkbrown-500">
                                        Perbarui komponen tarif pengiriman toko Anda.
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="showEditModal = false"
                                class="rounded-lg p-1.5 text-darkbrown-400 transition hover:bg-wheat-200/60 hover:text-darkbrown-700 cursor-pointer"
                                title="Tutup"
                            >
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>

                        <form action="{{ route('vendor.shipping.update') }}" method="POST" class="space-y-4 px-6 py-5">
                            @csrf
                            @method('PUT')

                            @if ($hasTariffErrors)
                                <div class="rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs text-red-800">
                                    <p class="font-bold">Periksa kembali input tarif Anda:</p>
                                    <ul class="mt-1 list-disc list-inside space-y-0.5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- 1. Tarif per Kilometer (Rp/km) --}}
                            <div>
                                <x-input-label for="rate_per_km" value="Tarif per Kilometer (Rp/km) *" class="font-bold text-xs text-darkbrown-900" />
                                <div class="relative mt-1.5">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center font-bold text-xs text-darkbrown-500 pointer-events-none">
                                        Rp
                                    </span>
                                    <x-text-input
                                        id="rate_per_km"
                                        name="rate_per_km"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        inputmode="decimal"
                                        class="w-full pl-10 pr-12 py-2.5 text-sm font-bold text-darkbrown-900 bg-white"
                                        :value="$ratePerKmValue"
                                        placeholder="Contoh: 5000"
                                        required
                                    />
                                    <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center font-semibold text-xs text-darkbrown-500 pointer-events-none">
                                        / km
                                    </span>
                                </div>
                                <p class="mt-1 text-[11px] text-darkbrown-500">
                                    Biaya pengiriman untuk setiap 1 kilometer jarak pengantaran ke pelanggan.
                                </p>
                                <x-input-error :messages="$errors->get('rate_per_km')" class="mt-1" />
                            </div>

                            {{-- 2. Batas Berat Normal (kg) --}}
                            <div>
                                <x-input-label for="weight_threshold_kg" value="Batas Berat Normal (kg) *" class="font-bold text-xs text-darkbrown-900" />
                                <div class="relative mt-1.5">
                                    <x-text-input
                                        id="weight_threshold_kg"
                                        name="weight_threshold_kg"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        inputmode="decimal"
                                        class="w-full pl-3.5 pr-12 py-2.5 text-sm font-bold text-darkbrown-900 bg-white"
                                        :value="$weightThresholdKgValue"
                                        placeholder="Contoh: 10"
                                        required
                                    />
                                    <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center font-semibold text-xs text-darkbrown-500 pointer-events-none">
                                        kg
                                    </span>
                                </div>
                                <p class="mt-1 text-[11px] text-darkbrown-500">
                                    Kuota berat maksimal pesanan yang masih ditanggung tarif normal.
                                </p>
                                <x-input-error :messages="$errors->get('weight_threshold_kg')" class="mt-1" />
                            </div>

                            {{-- 3. Biaya Tambahan per KG (Rp/kg) --}}
                            <div>
                                <x-input-label for="extra_weight_fee_per_kg" value="Biaya Tambahan per KG (Rp/kg) *" class="font-bold text-xs text-darkbrown-900" />
                                <div class="relative mt-1.5">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center font-bold text-xs text-darkbrown-500 pointer-events-none">
                                        Rp
                                    </span>
                                    <x-text-input
                                        id="extra_weight_fee_per_kg"
                                        name="extra_weight_fee_per_kg"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        inputmode="decimal"
                                        class="w-full pl-10 pr-12 py-2.5 text-sm font-bold text-darkbrown-900 bg-white"
                                        :value="$extraWeightFeePerKgValue"
                                        placeholder="Contoh: 2000"
                                        required
                                    />
                                    <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center font-semibold text-xs text-darkbrown-500 pointer-events-none">
                                        / kg
                                    </span>
                                </div>
                                <p class="mt-1 text-[11px] text-darkbrown-500">
                                    Tarif tambahan untuk setiap 1 kg kelebihan berat di atas batas normal.
                                </p>
                                <x-input-error :messages="$errors->get('extra_weight_fee_per_kg')" class="mt-1" />
                            </div>

                            <div class="flex items-center justify-end gap-2.5 border-t border-wheat-200 pt-4">
                                <button
                                    type="button"
                                    @click="showEditModal = false"
                                    class="btn-tendaku-secondary !px-4 !py-2.5 text-xs font-bold cursor-pointer"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="btn-tendaku-primary !px-5 !py-2.5 text-xs font-extrabold cursor-pointer"
                                >
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                    <span>Simpan Pengaturan</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
        @else
            {{-- Empty State + Form Konfigurasi Awal Tarif (ketika belum diatur) --}}
            <x-card variant="warm" class="border-wheat-300">
                <div class="flex flex-col sm:flex-row items-start gap-4">
                    <div class="w-11 h-11 rounded-2xl bg-avocado-100 border border-avocado-200 text-avocado-700 flex items-center justify-center shrink-0">
                        <i data-lucide="truck" class="w-5 h-5"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-extrabold text-darkbrown-900">
                                Tarif Pengiriman Belum Diatur
                            </h2>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-goldenrod-100 text-goldenrod-900 border border-goldenrod-300">
                                Perlu Konfigurasi
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-darkbrown-600 leading-relaxed">
                            Toko Anda belum memiliki pengaturan tarif pengiriman. Tentukan tarif per kilometer, batas berat normal, dan biaya tambahan per kilogram pada formulir di bawah ini agar ongkos kirim pesanan dapat dihitung sesuai kebijakan toko Anda.
                        </p>
                    </div>
                </div>
            </x-card>

            <x-card
                title="Parameter Tarif Pengiriman Toko"
                subtitle="Atur komponen perhitungan ongkos kirim tanpa biaya dasar tetap (tanpa base fee)."
                :headerBorder="true"
            >
                <form action="{{ route('vendor.shipping.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        {{-- 1. Tarif per Kilometer (Rp/km) --}}
                        <div class="flex flex-col justify-between rounded-2xl border border-wheat-200 bg-wheat-50/50 p-4">
                            <div>
                                <x-input-label for="rate_per_km" value="Tarif per Kilometer (Rp/km) *" class="font-bold text-xs text-darkbrown-900" />
                                <p class="mt-1 text-[11px] text-darkbrown-500 leading-relaxed">
                                    Biaya pengiriman untuk setiap 1 kilometer jarak pengantaran dari toko ke lokasi pelanggan.
                                </p>
                            </div>

                            <div class="mt-3">
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center font-bold text-xs text-darkbrown-500 pointer-events-none">
                                        Rp
                                    </span>
                                    <x-text-input
                                        id="rate_per_km"
                                        name="rate_per_km"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        inputmode="decimal"
                                        class="w-full pl-10 pr-12 py-2.5 text-sm font-bold text-darkbrown-900 bg-white"
                                        :value="old('rate_per_km')"
                                        placeholder="Contoh: 5000"
                                        required
                                    />
                                    <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center font-semibold text-xs text-darkbrown-500 pointer-events-none">
                                        / km
                                    </span>
                                </div>
                                <x-input-error :messages="$errors->get('rate_per_km')" class="mt-1.5" />
                            </div>
                        </div>

                        {{-- 2. Batas Berat Normal (kg) --}}
                        <div class="flex flex-col justify-between rounded-2xl border border-wheat-200 bg-wheat-50/50 p-4">
                            <div>
                                <x-input-label for="weight_threshold_kg" value="Batas Berat Normal (kg) *" class="font-bold text-xs text-darkbrown-900" />
                                <p class="mt-1 text-[11px] text-darkbrown-500 leading-relaxed">
                                    Kuota berat maksimal pesanan yang masih ditanggung tarif normal tanpa biaya tambahan berat.
                                </p>
                            </div>

                            <div class="mt-3">
                                <div class="relative">
                                    <x-text-input
                                        id="weight_threshold_kg"
                                        name="weight_threshold_kg"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        inputmode="decimal"
                                        class="w-full pl-3.5 pr-12 py-2.5 text-sm font-bold text-darkbrown-900 bg-white"
                                        :value="old('weight_threshold_kg')"
                                        placeholder="Contoh: 10"
                                        required
                                    />
                                    <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center font-semibold text-xs text-darkbrown-500 pointer-events-none">
                                        kg
                                    </span>
                                </div>
                                <x-input-error :messages="$errors->get('weight_threshold_kg')" class="mt-1.5" />
                            </div>
                        </div>

                        {{-- 3. Biaya Tambahan per KG (Rp/kg) --}}
                        <div class="flex flex-col justify-between rounded-2xl border border-wheat-200 bg-wheat-50/50 p-4">
                            <div>
                                <x-input-label for="extra_weight_fee_per_kg" value="Biaya Tambahan per KG (Rp/kg) *" class="font-bold text-xs text-darkbrown-900" />
                                <p class="mt-1 text-[11px] text-darkbrown-500 leading-relaxed">
                                    Tarif tambahan untuk setiap 1 kg kelebihan berat di atas batas berat normal toko.
                                </p>
                            </div>

                            <div class="mt-3">
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center font-bold text-xs text-darkbrown-500 pointer-events-none">
                                        Rp
                                    </span>
                                    <x-text-input
                                        id="extra_weight_fee_per_kg"
                                        name="extra_weight_fee_per_kg"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        inputmode="decimal"
                                        class="w-full pl-10 pr-12 py-2.5 text-sm font-bold text-darkbrown-900 bg-white"
                                        :value="old('extra_weight_fee_per_kg')"
                                        placeholder="Contoh: 2000"
                                        required
                                    />
                                    <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center font-semibold text-xs text-darkbrown-500 pointer-events-none">
                                        / kg
                                    </span>
                                </div>
                                <x-input-error :messages="$errors->get('extra_weight_fee_per_kg')" class="mt-1.5" />
                            </div>
                        </div>
                    </div>

                    {{-- Panduan Skema Perhitungan --}}
                    <div class="rounded-2xl border border-avocado-200/80 bg-avocado-50/50 p-4">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-avocado-600/15 text-avocado-800 shrink-0 mt-0.5">
                                <i data-lucide="info" class="w-4 h-4"></i>
                            </span>
                            <div class="space-y-1 text-xs text-darkbrown-700">
                                <p class="font-bold text-darkbrown-900">
                                    Skema Perhitungan Ongkos Kirim Toko
                                </p>
                                <p class="leading-relaxed">
                                    Total Ongkir = <strong>(Jarak km × Tarif per Kilometer)</strong> + <strong>(Kelebihan Berat kg × Biaya Tambahan per KG)</strong> apabila berat pesanan melebihi Batas Berat Normal. Tidak ada biaya dasar tetap (<em>base fee</em>).
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Action Bar --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-wheat-200">
                        <p class="text-[11px] text-darkbrown-500">
                            Perubahan tarif hanya berlaku untuk toko Anda dan tidak memengaruhi vendor lain.
                        </p>

                        <button
                            type="submit"
                            class="btn-tendaku-primary !px-6 !py-2.5 text-xs font-extrabold cursor-pointer w-full sm:w-auto"
                        >
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Simpan Pengaturan</span>
                        </button>
                    </div>
                </form>
            </x-card>
        @endif
    </div>
</x-layouts.vendor>
