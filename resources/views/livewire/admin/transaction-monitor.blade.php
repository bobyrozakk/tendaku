<div class="space-y-6" wire:poll.30s>
    @php
        $formatRupiah = fn (float $value): string => 'Rp '.number_format($value, 0, ',', '.');

        $stageVariants = [
            'awaiting_payment' => 'goldenrod',
            'paid_in_progress' => 'sunglow',
            'ready_settlement' => 'wheat',
            'in_settlement' => 'sunglow',
            'awaiting_transfer' => 'goldenrod',
            'paid_out' => 'avocado',
            'cancelled' => 'danger',
        ];

        $commissionVariants = [
            'accrued' => 'goldenrod',
            'settled' => 'avocado',
            'mixed' => 'sunglow',
            'none' => 'wheat',
        ];
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card
            title="Menunggu Pembayaran"
            :value="$summary['counts']['awaiting_payment'].' booking'"
            description="Belum ada pembayaran lunas"
            color="goldenrod"
        />
        <x-stat-card
            title="Diproses Rental"
            :value="$summary['counts']['paid_in_progress'].' booking'"
            description="Sudah dibayar, menunggu pickup/return"
            color="sunglow"
        />
        <x-stat-card
            title="Siap Settlement"
            :value="$summary['counts']['ready_settlement'].' booking'"
            description="Rental selesai, belum masuk batch"
            color="goldenrod"
        />
        <x-stat-card
            title="Menunggu Transfer"
            :value="$summary['counts']['awaiting_transfer'].' booking'"
            :description="$summary['awaiting_transfer_batches'].' batch · '.$formatRupiah($summary['awaiting_transfer_amount'])"
            color="dark"
        />
        <x-stat-card
            title="Sudah Dicairkan"
            :value="$summary['counts']['paid_out'].' booking'"
            :description="$summary['paid_out_batches'].' batch · '.$formatRupiah($summary['paid_out_amount'])"
            color="avocado"
        />
    </div>

    <div class="flex flex-col gap-3 rounded-2xl border border-wheat-200 bg-white p-4 shadow-tendaku-sm lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-2 text-[11px] text-darkbrown-600">
            <x-badge variant="wheat" size="sm">Komisi accrued {{ $formatRupiah($summary['commission_accrued']) }}</x-badge>
            <x-badge variant="sunglow" size="sm">{{ $summary['counts']['in_settlement'] }} draft settlement</x-badge>
            <x-badge variant="avocado" size="sm">{{ $summary['counts']['cancelled'] }} dibatalkan/expired</x-badge>
        </div>

        <p class="text-[11px] font-medium text-darkbrown-400">
            Refresh otomatis setiap 30 detik. Angka mengikuti pencarian dan filter vendor.
        </p>
    </div>

    <div class="card-tendaku p-5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <label class="block sm:col-span-2">
                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Cari Transaksi</span>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Kode booking, nama tamu, telepon, atau email customer..."
                    class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-semibold text-darkbrown-800 shadow-sm focus:border-goldenrod-500 focus:outline-none focus:ring-2 focus:ring-goldenrod-200"
                >
            </label>

            <label class="block">
                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Tahap</span>
                <select
                    wire:model.live="stage"
                    class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-semibold text-darkbrown-800 shadow-sm focus:border-goldenrod-500 focus:outline-none focus:ring-2 focus:ring-goldenrod-200"
                >
                    <option value="">Semua Tahap</option>
                    @foreach ($stageLabels as $stageKey => $stageLabel)
                        <option value="{{ $stageKey }}">{{ $stageLabel }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Vendor</span>
                <select
                    wire:model.live="vendorId"
                    class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-semibold text-darkbrown-800 shadow-sm focus:border-goldenrod-500 focus:outline-none focus:ring-2 focus:ring-goldenrod-200"
                >
                    <option value="">Semua Vendor</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-semibold text-darkbrown-400">
                <span class="rounded-lg border border-wheat-200 bg-wheat-50 px-2 py-1">Booking</span>
                <span>→</span>
                <span class="rounded-lg border border-wheat-200 bg-wheat-50 px-2 py-1">Pembayaran</span>
                <span>→</span>
                <span class="rounded-lg border border-wheat-200 bg-wheat-50 px-2 py-1">Rental selesai</span>
                <span>→</span>
                <span class="rounded-lg border border-wheat-200 bg-wheat-50 px-2 py-1">Invoice</span>
                <span>→</span>
                <span class="rounded-lg border border-wheat-200 bg-wheat-50 px-2 py-1">Payout</span>
            </div>

            <button
                type="button"
                wire:click="resetFilters"
                class="inline-flex items-center gap-1.5 rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-semibold text-darkbrown-700 transition hover:border-goldenrod-300 hover:bg-goldenrod-50"
            >
                Reset Filter
            </button>
        </div>
    </div>

    <x-card
        title="Alur Transaksi Customer → Payout Vendor"
        subtitle="Satu baris mewakili satu booking. Kolom kanan menunjukkan langkah berikutnya yang perlu dilakukan."
        padding="p-0"
        class="overflow-hidden"
    >
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1180px] text-left text-xs">
                <thead class="border-y border-wheat-200 bg-wheat-50 font-bold uppercase tracking-wider text-darkbrown-600">
                    <tr>
                        <th class="px-4 py-3">Booking / Vendor</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Pembayaran</th>
                        <th class="px-4 py-3">Komisi Platform</th>
                        <th class="px-4 py-3">Tahap &amp; Progress</th>
                        <th class="px-4 py-3">Settlement / Invoice</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200 text-darkbrown-800">
                    @forelse ($rentals as $rental)
                        <tr wire:key="monitor-rental-{{ $rental['rental_id'] }}" class="align-top transition hover:bg-wheat-50/60">
                            <td class="px-4 py-4">
                                <p class="font-mono text-[11px] font-extrabold text-darkbrown-950">{{ $rental['booking_code'] }}</p>
                                <p class="mt-0.5 text-[10px] text-darkbrown-400">
                                    {{ $rental['booking_created_at']?->isoFormat('D MMM Y HH:mm') ?? '—' }}
                                </p>
                                <p class="mt-2 font-semibold text-darkbrown-800">{{ $rental['vendor_name'] }}</p>
                                <p class="text-[10px] text-darkbrown-400">{{ $rental['vendor_city'] ?: '—' }}</p>
                            </td>

                            <td class="px-4 py-4">
                                <p class="font-bold text-darkbrown-900">{{ $rental['customer_name'] }}</p>
                                <p class="mt-0.5 text-[10px] text-darkbrown-400">{{ $rental['customer_type'] }}</p>
                                @if ($rental['customer_contact'])
                                    <p class="mt-0.5 break-all text-[10px] text-darkbrown-400">{{ $rental['customer_contact'] }}</p>
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <x-badge
                                        :variant="in_array($rental['payment_state'], ['paid'], true) ? 'avocado' : (in_array($rental['payment_state'], ['expired', 'failed', 'cancelled'], true) ? 'danger' : 'goldenrod')"
                                        size="sm"
                                        :dot="true"
                                    >
                                        {{ $rental['payment_label'] }}
                                    </x-badge>
                                    @if ($rental['requires_refund'])
                                        <x-badge variant="danger" size="sm">Perlu Refund</x-badge>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm font-extrabold text-darkbrown-900">
                                    {{ $formatRupiah($rental['paid_total'] > 0 ? $rental['paid_total'] : (float) ($rental['payment_amount'] ?? $rental['grand_total'])) }}
                                </p>
                                <p class="text-[10px] text-darkbrown-400">
                                    {{ $rental['payment_type_label'] }} · {{ $rental['payment_method_label'] }}
                                    @if ($rental['payment_channel'])
                                        · {{ strtoupper($rental['payment_channel']) }}
                                    @endif
                                </p>
                                @if ($rental['payment_date'])
                                    <p class="mt-1 text-[10px] text-darkbrown-400">{{ $rental['payment_date']->isoFormat('D MMM Y HH:mm') }}</p>
                                @endif
                                @if ($rental['paid_total'] > 0 && $rental['remaining_amount'] > 0)
                                    <p class="mt-1 text-[10px] font-bold text-goldenrod-700">
                                        Sisa {{ $formatRupiah($rental['remaining_amount']) }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                <p class="text-sm font-extrabold text-avocado-700">{{ $formatRupiah($rental['commission_amount']) }}</p>
                                <div class="mt-1">
                                    <x-badge :variant="$commissionVariants[$rental['commission_status']] ?? 'wheat'" size="sm">
                                        {{ $rental['commission_status_label'] }}
                                    </x-badge>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <x-badge :variant="$stageVariants[$rental['stage']] ?? 'wheat'" size="sm" :dot="true">
                                    {{ $rental['stage_label'] }}
                                </x-badge>

                                <div class="mt-2 flex items-center gap-1">
                                    @foreach ($rental['steps'] as $step)
                                        <span
                                            class="h-2 w-2 rounded-full {{ $step['done'] ? 'bg-avocado-500' : 'bg-wheat-300' }}"
                                            title="{{ $step['label'] }}{{ $step['done'] && $step['at'] ? ' · '.$step['at']->isoFormat('D MMM Y HH:mm') : ' · belum' }}"
                                        ></span>
                                        @if (! $loop->last)
                                            <span class="h-px w-3 bg-wheat-300"></span>
                                        @endif
                                    @endforeach
                                </div>

                                <p class="mt-1.5 max-w-[220px] text-[10px] leading-relaxed text-darkbrown-500">{{ $rental['next_action'] }}</p>
                                <p class="mt-1 text-[10px] font-semibold text-darkbrown-700">Rental: {{ $rental['rental_status_label'] }}</p>
                            </td>

                            <td class="px-4 py-4">
                                @if ($rental['settlement_id'])
                                    <p class="font-semibold text-darkbrown-800">
                                        Batch #{{ $rental['settlement_id'] }}
                                        <span class="text-[10px] font-medium text-darkbrown-400">· {{ $rental['settlement_status_label'] }}</span>
                                    </p>
                                    <p class="mt-0.5 text-[10px] text-darkbrown-400">
                                        {{ $rental['settlement_period_start']?->isoFormat('D MMM') }} – {{ $rental['settlement_period_end']?->isoFormat('D MMM Y') }}
                                    </p>
                                    <p class="mt-1 text-[11px] text-darkbrown-600">Net payout: <span class="font-bold">{{ $formatRupiah((float) $rental['net_payout']) }}</span></p>
                                @else
                                    <p class="text-[11px] text-darkbrown-400">Belum masuk settlement.</p>
                                @endif

                                @if ($rental['invoice_number'])
                                    <div class="mt-2 rounded-xl border border-wheat-200 bg-wheat-50/70 px-2.5 py-2">
                                        <p class="font-mono text-[10px] font-bold text-darkbrown-800">{{ $rental['invoice_number'] }}</p>
                                        <p class="mt-0.5 text-[10px] text-darkbrown-500">Invoice · {{ $rental['invoice_status_label'] }}</p>
                                        @if ($rental['invoice_paid_at'])
                                            <p class="mt-0.5 text-[10px] text-avocado-700">Dibayar {{ $rental['invoice_paid_at']->isoFormat('D MMM Y HH:mm') }}</p>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-4 text-right">
                                <div class="flex flex-col items-end gap-2">
                                    @if ($rental['invoice_url'])
                                        <a href="{{ $rental['invoice_url'] }}" target="_blank" rel="noopener" class="btn-tendaku-secondary !px-3 !py-1.5 text-[11px]">
                                            Lihat Invoice
                                        </a>
                                    @endif

                                    @if ($rental['action_url'])
                                        <a href="{{ $rental['action_url'] }}" class="btn-tendaku-primary !px-3 !py-1.5 text-[11px]">
                                            {{ $rental['action_label'] }}
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <p class="text-sm font-bold text-darkbrown-700">Belum ada transaksi pada filter ini.</p>
                                <p class="mt-1 text-xs text-darkbrown-400">Coba ubah tahap, vendor, atau kata pencarian.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rentals->hasPages())
            <div class="border-t border-wheat-200 px-5 py-3">
                {{ $rentals->links() }}
            </div>
        @endif
    </x-card>
</div>
