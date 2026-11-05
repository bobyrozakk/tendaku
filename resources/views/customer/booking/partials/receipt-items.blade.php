
<div class="receipt-section">
    <div class="receipt-section-title">RINCIAN BARANG</div>

    @forelse ($items as $item)
        <div class="receipt-item">
            <div class="receipt-item-name">
                <strong>{{ $item['name'] }}</strong>

                @if (!empty($item['category']))
                    <div class="receipt-muted">
                        {{ $item['category'] }}
                    </div>
                @endif

                <div class="receipt-muted">
                    {{ $item['quantity'] }} unit
                    × {{ $item['rental_days'] }} hari
                    × Rp {{ number_format($item['daily_rate'], 0, ',', '.') }}
                </div>
            </div>

            <div class="receipt-item-price">
                Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
            </div>
        </div>
    @empty
        <div class="receipt-muted">
            Belum ada rincian barang.
        </div>
    @endforelse
</div>
