<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bukti Payout {{ $settlement->iris_reference_no }} - Tendaku</title>
    @vite('resources/css/app.css')
    <style>
        @page { size: A4; margin: 14mm; }
        @media print {
            body { background: #ffffff !important; }
            .no-print { display: none !important; }
            .proof-sheet { box-shadow: none !important; border: 0 !important; margin: 0 !important; max-width: none !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-wheat-100 px-4 py-8 font-sans text-darkbrown-800">
    @php
        $statusLabel = match ($settlement->iris_status) {
            'completed' => 'Selesai',
            'processed' => 'Diproses bank',
            'approved' => 'Disetujui Midtrans',
            'queued', 'requested' => 'Menunggu Midtrans',
            'failed' => 'Gagal',
            'rejected' => 'Ditolak',
            default => ucfirst((string) ($settlement->iris_status ?: 'Belum diproses')),
        };
        $statusVariant = match ($settlement->iris_status) {
            'completed' => 'avocado',
            'failed', 'rejected' => 'danger',
            'approved', 'processed' => 'sunglow',
            default => 'wheat',
        };
    @endphp

    <div class="no-print mx-auto mb-4 flex max-w-[210mm] items-center justify-between gap-3">
        @if (auth()->user()?->isSuperAdmin())
            <a href="{{ route('admin.settlements') }}" class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                &larr; Kembali ke Settlement
            </a>
        @else
            <a href="{{ route('vendor.pendapatan') }}" class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                &larr; Kembali ke Pendapatan
            </a>
        @endif

        <button type="button" onclick="window.print()" class="btn-tendaku-primary !px-3.5 !py-2 text-xs">
            Cetak Bukti
        </button>
    </div>

    <main class="proof-sheet mx-auto max-w-[210mm] rounded-2xl border border-wheat-200 bg-white p-8 shadow-tendaku">
        <header class="flex flex-col gap-4 border-b border-wheat-200 pb-6 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-avocado-700">Tendaku</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-darkbrown-900">Bukti Pencairan Vendor</h1>
                <p class="mt-1 text-xs text-darkbrown-500">
                    Payout melalui Midtrans {{ config('services.midtrans_iris.is_production') ? 'Payouts/Iris' : 'Payouts/Iris Sandbox' }}.
                </p>
            </div>

            <div class="text-left sm:text-right">
                <x-badge :variant="$statusVariant" size="sm" :dot="true">{{ $statusLabel }}</x-badge>
                <p class="mt-2 font-mono text-sm font-extrabold text-darkbrown-900">{{ $settlement->iris_reference_no }}</p>
                <p class="mt-1 text-[11px] text-darkbrown-500">Referensi Midtrans Iris</p>
            </div>
        </header>

        <section class="grid grid-cols-1 gap-4 border-b border-wheat-200 py-6 sm:grid-cols-2">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Vendor</p>
                <p class="mt-1 text-sm font-extrabold text-darkbrown-900">{{ $settlement->vendor?->name ?? '-' }}</p>
                <p class="text-xs text-darkbrown-500">{{ $settlement->vendor?->city ?? '-' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Batch Settlement</p>
                <p class="mt-1 text-sm font-extrabold text-darkbrown-900">#{{ $settlement->id }}</p>
                <p class="text-xs text-darkbrown-500">
                    Periode {{ $settlement->period_start->isoFormat('D MMMM Y') }} – {{ $settlement->period_end->isoFormat('D MMMM Y') }}
                </p>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 border-b border-wheat-200 py-6 sm:grid-cols-2">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Nominal Payout</p>
                <p class="mt-1 text-2xl font-extrabold text-avocado-700">
                    Rp {{ number_format((float) $settlement->net_payout, 0, ',', '.') }}
                </p>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Rekening Tujuan</p>
                <p class="mt-1 text-sm font-bold text-darkbrown-900">{{ $settlement->bank_name ?: '-' }}</p>
                <p class="font-mono text-sm text-darkbrown-700">{{ $settlement->bank_account_number ?: '-' }}</p>
                <p class="text-xs text-darkbrown-500">a.n. {{ $settlement->bank_account_name ?: '-' }}</p>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 border-b border-wheat-200 py-6 sm:grid-cols-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Diminta</p>
                <p class="mt-1 text-xs font-semibold text-darkbrown-800">
                    {{ $settlement->iris_requested_at?->isoFormat('D MMM Y HH:mm') ?? '-' }}
                </p>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Disetujui Midtrans</p>
                <p class="mt-1 text-xs font-semibold text-darkbrown-800">
                    {{ $settlement->iris_approved_at?->isoFormat('D MMM Y HH:mm') ?? '-' }}
                </p>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Selesai</p>
                <p class="mt-1 text-xs font-semibold text-darkbrown-800">
                    {{ $settlement->iris_completed_at?->isoFormat('D MMM Y HH:mm') ?? '-' }}
                </p>
            </div>
        </section>

        @if ($settlement->iris_error)
            <section class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
                <p class="font-bold">Catatan Midtrans</p>
                <p class="mt-1">{{ $settlement->iris_error }}</p>
            </section>
        @endif

        @if ($invoice)
            <section class="mt-6 rounded-xl border border-wheat-200 bg-wheat-50 px-4 py-3 text-xs text-darkbrown-600">
                <p><span class="font-bold text-darkbrown-800">Invoice vendor:</span> {{ $invoice->number }}</p>
                <p class="mt-1">
                    Status invoice:
                    <span class="font-semibold text-darkbrown-800">{{ $invoice->status === 'paid' ? 'Lunas' : ucfirst($invoice->status) }}</span>
                    @if ($invoice->paid_at)
                        · {{ $invoice->paid_at->isoFormat('D MMM Y HH:mm') }}
                    @endif
                </p>
            </section>
        @endif

        <footer class="mt-8 border-t border-wheat-200 pt-4 text-[10px] leading-relaxed text-darkbrown-400">
            <p>
                Dokumen ini dihasilkan otomatis dari data payout Midtrans Iris dan bukan bukti transfer manual.
                Status payout dianggap berhasil hanya setelah Midtrans mengembalikan status <span class="font-semibold">completed</span>.
            </p>
            <p class="mt-1">Dicetak {{ now()->isoFormat('D MMMM Y HH:mm') }}.</p>
        </footer>
    </main>
</body>
</html>
