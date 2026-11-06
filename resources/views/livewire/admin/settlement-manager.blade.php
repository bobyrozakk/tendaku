@php
    $statusVariants = [
        'draft' => 'wheat',
        'approved' => 'sunglow',
        'paid' => 'avocado',
        'cancelled' => 'danger',
    ];

    $formatRupiah = fn (float $value): string => 'Rp '.number_format($value, 0, ',', '.');
@endphp

<div class="space-y-6">
    @if ($flashSuccess || $flashError)
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 4000)"
            x-show="show"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-x-8 scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-x-0 scale-100"
            x-transition:leave-end="opacity-0 translate-x-8 scale-95"
            class="fixed top-5 right-5 z-[9999] flex max-w-sm w-full items-start gap-3 rounded-2xl border p-4 shadow-tendaku-lg backdrop-blur-md {{ $flashSuccess ? 'border-avocado-200 bg-white/95 text-avocado-950' : 'border-red-200 bg-white/95 text-red-950' }}"
            role="alert"
        >
            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $flashSuccess ? 'bg-avocado-100 text-avocado-700' : 'bg-red-100 text-red-700' }}">
                @if ($flashSuccess)
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                @else
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-darkbrown-900">{{ $flashSuccess ? 'Berhasil!' : 'Perhatian' }}</p>
                <p class="mt-0.5 text-xs leading-relaxed text-darkbrown-600">{{ $flashSuccess ?? $flashError }}</p>
            </div>
            <button
                type="button"
                @click="show = false"
                class="shrink-0 rounded-lg p-1 text-darkbrown-400 transition hover:bg-wheat-100 hover:text-darkbrown-700"
                title="Tutup"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- 3 Stat Cards Utama -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card
            title="Draft Menunggu Review"
            :value="$draftCount.' batch'"
            description="Periksa rincian sebelum menyetujui batch"
            color="goldenrod"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Disetujui, Belum Dibayar"
            :value="$approvedCount.' batch'"
            description="Escrow dilepas setelah payout terkonfirmasi"
            color="sunglow"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Payout Bulan Ini"
            :value="$formatRupiah((float) $paidThisMonth)"
            description="Total net payout batch berstatus dibayar"
            color="avocado"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>
    </div>

    <!-- Container Terpadu: Toolbar Filter + Tabel Batch Settlement -->
    <div class="card-tendaku p-0 overflow-hidden">
        <!-- Header & Toolbar Terpadu -->
        <div class="border-b border-wheat-200 bg-white p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-extrabold tracking-tight text-darkbrown-900 sm:text-lg">Daftar Batch Settlement</h2>
                    <p class="mt-0.5 text-xs text-darkbrown-500">
                        Batch dikelompokkan per vendor dan periode Senin–Minggu atau permohonan penarikan dana manual vendor.
                    </p>
                </div>

                <button type="button"
                        wire:click="generateDraft"
                        wire:loading.attr="disabled"
                        wire:target="generateDraft"
                        class="btn-tendaku-secondary inline-flex items-center gap-2 !px-3.5 !py-2 text-xs font-bold">
                    <svg wire:loading.remove wire:target="generateDraft" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <svg wire:loading wire:target="generateDraft" class="h-4 w-4 animate-spin text-goldenrod-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Buat Draft Minggu Lalu</span>
                </button>
            </div>

            <!-- Filter Controls -->
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Status Batch</span>
                    <select wire:model.live="status"
                            class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-goldenrod-200">
                        <option value="">Semua Status</option>
                        <option value="draft">Draft</option>
                        <option value="approved">Disetujui</option>
                        <option value="paid">Dibayar</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Vendor</span>
                    <select wire:model.live="vendorId"
                            class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-goldenrod-200">
                        <option value="">Semua Vendor</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex items-end">
                    <button
                        type="button"
                        wire:click="$set('status', ''); $set('vendorId', null);"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-semibold text-darkbrown-700 transition hover:border-goldenrod-300 hover:bg-goldenrod-50"
                    >
                        Reset Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabel Batch Settlement -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-xs">
                <thead class="border-b border-wheat-200 bg-wheat-50 font-bold uppercase tracking-wider text-darkbrown-600">
                    <tr>
                        <th class="px-5 py-3">Periode</th>
                        <th class="px-5 py-3">Vendor</th>
                        <th class="px-5 py-3 text-right">Bruto</th>
                        <th class="px-5 py-3 text-right">Komisi</th>
                        <th class="px-5 py-3 text-right">Net Payout</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200/70">
                    @forelse ($settlements as $settlement)
                        <tr wire:key="settlement-{{ $settlement->id }}" class="align-top hover:bg-wheat-50/60">
                            <td class="px-5 py-3.5">
                                <p class="font-bold text-darkbrown-900">
                                    {{ $settlement->period_start->isoFormat('D MMM Y') }} – {{ $settlement->period_end->isoFormat('D MMM Y') }}
                                </p>
                                <div class="mt-0.5 flex items-center gap-1.5">
                                    <span class="font-mono text-[10px] text-darkbrown-400">#{{ $settlement->id }}</span>
                                    @if ($settlement->notes && str_contains(strtolower($settlement->notes), 'tarik dana'))
                                        <span class="rounded bg-sunglow-100 px-1 py-0.2 text-[9px] font-bold text-sunglow-900">Tarik Dana Manual</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-darkbrown-800">{{ $settlement->vendor?->name ?? '-' }}</p>
                                <p class="text-[11px] text-darkbrown-400">{{ $settlement->vendor?->city ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-right font-semibold text-darkbrown-800">
                                {{ $formatRupiah((float) $settlement->gross_qris + (float) $settlement->gross_fine_qris) }}
                            </td>
                            <td class="px-5 py-3.5 text-right text-goldenrod-700">
                                {{ $formatRupiah((float) $settlement->commission_total) }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-extrabold {{ (float) $settlement->net_payout < 0 ? 'text-red-600' : 'text-avocado-700' }}">
                                {{ $formatRupiah((float) $settlement->net_payout) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <x-badge :variant="$statusVariants[$settlement->status] ?? 'wheat'" size="sm">
                                    {{ $statusLabels[$settlement->status] ?? $settlement->status }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <button type="button"
                                        wire:click="openSettlement({{ $settlement->id }})"
                                        class="rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-1.5 text-xs font-bold text-darkbrown-700 transition hover:border-goldenrod-300 hover:bg-goldenrod-50 hover:text-darkbrown-900">
                                    Lihat Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <p class="text-sm font-bold text-darkbrown-700">Belum ada batch settlement.</p>
                                <p class="mt-1 text-xs text-darkbrown-400">Jalankan command atau klik “Buat Draft Minggu Lalu” untuk membuat batch pertama.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($settlements->hasPages())
            <div class="border-t border-wheat-200 px-5 py-3">
                {{ $settlements->links() }}
            </div>
        @endif
    </div>

    @if ($selectedSettlement)
        @php
            $detail = $selectedSettlement;
            $invoice = $detail->invoices->first();
        @endphp

        <div class="rounded-2xl border border-avocado-200 bg-white shadow-tendaku-sm">
            <div class="flex flex-col gap-3 border-b border-wheat-200 px-6 py-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge variant="dark" :dot="true">Batch #{{ $detail->id }}</x-badge>
                        <x-badge :variant="$statusVariants[$detail->status] ?? 'wheat'" size="sm">
                            {{ $statusLabels[$detail->status] ?? $detail->status }}
                        </x-badge>
                    </div>
                    <h3 class="mt-2 text-lg font-extrabold text-darkbrown-900">{{ $detail->vendor?->name }}</h3>
                    <p class="text-xs text-darkbrown-500">
                        Periode {{ $detail->period_start->isoFormat('D MMMM Y') }} – {{ $detail->period_end->isoFormat('D MMMM Y') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($invoice)
                        <a href="{{ route('admin.settlements.invoice', $detail) }}"
                           target="_blank"
                           class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                            <i data-lucide="printer" class="h-4 w-4"></i>
                            Cetak Invoice
                        </a>
                    @endif

                    @if ($detail->isDraft())
                        <button type="button"
                                wire:click="approve({{ $detail->id }})"
                                wire:confirm="Setujui batch ini? Pembayaran dan komisi akan dikunci ke batch."
                                class="btn-tendaku-primary !px-3.5 !py-2 text-xs">
                            <i data-lucide="check" class="h-4 w-4"></i>
                            Setujui Batch
                        </button>
                    @elseif ($detail->isApproved())
                        <button type="button"
                                wire:click="openPayModal({{ $detail->id }})"
                                class="btn-tendaku-primary !px-3.5 !py-2 text-xs">
                            <i data-lucide="upload" class="h-4 w-4"></i>
                            Transfer ke Vendor &amp; Upload Bukti
                        </button>
                    @endif

                    <button type="button" wire:click="closeDetail" class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                        Tutup
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 border-b border-wheat-200 px-6 py-5 lg:grid-cols-4">
                <div class="rounded-xl border border-wheat-200 bg-wheat-50/70 px-3 py-2.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Bruto QRIS/Transfer</p>
                    <p class="mt-1 text-sm font-extrabold text-darkbrown-900">{{ $formatRupiah((float) $detail->gross_qris) }}</p>
                </div>
                <div class="rounded-xl border border-wheat-200 bg-wheat-50/70 px-3 py-2.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Denda</p>
                    <p class="mt-1 text-sm font-extrabold text-darkbrown-900">{{ $formatRupiah((float) $detail->gross_fine_qris) }}</p>
                </div>
                <div class="rounded-xl border border-wheat-200 bg-wheat-50/70 px-3 py-2.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Komisi Platform</p>
                    <p class="mt-1 text-sm font-extrabold text-goldenrod-700">{{ $formatRupiah((float) $detail->commission_total) }}</p>
                </div>
                <div class="rounded-xl border border-avocado-200 bg-avocado-50/80 px-3 py-2.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-avocado-700">Net Payout</p>
                    <p class="mt-1 text-sm font-extrabold {{ (float) $detail->net_payout < 0 ? 'text-red-600' : 'text-avocado-800' }}">
                        {{ $formatRupiah((float) $detail->net_payout) }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 px-6 py-5 lg:grid-cols-3">
                <div class="space-y-2 text-xs text-darkbrown-600 lg:col-span-2">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <p><span class="font-semibold text-darkbrown-800">MDR standar (platform):</span> {{ $formatRupiah((float) $detail->gateway_fee_total) }}</p>
                        <p><span class="font-semibold text-darkbrown-800">MDR denda (potong vendor):</span> {{ $formatRupiah((float) $detail->fine_fee_total) }}</p>
                        <p><span class="font-semibold text-darkbrown-800">Komisi tunai (piutang):</span> {{ $formatRupiah((float) $detail->cash_commission_offset) }}</p>
                        <p><span class="font-semibold text-darkbrown-800">Biaya transfer (beban platform):</span> {{ $formatRupiah((float) $detail->transfer_fee) }}</p>
                        <p><span class="font-semibold text-darkbrown-800">Penyesuaian carry-forward:</span> {{ $formatRupiah((float) $detail->adjustment_amount) }}</p>
                        <p><span class="font-semibold text-darkbrown-800">Rekening tujuan:</span> {{ $detail->bank_name ?: '-' }} · {{ $detail->bank_account_number ?: '-' }}</p>
                    </div>

                    @if ($detail->notes)
                        <p class="rounded-xl border border-wheat-200 bg-wheat-50 px-3 py-2"><span class="font-semibold">Catatan:</span> {{ $detail->notes }}</p>
                    @endif
                </div>

                <div class="space-y-2 text-xs">
                    @if ($detail->approved_at)
                        <p class="text-darkbrown-600"><span class="font-semibold">Disetujui:</span> {{ $detail->approved_at->isoFormat('D MMM Y HH:mm') }}</p>
                    @endif
                    @if ($detail->paid_at)
                        <p class="text-darkbrown-600"><span class="font-semibold">Dibayar:</span> {{ $detail->paid_at->isoFormat('D MMM Y HH:mm') }}</p>
                    @endif
                    @if ($detail->reference_number)
                        <p class="text-darkbrown-600"><span class="font-semibold">Referensi:</span> {{ $detail->reference_number }}</p>
                    @endif
                    @if ($detail->proof_url)
                        <a href="{{ $detail->proof_url }}" target="_blank" class="inline-flex font-bold text-avocado-700 underline">
                            Lihat bukti transfer
                        </a>
                    @endif
                    @if ($invoice)
                        @php
                            $invoiceStatusLabel = match ($invoice->status) {
                                'issued' => 'Menunggu Transfer',
                                'paid' => 'Lunas',
                                'void' => 'Void',
                                default => ucfirst($invoice->status),
                            };
                            $invoiceStatusVariant = match ($invoice->status) {
                                'paid' => 'avocado',
                                'issued' => 'goldenrod',
                                'void' => 'danger',
                                default => 'wheat',
                            };
                        @endphp
                        <div class="rounded-xl border border-wheat-200 bg-wheat-50/70 px-3 py-2">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Invoice Vendor</p>
                            <p class="mt-1 font-mono text-[11px] font-bold text-darkbrown-800">{{ $invoice->number }}</p>
                            <div class="mt-1">
                                <x-badge :variant="$invoiceStatusVariant" size="sm">{{ $invoiceStatusLabel }}</x-badge>
                            </div>
                            <p class="mt-1 text-[10px] text-darkbrown-500">
                                Terbit {{ $invoice->issued_at?->isoFormat('D MMM Y HH:mm') ?? '-' }}
                            </p>
                            @if ($invoice->paid_at)
                                <p class="text-[10px] text-avocado-700">Lunas {{ $invoice->paid_at->isoFormat('D MMM Y HH:mm') }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="border-t border-wheat-200 px-6 py-5">
                <h4 class="mb-3 text-sm font-extrabold text-darkbrown-900">Rincian Item Batch</h4>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-xs">
                        <thead class="border-y border-wheat-200 bg-wheat-100/60 text-[10px] font-bold uppercase tracking-wider text-darkbrown-600">
                            <tr>
                                <th class="px-3 py-2.5">Tipe</th>
                                <th class="px-3 py-2.5">Kode Booking</th>
                                <th class="px-3 py-2.5 text-right">Bruto</th>
                                <th class="px-3 py-2.5 text-right">Komisi</th>
                                <th class="px-3 py-2.5 text-right">Fee</th>
                                <th class="px-3 py-2.5 text-right">Net</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-wheat-200/70">
                            @foreach ($detail->items as $item)
                                <tr wire:key="item-{{ $item->id }}">
                                    <td class="px-3 py-2.5 font-semibold text-darkbrown-800">{{ $itemTypeLabels[$item->type] ?? $item->type }}</td>
                                    <td class="px-3 py-2.5 font-mono text-darkbrown-600">{{ $item->payment?->rental?->booking_code ?? '-' }}</td>
                                    <td class="px-3 py-2.5 text-right">{{ $formatRupiah((float) $item->gross_amount) }}</td>
                                    <td class="px-3 py-2.5 text-right text-goldenrod-700">{{ $formatRupiah((float) $item->commission_amount) }}</td>
                                    <td class="px-3 py-2.5 text-right">{{ $formatRupiah((float) $item->fee_amount) }}</td>
                                    <td class="px-3 py-2.5 text-right font-bold {{ (float) $item->net_amount < 0 ? 'text-red-600' : 'text-avocado-700' }}">
                                        {{ $formatRupiah((float) $item->net_amount) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if ($showPayModal && $selectedSettlement)
        <div x-data
             @keydown.escape.window="$wire.closePayModal()"
             @click.self="$wire.closePayModal()"
             class="fixed inset-0 z-50 flex items-center justify-center bg-darkbrown-900/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-2xl border border-wheat-200 bg-white shadow-tendaku">
                <div class="border-b border-wheat-200 px-6 py-4">
                    <h3 class="text-lg font-extrabold text-darkbrown-900">Catat Pembayaran Batch</h3>
                    <p class="mt-1 text-xs text-darkbrown-500">
                        Batch #{{ $selectedSettlement->id }} · {{ $selectedSettlement->vendor?->name }} ·
                        {{ $formatRupiah((float) $selectedSettlement->net_payout) }}
                    </p>
                </div>

                <form wire:submit="markPaid" class="space-y-4 px-6 py-5">
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-darkbrown-700">Nomor Referensi Transfer <span class="text-red-500">*</span></span>
                        <input type="text" wire:model="referenceNumber"
                               class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2.5 text-sm text-darkbrown-800 focus:border-avocado-400 focus:outline-none focus:ring-2 focus:ring-avocado-200"
                               placeholder="Contoh: BCA-20261026-001">
                        @error('referenceNumber') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-darkbrown-700">Bukti Transfer <span class="text-red-500">*</span></span>
                        <input type="file" wire:model="proof" accept=".jpg,.jpeg,.png,.pdf"
                               class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2.5 text-sm text-darkbrown-700 file:mr-3 file:rounded-lg file:border-0 file:bg-avocado-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white">
                        <span class="mt-1 block text-[11px] text-darkbrown-400">JPG, PNG, atau PDF maksimal 5 MB.</span>
                        <div wire:loading wire:target="proof" class="mt-1 text-[11px] font-semibold text-avocado-700">Mengunggah file...</div>
                        @error('proof') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-darkbrown-700">Catatan (opsional)</span>
                        <textarea wire:model="paymentNotes" rows="3"
                                  class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2.5 text-sm text-darkbrown-800 focus:border-avocado-400 focus:outline-none focus:ring-2 focus:ring-avocado-200"
                                  placeholder="Catatan internal pencairan..."></textarea>
                        @error('paymentNotes') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <div class="flex items-center justify-end gap-2 border-t border-wheat-200 pt-4">
                        <button type="button" wire:click="closePayModal" class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="markPaid,proof"
                                class="btn-tendaku-primary !px-3.5 !py-2 text-xs">
                            <span wire:loading.remove wire:target="markPaid">Simpan &amp; Tandai Lunas</span>
                            <span wire:loading wire:target="markPaid" class="inline-flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>