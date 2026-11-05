<div class="min-h-screen bg-[#FAF6ED] text-darkbrown-800 antialiased selection:bg-avocado-500 selection:text-white">
    {{-- Modern Minimal Topbar (Without Sidebar) --}}
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-wheat-200/90 shadow-sm transition-all duration-200">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            {{-- Tombol Kembali ke Dashboard Kasir / Vendor --}}
            <div class="flex items-center gap-3">
                <a href="{{ auth()->user()?->isVendorOwner() ? route('vendor.owner.dashboard') : route('kasir.dashboard') }}"
                   wire:navigate
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200/80 border border-wheat-300/80 transition-all duration-150 active:scale-95 shadow-sm group">
                    <i data-lucide="arrow-left" class="w-4 h-4 transition-transform group-hover:-translate-x-0.5"></i>
                    <span>Kembali ke POS</span>
                </a>

                <div class="h-5 w-px bg-wheat-300/80 hidden sm:block"></div>

                <div class="hidden sm:flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-xs font-semibold text-darkbrown-600 truncate max-w-[200px]">
                        {{ $vendor?->name ?? 'Tendaku Store' }}
                    </span>
                </div>
            </div>

            {{-- Brand Logo & User Pill --}}
            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" class="hidden sm:inline-flex items-center gap-2" title="Tendaku">
                    <x-application-logo size="sm" :withText="false" />
                    <span class="font-extrabold text-sm tracking-tight text-darkbrown-900 font-serif">Tendaku</span>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="inline-block">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-red-700 bg-red-50 hover:bg-red-100/90 border border-red-200/90 transition-all duration-150 shadow-sm cursor-pointer"
                            title="Keluar dari sesi akun">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Main Content Container (Modern Glassmorphic Hero + Card Info) --}}
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-8">

        {{-- Hero Profile Banner --}}
        <div class="relative overflow-hidden rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-darkbrown-950/10"
             style="background: linear-gradient(135deg, #2b2202 0%, #3e3204 50%, #4e6506 100%);">
            
            {{-- Ambient Glow Orbs --}}
            <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-sunglow-500/15 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 w-64 h-64 rounded-full bg-avocado-500/20 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div class="flex items-center gap-5">
                    {{-- Avatar Initial --}}
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl font-black text-2xl sm:text-3xl flex items-center justify-center text-white shrink-0 shadow-lg border-2 border-white/20"
                         style="background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%);">
                        {{ strtoupper(substr($user->name ?? 'KA', 0, 2)) }}
                    </div>

                    {{-- Nama & Role --}}
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white font-serif">
                                {{ $user->name }}
                            </h1>
                            <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-white/15 text-sunglow-300 border border-white/20 backdrop-blur-sm">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                {{ $user->isVendorOwner() ? 'Vendor Owner' : 'Kasir Toko (Admin)' }}
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-wheat-200/90 flex items-center gap-2">
                            <i data-lucide="mail" class="w-4 h-4 text-sunglow-400"></i>
                            <span>{{ $user->email }}</span>
                        </p>
                        <p class="text-[11px] text-wheat-300/70 font-mono">
                            ID Petugas: #USR-{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }} • Bergabung: {{ $user->created_at?->format('d M Y') ?? '-' }}
                        </p>
                    </div>
                </div>

                {{-- Status Tag Toko --}}
                <div class="w-full sm:w-auto bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/15 shrink-0 sm:min-w-[220px]">
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <span class="text-[11px] font-bold text-wheat-200 uppercase tracking-wider">Toko Bertugas</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    </div>
                    <p class="text-sm font-extrabold text-white truncate">
                        {{ $vendor?->name ?? 'Belum terhubung ke toko' }}
                    </p>
                    <p class="text-[11px] text-wheat-300/80 truncate mt-0.5">
                        {{ $vendor?->city ?? 'Kota Operasional' }} • {{ $vendor?->status === 'approved' ? 'Toko Terverifikasi' : 'Status: ' . ($vendor?->status ?? 'Aktif') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- 3 Grid Cards: Ringkasan Toko, Info Akun, & Ubah Password --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
            
            {{-- Col 1: Detail Toko & Status Kasir --}}
            <div class="space-y-6">
                {{-- Card Toko --}}
                <div class="bg-white rounded-3xl border border-wheat-300/80 p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-3 pb-3 border-b border-wheat-200/80">
                        <div class="p-2 rounded-xl bg-avocado-100 text-avocado-800">
                            <i data-lucide="store" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-extrabold text-darkbrown-900">Informasi Toko</h2>
                            <p class="text-[11px] text-darkbrown-500">Tempat kasir bertugas saat ini</p>
                        </div>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="text-darkbrown-400 text-[11px] block font-medium">Nama Bisnis / Toko:</span>
                            <span class="font-bold text-darkbrown-800 text-sm">{{ $vendor?->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-darkbrown-400 text-[11px] block font-medium">Kontak Toko:</span>
                            <span class="font-bold text-darkbrown-800">{{ $vendor?->phone ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-darkbrown-400 text-[11px] block font-medium">Alamat Toko:</span>
                            <p class="font-medium text-darkbrown-700 leading-relaxed mt-0.5">
                                {{ $vendor?->address ?? '-' }}{{ $vendor?->city ? ', ' . $vendor->city : '' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-darkbrown-400 text-[11px] block font-medium">Status Operasional:</span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 mt-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                Toko Aktif & Siap Transaksi
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Card Hak Akses Kasir --}}
                <div class="bg-gradient-to-br from-wheat-100/70 to-wheat-200/50 rounded-3xl border border-wheat-300/80 p-6 shadow-sm space-y-3">
                    <div class="flex items-center gap-2 text-darkbrown-900 font-extrabold text-xs">
                        <i data-lucide="shield-alert" class="w-4 h-4 text-sunglow-700"></i>
                        <span>Hak Akses Petugas</span>
                    </div>
                    <p class="text-[11px] text-darkbrown-600 leading-relaxed">
                        Akun ini memiliki izin operasional kasir: validasi pesanan, scan barcode alat rental, serah terima unit (pickup), dan inspeksi retur.
                    </p>
                </div>
            </div>

            {{-- Col 2 & 3: Form Informasi Akun & Ubah Password --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- Form 1: Data Akun Kasir --}}
                <div class="bg-white rounded-3xl border border-wheat-300/80 p-6 sm:p-7 shadow-sm">
                    <div class="flex items-center justify-between gap-4 pb-4 border-b border-wheat-200/80">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-xl bg-wheat-100 text-darkbrown-800">
                                <i data-lucide="user-pen" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-extrabold text-darkbrown-900">Data Akun Kasir</h2>
                                <p class="text-xs text-darkbrown-500">Perbarui nama lengkap dan email resmi akun Anda</p>
                            </div>
                        </div>

                        @if($infoSaved)
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-xl border border-emerald-200 animate-fade-in">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                Tersimpan
                            </span>
                        @endif
                    </div>

                    <form wire:submit.prevent="updateProfileInformation" class="mt-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-xs font-bold text-darkbrown-800 mb-1.5">Nama Kasir / Petugas</label>
                                <input type="text"
                                       id="name"
                                       wire:model="name"
                                       class="w-full text-xs font-semibold px-4 py-2.5 rounded-xl border border-wheat-300 bg-wheat-50/40 text-darkbrown-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-500 transition"
                                       required />
                                @error('name')
                                    <p class="text-[11px] font-bold text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-xs font-bold text-darkbrown-800 mb-1.5">Alamat Email</label>
                                <input type="email"
                                       id="email"
                                       wire:model="email"
                                       class="w-full text-xs font-semibold px-4 py-2.5 rounded-xl border border-wheat-300 bg-wheat-50/40 text-darkbrown-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-500 transition"
                                       required />
                                @error('email')
                                    <p class="text-[11px] font-bold text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="phone" class="block text-xs font-bold text-darkbrown-800 mb-1.5">Nomor WhatsApp / HP</label>
                                <input type="text"
                                       id="phone"
                                       wire:model="phone"
                                       placeholder="Contoh: 08123456789"
                                       class="w-full text-xs font-semibold px-4 py-2.5 rounded-xl border border-wheat-300 bg-wheat-50/40 text-darkbrown-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-500 transition" />
                                @error('phone')
                                    <p class="text-[11px] font-bold text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="address" class="block text-xs font-bold text-darkbrown-800 mb-1.5">Alamat Domisili</label>
                                <input type="text"
                                       id="address"
                                       wire:model="address"
                                       placeholder="Kota tempat tinggal"
                                       class="w-full text-xs font-semibold px-4 py-2.5 rounded-xl border border-wheat-300 bg-wheat-50/40 text-darkbrown-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-500 transition" />
                                @error('address')
                                    <p class="text-[11px] font-bold text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="pt-3 flex justify-end">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-extrabold text-white bg-gradient-to-r from-emerald-600 to-avocado-600 hover:from-emerald-500 hover:to-avocado-500 shadow-md shadow-avocado-600/20 active:scale-95 transition cursor-pointer">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>Simpan Perubahan</span>
                            </button>
                        </div>
                    </form>
                </div>


            </div>
        </div>
    </main>
</div>
