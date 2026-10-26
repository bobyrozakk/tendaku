<div id="kelola-vendor" class="scroll-mt-24 space-y-5" wire:poll.15s>
    <!-- Header Section -->
    <div class="card-tendaku p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="mb-1 flex flex-wrap items-center gap-2">
                    <x-badge variant="wheat" :dot="true">Administrasi Tenant</x-badge>
                    <span class="text-xs text-darkbrown-400">Verifikasi &amp; hak operasional</span>
                </div>
                <h2 class="text-lg font-extrabold tracking-tight text-darkbrown-900">Kelola &amp; Approval Vendor</h2>
                <p class="mt-0.5 text-xs text-darkbrown-500">
                    Setujui toko baru, tangguhkan operasional, atau hapus data vendor yang tidak lagi aktif.
                </p>
            </div>

            <x-badge variant="wheat">{{ $totalCount }} Toko Terdaftar</x-badge>
        </div>
    </div>

    <!-- Feedback -->
    @if ($feedbackMessage)
        <x-alert :type="$feedbackType" :title="$feedbackType === 'success' ? 'Berhasil' : ($feedbackType === 'warning' ? 'Perhatian' : ($feedbackType === 'danger' ? 'Gagal' : 'Informasi'))">
            <div class="flex items-start justify-between gap-3">
                <span>{{ $feedbackMessage }}</span>
                <button wire:click="$set('feedbackMessage', null)" class="shrink-0 font-bold text-darkbrown-400 transition hover:text-darkbrown-700" title="Tutup">
                    &times;
                </button>
            </div>
        </x-alert>
    @endif

    <!-- Filter & Search -->
    <div class="card-tendaku p-4">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="$set('filterStatus', 'all')"
                        class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-bold transition {{ $filterStatus === 'all' ? 'bg-darkbrown-800 text-white shadow-sm' : 'bg-wheat-100 text-darkbrown-700 hover:bg-wheat-200' }}">
                    Semua Status
                    <span class="rounded-full px-1.5 text-[10px] {{ $filterStatus === 'all' ? 'bg-darkbrown-700 text-wheat-200' : 'bg-white text-darkbrown-600' }}">
                        {{ $totalCount }}
                    </span>
                </button>

                <button wire:click="$set('filterStatus', 'pending')"
                        class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-bold transition {{ $filterStatus === 'pending' ? 'bg-goldenrod-500 text-white shadow-sm' : 'border border-goldenrod-200 bg-goldenrod-50 text-goldenrod-800 hover:bg-goldenrod-100' }}">
                    Menunggu Persetujuan
                    @if ($pendingCount > 0)
                        <span class="rounded-full bg-goldenrod-700 px-1.5 text-[10px] font-extrabold text-goldenrod-50">
                            {{ $pendingCount }}
                        </span>
                    @endif
                </button>

                <button wire:click="$set('filterStatus', 'active')"
                        class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-bold transition {{ $filterStatus === 'active' ? 'bg-avocado-500 text-white shadow-sm' : 'border border-avocado-200 bg-avocado-50 text-avocado-800 hover:bg-avocado-100' }}">
                    Aktif Beroperasi
                    <span class="rounded-full px-1.5 text-[10px] {{ $filterStatus === 'active' ? 'bg-avocado-600 text-white' : 'bg-white text-avocado-800' }}">
                        {{ $activeCount }}
                    </span>
                </button>

                <button wire:click="$set('filterStatus', 'suspended')"
                        class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-bold transition {{ $filterStatus === 'suspended' ? 'bg-red-600 text-white shadow-sm' : 'border border-red-200 bg-red-50 text-red-800 hover:bg-red-100' }}">
                    Ditangguhkan
                    <span class="rounded-full px-1.5 text-[10px] {{ $filterStatus === 'suspended' ? 'bg-red-700 text-white' : 'bg-white text-red-800' }}">
                        {{ $suspendedCount }}
                    </span>
                </button>
            </div>

            <div class="relative w-full xl:w-72">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama, kota, telepon..."
                       class="input-tendaku pl-9 text-xs font-semibold">
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Vendor Table -->
    <div class="card-tendaku overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-wheat-200 bg-wheat-50 font-bold uppercase tracking-wider text-darkbrown-600">
                    <tr>
                        <th class="px-4 py-3.5">Toko &amp; Info</th>
                        <th class="px-4 py-3.5">Kontak &amp; Lokasi</th>
                        <th class="px-4 py-3.5 text-center">Inventori &amp; Sewa</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5">Tanggal Daftar</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200 text-darkbrown-800">
                    @forelse ($vendors as $vendor)
                        <tr wire:key="vendor-{{ $vendor->id }}" class="transition hover:bg-wheat-50/60">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-avocado text-sm font-black text-white shadow-sm">
                                        {{ strtoupper(substr($vendor->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-extrabold leading-tight text-darkbrown-950">{{ $vendor->name }}</div>
                                        <div class="mt-0.5 font-mono text-[11px] text-darkbrown-500">{{ $vendor->slug }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="font-semibold text-darkbrown-900">{{ $vendor->city ?: 'Belum diisi' }}</div>
                                <div class="text-[11px] text-darkbrown-600">{{ $vendor->phone ?: '—' }}</div>
                                <div class="max-w-[200px] truncate text-[11px] text-darkbrown-400" title="{{ $vendor->address }}">
                                    {{ $vendor->address ?: 'Alamat belum diisi' }}
                                </div>
                            </td>

                            <td class="px-4 py-3.5 text-center">
                                <div class="font-bold text-darkbrown-900">{{ $vendor->items_count }} Produk</div>
                                <div class="text-[11px] text-darkbrown-500">{{ $vendor->rentals_count }} Transaksi</div>
                            </td>

                            <td class="px-4 py-3.5 text-center">
                                @if ($vendor->status === 'active')
                                    <x-badge variant="avocado" size="sm" :dot="true">Aktif</x-badge>
                                @elseif ($vendor->status === 'pending')
                                    <x-badge variant="sunglow" size="sm" :dot="true">Menunggu</x-badge>
                                @else
                                    <x-badge variant="danger" size="sm" :dot="true">Ditangguhkan</x-badge>
                                @endif

                                @if ($vendor->approved_at)
                                    <div class="mt-1 text-[10px] text-darkbrown-400">
                                        Disetujui {{ $vendor->approved_at->format('d/m/Y') }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3.5 text-darkbrown-600">
                                <div>{{ $vendor->created_at->format('d M Y') }}</div>
                                <div class="text-[10px] text-darkbrown-400">{{ $vendor->created_at->format('H:i') }} WIB</div>
                            </td>

                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($vendor->status === 'pending')
                                        <button wire:click="confirmAction({{ $vendor->id }}, 'approve')"
                                                class="btn-tendaku-primary !px-2.5 !py-1 text-xs" title="Setujui Toko">
                                            Setujui
                                        </button>
                                        <button wire:click="confirmAction({{ $vendor->id }}, 'suspend')"
                                                class="btn-tendaku-gold !px-2.5 !py-1 text-xs" title="Tolak Pengajuan">
                                            Tolak
                                        </button>
                                    @elseif ($vendor->status === 'active')
                                        <button wire:click="confirmAction({{ $vendor->id }}, 'suspend')"
                                                class="btn-tendaku-accent !px-2.5 !py-1 text-xs" title="Tangguhkan Toko">
                                            Tangguhkan
                                        </button>
                                    @elseif ($vendor->status === 'suspended')
                                        <button wire:click="confirmAction({{ $vendor->id }}, 'approve')"
                                                class="btn-tendaku-primary !px-2.5 !py-1 text-xs" title="Aktifkan Kembali">
                                            Aktifkan
                                        </button>
                                    @endif

                                    <button wire:click="confirmAction({{ $vendor->id }}, 'delete')"
                                            class="rounded-lg p-1.5 text-darkbrown-400 transition hover:bg-red-50 hover:text-red-600" title="Hapus Vendor">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-darkbrown-400">
                                Tidak ada vendor yang cocok dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($vendors->hasPages())
            <div class="border-t border-wheat-200 bg-wheat-50/40 p-4">
                {{ $vendors->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Konfirmasi -->
    @if ($vendorToConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md space-y-4 rounded-2xl border border-wheat-200 bg-white p-6 shadow-tendaku-lg">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $actionType === 'approve' ? 'bg-avocado-100 text-avocado-700' : ($actionType === 'suspend' ? 'bg-goldenrod-100 text-goldenrod-700' : 'bg-red-100 text-red-700') }}">
                        @if ($actionType === 'approve')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif ($actionType === 'suspend')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold leading-tight text-darkbrown-900">
                            {{ $actionType === 'approve' ? 'Konfirmasi Persetujuan Toko' : ($actionType === 'suspend' ? 'Tangguhkan Operasional Toko' : 'Hapus Toko Vendor') }}
                        </h3>
                        <p class="mt-0.5 text-xs text-darkbrown-500">Tindakan ini mengubah status akun vendor terkait.</p>
                    </div>
                </div>

                <p class="rounded-xl border border-wheat-200 bg-wheat-50 p-3.5 text-xs leading-relaxed text-darkbrown-700">
                    @if ($actionType === 'approve')
                        Setujui akun toko ini? Toko akan berstatus aktif dan vendor dapat mulai mengunggah produk serta menerima pesanan.
                    @elseif ($actionType === 'suspend')
                        Tangguhkan akun toko ini? Produk vendor tidak akan muncul di katalog publik dan operasional toko dinonaktifkan sementara.
                    @else
                        Hapus data toko vendor ini? Data terkait akan dipindahkan ke arsip sampah (soft delete).
                    @endif
                </p>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button wire:click="cancelAction" class="btn-tendaku-secondary !px-4 !py-2 text-xs">
                        Batal
                    </button>
                    <button wire:click="executeAction"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-xs font-extrabold text-white shadow-sm transition {{ $actionType === 'delete' ? 'bg-red-600 hover:bg-red-700' : ($actionType === 'suspend' ? 'bg-goldenrod-500 hover:bg-goldenrod-600' : 'bg-avocado-500 hover:bg-avocado-600') }}">
                        Ya, Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
