<section class="py-20 sm:py-24 w-full" style="background:#2A370A" id="vendor" aria-labelledby="vend-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row items-start gap-14">
            {{-- Left heading --}}
            <div class="lg:w-[36%] shrink-0 space-y-6" data-sr>
                <div>
                    <span class="text-xs font-bold uppercase tracking-[.18em]" style="color:#90B123">Untuk Pemilik Rental</span>
                    <h2 id="vend-heading" class="font-display text-3xl sm:text-4xl lg:text-5xl leading-tight mt-2" style="color:#FAF6ED">
                        Bergabung sebagai<br><span style="color:#FBCE6B">Vendor Tendaku</span>
                    </h2>
                </div>
                <p class="text-sm leading-relaxed" style="color:#C4AC7E">
                    Kelola toko rental camping Anda dengan sistem manajemen yang modern — dari stok unit, booking pelanggan, hingga pembayaran otomatis dalam satu dashboard terintegrasi.
                </p>
                <div class="space-y-3">
                    <a href="{{ route('vendor.dashboard') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-bold transition-colors duration-200 w-full sm:w-auto justify-center"
                       style="background:#FBCE6B;color:#2B2202">
                        <i data-lucide="store" class="w-4 h-4" aria-hidden="true"></i>
                        Masuk Portal Vendor
                    </a>
                    <p class="text-xs" style="color:#7A9A30">Hubungi kami untuk proses pendaftaran vendor baru.</p>
                </div>

                {{-- Vendor stats --}}
                <div class="grid grid-cols-2 gap-4 pt-4" style="border-top:1px solid rgba(255,255,255,.1)">
                    @php $vStats = [
                        ['num'=>'5+',  'label'=>'Vendor Aktif'],
                        ['num'=>'300+','label'=>'Transaksi Sukses'],
                    ]; @endphp
                    @foreach($vStats as $vs)
                        <div>
                            <span class="font-display text-2xl font-bold block" style="color:#FBCE6B">{{ $vs['num'] }}</span>
                            <span class="text-xs mt-0.5 block" style="color:#7A9A30">{{ $vs['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Right: benefit cards grid --}}
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @php $vendBenefits = [
                    ['icon'=>'layout-dashboard', 'title'=>'Dashboard Manajemen Toko',
                     'desc'=>'Pantau seluruh aktivitas toko — booking masuk, unit tersedia, dan pendapatan — dari satu halaman.'],
                    ['icon'=>'package',          'title'=>'Manajemen Stok Unit',
                     'desc'=>'Kelola inventaris unit camping Anda secara real-time, termasuk status, kondisi, dan ketersediaan.'],
                    ['icon'=>'receipt',          'title'=>'Kasir POS Langsung',
                     'desc'=>'Sistem Point-of-Sale untuk transaksi langsung di toko — catat penyewaan kasir tanpa booking online.'],
                    ['icon'=>'bell',             'title'=>'Notifikasi Booking Otomatis',
                     'desc'=>'Terima notifikasi setiap ada booking baru, pengingat pickup, dan jadwal pengembalian unit.'],
                    ['icon'=>'bar-chart-2',      'title'=>'Laporan & Statistik',
                     'desc'=>'Analitik pendapatan, unit paling laris, dan tren penyewaan untuk membantu keputusan bisnis.'],
                    ['icon'=>'handshake',        'title'=>'Serah Terima Terstruktur',
                     'desc'=>'Alur serah terima unit yang terdokumentasi — dari checkout hingga return — aman dan tercatat.'],
                    ['icon'=>'credit-card',      'title'=>'Pembayaran Online Otomatis',
                     'desc'=>'Terima pembayaran via transfer & QRIS — langsung konfirmasi otomatis, tanpa manual cek mutasi.'],
                    ['icon'=>'users',            'title'=>'Multi-Outlet Support',
                     'desc'=>'Kelola lebih dari satu outlet dalam satu akun vendor — cocok untuk rental dengan banyak cabang.'],
                ]; @endphp
                @foreach($vendBenefits as $i => $b)
                    <div class="ld-vend-card" data-sr data-sr-delay="{{ $i * 65 }}">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background:rgba(251,206,107,.15)">
                                <i data-lucide="{{ $b['icon'] }}" class="w-4 h-4" style="color:#FBCE6B" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold" style="color:#FAF6ED">{{ $b['title'] }}</h4>
                                <p class="text-xs leading-relaxed mt-1" style="color:#9BAD6B">{{ $b['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
