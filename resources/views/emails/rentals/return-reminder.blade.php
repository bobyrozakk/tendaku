<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat Pengembalian Sewa - Tendaku</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f7f5ef;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #2c2420;
            line-height: 1.5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #ebd9c8;
            box-shadow: 0 4px 12px rgba(44, 36, 32, 0.05);
        }
        .header {
            background-color: #314426;
            color: #ffffff;
            padding: 28px 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 13px;
            color: #d1dfc7;
        }
        .content {
            padding: 24px;
        }
        .alert-box {
            background-color: #fef8ee;
            border: 1px solid #f6d49b;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .alert-box h2 {
            margin: 0 0 6px 0;
            font-size: 15px;
            color: #92540d;
            font-weight: 700;
        }
        .alert-box p {
            margin: 0;
            font-size: 13px;
            color: #633c0c;
        }
        .time-box {
            background-color: #f5f8f2;
            border: 2px solid #5a7d45;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            margin: 20px 0;
        }
        .time-label {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            color: #4b663a;
            letter-spacing: 0.5px;
        }
        .time-value {
            font-size: 18px;
            font-weight: 800;
            color: #233519;
            margin-top: 4px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            font-size: 13px;
        }
        .details-table th {
            text-align: left;
            background-color: #f7f5ef;
            padding: 10px 12px;
            border-bottom: 1px solid #ebd9c8;
            font-size: 11px;
            text-transform: uppercase;
            color: #6d5b52;
        }
        .details-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f2eae2;
        }
        .btn-wrapper {
            text-align: center;
            margin: 28px 0 16px 0;
        }
        .btn {
            display: inline-block;
            background-color: #4a6b35;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
        }
        .footer {
            background-color: #fbf9f5;
            padding: 20px 24px;
            text-align: center;
            font-size: 11px;
            color: #8c786e;
            border-top: 1px solid #ebd9c8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TENDAKU</h1>
            <p>Platform Sewa Alat Camping &amp; Petualangan</p>
        </div>

        <div class="content">
            <p style="margin-top: 0; font-size: 15px;">Halo <strong>{{ $customerName }}</strong>,</p>
            <p style="font-size: 13px; color: #4f423b;">
                Ini adalah pengingat bahwa masa sewa peralatan camping Anda untuk booking <strong>#{{ $rental->booking_code }}</strong> akan segera berakhir dalam <strong>5 jam ke depan</strong>.
            </p>

            <div class="time-box">
                <div class="time-label">Batas Waktu Pengembalian</div>
                <div class="time-value">{{ $returnDateTime->translatedFormat('l, d F Y • H:i') }} WIB</div>
            </div>

            <div class="alert-box">
                <h2>Lokasi Pengembalian:</h2>
                <p><strong>{{ $vendor?->name ?? 'Vendor Tendaku' }}</strong><br>
                {{ $vendor?->address ?? 'Silakan kembalikan ke toko rental tempat pengambilan unit.' }}
                @if($vendor?->phone)
                    <br>Kontak Vendor: {{ $vendor->phone }}
                @endif
                </p>
            </div>

            <h3 style="font-size: 14px; margin-bottom: 8px; color: #2c2420;">Daftar Peralatan yang Dipinjam:</h3>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Peralatan</th>
                        <th style="text-align: center;">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->item_name ?? $item->masterItem?->name ?? 'Peralatan Camping' }}</td>
                            <td style="text-align: center; font-weight: 700;">{{ $item->quantity }} unit</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="text-align: center; color: #8c786e;">Rincian peralatan sesuai pesanan booking.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="margin-top: 20px; font-size: 12px; color: #6d5b52; background: #faf8f4; padding: 12px; border-radius: 8px;">
                <strong>Tips Pengembalian:</strong>
                <ul style="margin: 6px 0 0 0; padding-left: 20px;">
                    <li>Pastikan tenda dan peralatan dalam keadaan kering dan bersih.</li>
                    <li>Periksa kembali pasak, frame, dan aksesoris agar tidak ada yang tertinggal.</li>
                    <li>Pengembalian tepat waktu membantu menghindari denda keterlambatan per jam/hari.</li>
                </ul>
            </div>

            <div class="btn-wrapper">
                <a href="{{ route('booking.status', $rental->booking_code) }}" class="btn">
                    Lihat Status Booking Saya
                </a>
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0;">Email ini dikirim otomatis oleh sistem Tendaku karena Anda memiliki sewa aktif.</p>
            <p style="margin: 4px 0 0 0;">&copy; {{ date('Y') }} Tendaku Platform. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

