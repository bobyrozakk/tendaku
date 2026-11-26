<section class="py-20 sm:py-24 w-full bg-[#FDFBF7]" id="keunggulan" aria-labelledby="feat-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12" data-sr>
            <span class="text-xs font-bold uppercase tracking-[.18em] text-goldenrod-600">Mengapa Tendaku?</span>
            <h2 id="feat-heading" class="font-display text-3xl sm:text-5xl text-darkbrown-900 mt-2">Alam Terbaik, Untuk Anda</h2>
            <p class="text-sm text-darkbrown-500 mt-3 max-w-lg mx-auto leading-relaxed">Dirancang untuk memudahkan camper Indonesia mendapatkan peralatan outdoor terawat tanpa ribet.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 max-w-4xl mx-auto">
            @php $feats = [
                ['icon'=>'sparkles',    'title'=>'Steril & Higienis', 'desc'=>'Setiap unit dicuci, disinfeksi, dan dikemas profesional pasca penyewaan.'],
                ['icon'=>'credit-card', 'title'=>'Jaminan KTP Saja',  'desc'=>'Tanpa deposit uang. KTP asli sebagai jaminan saat pengambilan unit di toko.'],
                ['icon'=>'zap',         'title'=>'Booking Instan',    'desc'=>'Pilih alat, tentukan tanggal, bayar online, dan ambil unit di vendor terdekat.'],
            ]; @endphp
            @foreach($feats as $i => $f)
                <div class="ld-feat-card text-center px-4" data-sr data-sr-delay="{{ $i * 130 }}">
                    <div class="ld-feat-circle text-avocado-700">
                        <i data-lucide="{{ $f['icon'] }}" class="w-6 h-6" aria-hidden="true"></i>
                    </div>
                    <h3 class="font-bold text-darkbrown-900 text-base">{{ $f['title'] }}</h3>
                    <p class="text-sm text-darkbrown-500 leading-relaxed mt-2">{{ $f['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
