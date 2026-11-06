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

    <!-- Container Terpadu: Filter + Tabel (Sleek, Simple & Clean) -->
    <div class="card-tendaku p-0 overflow-hidden">
        <!-- Header & Toolbar Minimalis -->
        <div class="border-b border-wheat-200 bg-white p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-extrabold tracking-tight text-darkbrown-900 sm:text-lg">Alur Transaksi Customer</h2>
                    <p class="text-xs text-darkbrown-500">
                        Status realtime booking, pembayaran escrow, komisi platform, dan settlement vendor.
                    </p>
                </div>

                @if ($search || $stage || $vendorId)
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="inline-flex items-center gap-1.5 self-start sm:self-auto rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-1.5 text-xs font-semibold text-darkbrown-700 transition hover:border-goldenrod-300 hover:bg-goldenrod-50"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.038 8.038 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset Filter
                    </button>
                @endif
            </div>

            <!-- Filter Controls Ramping -->
            <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2">
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-darkbrown-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari kode booking, nama customer, telepon, email..."
                            class="w-full rounded-xl border border-wheat-300 bg-wheat-50 py-2 pl-9 pr-3 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-goldenrod-200"
                        >
                    </div>
                </div>

                <div>
                    <select
                        wire:model.live="stage"
                        class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-goldenrod-200"
                    >
                        <option value="">Semua Tahap</option>
                        @foreach ($stageLabels as $stageKey => $stageLabel)
                            <option value="{{ $stageKey }}">{{ $stageLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select
                        wire:model.live="vendorId"
                        class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-goldenrod-200"
                    >
                        <option value="">Semua Vendor</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabel Monitoring Transaksi (Simple & Scannable) -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-xs">
                <thead class="border-b border-wheat-200 bg-wheat-50 font-bold uppercase tracking-wider text-darkbrown-600">
                    <tr>
                        <th class="px-4 py-3">Booking &amp; Vendor</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Pembayaran</th>
                        <th class="px-4 py-3">Komisi Platform</th>
                        <th class="px-4 py-3">Tahap Transaksi</th>
                        <th class="px-4 py-3">Settlement / Invoice</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200 text-darkbrown-800">
                    @forelse ($rentals as $rental)
                        <tr wire:key="monitor-rental-{{ $rental['rental_id'] }}" class="transition hover:bg-wheat-50/50">
                            <td class="px-4 py-3.5">
                                <span class="font-mono text-xs font-bold text-darkbrown-900">{{ $rental['booking_code'] }}</span>
                                <p class="mt-0.5 text-[11px] font-medium text-darkbrown-600">{{ $rental['vendor_name'] }}</p>
                                <p class="text-[10px] text-darkbrown-400">{{ $rental['booking_created_at']?->isoFormat('D MMM Y HH:mm') ?? '—' }}</p>
                            </td>

                            <td class="px-4 py-3.5">
                                <p class="font-bold text-darkbrown-900">{{ $rental['customer_name'] }}</p>
                                @if ($rental['customer_contact'])
                                    <p class="text-[11px] text-darkbrown-500">{{ $rental['customer_contact'] }}</p>
                                @endif
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-extrabold text-darkbrown-900">
                                        {{ $formatRupiah($rental['paid_total'] > 0 ? $rental['paid_total'] : (float) ($rental['payment_amount'] ?? $rental['grand_total'])) }}
                                    </span>
                                    <x-badge
                                        :variant="in_array($rental['payment_state'], ['paid'], true) ? 'avocado' : (in_array($rental['payment_state'], ['expired', 'failed', 'cancelled'], true) ? 'danger' : 'goldenrod')"
                                        size="sm"
                                        :dot="true"
                                    >
                                        {{ $rental['payment_label'] }}
                                    </x-badge>
                                </div>
                                <p class="mt-0.5 text-[10px] text-darkbrown-400">
                                    {{ $rental['payment_type_label'] }} · {{ $rental['payment_method_label'] }}
                                    @if ($rental['payment_channel'])
                                        · {{ strtoupper($rental['payment_channel']) }}
                                    @endif
                                </p>
                            </td>

                            <td class="px-4 py-3.5">
                                <span class="font-bold text-avocado-800">{{ $formatRupiah($rental['commission_amount']) }}</span>
                                <div class="mt-0.5">
                                    <x-badge :variant="$commissionVariants[$rental['commission_status']] ?? 'wheat'" size="sm">
                                        {{ $rental['commission_status_label'] }}
                                    </x-badge>
                                </div>
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-1.5">
                                    <x-badge :variant="$stageVariants[$rental['stage']] ?? 'wheat'" size="sm" :dot="true">
                                        {{ $rental['stage_label'] }}
                                    </x-badge>
                                    <span class="text-[11px] font-medium text-darkbrown-500">({{ $rental['rental_status_label'] }})</span>
                                </div>
                                <div class="mt-1.5 flex items-center gap-1">
                                    @foreach ($rental['steps'] as $step)
                                        <span
                                            class="h-1.5 w-1.5 rounded-full {{ $step['done'] ? 'bg-avocado-500' : 'bg-wheat-300' }}"
                                            title="{{ $step['label'] }}{{ $step['done'] && $step['at'] ? ' · '.$step['at']->isoFormat('D MMM Y HH:mm') : ' · belum' }}"
                                        ></span>
                                        @if (! $loop->last)
                                            <span class="h-px w-2 bg-wheat-200"></span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>

                            <td class="px-4 py-3.5">
                                @if ($rental['settlement_id'] || $rental['invoice_number'])
                                    @if ($rental['settlement_id'])
                                        <p class="font-semibold text-darkbrown-800">
                                            Batch #{{ $rental['settlement_id'] }}
                                            <span class="text-[10px] text-darkbrown-400">({{ $rental['settlement_status_label'] }})</span>
                                        </p>
                                    @endif
                                    @if ($rental['invoice_number'])
                                        <span class="font-mono text-[10px] font-bold text-darkbrown-700">{{ $rental['invoice_number'] }}</span>
                                    @endif
                                @else
                                    <span class="text-darkbrown-300">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($rental['invoice_url'])
                                        <a href="{{ $rental['invoice_url'] }}" target="_blank" rel="noopener" class="btn-tendaku-secondary !px-2.5 !py-1 text-[11px]">
                                            Invoice
                                        </a>
                                    @endif

                                    @if ($rental['action_url'])
                                        <a href="{{ $rental['action_url'] }}" class="btn-tendaku-primary !px-2.5 !py-1 text-[11px]">
                                            {{ $rental['action_label'] }}
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
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
