<x-layouts.customer>
    <x-slot name="title">
        @if(isset($rental) && $rental)
            Status Booking #{{ $rental->booking_code }} - Tendaku
        @else
            Riwayat Sewa Saya - Tendaku
        @endif
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs text-darkbrown-500 mb-1.5">
                    <a href="{{ route('home') }}" class="hover:text-avocado-600 transition">Beranda</a>
                    <span class="text-wheat-400">/</span>
                    @auth
                        @if(isset($rental) && $rental)
                            <a href="{{ route('customer.booking.index') }}" class="hover:text-avocado-600 transition">Riwayat Sewa</a>
                            <span class="text-wheat-400">/</span>
                            <span class="text-darkbrown-800 font-semibold">Status #{{ $rental->booking_code }}</span>
                        @else
                            <span class="text-darkbrown-800 font-semibold">Riwayat Sewa</span>
                        @endif
                    @else
                        <a href="{{ route('customer.catalog.index') }}" class="hover:text-avocado-600 transition">Katalog</a>
                        <span class="text-wheat-400">/</span>
                        <span class="text-darkbrown-800 font-semibold">Status #{{ $rental->booking_code }}</span>
                    @endauth
                </nav>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-900 tracking-tight font-heading">
                    @auth Riwayat & Status Sewa @else Status Booking & Rental @endauth
                </h1>
                @auth
                    <p class="text-xs text-darkbrown-600 mt-1">
                        Daftar seluruh pesanan peralatan camping dan status sewa Anda di Tendaku.
                    </p>
                @endauth
            </div>

            <div class="flex flex-wrap items-center gap-3 self-start sm:self-auto">
                @if(isset($rental) && $rental)
                    <div class="flex items-center gap-2.5 px-4 py-2.5 bg-wheat-100 rounded-2xl border border-wheat-300 shadow-tendaku-sm">
                        <div class="w-8 h-8 rounded-xl bg-darkbrown-800 text-wheat-200 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-darkbrown-500 block leading-none mb-0.5">Kode booking</span>
                            <span class="font-mono text-sm sm:text-base font-extrabold text-darkbrown-900">#{{ $rental->booking_code }}</span>
                        </div>
                    </div>
                @endif
                <a href="{{ route('customer.catalog.index') }}" class="btn-tendaku-primary py-2.5 px-4 rounded-xl text-xs font-bold">
                    + Sewa Alat Baru
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $filterStatus = request('status', '');
        $hasRental    = isset($rental) && $rental;
        $hasList      = auth()->check() && isset($rentals);

        $statusLabels = [
            'pending'   => 'Menunggu Pembayaran',
            'confirmed' => 'Dikonfirmasi (Siap Pickup)',
            'picked_up' => 'Sedang Dipakai (Disewa)',
            'returned'  => 'Telah Dikembalikan',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'expired'   => 'Kedaluwarsa',
        ];

        $statusTabs = ['' => 'Semua Transaksi'] + $statusLabels;
    @endphp

    @if($hasRental)
    @php
        $status = $rental->status;

        // Urutan titik di timeline & pemetaan status rental -> titik yang sedang terbuka
        $nodeOrder    = ['created', 'payment', 'pickup', 'rental', 'return', 'done'];
        $statusToNode = [
            'pending'   => 'payment',
            'confirmed' => 'pickup',
            'picked_up' => 'rental',
            'returned'  => 'return',
            'completed' => 'done',
        ];

        $isExpiredOrCancelled = in_array($status, ['cancelled', 'expired'], true);
        $isExpiredNow = $status === 'pending' && $rental->expires_at && $rental->expires_at->isPast();

        // Utamakan tagihan online yang masih menunggu (terbaru). Setelah ganti bank ada beberapa payment per rental.
        $onlinePayment = $rental->payments->where('channel', 'midtrans')->whereIn('payment_type', ['full', 'dp'])->where('status', 'pending')->sortByDesc('id')->first()
            ?? $latestPayment
            ?? $rental->payments->where('channel', 'midtrans')->sortByDesc('id')->first();
        $paidPayment   = $rental->payments->where('status', 'paid')->sortByDesc('paid_at')->first();
        $vaInfo        = $onlinePayment?->va_details;

        // Bank VA yang ditawarkan di pill. Kunci = kode bank di Midtrans (mandiri = Mandiri Bill / echannel).
        // Harus sama dengan daftar di controller (BookingVaController::BANKS)
        $vaBanks   = ['bca' => 'BCA', 'bni' => 'BNI', 'bri' => 'BRI', 'permata' => 'Permata', 'cimb' => 'CIMB Niaga', 'mandiri' => 'Mandiri'];
        $vaBankKey = strtolower((string) ($vaInfo['bank'] ?? ''));
        $vaBankKey = array_key_exists($vaBankKey, $vaBanks) ? $vaBankKey : 'bca';

        $hasDenda   = ($rental->total_late_fee > 0 || $rental->total_damage_fee > 0);
        $totalFinal = $rental->final_total ?: ($rental->grand_total + $rental->total_late_fee + $rental->total_damage_fee);
        $canPayFine = $hasDenda && Auth::check() && Auth::id() === $rental->user_id;

        $payAmount     = $onlinePayment?->amount ?? $totalFinal;
        $payTypeLabel  = $onlinePayment?->payment_type === 'dp' ? 'Uang muka (DP)' : 'Pelunasan penuh';
        $rupiah        = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
        $fmtDateTime   = fn ($d) => $d ? \Carbon\Carbon::parse($d)->translatedFormat('d M Y, H:i') : '';
        $fmtDate       = fn ($d) => $d ? \Carbon\Carbon::parse($d)->translatedFormat('d M Y') : '';

        // Data penyewa (member atau tamu)
        $renterName  = $rental->isGuest() ? ($rental->guest_name ?? 'Penyewa Tamu') : ($rental->customer?->name ?? 'Pelanggan');
        $renterPhone = $rental->isGuest() ? ($rental->guest_phone ?? null) : ($rental->customer?->phone ?? null);

        // Meta tiap titik timeline
        $nodes = [
            'created' => ['title' => 'Booking dibuat',        'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            'payment' => ['title' => 'Pembayaran',            'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            'pickup'  => ['title' => 'Serah terima alat',     'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            'rental'  => ['title' => 'Masa sewa',             'icon' => 'M19 20L10 4 1 20h18z'],
            'return'  => ['title' => 'Pengembalian',          'icon' => 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6'],
            'done'    => ['title' => 'Transaksi selesai',     'icon' => 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9'],
        ];

        $descs = [
            'created' => ['todo' => 'Pesanan tercatat', 'active' => 'Pesanan tercatat', 'done' => 'Pesanan tercatat dengan kode #' . $rental->booking_code],
            'payment' => ['todo' => 'Menunggu tagihan', 'active' => 'Menunggu pembayaran', 'done' => 'Pembayaran terverifikasi'],
            'pickup'  => ['todo' => 'Disiapkan setelah pembayaran', 'active' => 'Alat siap diserahkan', 'done' => 'Alat telah diserahkan'],
            'rental'  => ['todo' => 'Dimulai setelah serah terima', 'active' => 'Alat sedang dipakai', 'done' => 'Masa sewa selesai'],
            'return'  => ['todo' => 'Setelah alat dikembalikan', 'active' => 'Alat diperiksa vendor', 'done' => 'Alat dikembalikan dan diperiksa'],
            'done'    => ['todo' => 'Setelah semua kewajiban selesai', 'active' => 'Menyelesaikan transaksi', 'done' => 'Seluruh kewajiban sewa selesai'],
        ];

        $times = [
            'created' => $fmtDateTime($rental->created_at),
            'payment' => $paidPayment?->paid_at
                ? $fmtDateTime($paidPayment->paid_at)
                : ($status === 'pending' && $rental->expires_at ? 'Batas ' . $fmtDateTime($rental->expires_at) : ''),
            'pickup'  => $rental->picked_up_at ? $fmtDateTime($rental->picked_up_at) : $fmtDate($rental->pickup_date),
            'rental'  => $rental->picked_up_at ? $fmtDateTime($rental->picked_up_at) : '',
            'return'  => $rental->returned_at ? $fmtDateTime($rental->returned_at) : 'Maks ' . $fmtDate($rental->return_date),
            'done'    => $status === 'completed' ? $fmtDateTime($rental->updated_at) : '',
        ];

        // Riwayat aktivitas, semua dari data nyata
        $acts = collect();
        $acts->push(['title' => 'Booking dibuat', 'sub' => '#' . $rental->booking_code, 'at' => $rental->created_at, 'type' => 'doc', 'tone' => 'plain']);
        foreach ($rental->payments as $p) {
            $isPaid = $p->status === 'paid';
            $label = $isPaid ? 'Pembayaran diterima' : match ($p->status) {
                'pending'                  => 'Tagihan dibuat',
                'expire', 'expired'        => 'Tagihan kedaluwarsa',
                'failed', 'deny', 'cancel' => 'Pembayaran gagal',
                default                    => 'Tagihan ' . $p->status,
            };
            $acts->push([
                'title' => $label,
                'sub'   => $rupiah($p->amount),
                'at'    => $isPaid ? ($p->paid_at ?? $p->created_at) : $p->created_at,
                'type'  => 'card',
                'tone'  => $isPaid ? 'green' : ($p->status === 'pending' ? 'amber' : 'red'),
            ]);
        }
        if ($rental->picked_up_at)   $acts->push(['title' => 'Alat diserahkan',      'sub' => '', 'at' => $rental->picked_up_at,   'type' => 'bag',    'tone' => 'green']);
        if ($rental->ktp_received_at) $acts->push(['title' => 'KTP diterima toko',    'sub' => '', 'at' => $rental->ktp_received_at, 'type' => 'id',     'tone' => 'plain']);
        if ($rental->returned_at)    $acts->push(['title' => 'Alat dikembalikan',    'sub' => '', 'at' => $rental->returned_at,    'type' => 'return', 'tone' => 'green']);
        if ($rental->ktp_returned_at) $acts->push(['title' => 'KTP dikembalikan',     'sub' => '', 'at' => $rental->ktp_returned_at, 'type' => 'id',     'tone' => 'plain']);
        if ($status === 'completed') $acts->push(['title' => 'Transaksi selesai',    'sub' => '', 'at' => $rental->updated_at,     'type' => 'flag',   'tone' => 'green']);

        $activities = $acts
            ->sortByDesc(fn ($a) => \Carbon\Carbon::parse($a['at'])->timestamp)
            ->map(fn ($a) => [
                'title' => $a['title'],
                'sub'   => $a['sub'],
                'time'  => \Carbon\Carbon::parse($a['at'])->translatedFormat('d M, H:i'),
                'type'  => $a['type'],
                'tone'  => $a['tone'],
                'fresh' => false,
            ])->values()->all();

        $pageConfig = [
            'status'        => $status,
            'statusLabels'  => $statusLabels,
            'order'         => $nodeOrder,
            'statusToNode'  => $statusToNode,
            'descs'         => $descs,
            'times'         => $times,
            'activities'    => $activities,
            'expired'       => (bool) $isExpiredNow,
            'expiresAtIso'  => $status === 'pending' ? $rental->expires_at?->toIso8601String() : null,
            'payAmountText' => $rupiah($payAmount),
            'payUrl'        => route('booking.pay', $rental->booking_code),
            'syncUrl'       => route('booking.sync', $rental->booking_code),
            'csrf'          => csrf_token(),
            'vaUrl'         => route('booking.va', $rental->booking_code),
            'vaBanks'       => $vaBanks,
            'orderId'       => $onlinePayment?->order_id,
            'va'            => $vaInfo ? [
                'bank'   => $vaBankKey,
                'number' => $vaInfo['va_number'] ?? '',
                'biller' => $vaInfo['biller_code'] ?? '',
            ] : null,
            // Baru dibuat dari checkout (?new=1): mainkan animasi masuk sekali saja
            'fresh'         => $status === 'pending' && request()->boolean('new'),
        ];

        $ktpLabel = match ($rental->ktp_collateral_status) {
            'held'     => 'KTP ditahan di toko',
            'returned' => 'KTP sudah dikembalikan',
            default    => 'Menunggu serah terima',
        };
        $ktpBadge = match ($rental->ktp_collateral_status) {
            'held'     => 'bg-goldenrod-100 text-goldenrod-900 border-goldenrod-300',
            'returned' => 'bg-avocado-100 text-avocado-900 border-avocado-300',
            default    => 'bg-wheat-100 text-darkbrown-700 border-wheat-300',
        };
    @endphp
    @endif

    <style>
        [x-cloak] { display: none !important; }

        /* Layout: daftar booking | status | aktivitas & info. Ditulis manual supaya tidak bergantung pada build Tailwind */
        .status-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; align-items: start; }
        .status-list  { order: 0; min-width: 0; }
        .status-main  { order: 1; min-width: 0; }
        .status-right { order: 2; min-width: 0; }
        .status-right > * + * { margin-top: 1.25rem; }
        .status-list .bk-toggle { display: flex; }
        .status-list .bk-body { display: none; margin-top: .75rem; }
        .status-list .bk-body.is-open { display: block; }
        @@media (min-width: 1024px) {
            .status-grid { grid-template-columns: minmax(0, 3fr) minmax(0, 6fr) minmax(0, 3fr); gap: 2rem; }
            .status-grid.no-list { grid-template-columns: minmax(0, 8fr) minmax(0, 4fr); }
            .status-grid.no-right { grid-template-columns: minmax(0, 3fr) minmax(0, 9fr); }
            .status-list { position: sticky; top: 6rem; max-height: calc(100vh - 7.5rem); overflow-y: auto; padding: 2px; }
            .status-list .bk-toggle { display: none; }
            .status-list .bk-body { display: block; margin-top: 0; }
        }

        /* Pill VA (kecil): kiri 30% dropdown bank, kanan 70% nomor VA. Ditulis manual, bukan utility Tailwind */
        .va-pill { display: flex; align-items: center; width: 100%; min-height: 2.25rem; }
        .va-pill:focus-within { box-shadow: 0 0 0 3px rgba(212, 160, 23, .3); }
        .va-pill-bank { flex: 0 0 30%; min-width: 5.25rem; align-self: stretch; border-right-width: 1px; border-right-style: solid; border-radius: 9999px 0 0 9999px; }
        .va-bank-btn { display: flex; align-items: center; justify-content: space-between; gap: .25rem; width: 100%; height: 100%; min-height: 2.25rem;
                       padding: .25rem .55rem .25rem .85rem; font-size: .8rem; font-weight: 800; background: transparent; border: 0; outline: 0;
                       cursor: pointer; border-radius: 9999px 0 0 9999px; text-align: left; }
        .va-bank-btn:disabled { cursor: wait; opacity: .6; }
        .va-chev { width: .85rem; height: .85rem; flex-shrink: 0; transition: transform .2s ease; }
        .va-rot { transform: rotate(180deg); }
        .va-pill-num { flex: 1 1 0; min-width: 0; display: flex; align-items: center; gap: .4rem; padding: .2rem .3rem .2rem .75rem; }
        .va-num { flex: 1 1 0; min-width: 0; overflow-x: auto; white-space: nowrap; letter-spacing: .04em; font-size: clamp(.8rem, 3.2vw, 1rem); }
        /* Daftar bank: fixed supaya tidak terpotong overflow panel timeline */
        .va-menu { position: fixed; z-index: 60; min-width: 9.5rem; max-height: 16rem; overflow-y: auto; padding: .3rem; }
        .va-opt { display: flex; align-items: center; justify-content: space-between; gap: .75rem; width: 100%; padding: .45rem .7rem;
                  border: 0; border-radius: .65rem; font-size: .82rem; text-align: left; background: transparent; cursor: pointer; }
        .va-loading { animation: va-blink 1s ease-in-out infinite; }
        @@keyframes va-blink { 50% { opacity: .35; } }
        @@media (prefers-reduced-motion: reduce) { .va-loading { animation: none; } .va-chev { transition: none; } }

        /* Tab timeline: hanya satu yang terbuka, buka-tutup mulus tanpa hitung tinggi */
        .tl-collapse { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .6s cubic-bezier(.4, 0, .2, 1); }
        .tl-collapse.is-open { grid-template-rows: 1fr; }
        .tl-collapse > .tl-inner { overflow: hidden; min-height: 0; opacity: 0; transition: opacity .35s ease; }
        .tl-collapse.is-open > .tl-inner { opacity: 1; transition: opacity .5s ease .2s; }

        /* Centang "pembayaran diterima" */
        .pay-ring { stroke-dasharray: 166; stroke-dashoffset: 166; animation: tl-draw .6s cubic-bezier(.65, 0, .45, 1) forwards; }
        .pay-tick { stroke-dasharray: 48; stroke-dashoffset: 48; animation: tl-draw .35s cubic-bezier(.65, 0, .45, 1) .5s forwards; }
        @@keyframes tl-draw { to { stroke-dashoffset: 0; } }

        /* Bulatan selesai: membal sekali */
        .dot-pop { animation: tl-pop .55s cubic-bezier(.34, 1.56, .64, 1); }
        @@keyframes tl-pop { 0% { transform: scale(.6); } 100% { transform: scale(1); } }

        /* Aktivitas baru masuk dari atas */
        .act-in { animation: tl-in .5s ease; }
        @@keyframes tl-in { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: none; } }

        @@media (prefers-reduced-motion: reduce) {
            .tl-collapse, .tl-collapse > .tl-inner { transition: none; }
            .pay-ring, .pay-tick { animation-duration: .01s; animation-delay: 0s; }
            .dot-pop, .act-in { animation: none; }
        }
    </style>

    <div @if($hasRental) x-data="statusPage({{ \Illuminate\Support\Js::from($pageConfig) }})" @endif class="py-4 sm:py-6">

        @if($hasRental && $isExpiredOrCancelled)
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-sm text-red-800 flex items-start gap-2.5">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>Transaksi ini <strong>{{ strtolower($statusLabels[$status]) }}</strong>. Unit alat sudah dilepas kembali ke katalog.</span>
            </div>
        @endif

        <div class="status-grid {{ $hasList ? '' : 'no-list' }} {{ $hasRental ? '' : 'no-right' }}">
            @if($hasList)
            <!-- ============ DAFTAR BOOKING (riwayat) ============ -->
            <aside class="status-list" x-data="{ listOpen: false }">
                @if($hasRental)
                    <button type="button" class="bk-toggle w-full items-center justify-between gap-3 rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm px-4 py-3 text-left"
                            @click="listOpen = !listOpen" :aria-expanded="listOpen.toString()">
                        <span>
                            <span class="block text-xs text-darkbrown-500">Booking dipilih</span>
                            <span class="font-mono text-sm font-extrabold text-darkbrown-900">#{{ $rental->booking_code }}</span>
                        </span>
                        <span class="text-sm font-bold text-avocado-700" x-text="listOpen ? 'Tutup daftar' : 'Ganti booking'">Ganti booking</span>
                    </button>
                @endif

                <div class="bk-body {{ $hasRental ? '' : 'is-open' }} space-y-3" :class="{ 'is-open': listOpen }">

                    <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm p-4 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="font-extrabold text-darkbrown-900 font-heading">Riwayat sewa</h2>
                            <span class="text-xs text-darkbrown-500">{{ method_exists($rentals, 'total') ? $rentals->total() : $rentals->count() }} transaksi</span>
                        </div>
                        <div>
                            <label for="bk-filter" class="text-xs font-semibold text-darkbrown-600">Filter status</label>
                            <select id="bk-filter" onchange="window.location.href = this.value"
                                    class="mt-1 w-full rounded-xl border border-wheat-300 bg-white px-3 py-2 text-sm font-semibold text-darkbrown-900 focus:border-avocado-600 focus:ring-2 focus:ring-avocado-500/30">
                                @foreach($statusTabs as $tabKey => $tabLabel)
                                    <option value="{{ route('customer.booking.index', $tabKey ? ['status' => $tabKey] : []) }}" {{ $filterStatus === $tabKey ? 'selected' : '' }}>{{ $tabLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @forelse($rentals as $r)
                        @php
                            $isSel = $hasRental && $r->booking_code === $rental->booking_code;
                            $rBadge = match($r->status) {
                                'pending' => 'bg-sunglow-100 text-sunglow-900 border-sunglow-300',
                                'confirmed' => 'bg-sky-100 text-sky-900 border-sky-300',
                                'picked_up' => 'bg-avocado-100 text-avocado-900 border-avocado-300',
                                'returned' => 'bg-indigo-100 text-indigo-900 border-indigo-300',
                                'completed' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                                'cancelled', 'expired' => 'bg-red-100 text-red-900 border-red-300',
                                default => 'bg-wheat-100 text-darkbrown-700 border-wheat-300',
                            };
                        @endphp
                        <a href="{{ route('booking.status', $r->booking_code) }}{{ $filterStatus ? '?status=' . $filterStatus : '' }}"
                           @if($isSel) aria-current="page" @endif
                           class="block rounded-2xl border p-4 space-y-2.5 transition {{ $isSel ? 'bg-avocado-50 border-avocado-600 ring-2 ring-avocado-600/20' : 'bg-white border-wheat-200 hover:border-wheat-400 shadow-tendaku-sm' }}">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-sm font-extrabold text-darkbrown-900">#{{ $r->booking_code }}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $rBadge }}">{{ $statusLabels[$r->status] ?? ucfirst($r->status) }}</span>
                            </div>
                            <div class="text-xs text-darkbrown-500 font-medium">
                                Dipesan pada {{ $r->created_at->translatedFormat('d M Y, H:i') }} WIB
                            </div>
                            <div class="text-xs font-bold text-darkbrown-900">
                                {{ $r->vendor?->name }}
                                <span class="text-wheat-500">•</span>
                                <span class="text-darkbrown-600 font-medium">{{ $r->vendor?->city }}</span>
                            </div>
                            <div class="text-xs text-darkbrown-700 space-y-1">
                                <p class="font-medium">
                                    Periode: <strong>{{ \Carbon\Carbon::parse($r->pickup_date)->translatedFormat('d M Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($r->return_date)->translatedFormat('d M Y') }}</strong> ({{ $r->rental_days }} hari)
                                </p>
                                <p class="text-darkbrown-600">
                                    Barang: {{ $r->details->count() }} unit alat
                                    @if($r->details->first()?->masterItem)
                                        ({{ $r->details->first()->masterItem->name }}{{ $r->details->count() > 1 ? ', dll' : '' }})
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-end justify-between gap-3 pt-2 border-t border-wheat-100">
                                <div>
                                    <span class="text-[11px] text-darkbrown-500 block">Total Tagihan</span>
                                    <span class="text-base font-extrabold text-darkbrown-900 font-heading">Rp {{ number_format($r->final_total ?: $r->grand_total, 0, ',', '.') }}</span>
                                </div>
                                <span class="text-xs font-bold {{ $isSel ? 'text-avocado-800' : 'text-avocado-700' }}">{{ $isSel ? 'Sedang dilihat' : 'Lihat status →' }}</span>
                            </div>
                        </a>
                    @empty
                        <p class="rounded-2xl bg-white border border-wheat-200 p-4 text-sm text-darkbrown-600">Tidak ada transaksi untuk status ini.</p>
                    @endforelse

                    <div>
                        {{ $rentals->links() }}
                    </div>
                </div>
            </aside>
            @endif

            <main class="status-main space-y-6">
                @if($hasRental)

                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-extrabold text-darkbrown-900 font-heading">Alur pemesanan</h2>
                    <span class="px-3 py-1 rounded-full text-sm font-bold border"
                          :class="current ? 'bg-sunglow-100 border-sunglow-300 text-darkbrown-900' : 'bg-red-50 border-red-200 text-red-800'"
                          x-text="statusLabels[status]">{{ $statusLabels[$status] ?? $status }}</span>
                </div>

                <ol class="relative">
                    @foreach($nodes as $key => $n)
                        <li id="node-{{ $key }}" class="relative flex gap-4 sm:gap-5">

                            <!-- Bulatan & garis -->
                            <div class="flex flex-col items-center shrink-0">
                                <span class="relative z-10 w-10 h-10 rounded-full border-2 flex items-center justify-center transition-colors duration-500"
                                      :class="[dotCls('{{ $key }}'), justDone === '{{ $key }}' ? 'dot-pop' : '']">
                                    <span x-show="state('{{ $key }}') === 'active'" class="absolute inset-0 rounded-full bg-sunglow-300/60 animate-ping motion-reduce:animate-none" aria-hidden="true"></span>
                                    <svg x-show="state('{{ $key }}') === 'done'" class="relative w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    <svg x-show="state('{{ $key }}') !== 'done'" class="relative w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $n['icon'] }}" /></svg>
                                </span>
                                @unless($loop->last)
                                    <span class="relative w-0.5 flex-1 bg-wheat-200 my-1 rounded">
                                        <span class="absolute inset-x-0 top-0 bg-avocado-500 rounded transition-[height] duration-700 ease-out"
                                              :style="'height:' + lineFill({{ $loop->index }}) + '%'"></span>
                                    </span>
                                @endunless
                            </div>

                            <!-- Kartu -->
                            <section class="flex-1 min-w-0 mb-5 rounded-2xl border transition-colors duration-500"
                                     :class="cardCls('{{ $key }}')"
                                     :aria-current="current === '{{ $key }}' ? 'step' : null">

                                <header class="flex items-start justify-between gap-3 p-4 sm:p-5">
                                    <div class="min-w-0">
                                        <h3 class="font-extrabold text-darkbrown-900 leading-snug">{{ $n['title'] }}</h3>
                                        <p class="mt-0.5 text-sm text-darkbrown-600" x-text="descs['{{ $key }}'][state('{{ $key }}')]"></p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0 text-right">
                                        <span x-show="times['{{ $key }}']" class="text-xs font-semibold text-darkbrown-500" x-text="times['{{ $key }}']">{{ $times[$key] }}</span>
                                        @unless($key === 'created')
                                            <svg x-show="state('{{ $key }}') === 'todo'" class="w-4 h-4 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-label="Belum terbuka"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                        @endunless
                                    </div>
                                </header>

                                @unless($key === 'created')
                                <div class="tl-collapse" :class="{ 'is-open': isOpen('{{ $key }}') }" :inert="!isOpen('{{ $key }}')">
                                    <div class="tl-inner">
                                        <div class="relative border-t border-wheat-200 p-4 sm:p-5 space-y-4">

                                            @switch($key)

                                                {{-- ================= PEMBAYARAN ================= --}}
                                                @case('payment')
                                                    @if($status === 'pending')
                                                        <!-- Animasi "pembayaran diterima" -->
                                                        <div x-show="celebrate" x-cloak x-transition.opacity.duration.300ms
                                                             class="absolute inset-0 z-10 bg-white/95 flex flex-col items-center justify-center gap-2" role="status">
                                                            <svg class="w-16 h-16 text-avocado-600" viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                                                                <circle class="pay-ring" cx="28" cy="28" r="26.5" />
                                                                <path class="pay-tick" d="M16 29l8 8 16-17" />
                                                            </svg>
                                                            <span class="font-extrabold text-darkbrown-900">Pembayaran diterima</span>
                                                        </div>

                                                        <div class="flex flex-wrap items-end justify-between gap-3">
                                                            <div>
                                                                <div class="text-sm text-darkbrown-500">{{ $payTypeLabel }}</div>
                                                                <div class="text-2xl font-extrabold text-darkbrown-900 font-heading tracking-tight">{{ $rupiah($payAmount) }}</div>
                                                            </div>
                                                            @if($onlinePayment?->order_id)
                                                                <div class="text-right">
                                                                    <div class="text-xs text-darkbrown-500">ID pesanan</div>
                                                                    <div class="font-mono text-sm font-semibold text-darkbrown-800" x-text="orderId">{{ $onlinePayment->order_id }}</div>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="flex items-center gap-2 rounded-xl bg-sunglow-100/70 border border-sunglow-300/70 px-3.5 py-2.5 text-sm text-darkbrown-800">
                                                            <svg class="w-4 h-4 text-goldenrod-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                            <span x-show="!expired">
                                                                Bayar sebelum <strong>{{ $fmtDateTime($rental->expires_at) }}</strong>
                                                                <span x-show="remaining" x-cloak> · sisa <span class="font-mono font-bold" x-text="remaining"></span></span>
                                                            </span>
                                                            <span x-show="expired" x-cloak class="font-semibold text-red-700">Batas waktu pembayaran sudah terlewat.</span>
                                                        </div>

                                                        @if($vaInfo)
                                                            <div class="rounded-xl border-2 border-goldenrod-400 bg-white p-3 space-y-2">
                                                                <span class="text-xs font-bold text-darkbrown-800"
                                                                      x-text="vaBank === 'mandiri' ? 'Nomor bill key Mandiri' : 'Nomor virtual account'">Nomor virtual account</span>

                                                                <div x-show="vaBiller" x-cloak>
                                                                    <div class="text-xs text-darkbrown-500">Kode perusahaan (biller code)</div>
                                                                    <span class="mt-1 inline-block font-mono text-sm font-bold text-darkbrown-900 bg-wheat-100 px-2.5 py-1 rounded-lg border border-wheat-300 select-all" x-text="vaBiller">{{ $vaInfo['biller_code'] ?? '' }}</span>
                                                                </div>

                                                                <div class="va-pill rounded-full border border-goldenrod-400 bg-white" :aria-busy="vaLoading.toString()">
                                                                    <div class="va-pill-bank bg-wheat-100 border-goldenrod-400" @click.outside="vaOpen = false" @keydown.escape="vaOpen = false">
                                                                        <button type="button" x-ref="vaBtn" class="va-bank-btn text-darkbrown-900"
                                                                                @click="toggleBank()" :disabled="vaLoading || expired"
                                                                                aria-haspopup="listbox" :aria-expanded="vaOpen.toString()" aria-label="Pilih bank virtual account">
                                                                            <span x-text="vaBanks[vaBank] || vaBank.toUpperCase()">{{ $vaBanks[$vaBankKey] }}</span>
                                                                            <svg class="va-chev" :class="{ 'va-rot': vaOpen }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6" /></svg>
                                                                        </button>

                                                                        <div x-ref="vaMenu" x-show="vaOpen" x-cloak x-transition.opacity.duration.150ms role="listbox"
                                                                             class="va-menu rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm" style="top:-9999px;left:0">
                                                                            @foreach($vaBanks as $bankKey => $bankLabel)
                                                                                <button type="button" role="option" :aria-selected="(vaBank === '{{ $bankKey }}').toString()"
                                                                                        class="va-opt hover:bg-wheat-100"
                                                                                        :class="vaBank === '{{ $bankKey }}' ? 'font-extrabold text-darkbrown-900 bg-wheat-100' : 'font-semibold text-darkbrown-700'"
                                                                                        @click="pickBank('{{ $bankKey }}')">
                                                                                    <span>{{ $bankLabel }}</span>
                                                                                    <svg x-show="vaBank === '{{ $bankKey }}'" class="w-4 h-4 text-avocado-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7" /></svg>
                                                                                </button>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>

                                                                    <div class="va-pill-num">
                                                                        <span x-show="vaLoading" x-cloak class="va-loading text-xs font-semibold text-darkbrown-600">Membuat nomor VA...</span>
                                                                        <span x-show="!vaLoading" class="va-num font-mono font-black text-darkbrown-900 select-all" x-text="vaNumber || '-'">{{ $vaInfo['va_number'] ?? '-' }}</span>
                                                                        <button type="button" @click="copy(vaNumber)" :disabled="vaLoading || !vaNumber"
                                                                                class="shrink-0 px-2.5 py-1 rounded-full text-xs font-bold bg-white hover:bg-wheat-100 border border-wheat-300 transition disabled:opacity-60">
                                                                            <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                                                                        </button>
                                                                    </div>
                                                                </div>

                                                                <p class="text-xs text-darkbrown-500">Nomor dibuat langsung oleh Midtrans. Ganti bank = nomor sebelumnya dibatalkan.</p>
                                                            </div>
                                                        @endif

                                                        <div x-show="flash" x-cloak role="status"
                                                             :class="flashTone === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-wheat-50 border-wheat-200 text-darkbrown-700'"
                                                             class="rounded-xl border px-3.5 py-2.5 text-sm" x-text="flash"></div>

                                                        <div class="flex flex-wrap items-center gap-2.5" x-show="!expired">

                                                            @if($vaInfo && !config('services.midtrans.is_production'))
                                                                {{-- Sandbox: VA otomatis tersalin, lalu simulator Midtrans terbuka di tab baru --}}
                                                                <a href="https://simulator.sandbox.midtrans.com/" target="_blank" rel="noopener"
                                                                   @click="copy(vaNumber)"
                                                                   class="py-2.5 px-5 rounded-xl text-sm font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition flex items-center gap-2">
                                                                    <span>Bayar sekarang</span>
                                                                </a>
                                                            @else
                                                                {{-- Production / tanpa VA: pakai alur payNow() yang lama --}}
                                                                <button type="button" @click="payNow()" :disabled="paying"
                                                                        class="py-2.5 px-5 rounded-xl text-sm font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                                                                    <svg x-show="!paying" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                                                    <svg x-show="paying" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"></path></svg>
                                                                    <span x-text="paying ? 'Memproses...' : 'Bayar sekarang'">Bayar sekarang</span>
                                                                </button>
                                                            @endif

                                                            <button type="button" @click="sync()" :disabled="syncing"
                                                                    class="py-2.5 px-4 rounded-xl text-sm font-bold text-darkbrown-800 bg-white hover:bg-wheat-100 border border-wheat-300 shadow-sm transition flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                                                                <svg class="w-4 h-4" :class="syncing ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5.6 9A8 8 0 0119 11m-.6 4A8 8 0 015 13" /></svg>
                                                                <span x-text="syncing ? 'Mengecek...' : 'Cek status'">Cek status</span>
                                                            </button>
                                                        </div>
                                                    @endif
                                                    @break

                                                {{-- ================= SERAH TERIMA ================= --}}
                                                @case('pickup')
                                                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                                        <div>
                                                            <dt class="text-xs text-darkbrown-500">Tanggal ambil</dt>
                                                            <dd class="font-bold text-darkbrown-900">{{ $fmtDate($rental->pickup_date) }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-xs text-darkbrown-500">Tanggal kembali</dt>
                                                            <dd class="font-bold text-darkbrown-900">{{ $fmtDate($rental->return_date) }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-xs text-darkbrown-500">Cara menerima</dt>
                                                            <dd class="font-bold text-darkbrown-900">{{ $rental->pickup_method === 'delivery' ? 'Diantar kurir' : 'Ambil di toko' }}</dd>
                                                        </div>
                                                    </dl>

                                                    @if($rental->pickup_method === 'delivery' && $rental->delivery_address)
                                                        <div class="rounded-xl bg-wheat-50 border border-wheat-200 p-3.5 text-sm">
                                                            <div class="text-xs text-darkbrown-500">Alamat pengantaran</div>
                                                            <div class="font-semibold text-darkbrown-900">{{ $rental->delivery_address }}</div>
                                                        </div>
                                                    @elseif($rental->vendor)
                                                        <div class="rounded-xl bg-wheat-50 border border-wheat-200 p-3.5 text-sm">
                                                            <div class="text-xs text-darkbrown-500">Lokasi pengambilan</div>
                                                            <div class="font-semibold text-darkbrown-900">{{ $rental->vendor->name }}</div>
                                                            <div class="text-darkbrown-600">{{ $rental->vendor->address }}</div>
                                                        </div>
                                                    @endif

                                                    <p class="text-sm text-darkbrown-600 leading-relaxed">
                                                        Bawa <strong class="text-darkbrown-800">KTP asli</strong> dan tunjukkan kode booking <span class="font-mono font-bold text-darkbrown-900">#{{ $rental->booking_code }}</span> ke kasir vendor.
                                                    </p>
                                                    @break

                                                {{-- ================= MASA SEWA ================= --}}
                                                @case('rental')
                                                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                                        <div>
                                                            <dt class="text-xs text-darkbrown-500">Diserahkan</dt>
                                                            <dd class="font-bold text-darkbrown-900">{{ $rental->picked_up_at ? $fmtDateTime($rental->picked_up_at) : '-' }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-xs text-darkbrown-500">Wajib kembali</dt>
                                                            <dd class="font-bold text-darkbrown-900">{{ $fmtDate($rental->return_date) }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-xs text-darkbrown-500">Jaminan</dt>
                                                            <dd class="font-bold text-darkbrown-900">{{ $ktpLabel }}</dd>
                                                        </div>
                                                    </dl>
                                                    @if($canPayFine)
                                                        <button type="button" @click="payNow()" :disabled="paying"
                                                                class="w-full py-2.5 px-4 rounded-xl font-bold text-sm bg-red-600 hover:bg-red-700 text-white shadow transition disabled:opacity-70">
                                                            Bayar denda ({{ $rupiah($rental->total_late_fee + $rental->total_damage_fee) }})
                                                        </button>
                                                    @endif
                                                    @break

                                                {{-- ================= PENGEMBALIAN ================= --}}
                                                @case('return')
                                                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                                        <div>
                                                            <dt class="text-xs text-darkbrown-500">Dikembalikan</dt>
                                                            <dd class="font-bold text-darkbrown-900">{{ $rental->returned_at ? $fmtDateTime($rental->returned_at) : '-' }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-xs text-darkbrown-500">Jaminan</dt>
                                                            <dd class="font-bold text-darkbrown-900">{{ $ktpLabel }}</dd>
                                                        </div>
                                                    </dl>
                                                    @if($hasDenda)
                                                        <div class="rounded-xl bg-red-50 border border-red-200 p-3.5 text-sm text-red-800">
                                                            Ada denda yang perlu diselesaikan sebesar <strong>{{ $rupiah($rental->total_late_fee + $rental->total_damage_fee) }}</strong>.
                                                        </div>
                                                    @endif
                                                    @if($canPayFine)
                                                        <button type="button" @click="payNow()" :disabled="paying"
                                                                class="w-full py-2.5 px-4 rounded-xl font-bold text-sm bg-red-600 hover:bg-red-700 text-white shadow transition disabled:opacity-70">
                                                            Bayar denda ({{ $rupiah($rental->total_late_fee + $rental->total_damage_fee) }})
                                                        </button>
                                                    @endif
                                                    @break

                                                {{-- ================= SELESAI ================= --}}
                                                @case('done')
                                                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                                        <div>
                                                            <dt class="text-xs text-darkbrown-500">Total akhir</dt>
                                                            <dd class="font-extrabold text-darkbrown-900">{{ $rupiah($totalFinal) }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-xs text-darkbrown-500">Selesai pada</dt>
                                                            <dd class="font-bold text-darkbrown-900">{{ $fmtDateTime($rental->updated_at) }}</dd>
                                                        </div>
                                                    </dl>
                                                    @break

                                            @endswitch
                                        </div>
                                    </div>
                                </div>
                                @endunless
                            </section>
                        </li>
                    @endforeach
                </ol>

                <!-- RINCIAN PESANAN -->
                <section class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-5 sm:px-6 py-4 bg-wheat-100 border-b border-wheat-200">
                        <h3 class="font-extrabold text-darkbrown-900 font-heading">Rincian pesanan</h3>
                        <span class="text-sm text-darkbrown-600">{{ $rental->details->count() }} unit alat</span>
                    </div>

                    <ul class="divide-y divide-wheat-200">
                        @foreach($rental->details as $detail)
                            @php
                                $mItem = $detail->masterItem;
                                $unit  = $detail->itemUnit;
                            @endphp
                            <li class="px-5 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-wheat-100 border border-wheat-200 flex items-center justify-center shrink-0 text-avocado-700">
                                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 20L10 4 1 20h18z" /><path d="m14 20-4-8-4 8" /></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="font-bold text-darkbrown-900 text-sm">{{ $mItem?->name ?? 'Peralatan camping' }}</h4>
                                            @if($unit)
                                                <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-wheat-100 text-darkbrown-700 border border-wheat-300">{{ $unit->unit_code }}</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-sunglow-100 text-darkbrown-800 border border-sunglow-300">Unit dipilih saat serah terima</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-darkbrown-500 mt-0.5">
                                            {{ $rupiah($detail->daily_rate_snapshot) }}/hari × {{ $rental->rental_days }} hari
                                            @if($detail->condition_before)
                                                · Kondisi awal: <strong class="text-darkbrown-700">{{ $detail->condition_before }}</strong>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <span class="font-extrabold text-darkbrown-900 sm:text-right shrink-0">{{ $rupiah($detail->daily_rate_snapshot * $rental->rental_days) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="px-5 sm:px-6 py-5 bg-wheat-50/70 border-t border-wheat-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-darkbrown-700">Rincian biaya</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                  :class="current === 'payment' ? 'bg-sunglow-100 text-darkbrown-900 border-sunglow-300' : (current ? 'bg-avocado-100 text-avocado-800 border-avocado-300' : 'bg-red-50 text-red-800 border-red-200')"
                                  x-text="current === 'payment' ? 'Menunggu bayar' : (current ? 'Pembayaran diterima' : statusLabels[status])"></span>
                        </div>

                        <div class="space-y-2 text-sm text-darkbrown-600">
                            <div class="flex justify-between"><span>Subtotal sewa</span><span class="font-bold text-darkbrown-800">{{ $rupiah($rental->subtotal) }}</span></div>
                            @if($rental->delivery_fee > 0)
                                <div class="flex justify-between"><span>Biaya pengantaran</span><span class="font-bold text-darkbrown-800">{{ $rupiah($rental->delivery_fee) }}</span></div>
                            @endif
                            @if($rental->discount_amount > 0)
                                <div class="flex justify-between text-avocado-700 font-semibold"><span>Diskon</span><span>- {{ $rupiah($rental->discount_amount) }}</span></div>
                            @endif
                            @if($hasDenda)
                                <div class="pt-2 border-t border-red-200 text-red-700 space-y-1">
                                    @if($rental->total_late_fee > 0)
                                        <div class="flex justify-between"><span>Denda keterlambatan</span><span class="font-bold">+ {{ $rupiah($rental->total_late_fee) }}</span></div>
                                    @endif
                                    @if($rental->total_damage_fee > 0)
                                        <div class="flex justify-between"><span>Denda kerusakan unit</span><span class="font-bold">+ {{ $rupiah($rental->total_damage_fee) }}</span></div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="pt-3 border-t-2 border-wheat-200 flex items-baseline justify-between">
                            <span class="text-sm font-bold text-darkbrown-700">Total akhir</span>
                            <span class="text-2xl font-black tracking-tight font-heading {{ $hasDenda ? 'text-red-700' : 'text-darkbrown-900' }}">{{ $rupiah($totalFinal) }}</span>
                        </div>
                    </div>
                </section>
                            @else
                <div class="card-tendaku-warm p-12 text-center rounded-2xl border border-wheat-300">
                    <div class="w-16 h-16 bg-wheat-200 text-darkbrown-700 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                        ⛺
                    </div>
                    <h3 class="text-lg font-bold text-darkbrown-900 font-heading">Belum Ada Transaksi Sewa</h3>
                    <p class="text-sm text-darkbrown-600 max-w-md mx-auto mt-2">
                        Anda belum memiliki riwayat rental untuk kategori status yang dipilih. Jelajahi katalog dan sewa peralatan petualangan Anda sekarang!
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('customer.catalog.index') }}" class="btn-tendaku-primary py-2.5 px-6 rounded-xl text-sm font-bold inline-block">
                            Lihat Katalog Alat Camping
                        </a>
                    </div>
                </div>
                @endif
            </main>

            @if($hasRental)
            <aside class="status-right">
                <section class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm overflow-hidden">
                    <h3 class="px-5 py-4 font-extrabold text-darkbrown-900 font-heading border-b border-wheat-200">Aktivitas</h3>
                    <ul class="divide-y divide-wheat-200 max-h-[32rem] overflow-y-auto">
                        <template x-for="(a, i) in activities" :key="a.title + a.time + i">
                            <li class="flex items-start gap-3 px-5 py-3.5 border-l-4"
                                :class="[i === 0 ? 'border-avocado-500 bg-avocado-50/60' : 'border-transparent', a.fresh ? 'act-in' : '']">
                                <svg class="w-4 h-4 mt-1 shrink-0" :class="toneCls(a.tone)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" :d="iconPath[a.type]" /></svg>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-bold text-darkbrown-900 leading-snug" x-text="a.title"></div>
                                    <div x-show="a.sub" class="text-sm text-darkbrown-600" x-text="a.sub"></div>
                                    <div class="text-xs text-darkbrown-500 mt-0.5" x-text="a.time"></div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </section>

                                <div class="space-y-5">

                <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm p-5 text-center">
                    <div class="mx-auto w-20 h-20 rounded-full bg-avocado-800 text-wheat-100 flex items-center justify-center text-3xl font-extrabold font-heading">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($renterName, 0, 1)) }}
                    </div>
                    <h2 class="mt-3 font-extrabold text-darkbrown-900 leading-tight">{{ $renterName }}</h2>
                    <p class="text-sm text-darkbrown-500">Penyewa</p>

                    @if($rental->vendor?->phone)
                        <a href="tel:{{ $rental->vendor->phone }}" class="mt-4 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-bold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 border border-goldenrod-400 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            Hubungi vendor
                        </a>
                    @endif
                </div>

                <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm p-5 space-y-4 text-sm">
                    @if($renterPhone)
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-darkbrown-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            <div class="min-w-0">
                                <div class="text-xs text-darkbrown-500">WhatsApp penyewa</div>
                                <div class="font-semibold text-darkbrown-900">{{ $renterPhone }}</div>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-darkbrown-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        <div class="min-w-0">
                            <div class="text-xs text-darkbrown-500">Vendor</div>
                            <div class="font-semibold text-darkbrown-900">{{ $rental->vendor?->name }}</div>
                            <div class="text-darkbrown-600">{{ $rental->vendor?->address }}{{ $rental->vendor?->city ? ', ' . $rental->vendor->city : '' }}</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-darkbrown-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v9H3zM14 10h4l3 3v3h-7z" /><circle cx="7" cy="17.5" r="1.8" /><circle cx="17" cy="17.5" r="1.8" /></svg>
                        <div>
                            <div class="text-xs text-darkbrown-500">Cara menerima alat</div>
                            <div class="font-semibold text-darkbrown-900">{{ $rental->pickup_method === 'delivery' ? 'Diantar kurir vendor' : 'Ambil di toko' }}</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-darkbrown-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div>
                            <div class="text-xs text-darkbrown-500">Durasi sewa</div>
                            <div class="font-semibold text-darkbrown-900">{{ $rental->rental_days }} hari</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm p-5 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-bold text-darkbrown-900">Jaminan KTP</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $ktpBadge }}">{{ $ktpLabel }}</span>
                    </div>
                    <p class="text-sm text-darkbrown-600 leading-relaxed">
                        @if($rental->ktp_collateral_status === 'held')
                            KTP asli disimpan toko sejak {{ $fmtDateTime($rental->ktp_received_at) }} dan dikembalikan saat alat diserahkan kembali.
                        @elseif($rental->ktp_collateral_status === 'returned')
                            KTP asli dikembalikan pada {{ $fmtDateTime($rental->ktp_returned_at) }}.
                        @else
                            KTP asli diserahkan ke kasir vendor saat serah terima alat.
                        @endif
                    </p>
                    <a href="{{ route('booking.receipt', $rental->booking_code) }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-avocado-700 hover:text-avocado-800 underline underline-offset-2">
                        Lihat tanda terima KTP
                    </a>
                </div>
                            </div>
<div class="mt-5 rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm px-5 py-4 text-sm">
                    <div class="font-bold text-darkbrown-900">Butuh bantuan?</div>
                    <div class="text-darkbrown-500">Hubungi vendor untuk kendala bayar atau jadwal.</div>
                </div>
                        </aside>
            @endif
        </div>
    </div>

    @if($hasRental)
    {{-- Midtrans Snap SDK --}}
    @if(config('services.midtrans.client_key'))
    <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
            data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    @endif

    <script>
        function statusPage(cfg) {
            const wait = (ms) => new Promise(r => setTimeout(r, ms));
            const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            return {
                ...cfg,
                paying: false,
                syncing: false,
                celebrate: false,
                justDone: null,
                copied: false,
                remaining: '',
                vaBank: cfg.va?.bank ?? 'bca',
                vaNumber: cfg.va?.number ?? '',
                vaBiller: cfg.va?.biller ?? '',
                vaLoading: false,
                vaOpen: false,
                flash: '',
                flashTone: 'info',
                iconPath: {
                    doc:    'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    card:   'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
                    bag:    'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
                    return: 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6',
                    id:     'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0M9 14h.01M13 14h2',
                    flag:   'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9',
                },

                init() {
                    this.startCountdown();
                    // Daftar bank ditutup saat layar digulir/diubah ukurannya (posisinya fixed)
                    const closeBank = () => { if (this.vaOpen) this.vaOpen = false; };
                    window.addEventListener('scroll', closeBank, true);
                    window.addEventListener('resize', closeBank);
                    if (this.fresh) {
                        // Booking baru: bulatan "Booking dibuat" membal, aktivitas masuk dari atas
                        this.justDone = 'created';
                        this.activities.forEach(a => { a.fresh = true; });
                        setTimeout(() => { this.justDone = null; }, 900);
                        // Hapus ?new=1 supaya refresh tidak mengulang animasi
                        const u = new URL(window.location.href);
                        u.searchParams.delete('new');
                        window.history.replaceState({}, '', u.toString());
                    }
                },

                /* ---------- State timeline ---------- */
                get current() { return this.statusToNode[this.status] ?? null; },
                get ci() { return this.current ? this.order.indexOf(this.current) : -1; },

                state(key) {
                    if (key === 'created') return 'done';
                    if (this.ci < 0) return 'todo';
                    const i = this.order.indexOf(key);
                    if (i < this.ci) return 'done';
                    if (i === this.ci) return this.status === 'completed' ? 'done' : 'active';
                    return 'todo';
                },
                // Hanya titik yang sedang berjalan yang terbuka. Yang sudah lewat atau belum sampai tetap tertutup.
                isOpen(key) { return key !== 'created' && this.current === key; },
                lineFill(i) { return this.ci > i ? 100 : 0; },

                dotCls(key) {
                    const s = this.state(key);
                    if (s === 'done') return 'bg-avocado-600 border-avocado-600 text-white';
                    if (s === 'active') return 'bg-sunglow-300 border-goldenrod-400 text-darkbrown-900';
                    return 'bg-white border-wheat-300 text-darkbrown-400';
                },
                cardCls(key) {
                    const s = this.state(key);
                    if (s === 'active') return 'bg-white border-goldenrod-400 ring-2 ring-goldenrod-400/20 shadow-tendaku-sm';
                    if (s === 'done') return 'bg-white border-avocado-200';
                    return 'bg-[#FAF6ED] border-wheat-200';
                },
                toneCls(t) {
                    return { green: 'text-avocado-600', amber: 'text-goldenrod-600', red: 'text-red-600' }[t] || 'text-darkbrown-500';
                },

                /* ---------- Countdown ---------- */
                startCountdown() {
                    if (!this.expiresAtIso) return;
                    const tick = () => {
                        if (this.status !== 'pending') { this.remaining = ''; return; }
                        const ms = new Date(this.expiresAtIso) - new Date();
                        if (isNaN(ms)) { this.remaining = ''; return; }
                        if (ms <= 0) { this.remaining = ''; this.expired = true; return; }
                        const h = Math.floor(ms / 3600000);
                        const m = Math.floor((ms % 3600000) / 60000);
                        const s = Math.floor((ms % 60000) / 1000);
                        this.remaining = [h, m, s].map(n => String(n).padStart(2, '0')).join(':');
                    };
                    tick();
                    setInterval(tick, 1000);
                },

                /* ---------- Aksi ---------- */
                setFlash(msg, tone = 'info') { this.flash = msg; this.flashTone = tone; },

                headers() {
                    return {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                    };
                },

                copy(text) {
                    if (!text || !navigator.clipboard) return;
                    navigator.clipboard.writeText(text);
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 2000);
                },

                toggleBank() {
                    if (this.vaLoading || this.expired) return;
                    if (this.vaOpen) { this.vaOpen = false; return; }
                    this.vaOpen = true;
                    this.$nextTick(() => this.placeBankMenu());
                },
                placeBankMenu() {
                    const btn = this.$refs.vaBtn, menu = this.$refs.vaMenu;
                    if (!btn || !menu) return;
                    const r = btn.getBoundingClientRect();
                    const h = menu.offsetHeight, w = menu.offsetWidth;
                    const spaceBelow = window.innerHeight - r.bottom;
                    const top = (spaceBelow < h + 12 && r.top > h + 12) ? r.top - h - 6 : r.bottom + 6;
                    menu.style.top = top + 'px';
                    menu.style.left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8)) + 'px';
                },
                pickBank(key) {
                    this.vaOpen = false;
                    this.changeBank(key);
                },

                // Ganti bank VA: minta server membuat tagihan baru ke Midtrans. Nomor tidak pernah dibuat di browser.
                async changeBank(next) {
                    const prev = this.vaBank;
                    if (this.vaLoading || next === prev) return;
                    const prevNumber = this.vaNumber, prevBiller = this.vaBiller;
                    this.flash = '';
                    this.vaLoading = true;
                    this.vaNumber = '';
                    try {
                        const res = await fetch(this.vaUrl, { method: 'POST', headers: this.headers(), body: JSON.stringify({ bank: next }) });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok || !data.va_number) throw new Error(data.message || 'Gagal membuat nomor VA. Coba lagi.');
                        this.vaBank = next;
                        this.vaNumber = data.va_number;
                        this.vaBiller = data.biller_code || '';
                        if (data.order_id) this.orderId = data.order_id;
                    } catch (e) {
                        // Gagal: kembali ke bank & nomor sebelumnya (nomor lama masih aktif di Midtrans)
                        this.vaBank = prev;
                        this.vaNumber = prevNumber;
                        this.vaBiller = prevBiller;
                        this.setFlash(e.message || 'Koneksi bermasalah. Coba lagi.', 'error');
                    } finally {
                        this.vaLoading = false;
                    }
                },

                async payNow() {
                    if (this.paying) return;
                    this.paying = true;
                    this.flash = '';
                    try {
                        const res = await fetch(this.payUrl, { method: 'POST', headers: this.headers() });
                        const data = await res.json();

                        if (data && data.snap_token) {
                            if (typeof window.snap !== 'undefined') {
                                window.snap.pay(data.snap_token, {
                                    onSuccess: () => this.sync(true),
                                    // Belum dibayar tapi tagihan/VA sudah terbit: muat ulang agar nomor VA tampil
                                    onPending: () => window.location.reload(),
                                    onError: (r) => this.setFlash('Pembayaran gagal: ' + ((r && r.status_message) || 'terjadi kesalahan'), 'error'),
                                    onClose: () => this.sync(true),
                                });
                            } else {
                                this.setFlash('Jendela pembayaran belum siap. Muat ulang halaman lalu coba lagi.', 'error');
                            }
                        } else {
                            this.setFlash((data && data.message) || 'Gagal membuat tagihan pembayaran.', 'error');
                        }
                    } catch (e) {
                        this.setFlash('Koneksi bermasalah. Periksa internet Anda lalu coba lagi.', 'error');
                    } finally {
                        this.paying = false;
                    }
                },

                async sync(silent = false) {
                    if (this.syncing) return;
                    this.syncing = true;
                    if (!silent) this.flash = '';
                    try {
                        const res = await fetch(this.syncUrl, { method: 'POST', headers: this.headers() });
                        const data = await res.json();

                        if (data && data.is_paid) {
                            await this.handlePaid(data);
                        } else if (!silent) {
                            this.setFlash('Pembayaran belum terdeteksi' || 'menunggu pembayaran');
                        }
                    } catch (e) {
                        if (!silent) this.setFlash('Gagal mengecek status pembayaran. Coba lagi sebentar.', 'error');
                    } finally {
                        this.syncing = false;
                    }
                },

                // Pembayaran terdeteksi: centang -> tab bayar menutup -> tab serah terima membuka
                async handlePaid(data) {
                    // Pembayaran denda (bukan tagihan awal): cukup muat ulang agar angka terbaru tampil
                    if (this.current !== 'payment') { window.location.reload(); return; }

                    const slow = !reduced();

                    this.celebrate = true;
                    await wait(slow ? 1400 : 200);

                    const now = new Date().toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                    this.times.payment = data.paid_at_text || now;
                    this.activities.unshift({
                        title: 'Pembayaran diterima',
                        sub: this.payAmountText,
                        time: now,
                        type: 'card',
                        tone: 'green',
                        fresh: true,
                    });

                    // Ubah status: bulatan bayar jadi centang, garis terisi, tab bayar menutup, tab serah terima membuka
                    this.justDone = 'payment';
                    this.status = (data.status && this.statusToNode[data.status]) ? data.status : 'confirmed';

                    await this.$nextTick();
                    setTimeout(() => {
                        const el = document.getElementById('node-' + this.current);
                        if (el) el.scrollIntoView({ behavior: reduced() ? 'auto' : 'smooth', block: 'nearest' });
                    }, 350);
                    setTimeout(() => { this.celebrate = false; this.justDone = null; }, 900);
                },
            };
        }
    </script>
    @endif
</x-layouts.customer>
