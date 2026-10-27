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

            <ol class="hidden sm:flex items-center gap-2 text-sm" aria-label="Tahapan pemesanan">
                <li class="flex items-center gap-2 text-avocado-700 font-semibold">
                    <span class="w-6 h-6 rounded-full bg-avocado-600 text-white flex items-center justify-center">
                        <i data-lucide="check" class="w-3 h-3"></i>
                    </span>
                    Pilih alat
                </li>
                <span class="w-8 h-0.5 rounded bg-avocado-500"></span>
                <li class="flex items-center gap-2 text-darkbrown-900 font-bold" aria-current="step">
                    <span class="w-6 h-6 rounded-full bg-darkbrown-900 text-wheat-100 flex items-center justify-center text-xs">2</span>
                    Checkout
                </li>
                <span class="w-8 h-0.5 rounded bg-wheat-300"></span>
                <li class="flex items-center gap-2 text-darkbrown-500 font-medium">
                    <span class="w-6 h-6 rounded-full border-2 border-wheat-300 text-darkbrown-500 flex items-center justify-center text-xs">3</span>
                    Bayar
                </li>
            </ol>
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
        $savedAddress = $booking['delivery_address'] ?? ($user?->address ?? '');
        $needsAddress = $isLoggedIn && blank($savedAddress);

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
            'deliveryAddress'   => $savedAddress,
            'deliveryNotes'     => $booking['delivery_notes'] ?? '',
            'items'             => $items,
            'pickupDate'        => $toIso($booking['pickup_date'], $booking['pickup_date_iso'] ?? null),
            'returnDate'        => $toIso($booking['return_date'], $booking['return_date_iso'] ?? null),
            'minDate'           => now()->toDateString(),
            'initialDays'       => (int) $booking['rental_days'],
            'discount'          => (int) ($booking['discount'] ?? 0),
            'deliveryFeeAmount' => 25000,
            'serverDpAmount'    => (int) ($booking['dp_amount'] ?? 0),
            'dpPercentage'      => (int) ($booking['dp_percentage'] ?? 50),
            'expiresAtIso'      => $booking['expires_at_iso'] ?? null,
            'loginUrl'          => route('login'),
            'storeUrl'          => route('checkout.store'),
            'csrf'              => csrf_token(),
        ];

        $inputBase = 'w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-darkbrown-900 placeholder:text-darkbrown-400 focus:ring-2 transition';
        $okBorder  = 'border-wheat-300 focus:border-avocado-600 focus:ring-avocado-500/30';
        $errBorder = 'border-red-400 focus:border-red-500 focus:ring-red-200';
    @endphp

    <style>
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none !important;
            appearance: none !important;
            margin: 0 !important;
        }

        input[type="number"] {
            -moz-appearance: textfield !important;
            appearance: textfield !important;
        }

        /* ===== Serah terima: form menyusut ke atas, berganti alur status ===== */
        .co-collapse { display: grid; grid-template-rows: 1fr; transform-origin: top center;
            transition: grid-template-rows .8s cubic-bezier(.65, 0, .35, 1), opacity .5s ease, transform .8s cubic-bezier(.65, 0, .35, 1); }
        .co-collapse > .co-inner { min-height: 0; }
        .co-collapse.is-collapsed { grid-template-rows: 0fr; opacity: 0; transform: translateY(-1.25rem) scale(.985); }
        .co-collapse.is-collapsed > .co-inner { overflow: hidden; }

        .co-preview { display: grid; grid-template-rows: 0fr; opacity: 0;
            transition: grid-template-rows .8s cubic-bezier(.65, 0, .35, 1) .3s, opacity .5s ease .5s; }
        .co-preview.is-open { grid-template-rows: 1fr; opacity: 1; }
        .co-preview > .co-inner { overflow: hidden; min-height: 0; }

        @@keyframes co-draw { to { stroke-dashoffset: 0; } }

        .co-ring { stroke-dasharray: 166; stroke-dashoffset: 166; animation: co-draw .7s cubic-bezier(.65, 0, .45, 1) .25s forwards; }
        .co-bigtick { stroke-dasharray: 48; stroke-dashoffset: 48; animation: co-draw .4s cubic-bezier(.65, 0, .45, 1) .85s forwards; }
        .co-halo { animation: co-halo 1s ease-out .9s both; }
        @@keyframes co-halo { 0% { opacity: .9; transform: scale(.7); } 100% { opacity: 0; transform: scale(1.5); } }
        .co-copy { animation: co-copy .6s cubic-bezier(.2, .8, .2, 1) 1.1s both; }
        @@keyframes co-copy { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

        @@media (prefers-reduced-motion: reduce) {
            .co-collapse, .co-preview { transition: none; }
            .co-ring, .co-bigtick { animation-duration: .01s; animation-delay: 0s; }
            .co-halo { animation: none; opacity: 0; }
            .co-copy { animation: none; }
        }
    </style>

    <div x-data="checkoutPage({{ \Illuminate\Support\Js::from($checkoutConfig) }})" class="py-2 sm:py-4">

        <!-- booking dibuat -->
        <div class="co-preview" :class="{ 'is-open': phase !== 'form' }" :inert="phase === 'form'" :aria-hidden="phase === 'form' ? 'true' : 'false'">
            <div class="co-inner">
                <div class="flex flex-col items-center justify-center text-center gap-5 px-4" style="min-height: 62vh;" role="status" aria-live="polite">
                    <template x-if="phase !== 'form'">
                        <div class="flex flex-col items-center gap-5">
                            <div class="relative w-32 h-32 flex items-center justify-center">
                                <span class="co-halo absolute inset-0 rounded-full bg-avocado-200/70" aria-hidden="true"></span>
                            </div>

                            <div class="co-copy space-y-2">
                                <h2 class="text-3xl sm:text-4xl font-extrabold text-darkbrown-900 tracking-tight font-heading">Booking dibuat</h2>
                                <p x-show="createdCode" x-cloak class="font-mono text-base font-extrabold text-darkbrown-800" x-text="'#' + createdCode"></p>
                                <p class="text-sm text-darkbrown-600">Menyiapkan halaman pembayaran Anda…</p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- ============ FORM CHECKOUT (menyusut ke atas setelah booking dibuat) ============ -->
        <div class="co-collapse" :class="{ 'is-collapsed': phase !== 'form' }">
        <div class="co-inner space-y-6">

        <!-- ============ TRIP BANNER ============ -->
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
                            {{ $booking['vendor']['name'] }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/15 px-3 py-1">
                            <i data-lucide="calendar" class="w-4 h-4 text-sunglow-300"></i>
                            <span x-text="fmtShort(pickupDate) + ' – ' + fmtShort(returnDate)">{{ $booking['pickup_date'] }} – {{ $booking['return_date'] }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">

            <!-- ============ KOLOM KIRI ============ -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-6">

                <!-- DATA PENYEWA -->
                <section id="section-identity" class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm">
                    <div class="flex items-center gap-3 px-5 sm:px-6 pt-5 sm:pt-6">
                        <span class="w-8 h-8 rounded-full bg-avocado-700 text-white font-bold flex items-center justify-center shrink-0">1</span>
                        <div>
                            <h2 class="text-lg font-bold text-darkbrown-900 font-heading leading-tight">Data penyewa</h2>
                            <p class="text-sm text-darkbrown-500">
                                @if ($isGuest || $needsName || $needsPhone || $needsNik)
                                    Dipakai vendor untuk mencocokkan identitas saat serah terima alat.
                                @else
                                    Data diambil dari akun Anda. Sudah lengkap.
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 space-y-4">

                        {{-- Member: tampilkan data yang sudah ada --}}
                        @if ($isLoggedIn && (! $needsName || ! $needsPhone || ! $needsNik))
                        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                            @unless ($needsName)
                            <div class="rounded-xl bg-wheat-50 border border-wheat-200 px-3.5 py-2.5">
                                <dt class="text-darkbrown-500">Nama</dt>
                                <dd class="font-bold text-darkbrown-900 truncate">{{ $user->name }}</dd>
                            </div>
                            @endunless
                            @unless ($needsPhone)
                            <div class="rounded-xl bg-wheat-50 border border-wheat-200 px-3.5 py-2.5">
                                <dt class="text-darkbrown-500">WhatsApp</dt>
                                <dd class="font-bold text-darkbrown-900">{{ $user->phone }}</dd>
                            </div>
                            @endunless
                            @unless ($needsNik)
                            <div class="rounded-xl bg-wheat-50 border border-wheat-200 px-3.5 py-2.5">
                                <dt class="text-darkbrown-500">NIK</dt>
                                <dd class="font-mono font-bold text-darkbrown-900">{{ $maskedNik }}</dd>
                            </div>
                            @endunless
                        </dl>
                        @endif

                        {{-- Input hanya untuk data yang belum ada (tamu: selalu semua) --}}
                        @if ($needsName || $needsPhone || $needsNik)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @if ($needsName)
                            <div class="sm:col-span-2">
                                <label for="f_name" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">Nama lengkap</label>
                                <input type="text" id="f_name" x-model="name" autocomplete="name" placeholder="Sesuai KTP"
                                       :class="errors.name ? '{{ $errBorder }}' : '{{ $okBorder }}'"
                                       class="{{ $inputBase }}">
                                <p x-show="errors.name" x-text="errors.name" x-cloak class="mt-1 text-sm text-red-700"></p>
                            </div>
                            @endif

                            @if ($needsNik)
                            <div>
                                <label for="f_nik" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">NIK (nomor KTP)</label>
                                <div class="relative">
                                    <input type="text" id="f_nik" x-model="nik" @input="nik = nik.replace(/\D/g, '').slice(0, 16)"
                                           inputmode="numeric" maxlength="16" autocomplete="off" placeholder="16 digit angka"
                                           :class="errors.nik ? '{{ $errBorder }}' : '{{ $okBorder }}'"
                                           class="{{ $inputBase }} font-mono tracking-wide pr-14">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono"
                                          :class="nik.length === 16 ? 'text-avocado-700 font-bold' : 'text-darkbrown-400'"
                                          x-text="nik.length + '/16'"></span>
                                </div>
                                <p x-show="errors.nik" x-text="errors.nik" x-cloak class="mt-1 text-sm text-red-700"></p>
                            </div>
                            @endif

                            @if ($needsPhone)
                            <div>
                                <label for="f_phone" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">No. WhatsApp aktif</label>
                                <input type="tel" id="f_phone" x-model="phone" inputmode="tel" autocomplete="tel" placeholder="08xxxxxxxxxx"
                                       :class="errors.phone ? '{{ $errBorder }}' : '{{ $okBorder }}'"
                                       class="{{ $inputBase }}">
                                <p x-show="errors.phone" x-text="errors.phone" x-cloak class="mt-1 text-sm text-red-700"></p>
                            </div>
                            @endif
                        </div>
                        @endif

                        <div class="flex items-start gap-2.5 rounded-xl bg-sunglow-100/70 border border-sunglow-300/70 px-3.5 py-3 text-sm text-darkbrown-800">
                            <i data-lucide="id-card" class="w-4 h-4 text-sunglow-300"></i>
                            <span>Bawa <strong>KTP asli</strong> dan nomor booking saat mengambil alat. KTP ditahan toko selama masa sewa dan dikembalikan setelah alat diperiksa.</span>
                        </div>

                        @if ($isGuest || $needsPhone)
                            <div class="flex items-start gap-2.5 rounded-xl bg-avocado-50 border border-avocado-200 px-3.5 py-3 text-sm text-avocado-950">
                                <i data-lucide="bell" class="w-4 h-4 text-avocado-300"></i>
                                <span>Pengingat pengembalian (H-1 &amp; hari pengembalian) dikirim ke nomor WhatsApp yang Anda isi di atas.</span>
                            </div>
                        @else
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 rounded-xl bg-avocado-50 border border-avocado-200 px-3.5 py-3 text-sm text-avocado-950">
                                <span>
                                    Pengingat pengembalian (H-1 &amp; hari pengembalian) dikirim ke
                                    <span class="font-mono font-bold text-avocado-700">{{ $user->phone }}</span>.
                                </span>
                                <a href="{{ route('profile') }}" class="shrink-0 font-bold text-avocado-700 hover:text-avocado-800 underline underline-offset-2">Ubah di profil</a>
                            </div>
                        @endif
                    </div>
                </section>

                <!-- 2. CARA MENERIMA ALAT -->
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

                            <!-- Ambil di tempat: semua orang -->
                            <label :class="pickupMethod === 'self_pickup' ? 'border-avocado-600 bg-avocado-50 ring-2 ring-avocado-600/20' : 'border-wheat-300 bg-white hover:border-avocado-400'"
                                   class="relative flex gap-3 rounded-2xl border p-4 cursor-pointer transition focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-avocado-600">
                                <input type="radio" name="ui_pickup_method" value="self_pickup" x-model="pickupMethod" class="sr-only">
                                <div class="w-10 h-10 rounded-xl bg-avocado-100 text-avocado-700 flex items-center justify-center shrink-0">
                                    <i data-lucide="store" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0 flex-1 pr-6">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-darkbrown-900">Ambil di toko</span>
                                        <span class="rounded-md bg-avocado-100 border border-avocado-200 px-2 py-0.5 text-xs font-bold text-avocado-800">Gratis</span>
                                    </div>
                                    <p class="mt-1 text-sm text-darkbrown-600 leading-relaxed">Datang ke outlet vendor di {{ $booking['vendor']['city'] }}. Bisa untuk tamu maupun member.</p>
                                </div>
                                <span class="absolute top-3 right-3 w-5 h-5 rounded-full border-2 flex items-center justify-center transition"
                                      :class="pickupMethod === 'self_pickup' ? 'bg-avocado-600 border-avocado-600' : 'border-wheat-400 bg-white'" aria-hidden="true">
                                    <i data-lucide="check" class="w-3 h-3 text-white"></i>
                                </span>
                            </label>

                            <!-- Diantar: hanya member login -->
                            @if ($isLoggedIn)
                            <label :class="pickupMethod === 'delivery' ? 'border-avocado-600 bg-avocado-50 ring-2 ring-avocado-600/20' : 'border-wheat-300 bg-white hover:border-avocado-400'"
                                   class="relative flex gap-3 rounded-2xl border p-4 cursor-pointer transition focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-avocado-600">
                                <input type="radio" name="ui_pickup_method" value="delivery" x-model="pickupMethod" class="sr-only">
                                <div class="w-10 h-10 rounded-xl bg-sunglow-200 text-goldenrod-800 flex items-center justify-center shrink-0">
                                    <i data-lucide="truck" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0 flex-1 pr-6">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-darkbrown-900">Diantar ke lokasi</span>
                                        <span class="rounded-md bg-sunglow-100 border border-sunglow-300 px-2 py-0.5 text-xs font-bold text-darkbrown-900">Rp 25.000</span>
                                    </div>
                                    <p class="mt-1 text-sm text-darkbrown-600 leading-relaxed">Kurir vendor mengantar ke rumah atau titik kumpul trip. Area Malang Raya & Batu.</p>
                                </div>
                                <span class="absolute top-3 right-3 w-5 h-5 rounded-full border-2 flex items-center justify-center transition"
                                      :class="pickupMethod === 'delivery' ? 'bg-avocado-600 border-avocado-600' : 'border-wheat-400 bg-white'" aria-hidden="true">
                                    <i data-lucide="check" x-show="pickupMethod === 'delivery'" class="w-3 h-3 text-white"></i>
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

                        <!-- Detail: ambil di toko -->
                        <div x-show="pickupMethod === 'self_pickup'" x-cloak class="flex items-start gap-3 rounded-xl bg-wheat-50 border border-wheat-200 p-4 text-sm">
                            <i data-lucide="store" class="w-5 h-5 text-avocado-700 shrink-0 mt-0.5"></i>
                            <div class="space-y-0.5">
                                <div class="font-bold text-darkbrown-900">{{ $booking['vendor']['pickup_location'] }}</div>
                                <div class="text-darkbrown-600">{{ $booking['vendor']['address'] }}</div>
                                <div class="text-darkbrown-600 pt-1">Jam operasional: <strong class="text-darkbrown-800">{{ $booking['vendor']['operational_hours'] }}</strong></div>
                            </div>
                        </div>

                        <!-- Detail: diantar (member saja) -->
                        @if ($isLoggedIn)
                        <div x-show="pickupMethod === 'delivery'" x-cloak class="rounded-xl bg-wheat-50 border border-wheat-200 p-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-bold text-darkbrown-900">Alamat pengantaran</span>
                                <button type="button" x-show="!needsAddress" @click="editAddress = !editAddress"
                                        class="text-sm font-bold text-avocado-700 hover:text-avocado-800 underline underline-offset-2"
                                        x-text="editAddress ? 'Selesai' : 'Ubah'"></button>
                            </div>

                            <!-- Tampil saja kalau alamat sudah tersimpan -->
                            <div x-show="!showAddressForm" class="space-y-1">
                                <p class="font-semibold text-darkbrown-900 leading-relaxed" x-text="address"></p>
                                <p x-show="notes" class="text-darkbrown-600">Catatan untuk kurir: <span x-text="notes"></span></p>
                            </div>

                            <!-- Form alamat + penjelasan -->
                            <div x-show="showAddressForm" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="f_address" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">Alamat lengkap</label>
                                    <textarea id="f_address" rows="4" x-model="address" placeholder="Jalan, nomor, RT/RW, kelurahan, kecamatan, kota"
                                              :class="errors.address ? '{{ $errBorder }}' : '{{ $okBorder }}'"
                                              class="{{ $inputBase }} resize-none"></textarea>
                                    <p x-show="errors.address" x-text="errors.address" x-cloak class="mt-1 text-sm text-red-700"></p>
                                </div>
                                <div>
                                    <label for="f_notes" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">Penjelasan lokasi <span class="font-normal text-darkbrown-500">(opsional)</span></label>
                                    <textarea id="f_notes" rows="4" x-model="notes" placeholder="Patokan, warna pagar, titik kumpul trip, atau pesan untuk kurir"
                                              :class="errors.notes ? '{{ $errBorder }}' : '{{ $okBorder }}'"
                                              class="{{ $inputBase }} resize-none"></textarea>
                                    <p x-show="errors.notes" x-text="errors.notes" x-cloak class="mt-1 text-sm text-red-700"></p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </section>

                <!-- RINCIAN SEWA -->
                <section id="section-rental" class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm divide-y divide-wheat-200">

                    <!-- Vendor -->
                    <div class="p-5 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-12 h-12 rounded-xl bg-avocado-800 text-wheat-100 flex items-center justify-center shrink-0">
                                    <i data-lucide="store" class="w-6 h-6"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="text-sm text-darkbrown-500">Disiapkan oleh</div>
                                    <h3 class="font-bold text-darkbrown-900 text-base leading-snug">{{ $booking['vendor']['name'] }}</h3>
                                    <div class="text-sm text-darkbrown-600">{{ $booking['vendor']['city'] }}</div>
                                </div>
                            </div>
                            <a href="tel:{{ $booking['vendor']['phone'] }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl text-sm font-bold text-darkbrown-800 bg-white border border-wheat-300 hover:bg-wheat-50 transition self-start sm:self-center">
                                <i data-lucide="phone" class="w-4 h-4 text-avocado-600"></i>
                                {{ $booking['vendor']['phone'] }}
                            </a>
                        </div>
                    </div>

                    <!-- Jadwal (bisa diubah) -->
                    <div class="p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h3 class="font-bold text-darkbrown-900">Jadwal sewa</h3>
                            <span class="text-sm text-darkbrown-500">Tanggal bisa diubah</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto_1fr] items-stretch gap-3">
                            <div class="rounded-xl bg-avocado-50 border border-avocado-200 p-4">
                                <label for="f_pickup_date" class="text-sm font-semibold text-avocado-800">Tanggal ambil</label>
                                <input type="date" id="f_pickup_date" x-model="pickupDate" :min="minDate" @change="onPickupChange()"
                                       class="mt-1.5 w-full rounded-lg border border-avocado-300 bg-white px-3 py-2 text-sm font-semibold text-darkbrown-900 focus:border-avocado-600 focus:ring-2 focus:ring-avocado-500/30">
                                <div class="mt-1.5 text-sm text-darkbrown-600"><span x-text="fmtLong(pickupDate)"></span> · pukul {{ $booking['pickup_time'] }}</div>
                            </div>

                            <div class="flex sm:flex-col items-center justify-center gap-2">
                                <span class="hidden sm:block w-px flex-1 bg-wheat-300"></span>
                                <span class="rounded-full bg-darkbrown-900 text-wheat-100 px-3 py-1 text-xs font-bold whitespace-nowrap"
                                      x-text="rentalDays + ' hari · ' + Math.max(1, rentalDays - 1) + ' malam'"></span>
                                <span class="hidden sm:block w-px flex-1 bg-wheat-300"></span>
                            </div>

                            <div class="rounded-xl bg-sunglow-100/70 border border-sunglow-300/80 p-4">
                                <label for="f_return_date" class="text-sm font-semibold text-goldenrod-800">Tanggal kembali</label>
                                <input type="date" id="f_return_date" x-model="returnDate" :min="pickupDate || minDate" @change="onReturnChange()"
                                       class="mt-1.5 w-full rounded-lg border border-sunglow-300 bg-white px-3 py-2 text-sm font-semibold text-darkbrown-900 focus:border-goldenrod-500 focus:ring-2 focus:ring-goldenrod-400/30">
                                <div class="mt-1.5 text-sm text-darkbrown-600"><span x-text="fmtLong(returnDate)"></span> · maksimal {{ $booking['return_time'] }}</div>
                            </div>
                        </div>
                        <p x-show="errors.dates" x-text="errors.dates" x-cloak class="mt-2 text-sm text-red-700"></p>
                    </div>

                    <!-- Peralatan (jumlah unit bisa diubah) -->
                    <div class="p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h3 class="font-bold text-darkbrown-900">Peralatan yang disewa</h3>
                        </div>

                        <ul class="divide-y divide-wheat-200">
                            <template x-for="(item, i) in items" :key="item.id">
                                <li class="py-4 first:pt-0 last:pb-0 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div class="flex items-start gap-3.5 min-w-0">
                                        <div class="min-w-0 space-y-0.5">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="font-bold text-darkbrown-900 leading-snug" x-text="item.name"></h4>
                                                <span x-show="item.category" class="rounded-md bg-wheat-100 border border-wheat-200 px-2 py-0.5 text-xs font-semibold text-darkbrown-700" x-text="item.category"></span>
                                            </div>
                                            <p x-show="item.specs" class="text-sm text-darkbrown-500 line-clamp-2" x-text="item.specs"></p>
                                            <p class="text-sm text-darkbrown-700" x-text="formatRupiah(item.price_per_day) + '/hari × ' + rentalDays + ' hari'"></p>
                                            <p x-show="item.deposit_per_unit > 0" class="text-sm text-goldenrod-800" x-text="'Deposit ' + formatRupiah(item.deposit_per_unit * item.qty)"></p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between md:justify-end gap-5 shrink-0 pl-[3.75rem] md:pl-0">
                                        <div>
                                            <div class="inline-flex items-center rounded-xl border border-wheat-300 bg-white">
                                                <button type="button" @click="changeQty(i, -1)" :disabled="item.qty <= 1" aria-label="Kurangi unit"
                                                        class="w-9 h-9 flex items-center justify-center text-lg font-bold text-darkbrown-800 rounded-l-xl hover:bg-wheat-100 disabled:opacity-40 disabled:cursor-not-allowed transition">−</button>
                                                <input type="number" min="1" :max="item.max_qty" x-model.number="item.qty" @change="clampQty(i)" aria-label="Jumlah unit"
                                                       class="w-12 h-9 border-0 border-x border-wheat-200 p-0 text-center text-sm font-bold text-darkbrown-900 focus:ring-0">
                                                <button type="button" @click="changeQty(i, 1)" :disabled="item.qty >= item.max_qty" aria-label="Tambah unit"
                                                        class="w-9 h-9 flex items-center justify-center text-lg font-bold text-darkbrown-800 rounded-r-xl hover:bg-wheat-100 disabled:opacity-40 disabled:cursor-not-allowed transition">+</button>
                                            </div>
                                            <div x-show="item.max_qty < 99" class="mt-1 text-xs text-darkbrown-500 text-center" x-text="'Maks ' + item.max_qty + ' unit'"></div>
                                        </div>
                                    </div>
                                </li>
                            </template>
                        </ul>
                        <p x-show="errors.items" x-text="errors.items" x-cloak class="mt-2 text-sm text-red-700"></p>
                    </div>
                </section>
            </div>

            <!-- ============ KOLOM KANAN: RINGKASAN & BAYAR ============ -->
            <div class="lg:col-span-5 xl:col-span-4 space-y-5 lg:sticky lg:top-24">

                <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm overflow-hidden">
                    <div class="px-5 sm:px-6 py-4 bg-wheat-100 border-b border-wheat-200">
                        <h3 class="text-lg font-extrabold text-darkbrown-900 font-heading">Ringkasan pembayaran</h3>
                    </div>

                    <div class="p-5 sm:p-6 space-y-5">
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between text-darkbrown-600">
                                <span x-text="'Sewa alat (' + totalUnits + ' unit, ' + rentalDays + ' hari)'"></span>
                                <span class="font-bold text-darkbrown-900" x-text="formatRupiah(subtotal)"></span>
                            </div>

                            <div class="flex items-center justify-between text-darkbrown-600">
                                <span>Biaya admin</span>
                                <span class="font-bold text-darkbrown-900" x-text="formatRupiah(adminFee)"></span>
                            </div>

                            <div class="flex items-start justify-between text-darkbrown-600">
                                <div>
                                    <span>Ongkos kirim</span>
                                    <span x-show="pickupMethod === 'delivery'" x-cloak class="block text-xs text-goldenrod-700 font-semibold">Kurir vendor ke lokasi</span>
                                </div>
                                <span class="font-bold text-darkbrown-900" x-text="formatRupiah(deliveryFee)">Rp 0</span>
                            </div>

                            <div x-show="discount > 0" x-cloak class="flex items-center justify-between text-avocado-700">
                                <span>Diskon promo</span>
                                <span class="font-bold" x-text="'- ' + formatRupiah(discount)"></span>
                            </div>
                        </div>

                        <div class="rounded-xl bg-avocado-900 text-white px-4 py-3.5">
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="text-sm text-avocado-100">Total pembayaran</span>
                                <span class="text-2xl font-extrabold tracking-tight font-heading" x-text="formatRupiah(grandTotal)"></span>
                            </div>
                        </div>

                        <p x-show="touched" x-cloak class="text-xs text-darkbrown-500 leading-relaxed">
                            Total dihitung ulang dari perubahan jadwal atau jumlah unit.
                        </p>

                        <!-- Opsi Pembayaran-->
                        <fieldset>
                            <legend class="text-sm font-bold text-darkbrown-900 mb-2.5">Opsi pembayaran</legend>
                            <div class="grid grid-cols-2 gap-3">
                                <label :class="paymentOption === 'full' ? 'border-avocado-600 bg-avocado-50 ring-2 ring-avocado-600/20' : 'border-wheat-300 bg-white hover:border-avocado-400'"
                                       class="rounded-xl border p-3 cursor-pointer transition focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-avocado-600">
                                    <input type="radio" name="ui_payment_option" value="full" x-model="paymentOption" class="sr-only">
                                    <span class="block font-bold text-sm text-darkbrown-900">Bayar lunas</span>
                                    <span class="block mt-0.5 text-xs text-darkbrown-600 leading-relaxed">Langsung terkonfirmasi.</span>
                                    <span class="block mt-1.5 text-sm font-extrabold text-avocado-800" x-text="formatRupiah(grandTotal)"></span>
                                </label>
                                <label :class="paymentOption === 'dp' ? 'border-avocado-600 bg-avocado-50 ring-2 ring-avocado-600/20' : 'border-wheat-300 bg-white hover:border-avocado-400'"
                                       class="rounded-xl border p-3 cursor-pointer transition focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-avocado-600">
                                    <input type="radio" name="ui_payment_option" value="dp" x-model="paymentOption" class="sr-only">
                                    <span class="block font-bold text-sm text-darkbrown-900">Uang muka (DP)</span>
                                    <span class="block mt-0.5 text-xs text-darkbrown-600 leading-relaxed">Bayar <span x-text="dpPercentage + '%'"></span> dulu, sisanya saat ambil alat.</span>
                                    <span class="block mt-1.5 text-sm font-extrabold text-avocado-800" x-text="formatRupiah(dpAmount)"></span>
                                </label>
                            </div>
                        </fieldset>

                        <div x-show="formError" x-cloak role="alert" class="flex items-start gap-2 rounded-xl bg-red-50 border border-red-200 px-3.5 py-3 text-sm text-red-800">
                            <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0 mt-0.5"></i>
                            <span x-text="formError"></span>
                        </div>

                        <div>
                            <button type="button" x-on:click="submitBooking()" :disabled="isProcessing || expired"
                                    :class="(isProcessing || expired) ? 'opacity-70 cursor-not-allowed' : 'hover:bg-sunglow-400 active:bg-goldenrod-400 hover:shadow-tendaku-lg'"
                                    class="w-full py-3.5 px-5 rounded-xl font-extrabold text-base text-darkbrown-900 bg-sunglow-300 shadow-tendaku transition duration-200 flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-goldenrod-500 focus:ring-offset-2">
                                <template x-if="!isProcessing">
                                </template>
                                <template x-if="isProcessing">
                                    <i data-lucide="loader" class="w-5 h-5 animate-spin"></i>
                                </template>
                                <span x-text="isProcessing ? 'Membuat booking...' : 'Buat booking'"></span>
                                <span x-text="formatRupiah(payableAmount)"></span>
                            </button>
                        </div>
                    </div>
                </div>

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
        </div>
    </div>

    <script>
        function checkoutPage(cfg) {
            return {
                ...cfg,
                selectedChannel: 'qris',
                paymentOption: 'full',
                isProcessing: false,
                formError: '',
                errors: {},
                // name sudah diisi dari cfg (kosong untuk tamu / member tanpa nama)
                phone: '',
                nik: '',
                address: cfg.deliveryAddress || '',
                notes: cfg.deliveryNotes || '',
                editAddress: false,
                touched: false,
                remaining: '',
                expired: false,
                countdownTimer: null,

                // Serah terima setelah booking dibuat: form -> handoff
                phase: 'form',
                createdCode: '',

                init() { this.startCountdown(); },

                get isGuest() { return !this.isLoggedIn; },
                get showAddressForm() { return this.needsAddress || this.editAddress; },

                get rentalDays() {
                    const a = new Date(this.pickupDate), b = new Date(this.returnDate);
                    if (isNaN(a) || isNaN(b)) return this.initialDays;
                    return Math.max(1, Math.round((b - a) / 86400000) + 1);
                },
                get totalUnits() { return this.items.reduce((n, it) => n + (Number(it.qty) || 0), 0); },
                get subtotal() {
                    return this.items.reduce((n, it) => n + it.price_per_day * (Number(it.qty) || 0), 0) * this.rentalDays;
                },
                get adminFee() {
                    return Math.round(this.subtotal * 0.05);
                },
                get deposit() {
                    return 0;
                },
                get deliveryFee() {
                    return (this.isLoggedIn && this.pickupMethod === 'delivery') ? this.deliveryFeeAmount : 0;
                },
                get grandTotal() {
                    return Math.max(
                        0,
                        this.subtotal + this.adminFee + this.deliveryFee - this.discount
                    );
                },
                get dpAmount() {
                    return Math.round(
                        this.grandTotal * this.dpPercentage / 100
                    );
                },
                get payableAmount() {
                    return (this.paymentOption === 'dp') ? this.dpAmount : this.grandTotal;
                },

                // Form menyusut ke atas, animasi "Booking dibuat" tampil di area konten, lalu pindah ke halaman status (?new=1).
                async playHandoff(data) {
                    const wait = (ms) => new Promise(r => setTimeout(r, ms));
                    const full = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                    this.createdCode = data.booking_code || '';
                    const url = new URL(data.redirect_url, window.location.origin);
                    url.searchParams.set('new', '1');

                    window.scrollTo({ top: 0, behavior: full ? 'smooth' : 'auto' });
                    this.phase = 'handoff';
                    await wait(full ? 2400 : 300);
                    window.location.href = url.toString();
                },

                formatRupiah(num) {
                    return 'Rp ' + Math.round(num || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                },
                fmtLong(iso) {
                    const d = new Date(iso);
                    return isNaN(d) ? '-' : d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });
                },
                fmtShort(iso) {
                    const d = new Date(iso);
                    return isNaN(d) ? '-' : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });
                },

                changeQty(i, delta) {
                    this.items[i].qty = Number(this.items[i].qty || 1) + delta;
                    this.clampQty(i);
                },
                clampQty(i) {
                    const it = this.items[i];
                    let q = parseInt(it.qty, 10);
                    if (isNaN(q) || q < 1) q = 1;
                    if (q > it.max_qty) q = it.max_qty;
                    it.qty = q;
                    this.touched = true;
                },
                onPickupChange() {
                    if (!this.pickupDate) return;

                    if (!this.returnDate || this.returnDate < this.pickupDate) {
                        this.returnDate = this.pickupDate;
                    }

                    this.touched = true;
                },

                onReturnChange() {
                    this.touched = true;

                    if (
                        this.pickupDate &&
                        this.returnDate &&
                        this.returnDate < this.pickupDate
                    ) {
                        this.returnDate = this.pickupDate;
                    }
                },

                startCountdown() {
                    if (!this.expiresAtIso) return;

                    const tick = () => {
                        const expiresAt = new Date(this.expiresAtIso).getTime();

                        if (!Number.isFinite(expiresAt)) {
                            this.remaining = '';
                            clearInterval(this.countdownTimer);
                            return;
                        }

                        const ms = expiresAt - Date.now();

                        if (ms <= 0) {
                            this.remaining = '00:00:00';
                            this.expired = true;
                            clearInterval(this.countdownTimer);
                            return;
                        }

                        const h = Math.floor(ms / 3600000);
                        const m = Math.floor((ms % 3600000) / 60000);
                        const s = Math.floor((ms % 60000) / 1000);

                        this.remaining = [h, m, s]
                            .map(n => String(n).padStart(2, '0'))
                            .join(':');
                    };

                    tick();

                    if (!this.expired && this.remaining) {
                        this.countdownTimer = setInterval(tick, 1000);
                    }
                },

                validate() {
                    const e = {};
                    if (this.needsName && this.name.trim().length < 3) e.name = 'Isi nama lengkap sesuai KTP.';
                    if (this.needsPhone && !/^(\+62|62|0)8[0-9]{8,12}$/.test(this.phone.replace(/[\s-]/g, ''))) {
                        e.phone = 'Nomor WhatsApp belum valid. Contoh: 081234567890';
                    }
                    if (this.needsNik && !/^\d{16}$/.test(this.nik)) e.nik = 'NIK harus 16 digit angka.';

                    if (this.pickupMethod === 'delivery' && this.address.trim().length < 10) {
                        e.address = 'Alamat pengantaran belum lengkap.';
                        this.editAddress = true;
                    }
                    if (!this.pickupDate || !this.returnDate || this.returnDate < this.pickupDate) {
                        e.dates = 'Tanggal kembali tidak boleh sebelum tanggal ambil.';
                    } else if (this.pickupDate < this.minDate) {
                        e.dates = 'Tanggal ambil tidak boleh sebelum hari ini.';
                    }
                    if (this.items.some(it => !(it.qty >= 1 && it.qty <= it.max_qty))) {
                        e.items = 'Jumlah unit tidak valid.';
                    }

                    this.errors = e;
                    this.scrollToError();
                    return Object.keys(e).length === 0;
                },

                scrollToError() {
                    const e = this.errors;
                    const target = (e.name || e.phone || e.nik) ? 'section-identity'
                        : (e.address || e.notes) ? 'section-delivery'
                        : (e.dates || e.items) ? 'section-rental' : null;
                    if (!target) return;
                    const el = document.getElementById(target);
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                },

                async submitBooking() {
                    // Aturan bisnis: delivery hanya untuk akun login
                    if (this.pickupMethod === 'delivery' && !this.isLoggedIn) {
                        window.location.href = this.loginUrl;
                        return;
                    }
                    if (this.isProcessing) return;

                    if (this.expired) {
                        this.formError = 'Waktu penahanan alat sudah habis. Muat ulang halaman untuk memulai lagi.';
                        return;
                    }

                    this.formError = '';
                    if (!this.validate()) return;

                    this.isProcessing = true;

                    try {
                        const fd = new FormData();
                        fd.append('_token', this.csrf);
                        fd.append('pickup_method', this.pickupMethod);
                        fd.append('payment_option', this.paymentOption);
                        fd.append('payment_channel', this.selectedChannel);

                        // Jadwal & jumlah unit (harga dihitung ulang di server)
                        fd.append('pickup_date', this.pickupDate);
                        fd.append('return_date', this.returnDate);
                        this.items.forEach((it, i) => {
                            fd.append('items[' + i + '][id]', it.id);
                            fd.append('items[' + i + '][qty]', it.qty);
                        });

                        // Data penyewa (hanya yang memang diminta)
                        if (this.isGuest) {
                            fd.append('guest_name', this.name.trim());
                            fd.append('guest_phone', this.phone.trim());
                            fd.append('guest_id_number', this.nik);
                        } else {
                            if (this.needsName) fd.append('name', this.name.trim());
                            if (this.needsPhone) fd.append('phone', this.phone.trim());
                            if (this.needsNik) fd.append('nik', this.nik);
                        }

                        // Alamat antar (member + delivery)
                        if (this.pickupMethod === 'delivery') {
                            fd.append('delivery_address', this.address.trim());
                            fd.append('delivery_notes', this.notes.trim());
                        }

                        const response = await fetch(this.storeUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                            },
                            body: fd,
                        });

                        const contentType = response.headers.get('content-type') || '';

                        if (!contentType.includes('application/json')) {
                            throw new Error(
                                `Respons server tidak valid (HTTP ${response.status})`
                            );
                        }

                        const data = await response.json();

                        if (!response.ok) {
                            const map = {
                                guest_name: 'name', name: 'name',
                                guest_phone: 'phone', phone: 'phone',
                                guest_id_number: 'nik', nik: 'nik',
                                delivery_address: 'address', delivery_notes: 'notes',
                                pickup_date: 'dates', return_date: 'dates',
                            };
                            const serverErrors = {};
                            Object.keys(data.errors || {}).forEach(k => {
                                const key = k.startsWith('items') ? 'items' : map[k];
                                if (key && !serverErrors[key]) serverErrors[key] = data.errors[k][0];
                            });
                            this.errors = serverErrors;
                            this.formError = data.message || 'Terjadi kesalahan. Silakan coba lagi.';
                            this.scrollToError();
                            this.isProcessing = false;
                            return;
                        }

                        if (!data.redirect_url) {
                            this.formError = 'Booking dibuat, tetapi halaman status tidak ditemukan. Buka Riwayat Sewa untuk melanjutkan pembayaran.';
                            this.isProcessing = false;
                            return;
                        }

                        await this.playHandoff(data);
                    } catch (err) {
                        this.formError = 'Koneksi bermasalah. Periksa internet Anda lalu coba lagi.';
                        this.isProcessing = false;
                    }
                },
            };
        }
    </script>
</x-layouts.customer>
