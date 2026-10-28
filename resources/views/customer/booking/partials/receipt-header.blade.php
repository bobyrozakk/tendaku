
<div class="receipt-header">
    <div class="receipt-brand">TENDAKU</div>
    <div class="receipt-subtitle">BUKTI TRANSAKSI PENYEWAAN</div>

    <div class="receipt-divider"></div>

    <div class="receipt-row">
        <span>Kode Booking</span>
        <strong>{{ $rental->booking_code ?? $rental->id }}</strong>
    </div>

    <div class="receipt-row">
        <span>Tanggal Transaksi</span>
        <span>
            {{ optional($rental->created_at)->format('d/m/Y H:i') ?? '-' }}
        </span>
    </div>

    <div class="receipt-row">
        <span>Status</span>
        <strong>{{ $statusLabel ?? ucfirst($rental->status) }}</strong>
    </div>
</div>
