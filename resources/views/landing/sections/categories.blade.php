<section class="py-12 sm:py-20 lg:py-24 w-full" style="background:#EDEEE8" id="kategori" aria-labelledby="cat-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8 sm:mb-12" data-sr>
            <h2 id="cat-heading" class="font-display text-3xl sm:text-5xl text-darkbrown-900 mt-2">
                Temukan Peralatan<br>yang Tepat untuk Anda
            </h2>
            <p class="text-sm text-darkbrown-600 mt-2.5 max-w-lg mx-auto">Pilih kebutuhan camping Anda berdasarkan kategori terlaris</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
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
                    <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6 lg:p-7 z-10">
                        <h3 class="font-display text-2xl lg:text-3xl text-white leading-tight">{{ $cat['name'] }}</h3>
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
                   class="inline-flex items-center gap-2 px-5 py-2.5 min-h-[44px] rounded-full bg-white border border-wheat-300 text-sm font-semibold text-darkbrown-700 hover:border-avocado-400 hover:text-avocado-700 hover:bg-avocado-50 transition-all duration-200">
                    <i data-lucide="{{ $pill['icon'] }}" class="w-4 h-4" aria-hidden="true"></i>
                    {{ $pill['name'] }}
                </a>
            @endforeach
            <a href="{{ route('customer.catalog.index') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 min-h-[44px] rounded-full text-white text-sm font-bold transition-colors duration-200"
               style="background:#546E06;hover:background:#3F5306">
                Semua Kategori →
            </a>
        </div>
    </div>
</section>
