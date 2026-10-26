<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->number }} - Tendaku</title>
    @vite('resources/css/app.css')
    <style>
        @page { size: A4; margin: 14mm; }
        @media print {
            body { background: #ffffff !important; }
            .no-print { display: none !important; }
            .invoice-sheet { box-shadow: none !important; border: 0 !important; margin: 0 !important; max-width: none !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-wheat-100 px-4 py-8 font-sans text-darkbrown-800">
    <div class="no-print mx-auto mb-4 flex max-w-[210mm] items-center justify-between gap-3">
        @if (auth()->user()?->isSuperAdmin())
            <a href="{{ route('admin.settlements') }}" class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                &larr; Kembali ke Settlement
            </a>
        @elseif (auth()->user()?->isVendorOwner())
            <a href="{{ route('vendor.owner.dashboard') }}" class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                &larr; Kembali ke Dashboard
            </a>
        @else
            <a href="{{ url()->previous() }}" class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                &larr; Kembali
            </a>
        @endif
        <button type="button" onclick="window.print()" class="btn-tendaku-primary !px-3.5 !py-2 text-xs">
            Cetak / Simpan PDF
        </button>
    </div>

    <main class="invoice-sheet mx-auto max-w-[210mm] rounded-2xl border border-wheat-300 bg-white p-10 shadow-tendaku">
        <header class="flex flex-col gap-6 border-b-2 border-darkbrown-800 pb-6 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <x-application-logo size="md" />
                </div>
                <p class="mt-3 max-w-xs text-xs leading-relaxed text-darkbrown-500">
                    Tendaku — Platform persewaan tenda &amp; perlengkapan acara.
                    Invoice settlement komisi dan pencairan vendor.
                </p>
            </div>

            <div class="text-left sm:text-right">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-avocado-700">Invoice Settlement</p>
                <h1 class="mt-1 font-mono text-xl font-extrabold text-darkbrown-900">{{ $invoice->number }}</h1>
                <p class="mt-1 text-xs text-darkbrown-500">Terbit {{ $invoice->issued_at?->isoFormat('D MMMM Y HH:mm') ?? '-' }}</p>
                <span class="mt-2 inline-flex rounded-full border border-avocado-300 bg-avocado-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-avocado-800">
                    {{ $invoice->status === 'paid' ? 'Lunas' : ucfirst($invoice->status) }}
                </span>
            </div>
        </header>

        <section class="mt-6 grid grid-cols-1 gap-5 text-xs sm:grid-cols-2">
            <div class="rounded-xl border border-wheat-200 bg-wheat-50 p-4">
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Ditagihkan Kepada</p>
                <p class="mt-1 text-sm font-extrabold text-darkbrown-900">{{ $vendor?->name ?? '-' }}</p>
                <p class="mt-0.5 text-darkbrown-600">{{ $vendor?->address ?? '-' }}</p>
                <p class="text-darkbrown-600">{{ $vendor?->city ?? '-' }}</p>
                <p class="text-darkbrown-600">{{ $vendor?->phone ?? '-' }}</p>
            </div>

            <div class="rounded-xl border border-wheat-200 bg-wheat-50 p-4">
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Periode &amp; Rekening</p>
                <p class="mt-1 font-bold text-darkbrown-900">
                    {{ $invoice->period_start->isoFormat('D MMMM Y') }} – {{ $invoice->period_end->isoFormat('D MMMM Y') }}
                </p>
                <p class="mt-1 text-darkbrown-600">Bank: {{ $settlement->bank_name ?: '-' }}</p>
                <p class="text-darkbrown-600">No. Rekening: {{ $settlement->bank_account_number ?: '-' }}</p>
                <p class="text-darkbrown-600">Atas Nama: {{ $settlement->bank_account_name ?: '-' }}</p>
                @if ($settlement->reference_number)
                    <p class="mt-1 text-darkbrown-600">Referensi Transfer: {{ $settlement->reference_number }}</p>
                @endif
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-xl border border-wheat-200">
            <table class="w-full text-left text-xs">
                <thead class="bg-darkbrown-800 text-[10px] font-bold uppercase tracking-wider text-wheat-200">
                    <tr>
                        <th class="px-4 py-3">Komponen</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200">
                    <tr>
                        <td class="px-4 py-3">Pendapatan bruto QRIS/Transfer</td>
                        <td class="px-4 py-3 text-right font-semibold">Rp {{ number_format((float) $settlement->gross_qris, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Pendapatan denda</td>
                        <td class="px-4 py-3 text-right font-semibold">Rp {{ number_format((float) $settlement->gross_fine_qris, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Komisi platform</td>
                        <td class="px-4 py-3 text-right font-semibold text-red-600">− Rp {{ number_format((float) $settlement->commission_total, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">MDR denda (potong vendor)</td>
                        <td class="px-4 py-3 text-right font-semibold text-red-600">− Rp {{ number_format((float) $settlement->fine_fee_total, 0, ',', '.') }}</td>
                    </tr>
                    @if ((float) $settlement->adjustment_amount !== 0.0)
                        <tr>
                            <td class="px-4 py-3">Penyesuaian carry-forward</td>
                            <td class="px-4 py-3 text-right font-semibold {{ (float) $settlement->adjustment_amount < 0 ? 'text-red-600' : 'text-avocado-700' }}">
                                {{ (float) $settlement->adjustment_amount < 0 ? '−' : '+' }} Rp {{ number_format(abs((float) $settlement->adjustment_amount), 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                    <tr class="bg-avocado-50">
                        <td class="px-4 py-4 text-sm font-extrabold text-darkbrown-900">Total Net Payout</td>
                        <td class="px-4 py-4 text-right text-base font-extrabold {{ (float) $invoice->total < 0 ? 'text-red-600' : 'text-avocado-800' }}">
                            Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        @if ((float) $settlement->cash_commission_offset > 0)
            <p class="mt-4 rounded-xl border border-sunglow-300 bg-sunglow-50 px-4 py-3 text-[11px] text-goldenrod-800">
                Termasuk komisi tunai sebesar Rp {{ number_format((float) $settlement->cash_commission_offset, 0, ',', '.') }}
                yang dipotong dari payout batch ini (piutang komisi tunai vendor).
            </p>
        @endif

        @if ((float) $settlement->net_payout < 0)
            <p class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[11px] text-red-700">
                Net payout batch ini negatif. Saldo piutang akan dibawa ke batch settlement berikutnya.
            </p>
        @endif

        <section class="mt-8 grid grid-cols-1 gap-6 text-xs sm:grid-cols-2">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Rincian Item</p>
                <div class="mt-2 space-y-1.5">
                    @foreach ($settlement->items as $item)
                        <div class="flex items-start justify-between gap-3 border-b border-dashed border-wheat-200 pb-1.5">
                            <span class="text-darkbrown-600">
                                {{ ucfirst(str_replace('_', ' ', $item->type)) }}
                                @if ($item->payment?->rental?->booking_code)
                                    · <span class="font-mono">{{ $item->payment->rental->booking_code }}</span>
                                @endif
                            </span>
                            <span class="shrink-0 font-semibold {{ (float) $item->net_amount < 0 ? 'text-red-600' : 'text-darkbrown-800' }}">
                                Rp {{ number_format((float) $item->net_amount, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col justify-end">
                <div class="rounded-xl border border-wheat-200 bg-wheat-50 p-4 text-[11px] leading-relaxed text-darkbrown-600">
                    <p class="font-bold text-darkbrown-900">Catatan</p>
                    <p class="mt-1">
                        Invoice ini diterbitkan otomatis saat batch settlement ditandai sudah dibayar.
                        Dana QRIS dilepas dari escrow pada saat yang sama.
                    </p>
                    <p class="mt-2">
                        Biaya transfer bank sebesar Rp {{ number_format((float) $settlement->transfer_fee, 0, ',', '.') }}
                        ditanggung platform dan tidak mengurangi hak vendor.
                    </p>
                    @if ($settlement->notes)
                        <p class="mt-2">{{ $settlement->notes }}</p>
                    @endif
                </div>

                <div class="mt-8 text-right">
                    <p class="text-[11px] text-darkbrown-500">Dibayar pada {{ $settlement->paid_at?->isoFormat('D MMMM Y HH:mm') ?? '-' }}</p>
                    <div class="mt-10 border-t border-darkbrown-300 pt-2 text-[11px] font-bold text-darkbrown-800">
                        Tendaku Finance
                    </div>
                </div>
            </div>
        </section>

        <footer class="mt-8 border-t border-wheat-200 pt-3 text-center text-[10px] text-darkbrown-400">
            Dokumen ini dihasilkan otomatis oleh Tendaku. Tidak memerlukan tanda tangan basah.
        </footer>
    </main>
</body>
</html>
