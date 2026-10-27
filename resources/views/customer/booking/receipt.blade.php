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

        $vendor = $rental->vendor;
        $pickup = \Carbon\Carbon::parse($rental->pickup_date);
        $return = \Carbon\Carbon::parse($rental->return_date);

        /*
         * Variabel berikut sebaiknya disiapkan oleh controller
         * berdasarkan data transaksi dan pembayaran yang sebenarnya:
         *
         * $payment, $items, $subtotal, $discount, $deposit,
         * $deliveryFee, $grandTotal, $amountPaid, $balance,
         * $paymentMethod, $pickupMethod, $pickupLocation.
         *
         * Jangan menganggap pembayaran berhasil hanya karena
         * halaman receipt sedang dibuka.
         */

        $paymentStatus = strtolower((string) ($payment?->status ?? 'unknown'));

        $isPaid = in_array($paymentStatus, [
            'paid',
            'success',
            'settlement',
            'completed',
            'verified',
        ], true);

        $statusLabel = $isPaid
            ? 'Pembayaran terverifikasi'
            : match ($paymentStatus) {
                'pending' => 'Menunggu pembayaran',
                'failed', 'deny', 'cancel', 'cancelled', 'expire', 'expired'
                    => 'Pembayaran gagal atau kedaluwarsa',
                default => 'Status pembayaran belum terverifikasi',
            };

        $currency = fn ($value) => 'Rp' . number_format(
            (float) $value,
            0,
            ',',
            '.'
        );

        $items = $items ?? collect();
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

        {{-- Daftar barang --}}
        <section class="receipt-card p-5 sm:p-6 print-break-inside">
            <h3 class="mb-4 font-extrabold">Detail barang sewaan</h3>

            <div class="overflow-x-auto">
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th class="numeric">Jumlah</th>
                            <th class="numeric">Harga satuan</th>
                            <th class="numeric">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>
                                    <span class="font-bold text-stone-800">
                                        {{ $item->name ?? $item->item_name ?? 'Item sewaan' }}
                                    </span>
                                    @if(!empty($item->variant_name))
                                        <span class="block text-xs text-stone-500">
                                            {{ $item->variant_name }}
                                        </span>
                                    @endif
                                </td>
                                <td class="numeric">
                                    {{ $item->quantity ?? $item->pivot?->quantity ?? 1 }}
                                </td>
                                <td class="numeric">
                                    {{ isset($item->unit_price)
                                        ? $currency($item->unit_price)
                                        : (isset($item->pivot?->price)
                                            ? $currency($item->pivot->price)
                                            : '-') }}
                                </td>
                                <td class="numeric font-semibold">
                                    {{ isset($item->subtotal)
                                        ? $currency($item->subtotal)
                                        : (isset($item->pivot?->subtotal)
                                            ? $currency($item->pivot->subtotal)
                                            : '-') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-5 text-center text-stone-500">
                                    Detail item belum tersedia.
                                    Pastikan controller mengirim daftar item booking.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Ringkasan biaya --}}
        <section class="receipt-card p-5 sm:p-6 print-break-inside">
            <h3 class="mb-4 font-extrabold">Rincian pembayaran</h3>

            <div class="ml-auto max-w-md space-y-3 text-sm">
                <div class="flex items-start justify-between gap-4">
                    <span class="text-stone-600">Subtotal sewa</span>
                    <span class="font-semibold">
                        {{ isset($subtotal) ? $currency($subtotal) : '-' }}
                    </span>
                </div>

                @if(isset($discount) && (float) $discount > 0)
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-stone-600">Diskon</span>
                        <span class="font-semibold">-{{ $currency($discount) }}</span>
                    </div>
                @endif

                @if(isset($deposit) && (float) $deposit > 0)
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-stone-600">Deposit / jaminan</span>
                        <span class="font-semibold">{{ $currency($deposit) }}</span>
                    </div>
                @endif

                @if(isset($deliveryFee) && (float) $deliveryFee > 0)
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-stone-600">Biaya pengiriman</span>
                        <span class="font-semibold">{{ $currency($deliveryFee) }}</span>
                    </div>
                @endif

                <div class="receipt-divider pt-3">
                    <div class="flex items-start justify-between gap-4">
                        <span class="font-extrabold">Total transaksi</span>
                        <span class="text-lg font-black">
                            {{ isset($grandTotal) ? $currency($grandTotal) : '-' }}
                        </span>
                    </div>
                </div>

                <div class="flex items-start justify-between gap-4">
                    <span class="text-stone-600">Jumlah dibayar</span>
                    <span class="font-bold text-green-800">
                        {{ isset($amountPaid) ? $currency($amountPaid) : '-' }}
                    </span>
                </div>

                @if(isset($balance) && (float) $balance > 0)
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-stone-600">Sisa pembayaran</span>
                        <span class="font-bold text-amber-800">{{ $currency($balance) }}</span>
                    </div>
                @endif

                @if($payment?->created_at)
                    <div class="pt-2 text-xs text-stone-500">
                        Waktu transaksi:
                        {{ $payment->created_at->translatedFormat('d M Y, H:i') }}
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
