<div class="space-y-6">

    <!-- Top Filter & Action Bar -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-wheat-200 shadow-tendaku-sm space-y-4">
        <!-- Segmented Tab Pills: Status Filter -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
            <button wire:click="$set('statusFilter', 'all')"
                class="flex items-center gap-2 py-2 px-4 rounded-xl text-xs transition duration-200 shrink-0 font-bold
                {{ $statusFilter === 'all' ? 'bg-darkbrown-800 text-white shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                <span>Semua Kasir</span>
            </button>

            <button wire:click="$set('statusFilter', 'active')"
                class="flex items-center gap-2 py-2 px-4 rounded-xl text-xs transition duration-200 shrink-0 font-bold
                {{ $statusFilter === 'active' ? 'bg-avocado-600 text-white shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                <span>Aktif</span>
            </button>

            <button wire:click="$set('statusFilter', 'inactive')"
                class="flex items-center gap-2 py-2 px-4 rounded-xl text-xs transition duration-200 shrink-0 font-bold
                {{ $statusFilter === 'inactive' ? 'bg-red-600 text-white shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                <span>Nonaktif</span>
            </button>
        </div>

        <!-- Filter Row: Search & Tambah Kasir Button -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="Cari nama, email, nomor telepon..."
                       class="w-full pl-9 pr-4 py-2 text-xs bg-wheat-50 border border-wheat-300 rounded-xl focus:ring-2 focus:ring-avocado-500/20 focus:border-avocado-500 text-darkbrown-900 placeholder:text-darkbrown-400 font-medium transition" />
            </div>

            <!-- Tambah Kasir Button -->
            <button wire:click="openCreateModal"
                    type="button"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 transition shadow-sm shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Kasir Baru</span>
            </button>
        </div>
    </div>

    <!-- Cashier List Table -->
    <div class="bg-white rounded-2xl border border-wheat-200 shadow-tendaku-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-wheat-50 border-b border-wheat-200 text-darkbrown-500 uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-5 py-3.5">Kasir / Nama</th>
                        <th class="px-5 py-3.5">Kontak</th>
                        <th class="px-5 py-3.5">Peran</th>
                        <th class="px-5 py-3.5 text-center">Status Akun</th>
                        <th class="px-5 py-3.5">Tanggal Dibuat</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-100 text-darkbrown-800 font-medium">
                    @forelse ($cashiers as $cashier)
                        <tr class="hover:bg-wheat-50/50 transition">
                            <!-- Kasir / Nama -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl font-bold flex items-center justify-center text-xs text-white shrink-0"
                                         style="background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%);">
                                        {{ strtoupper(substr($cashier->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-sm text-darkbrown-900">{{ $cashier->name }}</p>
                                        <p class="text-[11px] text-darkbrown-500">{{ $cashier->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Kontak -->
                            <td class="px-5 py-4 text-darkbrown-700">
                                <span>{{ $cashier->phone ?: '-' }}</span>
                            </td>

                            <!-- Peran -->
                            <td class="px-5 py-4 text-darkbrown-700">
                                <span>Kasir POS</span>
                            </td>

                            <!-- Status Akun (Toggle) -->
                            <td class="px-5 py-4 text-center">
                                <button wire:click="toggleStatus({{ $cashier->id }})"
                                        type="button"
                                        title="Klik untuk ubah status kasir"
                                        class="text-xs font-bold transition duration-150 hover:underline {{ $cashier->is_active ? 'text-avocado-700' : 'text-red-600' }}">
                                    {{ $cashier->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>

                            <!-- Tanggal Dibuat -->
                            <td class="px-5 py-4 text-darkbrown-600">
                                <div>{{ $cashier->created_at ? $cashier->created_at->format('d M Y') : '-' }}</div>
                                <div class="text-[10px] text-darkbrown-400">{{ $cashier->created_at ? $cashier->created_at->format('H:i') : '' }} WIB</div>
                            </td>

                            <!-- Aksi -->
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <!-- Edit Button -->
                                    <button wire:click="openEditModal({{ $cashier->id }})"
                                            type="button"
                                            title="Edit Profil Kasir"
                                            class="p-2 rounded-xl text-darkbrown-600 hover:text-darkbrown-900 hover:bg-wheat-100 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    <!-- Toggle Status Button -->
                                    <button wire:click="toggleStatus({{ $cashier->id }})"
                                            type="button"
                                            title="{{ $cashier->is_active ? 'Nonaktifkan Kasir' : 'Aktifkan Kasir' }}"
                                            class="p-2 rounded-xl {{ $cashier->is_active ? 'text-avocado-600 hover:bg-avocado-50' : 'text-darkbrown-400 hover:bg-wheat-100' }} transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </button>

                                    <!-- Delete Button -->
                                    <button wire:click="confirmDelete({{ $cashier->id }})"
                                            type="button"
                                            title="Hapus Kasir"
                                            class="p-2 rounded-xl text-red-500 hover:text-red-700 hover:bg-red-50 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="max-w-sm mx-auto space-y-3">
                                    <div class="w-12 h-12 mx-auto rounded-2xl bg-wheat-100 flex items-center justify-center text-darkbrown-400">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-darkbrown-900">Belum Ada Akun Kasir</h3>
                                    <p class="text-xs text-darkbrown-500">
                                        {{ filled($search) || $statusFilter !== 'all'
                                            ? 'Tidak ada akun kasir yang cocok dengan filter atau kata kunci pencarian.'
                                            : 'Buat akun kasir untuk memberikan staf toko Anda akses transaksi POS secara mandiri.' }}
                                    </p>
                                    @if (!filled($search) && $statusFilter === 'all')
                                        <button wire:click="openCreateModal"
                                                type="button"
                                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-avocado-600 hover:bg-avocado-700 transition shadow-sm mt-2">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span>Tambah Kasir Sekarang</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($cashiers->hasPages())
            <div class="p-4 border-t border-wheat-200">
                {{ $cashiers->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form Tambah Kasir (Sama Persis dengan Form Registrasi Customer/Owner) -->
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
                 wire:click="closeModals"></div>

            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-wheat-200 space-y-6 z-10 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="flex items-start justify-between gap-4 pb-3 border-b border-wheat-200">
                        <div>
                            <h2 class="text-xl font-extrabold text-darkbrown-900 tracking-tight">Tambah Kasir Toko</h2>
                            <p class="text-xs text-darkbrown-500 mt-1">
                                Daftarkan akun staf kasir baru untuk toko Anda. Kasir akan otomatis terikat ke toko ini.
                            </p>
                        </div>
                        <button wire:click="closeModals"
                                type="button"
                                class="p-1 rounded-full text-darkbrown-400 hover:text-darkbrown-900 hover:bg-wheat-100 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Form Tambah Kasir Menggunakan Komponen x-auth.field -->
                    <form wire:submit="createCashier" class="space-y-4">
                        <x-auth.field name="name"
                                      label="Nama Lengkap Kasir"
                                      wire:model="name"
                                      placeholder="Nama lengkap kasir"
                                      autocomplete="name"
                                      required
                                      autofocus />

                        <x-auth.field name="email"
                                      label="Email Kasir"
                                      type="email"
                                      wire:model="email"
                                      placeholder="kasir@toko.com"
                                      autocomplete="username"
                                      required />

                        <x-auth.field name="phone"
                                      label="Nomor Telepon / WhatsApp"
                                      type="tel"
                                      wire:model="phone"
                                      placeholder="08xxxxxxxxxx"
                                      pattern="08[0-9]{8,11}"
                                      maxlength="13"
                                      autocomplete="tel"
                                      hint="Format: diawali 08 (10-13 digit)"
                                      required />

                        <x-auth.field name="password"
                                      label="Password"
                                      type="password"
                                      wire:model="password"
                                      placeholder="Minimal 8 karakter"
                                      autocomplete="new-password"
                                      required />

                        <x-auth.field name="password_confirmation"
                                      label="Konfirmasi Password"
                                      type="password"
                                      wire:model="password_confirmation"
                                      placeholder="Ulangi password"
                                      autocomplete="new-password"
                                      required />

                        <div class="pt-3">
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center rounded-full bg-tendaku-avocado px-6 py-3.5 text-base font-semibold text-white shadow-[0_4px_0_0_#566F06] transition hover:brightness-105 active:translate-y-[2px] active:shadow-none focus:outline-none focus-visible:ring-2 focus-visible:ring-tendaku-brown">
                                <span wire:loading.remove wire:target="createCashier">Buat Akun Kasir</span>
                                <span wire:loading wire:target="createCashier" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Menyimpan...</span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Form Edit Kasir -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
                 wire:click="closeModals"></div>

            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-wheat-200 space-y-6 z-10 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="flex items-start justify-between gap-4 pb-3 border-b border-wheat-200">
                        <div>
                            <h2 class="text-xl font-extrabold text-darkbrown-900 tracking-tight">Edit Akun Kasir</h2>
                            <p class="text-xs text-darkbrown-500 mt-1">
                                Perbarui informasi profil kasir atau reset kata sandi staf.
                            </p>
                        </div>
                        <button wire:click="closeModals"
                                type="button"
                                class="p-1 rounded-full text-darkbrown-400 hover:text-darkbrown-900 hover:bg-wheat-100 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Form Edit Kasir -->
                    <form wire:submit="updateCashier" class="space-y-4">
                        <x-auth.field name="name"
                                      label="Nama Lengkap Kasir"
                                      wire:model="name"
                                      placeholder="Nama lengkap kasir"
                                      autocomplete="name"
                                      required />

                        <x-auth.field name="email"
                                      label="Email Kasir"
                                      type="email"
                                      wire:model="email"
                                      placeholder="kasir@toko.com"
                                      autocomplete="username"
                                      required />

                        <x-auth.field name="phone"
                                      label="Nomor Telepon / WhatsApp"
                                      type="tel"
                                      wire:model="phone"
                                      placeholder="08xxxxxxxxxx"
                                      pattern="08[0-9]{8,11}"
                                      maxlength="13"
                                      autocomplete="tel"
                                      required />

                        <!-- Toggle Status Aktif / Nonaktif -->
                        <div class="p-3.5 bg-wheat-50 rounded-2xl border border-wheat-200 flex items-center justify-between gap-3">
                            <div>
                                <label class="text-sm font-bold text-darkbrown-900">Status Akun Kasir</label>
                                <p class="text-xs text-darkbrown-500">
                                    {{ $is_active ? 'Kasir dapat login dan memproses transaksi POS toko.' : 'Kasir tidak dapat login ke sistem.' }}
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-avocado-600"></div>
                            </label>
                        </div>

                        <!-- Reset Password (Opsional) -->
                        <div class="pt-2 border-t border-wheat-200">
                            <p class="text-xs font-bold text-darkbrown-700 uppercase tracking-wider mb-3">Ubah Password (Opsional)</p>
                            <p class="text-[11px] text-darkbrown-500 mb-3">Biarkan kosong jika tidak ingin mereset password kasir.</p>

                            <div class="space-y-4">
                                <x-auth.field name="password"
                                              label="Password Baru"
                                              type="password"
                                              wire:model="password"
                                              placeholder="Kosongkan jika tidak diubah"
                                              autocomplete="new-password" />

                                <x-auth.field name="password_confirmation"
                                              label="Konfirmasi Password Baru"
                                              type="password"
                                              wire:model="password_confirmation"
                                              placeholder="Ulangi password baru"
                                              autocomplete="new-password" />
                            </div>
                        </div>

                        <div class="pt-3">
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center rounded-full bg-tendaku-avocado px-6 py-3.5 text-base font-semibold text-white shadow-[0_4px_0_0_#566F06] transition hover:brightness-105 active:translate-y-[2px] active:shadow-none focus:outline-none focus-visible:ring-2 focus-visible:ring-tendaku-brown">
                                <span wire:loading.remove wire:target="updateCashier">Perbarui Data Kasir</span>
                                <span wire:loading wire:target="updateCashier" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Menyimpan...</span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Konfirmasi Hapus Kasir -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
                 wire:click="closeModals"></div>

            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-wheat-200 space-y-4 z-10">
                    <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>

                    <div class="text-center space-y-2">
                        <h3 class="text-lg font-extrabold text-darkbrown-900">Hapus Akun Kasir?</h3>
                        <p class="text-xs text-darkbrown-600">
                            Apakah Anda yakin ingin menghapus akun kasir <strong class="text-darkbrown-900">{{ $deletingCashierName }}</strong> dari toko Anda?
                        </p>
                    </div>

                    @if ($hasTransactionsWarning)
                        <div class="p-3 bg-goldenrod-50 border border-goldenrod-200 rounded-xl text-left">
                            <div class="flex gap-2">
                                <svg class="w-4 h-4 text-goldenrod-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-[11px] text-goldenrod-900">
                                    <strong>Perhatian:</strong> Kasir ini telah memiliki riwayat operasional POS (serah terima/pembayaran). Akun akan dihapus secara aman (soft delete) sehingga data riwayat transaksi toko tetap utuh.
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center gap-3 pt-2">
                        <button wire:click="closeModals"
                                type="button"
                                class="flex-1 px-4 py-2.5 rounded-full text-xs font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 transition">
                            Batal
                        </button>
                        <button wire:click="deleteCashier"
                                type="button"
                                class="flex-1 px-4 py-2.5 rounded-full text-xs font-bold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 transition shadow-sm">
                            <span wire:loading.remove wire:target="deleteCashier">Ya, Hapus Kasir</span>
                            <span wire:loading wire:target="deleteCashier">Menghapus...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
