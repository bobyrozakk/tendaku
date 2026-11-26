<div class="min-h-screen bg-[#FAF6ED] text-darkbrown-800 antialiased selection:bg-avocado-500 selection:text-white relative">
    {{-- Decorative Ambient Mesh in Background --}}
    <div class="absolute top-0 inset-x-0 h-96 bg-gradient-to-b from-wheat-200/40 via-wheat-100/20 to-transparent pointer-events-none"></div>

    {{-- Modern Glassmorphic Topbar --}}
    <header class="sticky top-0 z-30 bg-white/85 backdrop-blur-xl border-b border-wheat-200/80 shadow-2xs transition-all duration-200">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            {{-- Tombol Kembali ke Dashboard Kasir / POS --}}
            <div class="flex items-center gap-3">
                <a href="{{ auth()->user()?->isVendorOwner() ? route('vendor.owner.dashboard') : route('kasir.dashboard') }}"
                   wire:navigate
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-darkbrown-800 bg-white hover:bg-wheat-100/90 border border-wheat-300/80 transition-all duration-150 active:scale-95 shadow-xs group cursor-pointer">
                    <i data-lucide="arrow-left" class="w-4 h-4 transition-transform group-hover:-translate-x-0.5 text-darkbrown-600"></i>
                    <span>Kembali ke POS</span>
                </a>

                <div class="h-5 w-px bg-wheat-300/80 hidden sm:block"></div>

                <div class="hidden sm:flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-darkbrown-700 truncate max-w-[200px]">
                        {{ $vendor?->name ?? 'Tendaku Store Central' }}
                    </span>
                </div>
            </div>

            {{-- Brand Logo & User Pill --}}
            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}"
                   class="hidden sm:inline-flex items-center gap-2.5 px-3 py-1.5 rounded-xl transition-all duration-150 hover:opacity-90 shadow-xs"
                   style="background: linear-gradient(135deg, #1e1a10 0%, #2b2202 100%); border: 1px solid rgba(255,255,255,0.12);"
                   title="Tendaku — Kembali ke Beranda"
                   aria-label="Tendaku Home">
                    <img src="{{ asset('images/Logo.svg') }}"
                         alt="Tendaku Logo"
                         class="h-6 w-auto object-contain block"
                         style="filter: brightness(1.7) contrast(1.1) drop-shadow(0 1px 2px rgba(0,0,0,0.5));">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-wheat-200 border-l border-white/20 pl-2">
                        Portal Kasir
                    </span>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="inline-block">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100/90 border border-rose-200/90 transition-all duration-150 active:scale-95 shadow-xs cursor-pointer"
                            title="Keluar dari sesi akun">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Main Content Container --}}
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-8 relative z-10">

        {{-- Hero Profile Banner (Apple Obsidian & Forest Glassmorphism) --}}
        <div class="relative overflow-hidden rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-darkbrown-950/15 border border-white/15"
             style="background: linear-gradient(135deg, #181403 0%, #292004 45%, #3d4f06 100%);">

            {{-- Specular Light Sheen --}}
            <div class="absolute inset-0 bg-gradient-to-b from-white/10 via-transparent to-black/20 pointer-events-none"></div>

            {{-- Ambient Glow Orbs --}}
            <div class="absolute -right-16 -top-16 w-72 h-72 rounded-full bg-sunglow-500/20 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 w-72 h-72 rounded-full bg-avocado-500/25 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex items-center gap-5 sm:gap-6">
                    {{-- Avatar Initial with Active Dot --}}
                    <div class="relative shrink-0">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl font-black text-2xl sm:text-3xl flex items-center justify-center text-white shadow-xl border-2 border-white/30 backdrop-blur-md"
                             style="background: linear-gradient(135deg, #7da108 0%, #4e6506 100%);">
                            {{ strtoupper(substr($user->name ?? 'KA', 0, 2)) }}
                        </div>
                        <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 border-2 border-darkbrown-950 shadow-sm flex items-center justify-center" title="Akun Aktif">
                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                        </span>
                    </div>

                    {{-- Nama, Role & Meta --}}
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white font-serif">
                                {{ $user->name }}
                            </h1>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/15 text-sunglow-300 border border-white/20 backdrop-blur-md shadow-xs">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-sunglow-400"></i>
                                <span>{{ $user->isVendorOwner() ? 'Vendor Owner' : 'Kasir Toko (Admin)' }}</span>
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-wheat-200/90 flex items-center gap-2">
                            <i data-lucide="mail" class="w-4 h-4 text-sunglow-400 shrink-0"></i>
                            <span>{{ $user->email }}</span>
                        </p>
                        <div class="flex items-center gap-3 text-[11px] text-wheat-300/80 font-medium flex-wrap pt-0.5">
                            <span class="font-mono bg-white/10 px-2 py-0.5 rounded border border-white/15">
                                ID: #USR-{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                            <span>•</span>
                            <span class="flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-wheat-400"></i>
                                Bergabung {{ $user->created_at?->format('d M Y') ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Toko Card (Right Island Glass) --}}
                <div class="w-full md:w-auto bg-white/10 backdrop-blur-xl rounded-2xl p-5 border border-white/20 shrink-0 md:min-w-[260px] shadow-lg">
                    <div class="flex items-center justify-between gap-3 mb-2.5">
                        <span class="text-[10px] font-extrabold text-sunglow-300 uppercase tracking-wider block">Toko Penugasan</span>
                        <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-300 bg-emerald-950/50 px-2.5 py-0.5 rounded-full border border-emerald-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Terhubung
                        </span>
                    </div>
                    <p class="text-sm font-extrabold text-white truncate flex items-center gap-2">
                        <i data-lucide="store" class="w-4 h-4 text-sunglow-400 shrink-0"></i>
                        <span>{{ $vendor?->name ?? 'Belum terhubung ke toko' }}</span>
                    </p>
                    <p class="text-[11px] text-wheat-200/80 truncate mt-1.5 flex items-center gap-1.5">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-wheat-400 shrink-0"></i>
                        <span>{{ $vendor?->city ?? 'Kota Operasional' }} • {{ $vendor?->status === 'approved' ? 'Terverifikasi' : 'Aktif' }}</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- 3 Grid Columns: Detail Toko, Hak Akses & Form Data Akun --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">

            {{-- Col 1: Detail Toko & Status Kasir --}}
            <div class="space-y-6">
                {{-- Card Toko --}}
                <div class="bg-white rounded-2xl border border-wheat-200/90 p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-3 pb-3 border-b border-wheat-200/80">
                        <div class="w-9 h-9 rounded-xl bg-avocado-100 text-avocado-800 border border-avocado-200 flex items-center justify-center shrink-0">
                            <i data-lucide="store" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-extrabold text-darkbrown-900 leading-tight">Informasi Toko</h2>
                            <p class="text-[11px] text-darkbrown-500">Cabang outlet tempat bertugas</p>
                        </div>
                    </div>

                    <div class="space-y-3.5 text-xs">
                        <div>
                            <span class="text-darkbrown-400 text-[11px] block font-medium">Nama Usaha:</span>
                            <span class="font-extrabold text-darkbrown-900 text-sm block mt-0.5">{{ $vendor?->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-darkbrown-400 text-[11px] block font-medium">Kontak WhatsApp / Telepon:</span>
                            <span class="font-bold text-darkbrown-800 font-mono block mt-0.5">{{ $vendor?->phone ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-darkbrown-400 text-[11px] block font-medium">Alamat Fisik:</span>
                            <p class="font-medium text-darkbrown-700 leading-relaxed mt-0.5">
                                {{ $vendor?->address ?? '-' }}{{ $vendor?->city ? ', ' . $vendor->city : '' }}
                            </p>
                        </div>
                        <div class="pt-2 border-t border-wheat-100">
                            <span class="text-darkbrown-400 text-[11px] block font-medium mb-1">Status Operasional Toko:</span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                Toko Aktif & Siap Transaksi
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Card Hak Akses Kasir --}}
                <div class="bg-gradient-to-br from-wheat-100/70 to-wheat-50 rounded-2xl border border-wheat-200/90 p-5 shadow-sm space-y-3">
                    <div class="flex items-center gap-2 text-darkbrown-900 font-extrabold text-xs">
                        <div class="w-6 h-6 rounded-lg bg-sunglow-100 border border-sunglow-300 text-sunglow-800 flex items-center justify-center shrink-0">
                            <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                        </div>
                        <span>Otoritas & Akses Kasir</span>
                    </div>
                    <ul class="space-y-2 text-[11px] text-darkbrown-700 font-medium">
                        <li class="flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-3.5 h-3.5 text-avocado-700 shrink-0"></i>
                            <span>Transaksi & Checkout POS Kasir</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-3.5 h-3.5 text-avocado-700 shrink-0"></i>
                            <span>Scan Barcode & Alokasi Unit Fisik</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-3.5 h-3.5 text-avocado-700 shrink-0"></i>
                            <span>Serah Terima Barang (Pickup Order)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-3.5 h-3.5 text-avocado-700 shrink-0"></i>
                            <span>Inspeksi Fisik & Alur Retur Sewa</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Col 2 & 3: Form Informasi Akun Kasir --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Form: Data Akun Kasir --}}
                <div class="bg-white rounded-2xl border border-wheat-200/90 p-6 sm:p-7 shadow-sm">
                    <div class="flex items-center justify-between gap-4 pb-4 border-b border-wheat-200/80">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-wheat-100 border border-wheat-200 text-darkbrown-800 flex items-center justify-center shrink-0">
                                <i data-lucide="user-pen" class="w-5 h-5 text-avocado-800"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-extrabold text-darkbrown-900 leading-tight">Data Profil Kasir</h2>
                                <p class="text-xs text-darkbrown-500 mt-0.5">Kelola identitas resmi dan kontak akun petugas</p>
                            </div>
                        </div>

                        {{-- Saved Toast Indicator --}}
                        @if($infoSaved)
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200 shadow-2xs animate-fade-in">
                                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                Perubahan disimpan!
                            </span>
                        @endif
                    </div>

                    <form wire:submit.prevent="updateProfileInformation" class="mt-6 space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                            {{-- Field 1: Nama Kasir --}}
                            <div>
                                <label for="name" class="block text-xs font-extrabold text-darkbrown-800 mb-1.5">
                                    Nama Lengkap Petugas <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-darkbrown-400">
                                        <i data-lucide="user" class="w-4 h-4"></i>
                                    </div>
                                    <input type="text"
                                           id="name"
                                           wire:model="name"
                                           class="w-full text-xs font-semibold pl-10 pr-3.5 py-2.5 rounded-xl border border-wheat-300 bg-wheat-50/40 text-darkbrown-900 placeholder-darkbrown-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-avocado-500/25 focus:border-avocado-600 transition"
                                           placeholder="Nama lengkap kasir"
                                           required />
                                </div>
                                @error('name')
                                    <p class="text-[11px] font-bold text-rose-600 mt-1 flex items-center gap-1">
                                        <i data-lucide="alert-circle" class="w-3 h-3 shrink-0"></i>
                                        <span>{{ $message }}</span>
                                    </p>
                                @enderror
                            </div>

                            {{-- Field 2: Email --}}
                            <div>
                                <label for="email" class="block text-xs font-extrabold text-darkbrown-800 mb-1.5">
                                    Alamat Email Login <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-darkbrown-400">
                                        <i data-lucide="mail" class="w-4 h-4"></i>
                                    </div>
                                    <input type="email"
                                           id="email"
                                           wire:model="email"
                                           class="w-full text-xs font-semibold pl-10 pr-3.5 py-2.5 rounded-xl border border-wheat-300 bg-wheat-50/40 text-darkbrown-900 placeholder-darkbrown-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-avocado-500/25 focus:border-avocado-600 transition"
                                           placeholder="email@tendaku.id"
                                           required />
                                </div>
                                @error('email')
                                    <p class="text-[11px] font-bold text-rose-600 mt-1 flex items-center gap-1">
                                        <i data-lucide="alert-circle" class="w-3 h-3 shrink-0"></i>
                                        <span>{{ $message }}</span>
                                    </p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                            {{-- Field 3: WhatsApp / HP --}}
                            <div>
                                <label for="phone" class="block text-xs font-extrabold text-darkbrown-800 mb-1.5">
                                    Nomor WhatsApp / HP
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-darkbrown-400">
                                        <i data-lucide="phone" class="w-4 h-4"></i>
                                    </div>
                                    <input type="text"
                                           id="phone"
                                           wire:model="phone"
                                           placeholder="Contoh: 08123456789"
                                           class="w-full text-xs font-semibold font-mono pl-10 pr-3.5 py-2.5 rounded-xl border border-wheat-300 bg-wheat-50/40 text-darkbrown-900 placeholder-darkbrown-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-avocado-500/25 focus:border-avocado-600 transition" />
                                </div>
                                @error('phone')
                                    <p class="text-[11px] font-bold text-rose-600 mt-1 flex items-center gap-1">
                                        <i data-lucide="alert-circle" class="w-3 h-3 shrink-0"></i>
                                        <span>{{ $message }}</span>
                                    </p>
                                @enderror
                            </div>

                            {{-- Field 4: Alamat Domisili --}}
                            <div>
                                <label for="address" class="block text-xs font-extrabold text-darkbrown-800 mb-1.5">
                                    Kota Domisili / Alamat
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-darkbrown-400">
                                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                                    </div>
                                    <input type="text"
                                           id="address"
                                           wire:model="address"
                                           placeholder="Kota domisili kasir"
                                           class="w-full text-xs font-semibold pl-10 pr-3.5 py-2.5 rounded-xl border border-wheat-300 bg-wheat-50/40 text-darkbrown-900 placeholder-darkbrown-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-avocado-500/25 focus:border-avocado-600 transition" />
                                </div>
                                @error('address')
                                    <p class="text-[11px] font-bold text-rose-600 mt-1 flex items-center gap-1">
                                        <i data-lucide="alert-circle" class="w-3 h-3 shrink-0"></i>
                                        <span>{{ $message }}</span>
                                    </p>
                                @enderror
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="pt-4 border-t border-wheat-100 flex items-center justify-between gap-4">
                            <p class="text-[11px] text-darkbrown-500 font-medium">
                                Informasi ini digunakan untuk identifikasi petugas kasir pada struk transaksi.
                            </p>

                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-extrabold text-white bg-avocado-700 hover:bg-avocado-800 shadow-sm hover:shadow-tendaku-sm active:scale-[0.98] transition-all duration-150 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed shrink-0">
                                <span wire:loading.remove wire:target="updateProfileInformation" class="flex items-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                    <span>Simpan Perubahan</span>
                                </span>
                                <span wire:loading wire:target="updateProfileInformation" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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
    </main>
</div>
