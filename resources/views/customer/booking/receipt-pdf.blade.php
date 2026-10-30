<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Receipt Order #{{ $rental->booking_code }} - Tendaku</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #2b2118; line-height: 1.5; }
        .brand { text-align: center; font-size: 20px; font-weight: bold; letter-spacing: 3px; }
        .subtitle { text-align: center; font-size: 9px; letter-spacing: 1px; color: #6b5a48; margin-bottom: 6px; }
        .divider { border-top: 1px dashed #8a7a66; margin: 8px 0; }
        .section-title { font-weight: bold; font-size: 9px; letter-spacing: 1px; margin: 10px 0 4px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        td.r { text-align: right; }
        .muted { color: #6b5a48; font-size: 9px; }
        .total td { font-size: 12px; font-weight: bold; padding-top: 4px; }
        .footer { text-align: center; margin-top: 14px; }
        .footer-brand { margin-top: 6px; font-size: 8px; letter-spacing: 1px; color: #6b5a48; }
    </style>
</head>
<body>
    <div class="brand">TENDAKU</div>
    <div class="subtitle">BUKTI TRANSAKSI PENYEWAAN</div>
    <div class="divider"></div>

    <table>
        <tr><td>Kode Booking</td><td class="r"><strong>{{ $rental->booking_code ?? $rental->id }}</strong></td></tr>
        <tr><td>Tanggal Transaksi</td><td class="r">{{ optional($rental->created_at)->format('d/m/Y H:i') ?? '-' }}</td></tr>
        <tr><td>Status Pembayaran</td><td class="r"><strong>{{ $statusLabel }}</strong></td></tr>
    </table>

    <div class="section-title">INFORMASI PENYEWAAN</div>
    <table>
        <tr><td>Nama Penyewa</td><td class="r">{{ $customerName }}</td></tr>
        <tr><td>No. Telepon</td><td class="r">{{ $customerPhone }}</td></tr>
        <tr><td>Nama Vendor</td><td class="r">{{ $vendor?->name ?? '-' }}</td></tr>
        <tr><td>Mulai Sewa</td><td class="r">{{ $pickup->format('d/m/Y') }}</td></tr>
        <tr><td>Akhir Sewa</td><td class="r">{{ $return->format('d/m/Y') }}</td></tr>
        <tr><td>Durasi Sewa</td><td class="r">{{ $days }} hari</td></tr>
    </table>

    <div class="section-title">RINCIAN BARANG</div>
    <table>
        @forelse ($items as $item)
            <tr>
                <td>
                    <strong>{{ $item['name'] }}</strong>
                    @if (! empty($item['category']))
                        <div class="muted">{{ $item['category'] }}</div>
                    @endif
                    <div class="muted">
                        {{ $item['quantity'] }} unit × {{ $item['rental_days'] }} hari × Rp {{ number_format($item['daily_rate'], 0, ',', '.') }}
                    </div>
                </td>
                <td class="r">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td class="muted">Belum ada rincian barang.</td></tr>
        @endforelse
    </table>

    <div class="section-title">RINCIAN PEMBAYARAN</div>
    <table>
        <tr><td>Subtotal Sewa</td><td class="r">Rp {{ number_format($subtotal, 0, ',', '.') }}</td></tr>
        <tr><td>Biaya Admin</td><td class="r">Rp {{ number_format($adminFee, 0, ',', '.') }}</td></tr>
        <tr><td>Biaya Pengantaran</td><td class="r">Rp {{ number_format($deliveryFee, 0, ',', '.') }}</td></tr>
        @if ($discount > 0)
            <tr><td>Diskon</td><td class="r">- Rp {{ number_format($discount, 0, ',', '.') }}</td></tr>
        @endif
    </table>
    <div class="divider"></div>
    <table>
        <tr class="total"><td>TOTAL TRANSAKSI</td><td class="r">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td></tr>
        <tr><td>Metode Pembayaran</td><td class="r">{{ $paymentMethod }}</td></tr>
        <tr><td>Sudah Dibayar</td><td class="r">Rp {{ number_format($amountPaid, 0, ',', '.') }}</td></tr>
        <tr><td>Sisa Tagihan</td><td class="r"><strong>Rp {{ number_format($balance, 0, ',', '.') }}</strong></td></tr>
    </table>

    <div class="divider"></div>
    <div class="footer">
        <strong>Terima kasih telah menggunakan Tendaku!</strong>
        <div class="muted">Simpan bukti transaksi ini sebagai referensi selama masa penyewaan.</div>
        <div class="footer-brand">TENDAKU · Rental Peralatan Camping</div>
    </div>
</body>
</html>
