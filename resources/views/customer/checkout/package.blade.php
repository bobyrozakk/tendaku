<x-layouts.customer>
    <x-slot name="title">Checkout Paket Camping - Tendaku</x-slot>
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
                <h1 class="font-display font-normal tracking-tight text-3xl sm:text-4xl text-darkbrown-900">
                    Checkout &amp; Pembayaran
                </h1>
            </div>
        </div>
    </x-slot>

    @php
        $user = auth()->user();
        $isGuest = false;
        $price = (float) $package->price;
        $vendorName = $package->vendor?->name ?? 'Tendaku Vendor';
        $vendorCity = $package->vendor?->city ?? '';
        $image = $package->image_url
            ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80';

        // Data penyewa: hanya minta yang belum ada di akun (sama seperti checkout katalog)
        $needsName = false;
        $needsPhone = blank($user?->phone);
        $needsNik = blank($user?->nik);
        $maskedNik = ! $needsNik
            ? substr($user->nik, 0, 4) . ' ' . str_repeat('•', max(0, strlen($user->nik) - 8)) . ' ' . substr($user->nik, -4)
            : null;

        // Data vendor untuk partial jadwal & pengambilan alat (bentuk sama dengan checkout katalog)
        $booking = [
            'vendor' => [
                'name' => $vendorName,
                'city' => $vendorCity,
                'pickup_location' => $package->vendor?->pickup_location ?? $vendorName,
                'address' => $package->vendor?->address ?? '',
                'operational_hours' => $package->vendor?->operational_hours ?? '',
                'phone' => $package->vendor?->phone ?? '',
            ],
            'items' => [['stock' => 1]],
        ];
        $initialPickupDate = old('pickup_date', $pickupDate->format('Y-m-d'));
        $initialReturnDate = old('return_date', $returnDate->format('Y-m-d'));
        $equipmentLabel = 'Isi Paket';

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
            'packageId' => $package->id,
            'pickupMethod' => old('pickup_method', $pickupMethod),
            'packagePrice' => $price,
            'packageName' => $package->name,
            'pickupDate' => $initialPickupDate,
            'pickupTime' => old('pickup_time', $pickupTime),
            'returnDate' => $initialReturnDate,
            'returnTime' => old('return_time', $returnTime),
            'dpPercentage' => (int) $dpPercentage,
            'paymentOption' => old('payment_option', 'full') === 'dp' ? 'dp' : 'full',
            'durationDays' => $rentalDays,
            'totalHours' => $hours,
            'subtotal' => $subtotal,
            'csrf' => csrf_token(),
            'shippingPreviewUrl' => route('checkout.shipping.preview'),
            'storeAddressUrl' => route('customer.addresses.store'),
            'updateAddressUrl' => route('customer.addresses.update', ['address' => '__ADDRESS__']),
            'addresses' => $addressListJson,
            'selectedAddressId' => $selectedAddrId,
            'name' => $user?->name ?? '',
            'phone' => $user?->phone ?? '',
            'nik' => old('nik', ''),
            'needsPhone' => $needsPhone,
            'needsNik' => $needsNik,
            'requiresApproval' => session()->has('shipping_conflict'),
            'packageItems' => $package->packageItems->map(fn ($item) => [
                'id' => $item->master_item_id,
                'name' => $item->masterItem?->name,
                'qty' => $item->quantity,
                'category' => $item->masterItem?->category?->name,
                'image_url' => $item->masterItem?->image_url,
            ])->values(),
            'minDate' => now()->toDateString(),
            'calYear' => (int) $pickupDate->format('Y'),
            'calMonth' => (int) $pickupDate->format('n') - 1,
            'storeUrl' => route('customer.packages.booking.store', $package),
            'checkoutAttemptId' => old('checkout_attempt_id', (string) \Illuminate\Support\Str::uuid()),
            'vendorCity' => $vendorCity,
            'vendorPickupLocation' => $booking['vendor']['pickup_location'],
            'vendorAddress' => $booking['vendor']['address'],
            'vendorOperationalHours' => $booking['vendor']['operational_hours'],
        ];

        $inputBase = 'w-full rounded-xl border bg-white px-3.5 py-3 text-base sm:text-sm text-darkbrown-900 placeholder:text-darkbrown-400 focus:ring-2 transition';
        $okBorder  = 'border-wheat-300 focus:border-avocado-600 focus:ring-avocado-500/30';
        $errBorder = 'border-red-400 focus:border-red-500 focus:ring-red-200';
    @endphp

    <div x-data="packageCheckout({{ \Illuminate\Support\Js::from($packageCheckoutConfig) }})" class="py-2 sm:py-4 pb-28 lg:pb-4">
        @include('customer.booking.partials.create.handoff')
        <div class="co-collapse" :class="{ 'is-collapsed': phase !== 'form' }">
            <div class="co-inner space-y-6">
                @include('customer.booking.partials.create.trip-banner')
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
                    <div class="lg:col-span-7 xl:col-span-8 space-y-6" :inert="isProcessing">
                        {{-- Urutan alur: jadwal (+ cuaca) -> isi paket -> cara terima -> siapa penyewa --}}
                        @include('customer.booking.partials.create.rental-schedule')
                        @include('customer.checkout.partials.package-contents')
                        @include('customer.booking.partials.create.delivery-method')
                        @include('customer.booking.partials.create.renter-identity')
                    </div>
                    <div class="lg:col-span-5 xl:col-span-4">
                        @include('customer.checkout.partials.package-summary')
                    </div>
                </div>
            </div>
        </div>
        @include('customer.booking.partials.create.mobile-checkout-bar')
        @include('customer.booking.partials.create.add-address-modal')
    </div>
</x-layouts.customer>
