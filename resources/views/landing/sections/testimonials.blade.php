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
