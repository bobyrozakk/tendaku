<x-layouts.customer :full-width="true">
    <x-slot name="title">Tendaku — Sewa Alat Camping, Tanpa Ribet</x-slot>

    <style>
        /* ── Scroll-reveal ──────────────────────────────────────────────── */
        [data-sr]{opacity:0;transform:translateY(28px);transition:opacity .65s cubic-bezier(.22,.61,.36,1),transform .65s cubic-bezier(.22,.61,.36,1)}
        [data-sr].sr-visible{opacity:1;transform:translateY(0)}
        @media(prefers-reduced-motion:reduce){[data-sr]{opacity:1;transform:none;transition:none}}

        /* ── Anchor offset below sticky header ──────────────────────────── */
        section[id]{scroll-margin-top:8rem}
        @media (min-width:640px){section[id]{scroll-margin-top:6.5rem}}

        /* ── Hero ───────────────────────────────────────────────────────── */
        .ld-hero{
            min-height:88vh;width:100%;display:flex;flex-direction:column;justify-content:center;align-items:center;
            padding:5.5rem 1.5rem 4.5rem;
            position:relative;overflow:hidden;
            background-color:#1B1501;
            text-align:center;
        }
        /* ── Underline btn ──────────────────────────────────────────────── */
        .ld-btn-ul{position:relative;display:inline-flex;align-items:center;gap:.5rem;font-weight:600;text-decoration:none;color:#F9E3B6}
        .ld-btn-ul::after{content:'';position:absolute;bottom:-2px;left:0;width:0;height:1.5px;background:#F9E3B6;transition:width .3s ease}
        .ld-btn-ul:hover::after{width:100%}

        /* ── Category cards ─────────────────────────────────────────────── */
        .ld-cat-card{position:relative;overflow:hidden;border-radius:1.25rem;aspect-ratio:3/4;display:block}
        .ld-cat-card img{width:100%;height:100%;object-fit:cover;transition:transform .7s ease}
        .ld-cat-card:hover img{transform:scale(1.06)}
        .ld-cat-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.62) 0%,rgba(0,0,0,.12) 60%,transparent 100%);transition:background .4s}
        .ld-cat-card:hover .ld-cat-overlay{background:linear-gradient(to top,rgba(0,0,0,.78) 0%,rgba(0,0,0,.2) 60%,transparent 100%)}
        .ld-cat-cta{transform:translateY(14px);opacity:0;transition:transform .32s ease,opacity .32s ease}
        .ld-cat-card:hover .ld-cat-cta{transform:translateY(0);opacity:1}

        /* ── Product cards ──────────────────────────────────────────────── */
        .ld-prod-wrap{aspect-ratio:3/4;overflow:hidden;border-radius:1rem;position:relative;background:#F4F4EE}
        .ld-prod-wrap img{width:100%;height:100%;object-fit:cover;transition:transform .6s ease}
        .ld-prod-card:hover .ld-prod-wrap img{transform:scale(1.05)}
        .ld-sewa-btn{
            position:absolute;bottom:0;left:0;right:0;padding:.75rem 1rem;
            background:rgba(255,255,255,.88);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
            border-radius:0 0 1rem 1rem;transform:translateY(100%);
            transition:transform .32s cubic-bezier(.22,.61,.36,1);
            text-align:center;font-size:.8rem;font-weight:700;color:#3F5306;
        }
        .ld-prod-card:hover .ld-sewa-btn{transform:translateY(0)}

        /* ── Feature icon circles ───────────────────────────────────────── */
        .ld-feat-circle{width:4rem;height:4rem;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;box-shadow:0 2px 12px rgba(43,34,2,.09);transition:box-shadow .25s,transform .25s}
        .ld-feat-card:hover .ld-feat-circle{box-shadow:0 4px 20px rgba(108,139,8,.22);transform:translateY(-3px)}

        /* ── Customer feature list items ────────────────────────────────── */
        .ld-cust-feat{display:flex;align-items:flex-start;gap:.875rem;padding:.875rem 1rem;border-radius:.875rem;background:#fff;border:1px solid rgba(108,139,8,.14);transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease}
        .ld-cust-feat:hover{transform:translateX(5px);border-color:rgba(108,139,8,.32);box-shadow:0 4px 16px rgba(108,139,8,.1)}

        /* ── Vendor benefit cards ───────────────────────────────────────── */
        .ld-vend-card{padding:1.5rem;border-radius:1.125rem;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);backdrop-filter:blur(6px);transition:background .25s,border-color .25s,transform .25s}
        .ld-vend-card:hover{background:rgba(255,255,255,.12);border-color:rgba(251,206,107,.35);transform:translateY(-3px)}

        /* ── Testimonial glassmorphism ───────────────────────────────────── */
        .ld-testi-card{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:1.25rem;backdrop-filter:blur(6px)}

        /* ── KTP step rows ──────────────────────────────────────────────── */
        .ld-ktp-step{transition:transform .2s ease,background .25s ease,border-color .25s ease,box-shadow .25s ease}
        .ld-ktp-step:hover{transform:translateX(5px);background:rgba(108,139,8,.06);border-color:rgba(108,139,8,.22);box-shadow:0 2px 10px rgba(108,139,8,.1)}
    </style>

    {{-- ════════════════════════════════════════════════════════════
         HERO — Full Bleed Ambient Video
         ════════════════════════════════════════════════════════════ --}}
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
            {{-- Eyebrow badge --}}
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full border border-sunglow-300/30 bg-black/40 backdrop-blur-md" data-sr data-sr-delay="0">
                <span class="w-2 h-2 rounded-full bg-sunglow-400 animate-pulse" aria-hidden="true"></span>
                <span class="text-xs font-bold uppercase tracking-[.2em] text-sunglow-300">Platform Persewaan Camping #1</span>
            </div>

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
                    Deposit Rp 0 (KTP)
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

    {{-- ════════════════════════════════════════════════════════════
         SHOP BY CATEGORY — Full Width Band
         ════════════════════════════════════════════════════════════ --}}
    <section class="py-20 sm:py-24 w-full" style="background:#EDEEE8" id="kategori" aria-labelledby="cat-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" data-sr>
                <span class="text-xs font-bold uppercase tracking-[.18em] text-avocado-700">Belanja Berdasarkan Kategori</span>
                <h2 id="cat-heading" class="font-display text-3xl sm:text-5xl text-darkbrown-900 mt-2">
                    Temukan Peralatan<br>yang Tepat untuk Anda
                </h2>
                <p class="text-sm text-darkbrown-600 mt-2.5 max-w-lg mx-auto">Pilih kebutuhan camping Anda berdasarkan kategori terlaris</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                @php $categories = [
                    ['id'=>1,'name'=>'Tenda & Flysheet',  'sub'=>'Dome, tunnel, dan flysheet premium',
                     'img'=>'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80'],
                    ['id'=>2,'name'=>'Sleeping & Matras', 'sub'=>'Tidur nyenyak di alam bebas',
                     'img'=>'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=800&q=80'],
                    ['id'=>3,'name'=>'Masak & Carrier',   'sub'=>'Kompor, nesting, dan carrier ransel',
                     'img'=>'https://images.unsplash.com/photo-1526772662000-3f88f10405ff?auto=format&fit=crop&w=800&q=80'],
                ]; @endphp
                @foreach($categories as $i => $cat)
                    <a href="{{ route('customer.catalog.index', ['category_id' => $cat['id']]) }}"
                       class="ld-cat-card"
                       data-sr data-sr-delay="{{ $i * 120 }}"
                       aria-label="Kategori {{ $cat['name'] }}">
                        <img src="{{ $cat['img'] }}" alt="{{ $cat['name'] }}" loading="{{ $i===0?'eager':'lazy' }}" width="800" height="1067">
                        <div class="ld-cat-overlay"></div>
                        <div class="absolute inset-x-0 bottom-0 p-6 sm:p-7 z-10">
                            <h3 class="font-display text-2xl sm:text-3xl text-white leading-tight">{{ $cat['name'] }}</h3>
                            <p class="text-sm mt-1" style="color:#D9C9A0">{{ $cat['sub'] }}</p>
                            <div class="ld-cat-cta mt-4 inline-flex items-center gap-2 text-xs font-bold text-white px-4 py-2 rounded-full"
                                 style="border:1px solid rgba(255,255,255,.5)">
                                Lihat Koleksi
                                <i data-lucide="arrow-right" class="w-3 h-3" aria-hidden="true"></i>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8 flex flex-wrap justify-center gap-3" data-sr data-sr-delay="420">
                @php $pills = [
                    ['icon'=>'backpack',   'name'=>'Carrier & Ransel',   'id'=>4],
                    ['icon'=>'flashlight', 'name'=>'Lampu & Headlamp',   'id'=>5],
                    ['icon'=>'armchair',   'name'=>'Meja & Kursi Lipat', 'id'=>6],
                ]; @endphp
                @foreach($pills as $pill)
                    <a href="{{ route('customer.catalog.index', ['category_id' => $pill['id']]) }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-white border border-wheat-300 text-sm font-semibold text-darkbrown-700 hover:border-avocado-400 hover:text-avocado-700 hover:bg-avocado-50 transition-all duration-200">
                        <i data-lucide="{{ $pill['icon'] }}" class="w-4 h-4" aria-hidden="true"></i>
                        {{ $pill['name'] }}
                    </a>
                @endforeach
                <a href="{{ route('customer.catalog.index') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-white text-sm font-bold transition-colors duration-200"
                   style="background:#546E06;hover:background:#3F5306">
                    Semua Kategori →
                </a>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════════════
         BESTSELLERS — Full Width Band
         ════════════════════════════════════════════════════════════ --}}
    <section class="py-20 sm:py-24 w-full bg-[#FDFBF7]" id="shop" aria-labelledby="shop-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-10" data-sr>
                <div>
                    <span class="text-xs font-bold uppercase tracking-[.18em] text-avocado-600">Paling Sering Disewa</span>
                    <h2 id="shop-heading" class="font-display text-3xl sm:text-4xl text-darkbrown-900 mt-1">Peralatan Terpopuler</h2>
                </div>
                <a href="{{ route('customer.catalog.index') }}"
                   class="text-sm font-semibold text-darkbrown-700 hover:text-avocado-700 flex items-center gap-1 transition-colors shrink-0">
                    Lihat Semua Produk →
                </a>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between mb-6">
                <form method="GET" action="{{ route('home') }}#shop" class="flex flex-wrap items-end gap-3">
                    <label class="text-xs font-semibold text-darkbrown-700">
                        Mulai sewa
                        <input type="date" name="start_date" value="{{ request('start_date', $dateRange['start_date'] ?? '') }}" min="{{ now()->toDateString() }}" class="input-tendaku block" required />
                    </label>
                    <label class="text-xs font-semibold text-darkbrown-700">
                        Selesai sewa
                        <input type="date" name="end_date" value="{{ request('end_date', $dateRange['end_date'] ?? '') }}" min="{{ now()->toDateString() }}" class="input-tendaku block" required />
                    </label>
                    <button type="submit" class="btn-tendaku-primary text-xs">Cek Ketersediaan</button>
                </form>
                <x-stock-period :date-range="$dateRange" />
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
                @forelse($popularItems as $i => $product)
                    @php
                        $detailUrl = route('customer.product.show', ['id' => $product->id] + ($dateRange ?? request()->only('start_date', 'end_date')));
                    @endphp
                    <div class="ld-prod-card group" data-sr data-sr-delay="{{ $i * 90 }}">
                        <a href="{{ $detailUrl }}" aria-label="Lihat detail {{ $product->name }}">
                            <div class="ld-prod-wrap">
                                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-1.5 text-darkbrown-400" aria-hidden="true">
                                    <i data-lucide="image" class="w-8 h-8"></i>
                                    <span class="text-[11px] font-semibold">Foto belum tersedia</span>
                                </div>
                                <img src="{{ $product->image_url ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80' }}" alt="{{ $product->name }}" loading="lazy" width="600" height="800" class="relative" onerror="this.style.display='none'">
                                <div class="absolute top-3 left-3 z-10 max-w-[76%]">
                                    <x-stock-status :stock="$dateStockMap[$product->id]" />
                                </div>
                                <div class="ld-sewa-btn">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="shopping-bag" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                        Sewa Sekarang
                                    </span>
                                </div>
                            </div>
                        </a>
                        <div class="mt-3 px-0.5">
                            <span class="text-[11px] font-semibold text-avocado-700 flex items-center gap-1">
                                <i data-lucide="store" class="w-3 h-3" aria-hidden="true"></i>
                                {{ $product->vendor?->name }}
                            </span>
                            <a href="{{ $detailUrl }}"
                               class="text-sm font-bold text-darkbrown-800 hover:text-avocado-700 transition-colors duration-200 block leading-snug mt-1">
                                {{ $product->name }}
                            </a>
                            <span class="text-sm font-extrabold text-darkbrown-900 tabular-nums mt-1 block">
                                Rp {{ number_format($product->daily_rate, 0, ',', '.') }}<span class="text-xs font-normal text-darkbrown-400">/hari</span>
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="col-span-full text-sm text-darkbrown-600">Belum ada peralatan yang ditampilkan.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════════════
         CUSTOMER FEATURES — Fitur setelah login/register
         ════════════════════════════════════════════════════════════ --}}
    <section class="py-20 sm:py-24 w-full" style="background:#F4F7F2" id="fitur-akun" aria-labelledby="cust-feat-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-start gap-14 lg:gap-20">
                {{-- Left: heading + CTA --}}
                <div class="lg:w-[38%] shrink-0 space-y-6" data-sr>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-[.18em] text-avocado-600">Untuk Penyewa</span>
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
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    @php $custFeats = [
                        ['icon'=>'calendar-check', 'color'=>'avocado',   'title'=>'Booking Online Instan',
                         'desc'=>'Pilih alat, tentukan tanggal, bayar — selesai dalam menit tanpa perlu datang ke toko.'],
                        ['icon'=>'credit-card',    'color'=>'goldenrod',  'title'=>'Bayar via Transfer / QRIS',
                         'desc'=>'Pembayaran lunas atau uang muka (DP 50%) melalui transfer bank, QRIS, atau dompet digital.'],
                        ['icon'=>'map-pin',        'color'=>'avocado',   'title'=>'Ambil di Toko atau Dikirim',
                         'desc'=>'Pilih ambil sendiri di outlet vendor (gratis) atau diantar langsung ke lokasi Anda.'],
                        ['icon'=>'activity',       'color'=>'sunglow',   'title'=>'Pantau Status Booking',
                         'desc'=>'Cek status pemesanan secara real-time — mulai dari konfirmasi, siap pickup, hingga selesai.'],
                        ['icon'=>'message-circle', 'color'=>'avocado',   'title'=>'Notifikasi WhatsApp',
                         'desc'=>'Notifikasi jadwal pickup, pengingat pengembalian, dan konfirmasi booking langsung via WA.'],
                        ['icon'=>'clock',          'color'=>'goldenrod',  'title'=>'Riwayat Penyewaan Lengkap',
                         'desc'=>'Semua history sewa tersimpan rapi — mudah dilihat kembali untuk booking selanjutnya.'],
                        ['icon'=>'cloud-sun',      'color'=>'avocado',   'title'=>'Cek Cuaca Sebelum Sewa',
                         'desc'=>'Lihat prakiraan cuaca di halaman detail produk untuk memilih tanggal camping yang tepat.'],
                        ['icon'=>'shield-check',   'color'=>'avocado',   'title'=>'Jaminan KTP Tanpa Deposit',
                         'desc'=>'Tidak perlu siapkan deposit uang — cukup tunjukkan KTP asli saat ambil unit di toko.'],
                    ]; @endphp
                    @foreach($custFeats as $i => $f)
                        <div class="ld-cust-feat" data-sr data-sr-delay="{{ $i * 60 }}">
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
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════════════
         WHY TENDAKU — Full Width Band (3 icon circles)
         ════════════════════════════════════════════════════════════ --}}
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

    {{-- ════════════════════════════════════════════════════════════
         KTP JAMINAN — Full Width Band (Split layout)
         ════════════════════════════════════════════════════════════ --}}
    <section class="py-20 sm:py-24 w-full" style="background:#F4F7F2" id="ktp-jaminan" aria-labelledby="ktp-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-20">
                <div class="w-full lg:w-[42%] shrink-0" data-sr>
                    <div class="relative overflow-hidden shadow-2xl group" style="aspect-ratio:4/5;border-radius:1.5rem">
                        <img src="{{ asset('images/superadmin-bg.jpg') }}"
                             alt="Pemandangan Camping Tendaku"
                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                             loading="lazy" width="682" height="1024"
                             onerror="this.src='{{ asset('images/tendaku-footer-forest.jpg') }}'">
                        {{-- Gradient Scrim & Floating Badge --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent pointer-events-none"></div>
                        <div class="absolute bottom-6 left-6 right-6 text-white space-y-2 pointer-events-none">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-avocado-600/90 backdrop-blur-md text-xs font-bold text-white shadow-sm">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                Deposit Rp 0 • Jaminan KTP Asli
                            </span>
                            <h4 class="font-display text-xl text-white font-bold leading-tight">Camping Aman & Bebas Cemas</h4>
                            <p class="text-xs text-wheat-200 leading-relaxed">KTP fisik Anda disimpan aman oleh mitra vendor dan langsung dikembalikan saat serah terima pengembalian alat.</p>
                        </div>
                    </div>
                </div>
                <div class="flex-1 space-y-6">
                    <div data-sr data-sr-delay="100">
                        <span class="text-xs font-bold uppercase tracking-[.18em] text-avocado-600">Sistem Jaminan Tendaku</span>
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

    {{-- ════════════════════════════════════════════════════════════
         VENDOR BENEFITS — Full Width Band (Keuntungan daftar sebagai vendor)
         ════════════════════════════════════════════════════════════ --}}
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

    {{-- ════════════════════════════════════════════════════════════
         TESTIMONIALS — Full Width Band (Dark green glassmorphism)
         ════════════════════════════════════════════════════════════ --}}
    <section class="py-20 sm:py-24 w-full" style="background:#1B1501" id="testimoni" aria-labelledby="testi-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" data-sr>
                <span class="text-xs font-bold uppercase tracking-[.18em]" style="color:#90B123">Dari Para Camper</span>
                <h2 id="testi-heading" class="font-display text-3xl sm:text-5xl mt-2" style="color:#FAF6ED">
                    Dipercaya Komunitas Kami
                </h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 max-w-5xl mx-auto">
                @php $testis = [
                    ['name'=>'Raka Pratama',    'role'=>'Pendaki Semeru',       'stars'=>5,
                     'quote'=>'Tenda kondisinya bersih banget, nggak kalah sama beli baru. Proses booking cepat dan pickup mudah. Pasti balik lagi!'],
                    ['name'=>'Sari Dewi',       'role'=>'Solo Hiker, Bromo',    'stars'=>5,
                     'quote'=>'Sistem KTP-nya bikin tenang, nggak perlu deposit cash. Sleeping bag-nya juga hangat banget buat suhu Bromo. Recommended!'],
                    ['name'=>'Dimas Kurniawan', 'role'=>'Group Camping, Rinjani','stars'=>5,
                     'quote'=>'Sewa 4 tenda sekaligus, semuanya lengkap dan terawat. Vendor ramah dan responsif. Tendaku jadi pilihan wajib tim kita.'],
                ]; @endphp
                @foreach($testis as $i => $t)
                    <div class="ld-testi-card p-7 space-y-4" data-sr data-sr-delay="{{ $i * 120 }}">
                        <div class="flex gap-1" aria-label="{{ $t['stars'] }} bintang">
                            @for($s=0;$s<$t['stars'];$s++)
                                <i data-lucide="star" class="w-4 h-4" style="fill:#6C8B08;color:#6C8B08" aria-hidden="true"></i>
                            @endfor
                        </div>
                        <p class="text-sm leading-relaxed italic" style="color:#D9C9A0">"{{ $t['quote'] }}"</p>
                        <div class="pt-3" style="border-top:1px solid rgba(255,255,255,.1)">
                            <div class="font-bold text-sm" style="color:#FAF6ED">{{ $t['name'] }}</div>
                            <div class="text-xs mt-0.5" style="color:#7A9A30">{{ $t['role'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════════════
         FINAL CTA — Full Width Band (Dual CTA: customer + vendor)
         ════════════════════════════════════════════════════════════ --}}
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

    @push('scripts')
    <script>
    (function(){
        'use strict';
        const rm = window.matchMedia('(prefers-reduced-motion:reduce)').matches;

        // 1. Scroll reveal
        (function(){
            const els = document.querySelectorAll('[data-sr]');
            if(!els.length) return;
            if(rm){ els.forEach(el=>el.classList.add('sr-visible')); return; }
            els.forEach(el=>{
                const d = parseInt(el.getAttribute('data-sr-delay')||'0',10);
                el.style.transitionDelay = d+'ms';
            });
            const obs = new IntersectionObserver(entries=>{
                entries.forEach(e=>{ if(!e.isIntersecting) return; e.target.classList.add('sr-visible'); obs.unobserve(e.target); });
            },{threshold:.07,rootMargin:'0px 0px -40px 0px'});
            els.forEach(el=>obs.observe(el));
        })();

        // 2. Counter animation
        (function(){
            const els = document.querySelectorAll('[data-counter]');
            if(!els.length) return;
            function run(el){
                const target=parseInt(el.dataset.counter,10),suffix=el.dataset.suffix||'',decimal=parseInt(el.dataset.decimal||'0',10);
                const dur=1300,t0=performance.now();
                (function tick(now){
                    const p=Math.min((now-t0)/dur,1),e=1-Math.pow(1-p,3),v=target*e;
                    el.textContent=(decimal>0?v.toFixed(decimal):Math.floor(v))+suffix;
                    if(p<1) requestAnimationFrame(tick);
                    else el.textContent=(decimal>0?target.toFixed(decimal):target)+suffix;
                })(t0);
            }
            if(rm){
                els.forEach(el=>{ const t=parseInt(el.dataset.counter,10),s=el.dataset.suffix||'',d=parseInt(el.dataset.decimal||'0',10); el.textContent=(d>0?t.toFixed(d):t)+s; });
                return;
            }
            const obs=new IntersectionObserver(entries=>{ entries.forEach(e=>{ if(!e.isIntersecting) return; run(e.target); obs.unobserve(e.target); }); },{threshold:.5});
            els.forEach(el=>obs.observe(el));
        })();

        // 3. Product card hover lift
        if(!rm){
            document.querySelectorAll('.ld-prod-card').forEach(c=>{
                c.style.transition='transform .25s ease,box-shadow .25s ease';
                c.addEventListener('mouseenter',()=>{ c.style.transform='translateY(-3px)'; c.style.boxShadow='0 10px 28px -4px rgba(43,34,2,.12)'; });
                c.addEventListener('mouseleave',()=>{ c.style.transform=''; c.style.boxShadow=''; });
            });
        }

        // 4. Ensure hero video is strictly muted and respects reduced motion
        const heroVideo = document.getElementById('hero-bg-video');
        if (heroVideo) {
            heroVideo.muted = true;
            heroVideo.volume = 0;

            if (rm) {
                heroVideo.pause();
            } else if (heroVideo.paused) {
                const playAttempt = heroVideo.play();
                if (playAttempt && typeof playAttempt.catch === 'function') {
                    playAttempt.catch(() => {});
                }
            }
        }

        // 5. Re-init lucide icons
        if(window.lucide && typeof window.lucide.createIcons==='function'){
            window.lucide.createIcons();
        }
    })();
    </script>
    @endpush

</x-layouts.customer>