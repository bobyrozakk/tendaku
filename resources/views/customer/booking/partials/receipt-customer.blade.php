
<div class="receipt-section">
    <div class="receipt-section-title">INFORMASI PENYEWAAN</div>

    <div class="receipt-row">
        <span>Nama Penyewa</span>
        <span>{{ $customerName ?? $rental->customer?->name ?? '-' }}</span>
    </div>

    <div class="receipt-row">
        <span>No. Telepon</span>
        <span>{{ $customerPhone ?? $rental->customer?->phone ?? '-' }}</span>
    </div>

    <div class="receipt-row">
        <span>Nama Vendor</span>
        <span>{{ $vendor?->name ?? $rental->vendor?->name ?? '-' }}</span>
    </div>

    <div class="receipt-row">
        <span>Mulai Sewa</span>
        <span>
            {{ isset($pickup) ? $pickup->format('d/m/Y') : \Carbon\Carbon::parse($rental->pickup_date)->format('d/m/Y') }}
        </span>
    </div>

    <div class="receipt-row">
        <span>Akhir Sewa</span>
        <span>
            {{ isset($return) ? $return->format('d/m/Y') : \Carbon\Carbon::parse($rental->return_date)->format('d/m/Y') }}
        </span>
    </div>

    <div class="receipt-row">
        <span>Durasi Sewa</span>
        <span>{{ $days ?? $rental->rental_days }} hari</span>
    </div>
</div>
