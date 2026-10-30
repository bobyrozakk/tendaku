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
                        <div>
                            <span class="text-xs font-semibold text-darkbrown-500 block leading-none mb-0.5">Kode booking</span>
                            <span class="font-mono text-sm sm:text-base font-extrabold text-darkbrown-900">#{{ $rental->booking_code }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $filterStatus = request('status', '');
        $hasRental    = isset($rental) && $rental;
        $hasList      = auth()->check() && isset($rentals);

        // Determine paid status safely (rental may not be set on list-only page)
        $isPaid = $hasRental
            ? $rental->payments->contains(fn ($p) => $p->status === 'paid')
            : false;

        $statusLabels = [
            'pending'   => $isPaid ? 'Menunggu Validasi Kasir' : 'Menunggu Pembayaran',
            'confirmed' => 'Dikonfirmasi (Siap Pickup)',
            'picked_up' => 'Sedang Dipakai (Disewa)',
            'returned'  => 'Telah Dikembalikan',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'expired'   => 'Kedaluwarsa',
        ];

        $statusTabs = ['' => 'Semua Transaksi'] + [
            'pending'   => 'Menunggu Pembayaran / Validasi',
            'confirmed' => 'Dikonfirmasi (Siap Pickup)',
            'picked_up' => 'Sedang Dipakai (Disewa)',
            'returned'  => 'Telah Dikembalikan',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'expired'   => 'Kedaluwarsa',
        ];
    @endphp

    @if($hasRental)
    @php
        $status = $rental->status;

        // Urutan titik timeline dan pemetaan status rental ke titik aktif.
        $nodeOrder = ['created', 'payment', 'pickup', 'rental', 'return', 'done'];

        // Jika pembayaran sudah dibayar (paid) tetapi status rental masih pending (menunggu validasi barcode kasir),
        // tampilan timeline customer sudah maju ke tahap serah terima / pickup
        // ($isPaid sudah dihitung di atas, cukup dipakai ulang di sini)

        $statusToNode = [
            'pending'   => $isPaid ? 'pickup' : 'payment',
            'confirmed' => 'pickup',
            'picked_up' => 'rental',
            'returned'  => 'return',
            'completed' => 'done',
            'cancelled' => null,
            'expired'   => null,
        ];

        $isExpiredOrCancelled = in_array($status, ['cancelled', 'expired'], true);
        $isExpiredNow = $status === 'pending' && ! $isPaid && $rental->expires_at && $rental->expires_at->isPast();

        // Utamakan tagihan online yang masih menunggu (terbaru). Setelah ganti bank ada beberapa payment per rental.
        $onlinePayment = $rental->payments->where('channel', 'midtrans')->whereIn('payment_type', ['full', 'dp'])->where('status', 'pending')->sortByDesc('id')->first()
            ?? $latestPayment
            ?? $rental->payments->where('channel', 'midtrans')->sortByDesc('id')->first();
        $paidPayment   = $rental->payments->where('status', 'paid')->sortByDesc('paid_at')->first();
        $vaInfo        = $onlinePayment?->va_details;

        $vaBanks = [
            'bca'     => 'BCA',
            'bni'     => 'BNI',
            'bri'     => 'BRI',
            'permata' => 'Permata',
            'cimb'    => 'CIMB Niaga',
        ];
        $vaBankKey = strtolower((string) ($vaInfo['bank'] ?? ''));
        $vaBankKey = array_key_exists($vaBankKey, $vaBanks) ? $vaBankKey : 'bca';

        $fineAmount = $rental->total_late_fee + $rental->total_damage_fee;
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
            'rental'  => ['title' => 'Sedang disewa',             'icon' => 'M19 20L10 4 1 20h18z'],
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
            $actTime = $isPaid ? ($p->paid_at ?? $p->created_at) : $p->created_at;
            $acts->push([
                'title' => $label,
                'sub'   => $rupiah($p->amount),
                'at'    => $actTime ? \Carbon\Carbon::parse($actTime)->timezone(config('app.timezone', 'Asia/Jakarta'))->toIso8601String() : now()->toIso8601String(),
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
            'isPaid'        => (bool) $isPaid,
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

    <div @if($hasRental) x-data="statusPage({{ \Illuminate\Support\Js::from($pageConfig) }})" @endif class="py-4 sm:py-6">

        @include('customer.booking.status.sections.expired-notice')

        <div class="status-grid {{ $hasList ? '' : 'no-list' }} {{ $hasRental ? '' : 'no-right' }}">
            @include('customer.booking.status.sections.history')

            <main class="status-main space-y-6">
                @if($hasRental)

                @include('customer.booking.status.sections.timeline')

                @include('customer.booking.status.sections.details')
                @else
                @include('customer.booking.status.sections.empty-state')

                @endif
            </main>

            @if($hasRental)
            @include('customer.booking.status.sections.sidebar')
            @endif
        </div>
    </div>

    @if($hasRental)
    {{-- Midtrans Snap SDK --}}
    @if(config('services.midtrans.client_key'))
    <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
            data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    @endif

    @endif
</x-layouts.customer>
