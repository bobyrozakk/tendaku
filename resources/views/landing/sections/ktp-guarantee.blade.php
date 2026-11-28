<section class="py-12 sm:py-20 lg:py-24 w-full" style="background:#F4F7F2" id="ktp-jaminan" aria-labelledby="ktp-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row items-center gap-8 sm:gap-12 lg:gap-20">
            <div class="w-full lg:w-[42%] shrink-0" data-sr>
                <div class="relative overflow-hidden shadow-2xl group aspect-[4/3] sm:aspect-[4/5]" style="border-radius:1.5rem">
                    <img src="{{ asset('images/superadmin-bg.jpg') }}"
                         alt="Pemandangan Camping Tendaku"
                         class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                         loading="lazy" width="682" height="1024"
                         onerror="this.src='{{ asset('images/tendaku-footer-forest.jpg') }}'">
                    {{-- Gradient Scrim & Floating Badge --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent pointer-events-none"></div>
                    <div class="absolute bottom-6 left-6 right-6 text-white space-y-2 pointer-events-none">
                        <h4 class="font-display text-xl text-white font-bold leading-tight">Camping Aman & Bebas Cemas</h4>
                        <p class="text-xs text-wheat-200 leading-relaxed">KTP fisik Anda disimpan aman oleh mitra vendor dan langsung dikembalikan saat serah terima pengembalian alat.</p>
                    </div>
                </div>
            </div>
            <div class="flex-1 space-y-6">
                <div data-sr data-sr-delay="100">
                    <h2 id="ktp-heading" class="font-display text-3xl sm:text-4xl lg:text-5xl text-darkbrown-900 leading-tight mt-2">
                        Tanpa Deposit Uang.<br>Hanya <span class="text-avocado-700">KTP Asli</span>.
                    </h2>
                </div>
                <p class="text-base text-darkbrown-600 leading-relaxed" data-sr data-sr-delay="180">
                    Tendaku menerapkan sistem <strong>jaminan fisik KTP asli</strong> sebagai pengganti uang deposit. KTP Anda disimpan aman oleh vendor selama masa sewa dan dikembalikan saat unit dikembalikan dalam kondisi baik.
                </p>
                <div class="space-y-3" data-sr data-sr-delay="260">
                    @php $steps = [
                        ['n'=>'01','icon'=>'clipboard-check','title'=>'Booking Online',       'desc'=>'Pilih alat & tanggal, bayar lunas via transfer atau QRIS.'],
                        ['n'=>'02','icon'=>'credit-card',    'title'=>'Serah Terima KTP',     'desc'=>'Tunjukkan KTP asli saat ambil unit di outlet vendor.'],
                        ['n'=>'03','icon'=>'check-circle',   'title'=>'Kembalikan & Selesai', 'desc'=>'Return unit, cek kondisi, KTP langsung dikembalikan.'],
                    ]; @endphp
                    @foreach($steps as $step)
                        <div class="ld-ktp-step flex items-start gap-4 p-4 rounded-xl border"
                             style="background:rgba(108,139,8,.04);border-color:rgba(108,139,8,.14)">
                            <span class="font-display text-xs font-extrabold text-avocado-500 w-6 shrink-0 mt-0.5">{{ $step['n'] }}</span>
                            <div class="w-8 h-8 rounded-lg bg-white border border-avocado-200 flex items-center justify-center shrink-0 shadow-sm">
                                <i data-lucide="{{ $step['icon'] }}" class="w-4 h-4 text-avocado-700" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-darkbrown-900">{{ $step['title'] }}</h4>
                                <p class="text-xs text-darkbrown-500 mt-0.5 leading-snug">{{ $step['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div data-sr data-sr-delay="340">
                    <a href="{{ route('customer.catalog.index') }}"
                       class="inline-flex items-center gap-2 px-7 py-3.5 rounded-xl text-sm font-bold text-white transition-colors duration-200"
                       style="background:#2B2202">
                        Mulai Sewa Sekarang
                        <i data-lucide="arrow-right" class="w-4 h-4" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
