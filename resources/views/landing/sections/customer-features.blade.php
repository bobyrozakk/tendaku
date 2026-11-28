<section class="py-12 sm:py-20 lg:py-24 w-full" style="background:#F4F7F2" id="fitur-akun" aria-labelledby="cust-feat-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row items-start gap-8 sm:gap-14 lg:gap-20">
            {{-- Left: heading + CTA --}}
            <div class="lg:w-[38%] shrink-0 space-y-6" data-sr>
                <div>
                    <h2 id="cust-feat-heading" class="font-display text-3xl sm:text-4xl lg:text-5xl text-darkbrown-900 leading-tight mt-2">
                        Daftar Akun,<br>Nikmati Semua<br><span class="text-avocado-700">Kemudahannya</span>
                    </h2>
                </div>
                <p class="text-sm text-darkbrown-600 leading-relaxed">
                    Dengan akun Tendaku, Anda bisa booking, bayar, dan pantau status penyewaan alat camping — semuanya dari satu tempat, kapan saja dan di mana saja.
                </p>
                <div class="flex flex-col sm:flex-row gap-3">
                    @guest
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-sm font-bold text-white transition-colors duration-200"
                           style="background:#3F5306">
                            <i data-lucide="user-plus" class="w-4 h-4" aria-hidden="true"></i>
                            Daftar Gratis
                        </a>
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-darkbrown-800 border border-darkbrown-300 bg-white hover:bg-wheat-50 transition-colors duration-200">
                            Sudah Punya Akun? Masuk
                        </a>
                    @else
                        <a href="{{ route('customer.booking.index') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-bold text-white transition-colors duration-200"
                           style="background:#3F5306">
                            <i data-lucide="calendar-check" class="w-4 h-4" aria-hidden="true"></i>
                            Lihat Booking Saya
                        </a>
                    @endguest
                </div>

                {{-- Small trust badge --}}
                <div class="flex items-center gap-3 pt-2">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" style="background:#E7EDC5">
                        <i data-lucide="shield-check" class="w-5 h-5 text-avocado-700" aria-hidden="true"></i>
                    </div>
                    <p class="text-xs text-darkbrown-500 leading-snug">Daftar gratis, tanpa biaya berlangganan.<br>Data Anda aman & terenkripsi.</p>
                </div>
            </div>

            {{-- Right: feature list --}}
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3.5 items-stretch">
                @php $custFeats = [
                    ['icon'=>'calendar-check', 'color'=>'avocado',   'title'=>'Booking Online Instan',
                     'desc'=>'Pilih alat, tentukan tanggal, bayar — selesai dalam menit tanpa perlu datang ke toko.'],
                    ['icon'=>'credit-card',    'color'=>'goldenrod',  'title'=>'Bayar via Transfer / QRIS',
                     'desc'=>'Pembayaran lunas atau uang muka (DP 50%) melalui transfer bank, QRIS, atau dompet digital.'],
                    ['icon'=>'map-pin',        'color'=>'goldenrod',   'title'=>'Ambil di Toko atau Dikirim',
                     'desc'=>'Pilih ambil sendiri di outlet vendor (gratis) atau diantar langsung ke lokasi Anda.'],
                    ['icon'=>'activity',       'color'=>'avocado',   'title'=>'Pantau Status Booking',
                     'desc'=>'Cek status pemesanan secara real-time — mulai dari konfirmasi, siap pickup, hingga selesai.'],
                    ['icon'=>'clock',          'color'=>'avocado',  'title'=>'Riwayat Penyewaan Lengkap',
                     'desc'=>'Semua history sewa tersimpan rapi — mudah dilihat kembali untuk booking selanjutnya.'],
                    ['icon'=>'cloud-sun',      'color'=>'goldenrod',   'title'=>'Cek Cuaca Sebelum Sewa',
                     'desc'=>'Lihat prakiraan cuaca di halaman detail produk untuk memilih tanggal camping yang tepat.'],
                ]; @endphp
                @foreach($custFeats as $i => $f)
                    <div data-sr data-sr-delay="{{ $i * 60 }}">
                    <div class="ld-cust-feat h-full">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 mt-0.5"
                             style="background:{{ $f['color']==='goldenrod' ? '#FCF6C5' : ($f['color']==='sunglow' ? '#FEF5DB' : '#E7EDC5') }}">
                            <i data-lucide="{{ $f['icon'] }}" class="w-4 h-4"
                               style="color:{{ $f['color']==='goldenrod' ? '#8E5706' : ($f['color']==='sunglow' ? '#97540B' : '#3F5306') }}"
                               aria-hidden="true"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-darkbrown-900">{{ $f['title'] }}</h4>
                            <p class="text-xs text-darkbrown-500 leading-relaxed mt-0.5">{{ $f['desc'] }}</p>
                        </div>
                    </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
