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

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card
            title="Menunggu Pembayaran"
            :value="$summary['counts']['awaiting_payment'].' booking'"
            description="Belum ada pembayaran lunas"
            color="goldenrod"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Diproses Rental"
            :value="$summary['counts']['paid_in_progress'].' booking'"
            description="Sudah dibayar, menunggu pickup/return"
            color="sunglow"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Siap Settlement"
            :value="$summary['counts']['ready_settlement'].' booking'"
            :description="'Komisi accrued ' . $formatRupiah($summary['commission_accrued'])"
            color="dark"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Pencairan Vendor (Payout)"
            :value="$summary['counts']['paid_out'].' booking dicairkan'"
            :description="$summary['awaiting_transfer_batches'].' batch antre · '.$summary['paid_out_batches'].' batch cair ('.$formatRupiah($summary['paid_out_amount']).')'"
            color="avocado"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </x-slot>
        </x-stat-card>
    </div>

    <!-- Container Terpadu: Filter + Ringkasan Stage + Tabel -->
    <div class="card-tendaku p-0 overflow-hidden">
        <!-- Header & Toolbar Terpadu -->
        <div class="border-b border-wheat-200 bg-white p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge variant="goldenrod" :dot="true">Live Pipeline</x-badge>
                        <span class="text-xs text-darkbrown-400">Refresh otomatis setiap 30 detik</span>
                    </div>
                    <h2 class="mt-1 text-base font-extrabold text-darkbrown-900 sm:text-lg">Alur Transaksi Customer → Payout Vendor</h2>
                    <p class="text-xs text-darkbrown-500">
                        Pantau status booking tiap langkah dari pembayaran customer hingga pencairan ke rekening vendor.
                    </p>
                </div>

                <!-- Secondary Badges / Quick Metrics -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <x-badge variant="wheat" size="sm">
                        Komisi accrued {{ $formatRupiah($summary['commission_accrued']) }}
                    </x-badge>
                    <x-badge variant="sunglow" size="sm">
                        {{ $summary['counts']['in_settlement'] }} draft settlement
                    </x-badge>
                    <x-badge variant="goldenrod" size="sm">
                        {{ $summary['counts']['awaiting_transfer'] }} menunggu transfer ({{ $formatRupiah($summary['awaiting_transfer_amount']) }})
                    </x-badge>
                    <x-badge variant="avocado" size="sm">
                        {{ $summary['counts']['cancelled'] }} dibatalkan/expired
                    </x-badge>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block sm:col-span-2">
                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Cari Transaksi</span>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari kode booking, nama customer, telepon, email..."
                        class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3.5 py-2 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-goldenrod-200"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Tahap Transaksi</span>
                    <select
                        wire:model.live="stage"
                        class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-goldenrod-200"
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
                        class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-goldenrod-200"
                    >
                        <option value="">Semua Vendor</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <!-- Pipeline Diagram & Reset Button -->
            <div class="mt-3.5 flex flex-wrap items-center justify-between gap-3 border-t border-wheat-100 pt-3">
                <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-semibold text-darkbrown-500">
                    <span class="rounded-lg border border-wheat-200 bg-wheat-50 px-2 py-0.5">1. Booking</span>
                    <span class="text-wheat-400">→</span>
                    <span class="rounded-lg border border-wheat-200 bg-wheat-50 px-2 py-0.5">2. Pembayaran (Escrow)</span>
                    <span class="text-wheat-400">→</span>
                    <span class="rounded-lg border border-wheat-200 bg-wheat-50 px-2 py-0.5">3. Rental Selesai</span>
                    <span class="text-wheat-400">→</span>
                    <span class="rounded-lg border border-wheat-200 bg-wheat-50 px-2 py-0.5">4. Invoice Settlement</span>
                    <span class="text-wheat-400">→</span>
                    <span class="rounded-lg border border-wheat-200 bg-wheat-50 px-2 py-0.5">5. Payout Vendor</span>
                </div>

                <button
                    type="button"
                    wire:click="resetFilters"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-1.5 text-xs font-semibold text-darkbrown-700 transition hover:border-goldenrod-300 hover:bg-goldenrod-50"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.038 8.038 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset Filter
                </button>
            </div>
        </div>

        <!-- Tabel Monitoring Transaksi -->
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
    </div>
</div>
