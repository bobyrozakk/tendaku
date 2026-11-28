<section class="py-12 sm:py-20 lg:py-24 w-full scroll-mt-20" style="background:#1B1501" id="cara-penyewaan" aria-labelledby="cara-sewa-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center mb-10 sm:mb-16 max-w-3xl mx-auto" data-sr>
            <h2 id="cara-sewa-heading" class="font-display text-3xl sm:text-5xl mt-3 tracking-tight" style="color:#FAF6ED">
                Cara Penyewaan pada Tendaku
            </h2>
            <p class="mt-3 text-sm sm:text-base leading-relaxed text-[#D9C9A0] font-normal">
                Sewa alat camping lebih praktis, transparan, dan tanpa rasa khawatir. Dilengkapi sistem jaminan identitas KTP asli tanpa perlu deposit uang tunai.
            </p>
        </div>

        {{-- 5 Steps Cards Grid --}}
        @php
            $rentalSteps = [
                [
                    'step' => '01',
                    'title' => 'Pilih Alat & Tentukan Jadwal',
                    'desc' => 'Jelajahi katalog alat atau paket camping. Tentukan tanggal mulai dan selesai sewa; stok ketersediaan unit dicek real-time.',
                    'highlight' => 'Stok Kalender Akurat',
                ],
                [
                    'step' => '02',
                    'title' => 'Isi Data & Opsi Pengambilan',
                    'desc' => 'Lengkapi data pemesanan. Pilih opsi ambil langsung di outlet vendor terdekat atau gunakan kurir antar ke titik kumpul di Malang Raya.',
                    'highlight' => 'Area Malang Raya & Batu',
                ],
                [
                    'step' => '03',
                    'title' => 'Pembayaran Online Otomatis',
                    'desc' => 'Bayar DP 50% atau lunas melalui QRIS atau Virtual Account bank (BCA, BNI, BRI, Permata, Mandiri) dengan batas waktu 60 menit.',
                    'highlight' => 'QRIS & Virtual Account',
                ],
                [
                    'step' => '04',
                    'title' => 'Serah Terima & Jaminan KTP Asli',
                    'desc' => 'Datang ke outlet vendor saat jadwal sewa tiba. Lakukan pengecekan fisik unit bersama kasir dan serahkan KTP fisik sebagai jaminan sewa.',
                    'highlight' => 'Tanpa Deposit Uang Tunai',
                ],
                [
                    'step' => '05',
                    'title' => 'Kembalikan Alat & Ambil KTP',
                    'desc' => 'Nikmati campingmu! Kembalikan unit tepat waktu untuk pemeriksaan kondisi alat. KTP fisikmu diserahkan kembali dan pesanan selesai.',
                    'highlight' => 'Pengembalian Cepat & Transparan',
                ],
            ];
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
            @foreach($rentalSteps as $idx => $step)
                <div class="relative flex flex-col justify-between p-6 sm:p-7 rounded-2xl border border-white/10 bg-white/[0.03] backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-sunglow-300/30 hover:bg-white/[0.05] {{ $loop->last ? 'md:col-span-2 lg:col-span-1' : '' }}"
                     data-sr data-sr-delay="{{ $idx * 100 }}">

                    <div>
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-sunglow-400/10 border border-sunglow-400/20 text-sunglow-400 font-mono text-base font-bold tracking-wider mb-5">
                            {{ $step['step'] }}
                        </span>

                        <h3 class="font-display text-lg sm:text-xl font-bold tracking-tight text-[#FAF6ED] mb-2.5">
                            {{ $step['title'] }}
                        </h3>

                        <p class="text-sm leading-relaxed text-[#D9C9A0] font-normal">
                            {{ $step['desc'] }}
                        </p>
                    </div>

                    <div class="mt-5 pt-4 border-t border-white/10">
                        <span class="text-xs font-semibold text-avocado-300 tracking-wide">
                            {{ $step['highlight'] }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Bottom CTA Box --}}
        <div class="mt-14 max-w-4xl mx-auto p-6 sm:p-8 rounded-2xl border border-sunglow-300/20 bg-gradient-to-r from-sunglow-950/30 via-darkbrown-900 to-avocado-950/30 flex flex-col sm:flex-row items-center justify-between gap-5 text-center sm:text-left" data-sr>
            <div>
                <h4 class="font-display text-xl sm:text-2xl font-bold text-[#FAF6ED]">Siap Merencanakan Petualanganmu?</h4>
                <p class="text-sm text-[#D9C9A0] mt-1">Pilih perlengkapan outdoor terbaik sekarang dan rasakan kemudahan sewa di Tendaku.</p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-3 shrink-0">
                <a href="{{ route('customer.catalog.index') }}"
                   class="inline-flex items-center gap-2 px-5 py-3 min-h-[44px] rounded-full font-bold text-sm bg-sunglow-400 text-darkbrown-900 hover:bg-sunglow-300 transition-colors shadow-sm">
                    Buka Katalog
                </a>
                <a href="{{ url('/layanan#panduan-penyewaan') }}"
                   class="inline-flex items-center gap-2 px-5 py-3 min-h-[44px] rounded-full font-bold text-sm border border-white/20 text-[#FAF6ED] hover:bg-white/10 transition-colors">
                    Pelajari Lengkap
                    <i data-lucide="arrow-right" class="w-4 h-4" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</section>
