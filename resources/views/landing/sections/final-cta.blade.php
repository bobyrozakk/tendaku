<section class="py-20 sm:py-24 w-full bg-[#FDFBF7]" aria-label="Call to Action">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6" data-sr>
            {{-- Customer CTA --}}
            <div class="relative overflow-hidden rounded-3xl p-8 sm:p-10 flex flex-col justify-between gap-8 shadow-sm"
                 style="background:linear-gradient(135deg,#E7EDC5 0%,#D0DE8F 100%);min-height:220px">
                <div class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wider" style="color:#3F5306">Untuk Penyewa</span>
                    <h3 class="font-display text-2xl sm:text-3xl" style="color:#2A370A">Mulai Petualangan<br>Anda Sekarang</h3>
                    <p class="text-sm leading-relaxed" style="color:#546E06">Daftar gratis dan nikmati kemudahan booking alat camping online.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    @guest
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white transition-colors duration-200"
                           style="background:#3F5306">
                            <i data-lucide="user-plus" class="w-4 h-4" aria-hidden="true"></i>
                            Daftar Sekarang
                        </a>
                    @else
                        <a href="{{ route('customer.catalog.index') }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white transition-colors duration-200"
                           style="background:#3F5306">
                            <i data-lucide="tent" class="w-4 h-4" aria-hidden="true"></i>
                            Jelajahi Katalog
                        </a>
                    @endguest
                </div>
            </div>

            {{-- Vendor CTA --}}
            <div class="relative overflow-hidden rounded-3xl p-8 sm:p-10 flex flex-col justify-between gap-8 shadow-sm"
                 style="background:linear-gradient(135deg,#2A370A 0%,#3F5306 100%);min-height:220px">
                <div class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wider" style="color:#90B123">Untuk Pemilik Rental</span>
                    <h3 class="font-display text-2xl sm:text-3xl" style="color:#FAF6ED">Kelola Bisnis Rental<br>Lebih Efisien</h3>
                    <p class="text-sm leading-relaxed" style="color:#9BAD6B">Dashboard, kasir POS, dan laporan bisnis dalam satu platform.</p>
                </div>
                <a href="{{ route('vendor.dashboard') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-colors duration-200 self-start"
                   style="background:#FBCE6B;color:#2B2202">
                    <i data-lucide="store" class="w-4 h-4" aria-hidden="true"></i>
                    Masuk Portal Vendor
                </a>
            </div>
        </div>
    </div>
</section>
