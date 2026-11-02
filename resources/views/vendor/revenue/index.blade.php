<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Pendapatan &amp; Pencairan
                </h1>
                <p class="text-xs text-darkbrown-500 mt-0.5">
                    Invoice settlement, rincian komisi platform, dan status transfer dana ke rekening toko.
                </p>
            </div>

            <x-badge variant="avocado" :dot="true">Portal Vendor</x-badge>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-card variant="dark">
                <p class="text-[10px] font-bold uppercase tracking-wider text-wheat-300">Sudah Dicairkan</p>
                <p class="mt-2 text-2xl font-extrabold text-wheat-100">
                    Rp {{ number_format($paidTotal, 0, ',', '.') }}
                </p>
                <p class="mt-1 text-[11px] text-wheat-300">Total invoice berstatus lunas.</p>
            </x-card>

            <x-card>
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Menunggu Transfer</p>
                <p class="mt-2 text-2xl font-extrabold text-goldenrod-700">
                    Rp {{ number_format($awaitingTotal, 0, ',', '.') }}
                </p>
                <p class="mt-1 text-[11px] text-darkbrown-500">
                    {{ $awaitingCount }} invoice sudah terbit dan menunggu pembayaran platform.
                </p>
            </x-card>

            <x-card>
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Total Komisi Platform</p>
                <p class="mt-2 text-2xl font-extrabold text-darkbrown-900">
                    Rp {{ number_format($commissionTotal, 0, ',', '.') }}
                </p>
                <p class="mt-1 text-[11px] text-darkbrown-500">Komisi 5% dari nilai sewa, minimum Rp2.500 per rental.</p>
            </x-card>

            <livewire:vendor.bank-account-settings />
        </div>

        <x-card
            title="Invoice Settlement"
            subtitle="Dokumen tagihan pencairan yang diterbitkan setiap batch mingguan."
            padding="p-0"
            class="overflow-hidden"
        >
            @forelse ($invoices as $invoice)
                @php
                    $settlement = $invoice->settlement;
                    $statusLabel = match ($invoice->status) {
                        'paid' => 'Sudah Ditransfer',
                        'issued' => 'Menunggu Transfer',
                        'void' => 'Dibatalkan',
                        default => 'Draft',
                    };
                    $statusVariant = match ($invoice->status) {
                        'paid' => 'avocado',
                        'issued' => 'goldenrod',
                        'void' => 'danger',
                        default => 'wheat',
                    };
                @endphp

                <article class="border-b border-wheat-200/80 p-5 last:border-b-0 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-mono text-sm font-extrabold text-darkbrown-900">{{ $invoice->number }}</p>
                                <x-badge :variant="$statusVariant" size="sm" :dot="true">{{ $statusLabel }}</x-badge>
                            </div>
                            <p class="mt-1 text-xs text-darkbrown-500">
                                Periode {{ $invoice->period_start->isoFormat('D MMM Y') }} – {{ $invoice->period_end->isoFormat('D MMM Y') }}
                                · Terbit {{ $invoice->issued_at?->isoFormat('D MMM Y HH:mm') ?? '-' }}
                                @if ($invoice->paid_at)
                                    · Dibayar {{ $invoice->paid_at->isoFormat('D MMM Y HH:mm') }}
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($settlement)
                                <a href="{{ route('vendor.pendapatan.invoice', $settlement) }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="btn-tendaku-secondary !px-3 !py-1.5 text-xs">
                                    Cetak Invoice
                                </a>
                            @endif
                        </div>
                    </div>

                    @if ($settlement)
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
                            <div class="rounded-xl border border-wheat-200 bg-wheat-50 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Bruto QRIS/Transfer</p>
                                <p class="mt-1 text-sm font-bold text-darkbrown-800">Rp {{ number_format((float) $settlement->gross_qris, 0, ',', '.') }}</p>
                            </div>
                            <div class="rounded-xl border border-wheat-200 bg-wheat-50 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Denda</p>
                                <p class="mt-1 text-sm font-bold text-darkbrown-800">Rp {{ number_format((float) $settlement->gross_fine_qris, 0, ',', '.') }}</p>
                            </div>
                            <div class="rounded-xl border border-wheat-200 bg-wheat-50 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Komisi</p>
                                <p class="mt-1 text-sm font-bold text-red-600">− Rp {{ number_format((float) $settlement->commission_total, 0, ',', '.') }}</p>
                            </div>
                            <div class="rounded-xl border border-wheat-200 bg-wheat-50 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">MDR Denda</p>
                                <p class="mt-1 text-sm font-bold text-red-600">− Rp {{ number_format((float) $settlement->fine_fee_total, 0, ',', '.') }}</p>
                            </div>
                            <div class="rounded-xl border border-wheat-200 bg-wheat-50 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Komisi Tunai</p>
                                <p class="mt-1 text-sm font-bold text-darkbrown-800">Rp {{ number_format((float) $settlement->cash_commission_offset, 0, ',', '.') }}</p>
                            </div>
                            <div class="rounded-xl border border-avocado-200 bg-avocado-50 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-avocado-700">Net Payout</p>
                                <p class="mt-1 text-sm font-extrabold {{ (float) $invoice->total < 0 ? 'text-red-600' : 'text-avocado-800' }}">
                                    Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>

                        @if ((float) $settlement->adjustment_amount !== 0.0)
                            <p class="mt-3 rounded-xl border border-sunglow-300 bg-sunglow-50 px-3 py-2 text-[11px] text-goldenrod-800">
                                Penyesuaian carry-forward:
                                {{ (float) $settlement->adjustment_amount < 0 ? '−' : '+' }}
                                Rp {{ number_format(abs((float) $settlement->adjustment_amount), 0, ',', '.') }}
                                dari batch sebelumnya.
                            </p>
                        @endif

                        <div class="mt-3 flex flex-col gap-1.5 text-[11px] text-darkbrown-500 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-5">
                            <span>Rekening: {{ $settlement->bank_name ?: '-' }} · {{ $settlement->bank_account_number ?: '-' }} · a.n. {{ $settlement->bank_account_name ?: '-' }}</span>
                            @if ($settlement->reference_number)
                                <span>Referensi transfer: <span class="font-mono font-semibold text-darkbrown-700">{{ $settlement->reference_number }}</span></span>
                            @endif
                            @if ($invoice->status === 'paid' && $settlement->proof_url)
                                <a href="{{ $settlement->proof_url }}" target="_blank" rel="noopener" class="font-semibold text-avocado-700 hover:text-avocado-800">
                                    Lihat bukti transfer
                                </a>
                            @endif
                        </div>
                    @else
                        <p class="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                            Data batch settlement untuk invoice ini tidak ditemukan. Hubungi admin platform.
                        </p>
                    @endif
                </article>
            @empty
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-wheat-100 text-darkbrown-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <p class="mt-3 text-sm font-bold text-darkbrown-800">Belum ada invoice settlement</p>
                    <p class="mt-1 text-xs text-darkbrown-500">
                        Invoice akan muncul setelah batch pencairan disetujui oleh admin platform.
                    </p>
                </div>
            @endforelse

            @if ($invoices->hasPages())
                <div class="border-t border-wheat-200 bg-wheat-50/50 px-5 py-3">
                    {{ $invoices->links() }}
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.vendor>
