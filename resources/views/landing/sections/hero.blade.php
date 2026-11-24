<section class="ld-hero" aria-label="Hero Tendaku">
    {{-- HTML5 Ambient Background Video (strictly silent / muted) --}}
    <video autoplay muted loop playsinline aria-hidden="true"
           class="absolute inset-0 w-full h-full object-cover z-0 pointer-events-none"
           id="hero-bg-video"
           poster="{{ asset('images/tendaku-footer-forest.jpg') }}">
        <source src="{{ asset('videos/valley.mp4') }}" type="video/mp4">
    </video>

    {{-- Cinematic Dark Scrim Overlay --}}
    <div class="absolute inset-0 bg-gradient-to-b from-black/80 via-black/45 to-black/90 z-0 pointer-events-none" aria-hidden="true"></div>

    <div class="relative z-10 w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col items-center space-y-6">
        {{-- Main Title --}}
        <h1 class="font-display leading-[1.08] tracking-tight text-white max-w-4xl"
            style="font-size:clamp(2.5rem,5.5vw,4.5rem)"
            data-sr data-sr-delay="80">
            Petualangan Nyata,<br>
            <span style="color:#FBCE6B">Perlengkapan Sempurna.</span>
        </h1>

        {{-- Description --}}
        <p class="text-base sm:text-lg leading-relaxed max-w-2xl text-wheat-200 font-normal" data-sr data-sr-delay="160">
            Sewa tenda dome, sleeping bag hangat, kompor portabel, hingga carrier tanpa ribet. Higienis, terawat, dan jaminan KTP fisik tanpa perlu deposit uang tunai.
        </p>

        {{-- Interactive Search & Filter Bar --}}
        <form action="{{ route('customer.catalog.index') }}" method="GET"
              class="w-full max-w-3xl bg-white/95 backdrop-blur-md p-2 sm:p-2.5 rounded-2xl sm:rounded-full shadow-2xl border border-white/60 flex flex-col sm:flex-row items-center gap-2 mt-2"
              data-sr data-sr-delay="240">
            <div class="flex-1 flex items-center gap-2.5 px-3 py-2 w-full">
                <i data-lucide="search" class="w-4 h-4 text-darkbrown-400 shrink-0"></i>
                <input type="text" name="search" placeholder="Cari tenda dome, nesting, carrier..." aria-label="Cari alat camping"
                       class="w-full bg-transparent border-none text-darkbrown-900 text-sm placeholder:text-darkbrown-400 focus:outline-none focus:ring-0 p-0 font-medium">
            </div>
            <div class="hidden sm:block h-6 w-px bg-wheat-300"></div>
            <div class="flex items-center gap-2 px-3 py-2 w-full sm:w-auto">
                <i data-lucide="tag" class="w-4 h-4 text-darkbrown-400 shrink-0"></i>
                <select name="category_id" aria-label="Pilih kategori" class="bg-transparent border-none text-darkbrown-800 text-sm focus:outline-none focus:ring-0 p-0 pr-6 font-semibold cursor-pointer">
                    <option value="">Semua Kategori</option>
                    <option value="1">Tenda & Flysheet</option>
                    <option value="2">Sleeping Bag & Matras</option>
                    <option value="3">Masak & Nesting</option>
                    <option value="4">Carrier & Ransel</option>
                    <option value="5">Lampu & Headlamp</option>
                    <option value="6">Meja & Kursi Lipat</option>
                </select>
            </div>
            <button type="submit"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl sm:rounded-full text-white text-sm font-bold shadow-md hover:shadow-lg transition-all duration-200 shrink-0 cursor-pointer"
                    style="background:#3F5306">
                <i data-lucide="compass" class="w-4 h-4"></i>
                <span>Cari Alat</span>
            </button>
        </form>

        {{-- Trust badges row --}}
        <div class="flex flex-wrap items-center justify-center gap-3 pt-1 text-xs text-wheat-200" data-sr data-sr-delay="320">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-sm border border-white/15">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-sunglow-300"></i>
                Deposit Rp 0
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-sm border border-white/15">
                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-sunglow-300"></i>
                100% Steril & Bersih
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-sm border border-white/15">
                <i data-lucide="zap" class="w-3.5 h-3.5 text-sunglow-300"></i>
                Booking Instan
            </span>
            <a href="#ktp-jaminan" class="ld-btn-ul text-xs ml-1">
                Pelajari Jaminan KTP ↓
            </a>
        </div>

        {{-- Docked Stats Strip --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-8 w-full pt-8 mt-4 border-t border-white/15"
             data-sr data-sr-delay="400">
            @php $heroStats = [
                ['num'=>100,'suffix'=>'+', 'decimal'=>0,'label'=>'Unit Siap Sewa'],
                ['num'=>100,'suffix'=>'%', 'decimal'=>0,'label'=>'Steril & Bersih'],
                ['num'=>24, 'suffix'=>' Jam','decimal'=>0,'label'=>'Respons Cepat'],
                ['num'=>49, 'suffix'=>' ★','decimal'=>1,'label'=>'Rating Camper'],
            ]; @endphp
            @foreach($heroStats as $s)
                <div class="text-center">
                    <span class="font-display text-2xl sm:text-3xl font-bold block" style="color:#FBCE6B;font-variant-numeric:tabular-nums"
                          data-counter="{{ $s['num'] }}" data-suffix="{{ $s['suffix'] }}" data-decimal="{{ $s['decimal'] }}">0{{ $s['suffix'] }}</span>
                    <span class="text-xs mt-0.5 block" style="color:#F6ECCF">{{ $s['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>
