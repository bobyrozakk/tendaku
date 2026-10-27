@php
    $payment = $payment ?? null;
    $items = $items ?? collect();
@endphp

<x-layouts.customer>
    <x-slot name="title">
        Receipt Order #{{ $rental->booking_code }} - Tendaku
    </x-slot>

    <x-slot name="header">
        <div class="no-print">
            <nav class="flex flex-wrap items-center gap-2 text-xs text-darkbrown-500 mb-1.5">
                <a href="{{ route('home') }}" class="hover:text-avocado-600">Beranda</a>
                <span>/</span>
                <a href="{{ route('customer.catalog.index') }}" class="hover:text-avocado-600">Katalog</a>
                <span>/</span>
                <a href="{{ route('booking.status', $rental->booking_code) }}" class="hover:text-avocado-600">
                    Status #{{ $rental->booking_code }}
                </a>
                <span>/</span>
                <span class="font-semibold text-darkbrown-800">Receipt Order</span>
            </nav>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-800">
                Bukti Pemesanan & Pembayaran
            </h1>
            <p class="text-sm text-darkbrown-600 mt-1">
                Ringkasan transaksi dan jadwal penyewaan alat.
            </p>
        </div>
    </x-slot>

        @php
        $customerName = $rental->isGuest()
            ? ($rental->guest_name ?? 'Penyewa Tamu')
            : ($rental->customer?->name ?? 'Pelanggan');

        $customerPhone = $rental->isGuest()
            ? ($rental->guest_phone ?? '-')
            : ($rental->customer?->phone ?? '-');

        $vendor  = $rental->vendor;
        $pickup  = \Carbon\Carbon::parse($rental->pickup_date);
        $return  = \Carbon\Carbon::parse($rental->return_date);
        $days    = max(1, (int) $rental->rental_days);

        // Status & data pembayaran
        $paidPayments = $rental->payments->where('status', 'paid');
        $paidPayment  = $paidPayments->sortByDesc('paid_at')->first();
        $latestPayment = $rental->payments->sortByDesc('id')->first();
        $isPaid = in_array($rental->status, ['confirmed', 'picked_up', 'returned', 'completed'], true) || $paidPayments->isNotEmpty();

        $rawMethod = $paidPayment?->method ?? $paidPayment?->channel ?? $latestPayment?->channel ?? 'online';
                // Daftar nama resmi bank virtual account
        $vaBanks = [
            'bca'     => 'BCA',
            'bni'     => 'BNI',
            'bri'     => 'BRI',
            'permata' => 'Permata',
            'cimb'    => 'CIMB Niaga',
            'mandiri' => 'Mandiri',
        ];

        $activePayment = $paidPayment ?? $latestPayment;
        $detectedBank = strtolower((string) ($activePayment?->va_details['bank'] ?? ''));

        $channelKey = str_replace(['_va', 'va_', 'va'], '', strtolower($activePayment->payment_channel ?? $activePayment->method ?? ''));
        $bankCode = array_key_exists($detectedBank, $vaBanks) ? $detectedBank : ($vaBanks[$channelKey] ?? null ? $channelKey : null);
        $bankName = $bankCode ? $vaBanks[$bankCode] : null;

        $paymentMethod = $bankName
            ? ($bankName === 'Mandiri' ? 'Mandiri Bill Payment' : "{$bankName} Virtual Account")
            : match (strtolower((string) ($activePayment?->channel ?? $activePayment?->method ?? ''))) {
                'qris'  => 'QRIS Realtime',
                'cash'  => 'Tunai di Kasir Vendor',
                default => 'BCA Virtual Account', // Default VA utama
            };

        $statusLabel = match ($rental->status) {
            'pending'   => 'Menunggu Pembayaran',
            'cancelled' => 'Pesanan Dibatalkan',
            'expired'   => 'Kedaluwarsa',
            default     => $isPaid ? 'Pembayaran Terverifikasi' : 'Dikonfirmasi',
        };

        // Rincian Biaya Dinamis
        $subtotal    = (float) $rental->subtotal;
        $adminFee    = (float) round($subtotal * 0.05);
        $deliveryFee = (float) $rental->delivery_fee;
        $discount    = (float) $rental->discount_amount;
        $grandTotal  = (float) ($rental->final_total ?: $rental->grand_total);
        $amountPaid  = (float) $paidPayments->sum('amount');
        $balance     = max(0.0, $grandTotal - $amountPaid);

        // Ekstrak & kelompokkan barang yang disewa dari rental_details
        $items = $rental->details
            ->groupBy('master_item_id')
            ->map(function ($group) use ($days) {
                $first = $group->first();
                $qty   = $group->count();
                $rate  = (float) $first->daily_rate_snapshot;
                return (object) [
                    'name'        => $first->masterItem?->name ?? 'Peralatan Camping',
                    'category'    => $first->masterItem?->category?->name ?? null,
                    'quantity'    => $qty,
                    'daily_rate'  => $rate,
                    'rental_days' => $days,
                    'subtotal'    => $rate * $qty * $days,
                ];
            });

        $currency = fn ($val) => 'Rp ' . number_format((float) $val, 0, ',', '.');
    @endphp

    <div class="no-print max-w-5xl mx-auto flex flex-wrap justify-end gap-2 pt-4">
        <a href="{{ route('booking.status', $rental->booking_code) }}"
           class="inline-flex items-center gap-2 rounded-xl border border-wheat-300 bg-white px-4 py-2 text-sm font-bold text-darkbrown-800">
            <i data-lucide="chevron-left" class="h-4 w-4"></i>
            Kembali ke pesanan
        </a>

        <button type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-xl bg-avocado-700 px-5 py-2 text-sm font-bold text-white hover:bg-avocado-800">
            Cetak receipt
        </button>

        @if($vendor?->phone)
            <a href="tel:{{ $vendor->phone }}"
               class="inline-flex items-center rounded-xl border border-wheat-300 bg-white px-4 py-2 text-sm font-bold text-darkbrown-800">
                Hubungi vendor
            </a>
        @endif
    </div>

    <main id="order-receipt" class="receipt-page max-w-5xl mx-auto px-4 py-5 sm:py-7 space-y-5">

        {{-- Header receipt --}}
        <section class="receipt-card p-5 sm:p-8 print-break-inside">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-3">
                    <x-application-logo size="md" :withText="false" />
                    <div>
                        <div class="text-xl font-black tracking-tight">
                            TENDA<span class="text-avocado-600">KU</span>
                        </div>
                        <div class="text-xs text-stone-500">Outdoor rental platform</div>
                    </div>
                </div>

                <div class="sm:text-right">
                    <h2 class="text-xl font-extrabold">Bukti Pemesanan</h2>
                    <p class="text-sm text-stone-500">Receipt order & pembayaran</p>
                    <p class="receipt-mono mt-2 text-sm font-bold">
                        #{{ $rental->booking_code }}
                    </p>

                    <span class="receipt-status {{ $isPaid ? 'paid' : 'unverified' }} mt-3">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>

            <div class="receipt-divider my-6"></div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <span class="receipt-label">Nama penyewa</span>
                    <span class="receipt-value">{{ $customerName }}</span>
                    <span class="mt-1 block text-xs text-stone-500">{{ $customerPhone }}</span>
                </div>

                <div>
                    <span class="receipt-label">Tanggal booking</span>
                    <span class="receipt-value">
                        {{ $rental->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                    </span>
                </div>

                <div>
                    <span class="receipt-label">Metode pembayaran</span>
                    <span class="receipt-value">
                        {{ $paymentMethod ?? 'Sesuai transaksi' }}
                    </span>
                </div>
            </div>
        </section>

        {{-- Vendor dan jadwal --}}
        <section class="grid grid-cols-1 gap-5 md:grid-cols-2 print-break-inside">
            <div class="receipt-card p-5 sm:p-6">
                <h3 class="mb-4 font-extrabold">Informasi vendor</h3>

                <dl class="space-y-3">
                    <div>
                        <dt class="receipt-label">Nama toko</dt>
                        <dd class="receipt-value">{{ $vendor?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="receipt-label">Telepon</dt>
                        <dd class="receipt-value">{{ $vendor?->phone ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="receipt-label">Alamat toko</dt>
                        <dd class="receipt-value font-medium">
                            {{ $pickupLocation ?? $vendor?->address ?? '-' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="receipt-card p-5 sm:p-6">
                <h3 class="mb-4 font-extrabold">Jadwal penyewaan</h3>

                <dl class="space-y-3">
                    <div>
                        <dt class="receipt-label">Tanggal ambil</dt>
                        <dd class="receipt-value">{{ $pickup->translatedFormat('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="receipt-label">Tanggal kembali</dt>
                        <dd class="receipt-value">{{ $return->translatedFormat('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="receipt-label">Cara menerima</dt>
                        <dd class="receipt-value">{{ $pickupMethod ?? 'Ambil di toko' }}</dd>
                    </div>
                    <div>
                        <dt class="receipt-label">Durasi sewa</dt>
                        <dd class="receipt-value">{{ $rental->rental_days ?? '-' }} hari</dd>
                    </div>
                </dl>
            </div>
        </section>

        {{-- Daftar barang sewaan --}}
        <section class="receipt-card p-5 sm:p-6 print-break-inside">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-extrabold text-darkbrown-900">Detail barang sewaan</h3>
                <span class="text-xs text-darkbrown-500 font-medium">{{ $rental->details->count() }} unit total · durasi {{ $days }} hari</span>
            </div>

            <div class="overflow-x-auto">
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>Peralatan</th>
                            <th class="numeric">Jumlah</th>
                            <th class="numeric">Tarif per hari</th>
                            <th class="numeric">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>
                                    <span class="font-bold text-darkbrown-900 block">
                                        {{ $item->name }}
                                    </span>
                                    @if($item->category)
                                        <span class="block text-xs text-darkbrown-500">
                                            {{ $item->category }}
                                        </span>
                                    @endif
                                </td>
                                <td class="numeric font-semibold text-darkbrown-800">
                                    {{ $item->quantity }} unit
                                </td>
                                <td class="numeric text-darkbrown-600">
                                    {{ $currency($item->daily_rate) }}
                                    <span class="text-[11px] text-darkbrown-400 block">× {{ $item->rental_days }} hari</span>
                                </td>
                                <td class="numeric font-bold text-darkbrown-900">
                                    {{ $currency($item->subtotal) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-5 text-center text-darkbrown-500">
                                    Tidak ada rincian peralatan pada pesanan ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Ringkasan biaya --}}
        <section class="receipt-card p-5 sm:p-6 print-break-inside">
            <h3 class="mb-4 font-extrabold text-darkbrown-900">Rincian pembayaran</h3>

            <div class="ml-auto max-w-md space-y-3 text-sm">
                <div class="flex items-center justify-between text-darkbrown-700">
                    <span>Subtotal sewa ({{ $days }} hari)</span>
                    <span class="font-semibold text-darkbrown-900">{{ $currency($subtotal) }}</span>
                </div>

                @if($adminFee > 0)
                    <div class="flex items-center justify-between text-darkbrown-700">
                        <div>
                            <span>Biaya layanan & admin (5%)</span>
                            <span class="block text-[11px] text-darkbrown-400">Pemeliharaan platform & jaminan transaksi</span>
                        </div>
                        <span class="font-semibold text-darkbrown-900">{{ $currency($adminFee) }}</span>
                    </div>
                @endif

                @if($deliveryFee > 0)
                    <div class="flex items-center justify-between text-darkbrown-700">
                        <span>Biaya pengantaran</span>
                        <span class="font-semibold text-darkbrown-900">{{ $currency($deliveryFee) }}</span>
                    </div>
                @endif

                @if($discount > 0)
                    <div class="flex items-center justify-between text-avocado-700 font-medium">
                        <span>Diskon promo</span>
                        <span>-{{ $currency($discount) }}</span>
                    </div>
                @endif

                <div class="receipt-divider pt-3">
                    <div class="flex items-baseline justify-between">
                        <span class="font-extrabold text-darkbrown-900 text-base">Total transaksi</span>
                        <span class="text-xl font-black text-darkbrown-900 font-heading">
                            {{ $currency($grandTotal) }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1 text-darkbrown-700">
                    <span class="font-medium">Jumlah yang telah dibayar</span>
                    <span class="font-bold text-avocado-700">
                        {{ $amountPaid > 0 ? $currency($amountPaid) : 'Rp 0' }}
                    </span>
                </div>

                @if($balance > 0)
                    <div class="flex items-center justify-between rounded-xl bg-sunglow-50 border border-sunglow-300 px-3.5 py-2.5 text-xs text-darkbrown-900">
                        <span class="font-semibold">Sisa pelunasan (saat ambil alat)</span>
                        <span class="font-bold text-goldenrod-900 text-sm">{{ $currency($balance) }}</span>
                    </div>
                @else
                    <div class="flex items-center justify-between rounded-xl bg-avocado-50 border border-avocado-200 px-3.5 py-2 text-xs text-avocado-900">
                        <span class="font-semibold">Status tagihan</span>
                        <span class="font-bold text-avocado-800">Lunas Sepenuhnya</span>
                    </div>
                @endif

                @if($paidPayment?->paid_at)
                    <div class="pt-2 text-right text-[11px] text-darkbrown-400">
                        Waktu verifikasi: {{ \Carbon\Carbon::parse($paidPayment->paid_at)->translatedFormat('d M Y, H:i') }} WIB
                    </div>
                @endif
            </div>
        </section>

        {{-- Informasi pengambilan --}}
        <section class="receipt-card p-5 sm:p-6 print-break-inside">
            <h3 class="mb-3 font-extrabold">Informasi pengambilan</h3>

            <p class="text-sm leading-relaxed text-stone-700">
                Tunjukkan kode booking
                <strong class="receipt-mono">{{ $rental->booking_code }}</strong>
                kepada petugas vendor saat mengambil alat. Bawa KTP asli untuk
                verifikasi identitas sesuai kebijakan vendor.
            </p>

            <p class="mt-3 text-sm leading-relaxed text-stone-600">
                Receipt ini merupakan ringkasan pesanan dan pembayaran.
                Receipt ini bukan bukti bahwa alat telah diserahterimakan atau
                bahwa KTP telah diterima vendor.
            </p>
        </section>

        <footer class="pb-4 text-center text-xs text-stone-500">
            <p>Terima kasih telah menggunakan Tendaku.</p>
            <p class="mt-1">Simpan receipt ini sebagai referensi transaksi Anda.</p>
        </footer>
    </main>
</x-layouts.customer>
