{{-- Sidebar aktivitas dan informasi vendor. Variabel berasal dari view induk. --}}
<aside class="status-right space-y-5">
    {{-- Card 1: Riwayat Aktivitas --}}
    <section class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm overflow-hidden">
        <h3 class="px-5 py-4 font-extrabold text-darkbrown-900 font-heading border-b border-wheat-200">Aktivitas</h3>
        <ul class="divide-y divide-wheat-200 max-h-[28rem] overflow-y-auto">
            <template x-for="(a, i) in activities" :key="a.title + a.time + i">
                <li class="flex items-start gap-3 px-5 py-3.5 border-l-4"
                    :class="[i === 0 ? 'border-avocado-500 bg-avocado-50/60' : 'border-transparent', a.fresh ? 'act-in' : '']">
                    <svg class="w-4 h-4 mt-1 shrink-0" :class="toneCls(a.tone)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" :d="iconPath[a.type]" /></svg>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold text-darkbrown-900 leading-snug" x-text="a.title"></div>
                        <div x-show="a.sub" class="text-sm text-darkbrown-600" x-text="a.sub"></div>
                        <div class="text-xs text-darkbrown-500 mt-0.5" x-text="a.time"></div>
                    </div>
                </li>
            </template>
        </ul>
    </section>

    {{-- Card 2: Informasi Vendor & Pemesanan (Disatukan menjadi 1 kartu) --}}
    @php
        $vendorName = $rental->vendor?->name ?? 'Tendaku Partner';
        $vendorInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($vendorName, 0, 1));
        $vendorPhoneRaw = preg_replace('/\D+/', '', $rental->vendor?->phone ?? '');
        if (str_starts_with($vendorPhoneRaw, '0')) {
            $vendorPhoneWa = '62' . substr($vendorPhoneRaw, 1);
        } else {
            $vendorPhoneWa = $vendorPhoneRaw;
        }
    @endphp

    <section class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm overflow-hidden">
        {{-- Header Profil Vendor --}}
        <div class="p-5 text-center border-b border-wheat-200 bg-wheat-50/50">
            <div class="mx-auto w-16 h-16 rounded-full bg-avocado-800 text-wheat-100 flex items-center justify-center text-2xl font-extrabold font-heading shadow-sm">
                {{ $vendorInitial }}
            </div>
            <h2 class="mt-3 font-extrabold text-base text-darkbrown-900 leading-tight">{{ $vendorName }}</h2>
            <p class="text-xs font-semibold text-avocado-700 mt-0.5">Vendor Penyedia Alat</p>

            @if($vendorPhoneWa)
                <a href="https://wa.me/{{ $vendorPhoneWa }}" target="_blank" rel="noopener noreferrer"
                   class="mt-3.5 inline-flex items-center justify-center gap-2 w-full py-2 px-4 rounded-xl text-xs font-bold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 border border-goldenrod-400 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    Hubungi Vendor via WA
                </a>
            @endif
        </div>

        {{-- Detail Informasi Sewa & Vendor --}}
        <div class="p-5 space-y-4 text-sm divide-y divide-wheat-100">
            {{-- Alamat Vendor --}}
            <div class="flex items-start gap-3 pt-1">
                <svg class="w-4 h-4 text-darkbrown-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                <div class="min-w-0">
                    <div class="text-xs text-darkbrown-500">Alamat Outlet</div>
                    <div class="text-xs text-darkbrown-700 mt-0.5 leading-relaxed">{{ $rental->vendor?->address }}{{ $rental->vendor?->city ? ', ' . $rental->vendor->city : '' }}</div>
                </div>
            </div>

            {{-- Cara Menerima Alat --}}
            <div class="flex items-start gap-3 pt-3">
                <svg class="w-4 h-4 text-darkbrown-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v9H3zM14 10h4l3 3v3h-7z" /><circle cx="7" cy="17.5" r="1.8" /><circle cx="17" cy="17.5" r="1.8" /></svg>
                <div>
                    <div class="text-xs text-darkbrown-500">Cara Menerima Alat</div>
                    <div class="font-semibold text-darkbrown-900">{{ $rental->pickup_method === 'delivery' ? 'Diantar kurir vendor' : 'Ambil di toko' }}</div>
                </div>
            </div>

            {{-- Durasi Sewa --}}
            <div class="flex items-start gap-3 pt-3">
                <svg class="w-4 h-4 text-darkbrown-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <div>
                    <div class="text-xs text-darkbrown-500">Durasi Sewa</div>
                    <div class="font-semibold text-darkbrown-900">{{ $rental->rental_days }} hari</div>
                </div>
            </div>

            {{-- Identitas Penyewa --}}
            <div class="flex items-start gap-3 pt-3">
                <svg class="w-4 h-4 text-darkbrown-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <div class="min-w-0">
                    <div class="text-xs text-darkbrown-500">Penyewa</div>
                    <div class="font-semibold text-darkbrown-900">{{ $renterName }}</div>
                    @if($renterPhone)
                        <div class="text-xs text-darkbrown-600">{{ $renterPhone }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Card Bantuan Singkat --}}
    <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm px-5 py-4 text-sm">
        <div class="font-bold text-darkbrown-900">Butuh bantuan?</div>
        <div class="text-xs text-darkbrown-500 mt-0.5">Hubungi vendor untuk kendala serah terima atau jadwal.</div>
    </div>
</aside>
