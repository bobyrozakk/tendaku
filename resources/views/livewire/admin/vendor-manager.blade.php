<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-2xl p-5 border border-wheat-300 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-darkbrown-100 text-darkbrown-800 border border-darkbrown-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-darkbrown-700"></span>
                    Administrasi Tenant
                </span>
                <span class="text-xs text-darkbrown-500">• Verifikasi & Hak Operasional</span>
            </div>
            <h1 class="text-2xl font-black text-darkbrown-900 leading-tight">Manajemen & Approval Vendor</h1>
            <p class="text-xs text-darkbrown-600 mt-0.5">Kelola verifikasi toko baru, perizinan operasional, dan pemantauan status kemitraan.</p>
        </div>

        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold px-3 py-1.5 rounded-xl bg-wheat-100 text-darkbrown-800 border border-wheat-200">
                Total: {{ $vendors->total() }} Toko Terdaftar
            </span>
        </div>
    </div>

    <!-- Feedback Message Alert -->
    @if ($feedbackMessage)
        <div class="p-4 rounded-2xl text-xs font-bold flex items-center justify-between gap-3 shadow-sm border {{ $feedbackType === 'success' ? 'bg-emerald-50 text-emerald-900 border-emerald-200' : ($feedbackType === 'warning' ? 'bg-amber-50 text-amber-900 border-amber-200' : 'bg-blue-50 text-blue-900 border-blue-200') }}">
            <div class="flex items-center gap-2">
                <span>{{ $feedbackType === 'success' ? '✓' : 'ℹ' }}</span>
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-darkbrown-400 hover:text-darkbrown-700 font-bold">✕</button>
        </div>
    @endif

    <!-- Filter Pills & Search Input -->
    <div class="bg-white rounded-2xl p-4 border border-wheat-300 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Status Tabs -->
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="$set('filterStatus', 'all')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $filterStatus === 'all' ? 'bg-darkbrown-800 text-white shadow-sm' : 'bg-wheat-100 text-darkbrown-700 hover:bg-wheat-200' }}">
                <span>Semua Status</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $filterStatus === 'all' ? 'bg-darkbrown-700 text-amber-200' : 'bg-white text-darkbrown-600' }}">
                    {{ $vendors->total() }}
                </span>
            </button>

            <button wire:click="$set('filterStatus', 'pending')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $filterStatus === 'pending' ? 'bg-amber-600 text-white shadow-sm' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200' }}">
                <span>Menunggu Persetujuan</span>
                @if ($pendingCount > 0)
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-amber-800 text-amber-100 font-extrabold animate-pulse">
                        {{ $pendingCount }}
                    </span>
                @endif
            </button>

            <button wire:click="$set('filterStatus', 'active')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $filterStatus === 'active' ? 'bg-emerald-700 text-white shadow-sm' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200' }}">
                <span>Aktif Beroperasi</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $filterStatus === 'active' ? 'bg-emerald-800 text-emerald-100' : 'bg-white text-emerald-800' }}">
                    {{ $activeCount }}
                </span>
            </button>

            <button wire:click="$set('filterStatus', 'suspended')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $filterStatus === 'suspended' ? 'bg-rose-700 text-white shadow-sm' : 'bg-rose-50 text-rose-800 hover:bg-rose-100 border border-rose-200' }}">
                <span>Ditangguhkan</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $filterStatus === 'suspended' ? 'bg-rose-800 text-rose-100' : 'bg-white text-rose-800' }}">
                    {{ $suspendedCount }}
                </span>
            </button>
        </div>

        <!-- Search Input -->
        <div class="relative w-full md:w-72">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama, kota, telepon..."
                   class="w-full text-xs rounded-xl border-wheat-300 bg-wheat-50/50 text-darkbrown-800 focus:border-goldenrod-500 focus:ring-goldenrod-500 pl-9 pr-3 py-2 shadow-sm font-semibold">
            <svg class="w-4 h-4 text-darkbrown-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- Vendor Table -->
    <div class="bg-white rounded-2xl border border-wheat-300 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-wheat-50 border-b border-wheat-200 text-darkbrown-600 uppercase font-bold tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4">Toko & Info</th>
                        <th class="py-3.5 px-4">Kontak & Lokasi</th>
                        <th class="py-3.5 px-4 text-center">Inventori & Sewa</th>
                        <th class="py-3.5 px-4 text-center">Status Operasional</th>
                        <th class="py-3.5 px-4">Tanggal Daftar</th>
                        <th class="py-3.5 px-4 text-right">Aksi Super Admin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200 text-darkbrown-800">
                    @forelse ($vendors as $vendor)
                        <tr class="hover:bg-wheat-50/50 transition">
                            <!-- Kolom Toko -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl font-black text-sm flex items-center justify-center text-white shrink-0 shadow-sm"
                                         style="background: linear-gradient(135deg, #2b2202 0%, #6c8b08 100%);">
                                        {{ strtoupper(substr($vendor->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-extrabold text-darkbrown-950 text-sm leading-tight">{{ $vendor->name }}</div>
                                        <div class="text-[11px] text-darkbrown-500 font-mono mt-0.5">Slug: {{ $vendor->slug }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kontak & Lokasi -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-darkbrown-900">{{ $vendor->city ?: 'Belum diisi' }}</div>
                                <div class="text-[11px] text-darkbrown-600">{{ $vendor->phone }}</div>
                                <div class="text-[11px] text-darkbrown-400 truncate max-w-[200px]" title="{{ $vendor->address }}">{{ $vendor->address }}</div>
                            </td>

                            <!-- Statistik -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="font-bold text-darkbrown-900">{{ $vendor->items_count }} Produk</div>
                                <div class="text-[11px] text-darkbrown-500">{{ $vendor->rentals_count }} Transaksi</div>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if ($vendor->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        Aktif
                                    </span>
                                @elseif ($vendor->status === 'pending')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                        Menunggu Approval
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                        Ditangguhkan
                                    </span>
                                @endif

                                @if ($vendor->approved_at)
                                    <div class="text-[10px] text-darkbrown-400 mt-1">
                                        Disetujui: {{ $vendor->approved_at->format('d/m/Y') }}
                                    </div>
                                @endif
                            </td>

                            <!-- Tanggal Daftar -->
                            <td class="py-3.5 px-4 text-darkbrown-600">
                                <div>{{ $vendor->created_at->format('d M Y') }}</div>
                                <div class="text-[10px] text-darkbrown-400">{{ $vendor->created_at->format('H:i') }} WIB</div>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($vendor->status === 'pending')
                                        <!-- Tombol Approve -->
                                        <button wire:click="confirmAction({{ $vendor->id }}, 'approve')"
                                                class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm"
                                                title="Setujui Toko Vendor">
                                            ✓ Setujui
                                        </button>
                                        <!-- Tombol Tolak / Suspend -->
                                        <button wire:click="confirmAction({{ $vendor->id }}, 'suspend')"
                                                class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white transition"
                                                title="Tolak Pengajuan">
                                            ✕ Tolak
                                        </button>
                                    @elseif ($vendor->status === 'active')
                                        <!-- Tombol Suspend -->
                                        <button wire:click="confirmAction({{ $vendor->id }}, 'suspend')"
                                                class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 hover:bg-amber-200 text-amber-900 border border-amber-300 transition"
                                                title="Tangguhkan Toko">
                                            Tangguhkan
                                        </button>
                                    @elseif ($vendor->status === 'suspended')
                                        <!-- Tombol Re-activate -->
                                        <button wire:click="confirmAction({{ $vendor->id }}, 'approve')"
                                                class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 hover:bg-emerald-200 text-emerald-800 border border-emerald-300 transition"
                                                title="Aktifkan Kembali">
                                            Aktifkan
                                        </button>
                                    @endif

                                    <!-- Tombol Hapus -->
                                    <button wire:click="confirmAction({{ $vendor->id }}, 'delete')"
                                            class="p-1.5 rounded-lg text-darkbrown-400 hover:text-red-600 hover:bg-red-50 transition"
                                            title="Hapus Vendor">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-darkbrown-400">
                                Tidak ada data vendor yang cocok dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($vendors->hasPages())
            <div class="p-4 border-t border-wheat-200 bg-wheat-50/30">
                {{ $vendors->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Konfirmasi Aksi -->
    @if ($vendorToConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-wheat-300 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg font-bold shrink-0 {{ $actionType === 'approve' ? 'bg-emerald-100 text-emerald-800' : ($actionType === 'suspend' ? 'bg-amber-100 text-amber-900' : 'bg-red-100 text-red-800') }}">
                        {{ $actionType === 'approve' ? '✓' : ($actionType === 'suspend' ? '⚠' : '🗑') }}
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-darkbrown-900 leading-tight">
                            {{ $actionType === 'approve' ? 'Konfirmasi Persetujuan Toko' : ($actionType === 'suspend' ? 'Tangguhkan Operasional Toko' : 'Hapus Toko Vendor') }}
                        </h3>
                        <p class="text-xs text-darkbrown-500 mt-0.5">Tindakan ini akan mempengaruhi status akun vendor terkait.</p>
                    </div>
                </div>

                <p class="text-xs text-darkbrown-700 bg-wheat-50 p-3.5 rounded-xl border border-wheat-200 leading-relaxed">
                    @if ($actionType === 'approve')
                        Apakah Anda yakin ingin <strong>menyetujui (Approve)</strong> akun toko ini? Toko akan berstatus aktif dan vendor dapat mulai mengunggah produk serta menerima pesanan pelanggan.
                    @elseif ($actionType === 'suspend')
                        Apakah Anda yakin ingin <strong>menangguhkan (Suspend)</strong> akun toko ini? Produk vendor ini tidak akan muncul di katalog publik dan operasional toko akan dinonaktifkan sementara.
                    @else
                        Apakah Anda yakin ingin <strong>menghapus</strong> data toko vendor ini? Data produk dan unit terkait akan dipindahkan ke arsip sampah (Soft Delete).
                    @endif
                </p>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button wire:click="cancelAction"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 transition">
                        Batal
                    </button>
                    <button wire:click="executeAction"
                            class="px-4 py-2 rounded-xl text-xs font-extrabold text-white transition shadow-sm {{ $actionType === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : ($actionType === 'suspend' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-red-600 hover:bg-red-700') }}">
                        Ya, Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
