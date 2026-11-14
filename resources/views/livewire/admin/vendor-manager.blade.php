<div id="kelola-vendor" class="scroll-mt-24 card-tendaku overflow-hidden" wire:poll.15s>
    <!-- Header, Filter, & Pencarian -->
    <div class="border-b border-wheat-200 bg-white p-4 sm:p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-avocado-100 text-avocado-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </span>
                <h2 class="text-lg font-extrabold tracking-tight text-darkbrown-900">Kelola &amp; Approval Vendor</h2>
            </div>

            <div class="w-full xl:w-72">
                <label for="vendor-search" class="sr-only">Cari vendor</label>
                <div class="relative">
                    <input id="vendor-search" type="search" wire:model.live.debounce.300ms="search"
                           placeholder="Cari nama, kota, atau telepon"
                           aria-describedby="vendor-search-hint"
                           class="input-tendaku pl-9 pr-3 text-xs font-semibold">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-darkbrown-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <p id="vendor-search-hint" class="mt-1 text-[11px] text-darkbrown-500" aria-live="polite">
                    {{ $vendors->total() }} vendor
                </p>
            </div>
        </div>

        <!-- Filter Status -->
        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-wheat-100 pt-3" role="group" aria-label="Filter status vendor">
            <button type="button" wire:click="$set('filterStatus', 'all')"
                    aria-pressed="{{ $filterStatus === 'all' ? 'true' : 'false' }}"
                    aria-label="Tampilkan semua vendor"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 {{ $filterStatus === 'all' ? 'bg-darkbrown-800 text-white shadow-sm' : 'bg-wheat-100 text-darkbrown-700 hover:bg-wheat-200' }}">
                Semua
                <span class="rounded-full px-1.5 text-[10px] {{ $filterStatus === 'all' ? 'bg-darkbrown-700 text-wheat-200' : 'bg-white text-darkbrown-600' }}">
                    {{ $totalCount }}
                </span>
            </button>

            <button type="button" wire:click="$set('filterStatus', 'pending')"
                    aria-pressed="{{ $filterStatus === 'pending' ? 'true' : 'false' }}"
                    aria-label="Tampilkan vendor yang menunggu persetujuan"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-goldenrod-500 focus-visible:ring-offset-2 {{ $filterStatus === 'pending' ? 'bg-goldenrod-500 text-white shadow-sm' : 'border border-goldenrod-200 bg-goldenrod-50 text-goldenrod-800 hover:bg-goldenrod-100' }}">
                Pending
                @if ($pendingCount > 0)
                    <span class="rounded-full bg-goldenrod-700 px-1.5 text-[10px] font-extrabold text-goldenrod-50">
                        {{ $pendingCount }}
                    </span>
                @endif
            </button>

            <button type="button" wire:click="$set('filterStatus', 'active')"
                    aria-pressed="{{ $filterStatus === 'active' ? 'true' : 'false' }}"
                    aria-label="Tampilkan vendor aktif"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 {{ $filterStatus === 'active' ? 'bg-avocado-500 text-white shadow-sm' : 'border border-avocado-200 bg-avocado-50 text-avocado-800 hover:bg-avocado-100' }}">
                Aktif
                <span class="rounded-full px-1.5 text-[10px] {{ $filterStatus === 'active' ? 'bg-avocado-600 text-white' : 'bg-white text-avocado-800' }}">
                    {{ $activeCount }}
                </span>
            </button>

            <button type="button" wire:click="$set('filterStatus', 'suspended')"
                    aria-pressed="{{ $filterStatus === 'suspended' ? 'true' : 'false' }}"
                    aria-label="Tampilkan vendor yang ditangguhkan"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 {{ $filterStatus === 'suspended' ? 'bg-red-600 text-white shadow-sm' : 'border border-red-200 bg-red-50 text-red-800 hover:bg-red-100' }}">
                Ditangguhkan
                <span class="rounded-full px-1.5 text-[10px] {{ $filterStatus === 'suspended' ? 'bg-red-700 text-white' : 'bg-white text-red-800' }}">
                    {{ $suspendedCount }}
                </span>
            </button>
        </div>
    </div>

    <!-- Vendor Table -->
    <div>
        <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabel vendor, geser horizontal bila diperlukan">
            <table class="w-full text-left text-xs {{ $vendors->isEmpty() ? '' : 'min-w-[720px]' }}">
                <caption class="sr-only">Daftar vendor Tendaku beserta status, inventori, dan aksi pengelolaan</caption>
                <thead class="border-b border-wheat-200 bg-wheat-50 font-bold uppercase tracking-wider text-darkbrown-600">
                    <tr>
                        <th scope="col" class="px-4 py-3.5">Toko</th>
                        <th scope="col" class="px-4 py-3.5">Kontak</th>
                        <th scope="col" class="px-4 py-3.5 text-center">Produk &amp; Sewa</th>
                        <th scope="col" class="px-4 py-3.5 text-center">Status</th>
                        <th scope="col" class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200 text-darkbrown-800">
                    @forelse ($vendors as $vendor)
                        <tr wire:key="vendor-{{ $vendor->id }}"
                            class="transition hover:bg-wheat-50/60 {{ $vendor->status === 'pending' ? 'bg-goldenrod-50/40' : '' }}">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-black shadow-sm {{ $vendor->status === 'active' ? 'bg-gradient-avocado text-white' : ($vendor->status === 'pending' ? 'bg-gradient-gold text-darkbrown-900' : 'bg-darkbrown-700 text-wheat-200') }}">
                                        {{ strtoupper(substr($vendor->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="max-w-[220px] truncate text-sm font-extrabold leading-tight text-darkbrown-950" title="{{ $vendor->name }}">{{ $vendor->name }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="font-semibold text-darkbrown-900">{{ $vendor->city ?: 'Belum diisi' }}</div>
                                <div class="text-[11px] text-darkbrown-600">{{ $vendor->phone ?: '—' }}</div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3.5 text-center">
                                <div class="font-bold tabular-nums text-darkbrown-900">{{ $vendor->items_count }} produk</div>
                                <div class="text-[11px] tabular-nums text-darkbrown-500">{{ $vendor->rentals_count }} sewa</div>
                            </td>

                            <td class="px-4 py-3.5 text-center">
                                @if ($vendor->status === 'active')
                                    <x-badge variant="avocado" size="sm" :dot="true">Aktif</x-badge>
                                @elseif ($vendor->status === 'pending')
                                    <x-badge variant="sunglow" size="sm" :dot="true">Pending</x-badge>
                                @else
                                    <x-badge variant="danger" size="sm" :dot="true">Ditangguhkan</x-badge>
                                @endif
                            </td>

                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($vendor->status === 'pending')
                                        <button type="button" wire:click="confirmAction({{ $vendor->id }}, 'approve')"
                                                class="btn-tendaku-primary !px-2.5 !py-1 text-xs"
                                                aria-label="Setujui vendor {{ $vendor->name }}"
                                                title="Setujui Toko">
                                            Setujui
                                        </button>
                                        <button type="button" wire:click="confirmAction({{ $vendor->id }}, 'suspend')"
                                                class="btn-tendaku-gold !px-2.5 !py-1 text-xs"
                                                aria-label="Tolak pengajuan vendor {{ $vendor->name }}"
                                                title="Tolak Pengajuan">
                                            Tolak
                                        </button>
                                    @elseif ($vendor->status === 'active')
                                        <button type="button" wire:click="confirmAction({{ $vendor->id }}, 'suspend')"
                                                class="btn-tendaku-accent !px-2.5 !py-1 text-xs"
                                                aria-label="Tangguhkan vendor {{ $vendor->name }}"
                                                title="Tangguhkan Toko">
                                            Tangguhkan
                                        </button>
                                    @elseif ($vendor->status === 'suspended')
                                        <button type="button" wire:click="confirmAction({{ $vendor->id }}, 'approve')"
                                                class="btn-tendaku-primary !px-2.5 !py-1 text-xs"
                                                aria-label="Aktifkan kembali vendor {{ $vendor->name }}"
                                                title="Aktifkan Kembali">
                                            Aktifkan
                                        </button>
                                    @endif

                                    <button type="button" wire:click="confirmAction({{ $vendor->id }}, 'delete')"
                                            class="rounded-lg p-1.5 text-darkbrown-500 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                                            aria-label="Hapus vendor {{ $vendor->name }}"
                                            title="Hapus Vendor">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center">
                                <p class="text-sm font-bold text-darkbrown-700">Tidak ada vendor.</p>
                                <button type="button"
                                        wire:click="$set('search', ''); $set('filterStatus', 'all')"
                                        class="btn-tendaku-secondary mt-3 !px-3.5 !py-2 text-xs">
                                    Reset filter
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($vendors->isNotEmpty())
            <p class="border-t border-wheat-100 px-4 py-2 text-[10px] font-medium text-darkbrown-500 sm:hidden">
                Geser untuk melihat aksi.
            </p>
        @endif

        @if ($vendors->hasPages())
            <div class="border-t border-wheat-200 bg-wheat-50/40 p-4">
                {{ $vendors->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Konfirmasi -->
    @if ($vendorToConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-darkbrown-950/70 p-4 backdrop-blur-sm"
             x-data
             x-init="$nextTick(() => $refs.cancelButton && $refs.cancelButton.focus())"
             @keydown.escape.window="$wire.cancelAction()"
             @click.self="$wire.cancelAction()"
             role="dialog"
             aria-modal="true"
             aria-labelledby="vendor-confirm-title"
             aria-describedby="vendor-confirm-description">
            <div class="w-full max-w-md space-y-4 rounded-2xl border border-wheat-200 bg-white p-5 shadow-tendaku-lg">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $actionType === 'approve' ? 'bg-avocado-100 text-avocado-700' : ($actionType === 'suspend' ? 'bg-goldenrod-100 text-goldenrod-700' : 'bg-red-100 text-red-700') }}">
                        @if ($actionType === 'approve')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif ($actionType === 'suspend')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        @endif
                    </div>
                    <h3 id="vendor-confirm-title" class="text-base font-extrabold leading-tight text-darkbrown-900">
                        {{ $actionType === 'approve' ? 'Setujui vendor?' : ($actionType === 'suspend' ? 'Tangguhkan vendor?' : 'Hapus vendor?') }}
                    </h3>
                </div>

                <p id="vendor-confirm-description" class="rounded-xl border border-wheat-200 bg-wheat-50 p-3.5 text-xs leading-relaxed text-darkbrown-700">
                    @if ($actionType === 'approve')
                        Toko akan aktif dan dapat menerima pesanan.
                    @elseif ($actionType === 'suspend')
                        Toko akan disembunyikan dari katalog publik.
                    @else
                        Data toko akan dipindahkan ke arsip.
                    @endif
                </p>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button" wire:click="cancelAction" x-ref="cancelButton" class="btn-tendaku-secondary !px-4 !py-2 text-xs">
                        Batal
                    </button>
                    <button type="button" wire:click="executeAction"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-xs font-extrabold text-white shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 {{ $actionType === 'delete' ? 'bg-red-600 hover:bg-red-700 focus-visible:ring-red-500' : ($actionType === 'suspend' ? 'bg-goldenrod-500 hover:bg-goldenrod-600 focus-visible:ring-goldenrod-500' : 'bg-avocado-500 hover:bg-avocado-600 focus-visible:ring-avocado-500') }}">
                        Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
