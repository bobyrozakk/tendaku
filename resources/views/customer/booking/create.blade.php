<x-layouts.customer>
    <x-slot name="title">Checkout Sewa Alat Camping - Tendaku</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-sm text-darkbrown-500 mb-1.5">
                    <a href="{{ route('home') }}" class="hover:text-avocado-700 transition">Beranda</a>
                    <span class="text-wheat-400">/</span>
                    <a href="{{ route('customer.catalog.index') }}" class="hover:text-avocado-700 transition">Katalog</a>
                    <span class="text-wheat-400">/</span>
                    <span class="text-darkbrown-800 font-semibold">Booking</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-900 tracking-tight font-heading">
                    Checkout & Pembayaran
                </h1>
            </div>
        </div>
    </x-slot>

    @php
        $user = auth()->user();
        $isGuest = ! $isLoggedIn;

        /*
         | Field yang perlu diisi. Tamu: nama, WA, NIK SELALU muncul.
         | Member: hanya muncul kalau data di akunnya masih kosong.
         */
        $needsName  = $isGuest || empty($user?->name);
        $needsPhone = $isGuest || empty($user?->phone);
        $needsNik   = $isGuest || empty($user?->nik);

        // Alamat antar: hanya relevan untuk member (tamu tidak bisa delivery)
        $addressList = ($addresses ?? collect())->map(fn ($addr) => [
            'id' => $addr->id,
            'label' => $addr->label,
            'recipient_name' => $addr->recipient_name,
            'phone_number' => $addr->phone_number,
            'full_address' => $addr->full_address,
            'province' => $addr->province,
            'city' => $addr->city,
            'district' => $addr->district,
            'village' => $addr->village,
            'postal_code' => $addr->postal_code,
            'notes' => $addr->notes,
            'is_default' => (bool) $addr->is_default,
            'formatted_address' => $addr->formatted_address,
            'delivery_snapshot' => $addr->toDeliverySnapshot(),
        ])->values()->all();

        $initialAddressId = $defaultAddress?->id ?? ($addressList[0]['id'] ?? null);
        $savedAddress = $defaultAddress ? $defaultAddress->toDeliverySnapshot() : ($booking['delivery_address'] ?? ($user?->address ?? ''));
        $needsAddress = $isLoggedIn && empty($addressList) && blank($savedAddress);

        $maskedNik = (! $isGuest && ! empty($user?->nik))
            ? substr($user->nik, 0, 4) . str_repeat('•', max(0, strlen($user->nik) - 8)) . substr($user->nik, -4)
            : null;

        // Tanggal ISO (Y-m-d) untuk input date. Idealnya controller kirim pickup_date_iso & return_date_iso.
        $toIso = function ($formatted, $iso) {
            if ($iso) return $iso;
            try { return \Carbon\Carbon::parse($formatted)->toDateString(); } catch (\Throwable $e) { return null; }
        };

        $items = collect($booking['items'])->values()->map(fn ($it, $k) => [
            'id'               => $it['id'] ?? $k,
            'name'             => $it['name'],
            'category'         => $it['category'] ?? '',
            'specs'            => $it['specs'] ?? '',
            'icon'             => $it['icon'] ?? 'default',
            'price_per_day'    => (int) $it['price_per_day'],
            'deposit_per_unit' => (int) ($it['deposit_per_unit'] ?? 0),
            'qty'              => (int) $it['qty'],
            'max_qty'          => (int) ($it['max_qty'] ?? $it['stock'] ?? 99),
        ])->all();

        $checkoutConfig = [
            'isLoggedIn'        => (bool) $isLoggedIn,
            'needsName'         => (bool) $needsName,
            'needsPhone'        => (bool) $needsPhone,
            'needsNik'          => (bool) $needsNik,
            'needsAddress'      => (bool) $needsAddress,
            // Guest tidak boleh delivery: paksa self_pickup sejak awal
            'pickupMethod'      => $isLoggedIn
                ? ($booking['pickup_method'] ?? 'self_pickup')
                : 'self_pickup',
            'name'              => $needsName ? '' : ($user->name ?? ''),
            'addresses'         => $addressList,
            'selectedAddressId' => $initialAddressId,
            'storeAddressUrl'   => route('customer.addresses.store'),
            'deliveryAddress'   => $savedAddress,
            'deliveryNotes'     => $booking['delivery_notes'] ?? '',
            'items'             => $items,
            'pickupDate'        => $toIso($booking['pickup_date'], $booking['pickup_date_iso'] ?? null),
            'returnDate'        => $toIso($booking['return_date'], $booking['return_date_iso'] ?? null),
            'minDate'           => now()->toDateString(),
            'initialDays'       => (int) $booking['rental_days'],
            'discount'          => (int) ($booking['discount'] ?? 0),
            'deliveryFeeAmount' => 20000,
            'serverDpAmount'    => (int) ($booking['dp_amount'] ?? 0),
            'dpPercentage'      => (int) ($booking['dp_percentage'] ?? 50),
            'expiresAtIso'      => $booking['expires_at_iso'] ?? null,
            'loginUrl'          => route('login'),
            'storeUrl'          => route('checkout.store'),
            'csrf'              => csrf_token(),
            'pickupTime'        => $booking['pickup_time'] ?? '09:00',
        ];

        $inputBase = 'w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-darkbrown-900 placeholder:text-darkbrown-400 focus:ring-2 transition';
        $okBorder  = 'border-wheat-300 focus:border-avocado-600 focus:ring-avocado-500/30';
        $errBorder = 'border-red-400 focus:border-red-500 focus:ring-red-200';
    @endphp

    <div x-data="checkoutPage({{ \Illuminate\Support\Js::from($checkoutConfig) }})" class="py-2 sm:py-4">
        @include('customer.booking.partials.create.handoff')
        <div class="co-collapse" :class="{ 'is-collapsed': phase !== 'form' }">
            <div class="co-inner space-y-6">
                @include('customer.booking.partials.create.trip-banner')
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
                    <div class="lg:col-span-7 xl:col-span-8 space-y-6">
                        @include('customer.booking.partials.create.renter-identity')
                        @include('customer.booking.partials.create.delivery-method')
                        @include('customer.booking.partials.create.rental-details')
                    </div>
                    <div class="lg:col-span-5 xl:col-span-4">
                        @include('customer.booking.partials.create.payment-summary')
                    </div>
                </div>
            </div>
        </div>
        @include('customer.booking.partials.create.add-address-modal')
    </div>
</x-layouts.customer>
