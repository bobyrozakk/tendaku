
<div class="receipt-section">
    <div class="receipt-section-title">RINCIAN PEMBAYARAN</div>

    <div class="receipt-row">
        <span>Subtotal Sewa</span>
        <span>Rp {{ number_format($subtotal ?? 0, 0, ',', '.') }}</span>
    </div>

    <div class="receipt-row">
        <span>Biaya Admin</span>
        <span>Rp {{ number_format($adminFee ?? 0, 0, ',', '.') }}</span>
    </div>

    <div class="receipt-row">
        <span>Biaya Pengantaran</span>
        <span>Rp {{ number_format($deliveryFee ?? 0, 0, ',', '.') }}</span>
    </div>

    @if (($discount ?? 0) > 0)
        <div class="receipt-row">
            <span>Diskon</span>
            <span>- Rp {{ number_format($discount, 0, ',', '.') }}</span>
        </div>
    @endif

    <div class="receipt-divider"></div>

    <div class="receipt-row receipt-grand-total">
        <strong>TOTAL TRANSAKSI</strong>
        <strong>Rp {{ number_format($grandTotal ?? 0, 0, ',', '.') }}</strong>
    </div>

    <div class="receipt-row">
        <span>Metode Pembayaran</span>
        <span>{{ $paymentMethod ?? 'Belum tersedia' }}</span>
    </div>

    <div class="receipt-row">
        <span>Sudah Dibayar</span>
        <span>Rp {{ number_format($amountPaid ?? 0, 0, ',', '.') }}</span>
    </div>

    <div class="receipt-row">
        <span>Sisa Tagihan</span>
        <strong>Rp {{ number_format($balance ?? 0, 0, ',', '.') }}</strong>
    </div>
</div>
