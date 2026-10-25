<x-layouts.customer>
    <x-slot name="title">
        Tanda Terima Digital KTP - #REC-{{ $rental->booking_code }} - Tendaku
    </x-slot>

    <x-slot name="header">
        <div>
            <nav class="flex flex-wrap items-center gap-2 text-xs text-darkbrown-500 mb-1.5">
                <a href="{{ route('home') }}" class="hover:text-avocado-600 transition">Beranda</a>
                <span class="text-wheat-400">/</span>
                <a href="{{ route('customer.catalog.index') }}" class="hover:text-avocado-600 transition">Katalog</a>
                <span class="text-wheat-400">/</span>
                <a href="{{ route('booking.status', $rental->booking_code) }}" class="hover:text-avocado-600 transition">Status #{{ $rental->booking_code }}</a>
                <span class="text-wheat-400">/</span>
                <span class="text-darkbrown-800 font-semibold">Tanda Terima KTP</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-800 tracking-tight font-heading">
                Tanda terima jaminan KTP
            </h1>
            <p class="text-xs text-darkbrown-600 mt-1">Bukti elektronik penyerahan KTP asli ke toko vendor selama masa sewa.</p>
        </div>
    </x-slot>

    @php
        $customerName    = $rental->isGuest() ? ($rental->guest_name ?? 'Penyewa Tamu') : ($rental->customer?->name ?? 'Pelanggan');
        $customerPhone   = $rental->isGuest() ? ($rental->guest_phone ?? '-') : ($rental->customer?->phone ?? '-');
        $customerNik     = $rental->isGuest() ? ($rental->guest_id_number ?? '-') : ($rental->customer?->nik ?? '-');
        $customerAddress = $rental->isGuest() ? ($rental->delivery_address ?? '-') : ($rental->customer?->address ?? '-');
        $staffName       = $rental->pickedUpBy?->name ?? 'Staf Toko Vendor';

        $ktpStatus = $rental->ktp_collateral_status;
        [$stampClass, $stampText, $stampSub] = match ($ktpStatus) {
            'held'     => ['is-held',     'KTP ditahan',      'Disimpan di brankas toko'],
            'returned' => ['is-returned', 'KTP dikembalikan', 'Jaminan sudah selesai'],
            default    => ['is-none',     'Belum diserahkan', 'Menunggu serah terima'],
        };

        $pickup = \Carbon\Carbon::parse($rental->pickup_date);
        $return = \Carbon\Carbon::parse($rental->return_date);
    @endphp

    <style>
        .rc-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; font-variant-numeric: tabular-nums; }

        .rc-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; }
        .rc-aside, .rc-main { min-width: 0; }
        .rc-main { display: flex; flex-direction: column; gap: 1.25rem; }

        .rc-card { background: #fff; border: 2px solid #f0e3c0; border-radius: 1.5rem; box-shadow: 0 6px 24px -12px rgba(90, 60, 10, .18); }

        /* ---- Banner 4 kolom ---- */
        .rc-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem 1rem; }
        .rc-label { display: block; font-size: .75rem; color: #8a7a62; margin-bottom: .25rem; }
        .rc-value { display: block; font-size: .875rem; font-weight: 700; color: #2b1d0e; line-height: 1.35; }
        .rc-sub { display: block; font-size: .75rem; color: #7a6a52; margin-top: .125rem; }

        .rc-pair { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.25rem; }
        .rc-steps { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }

        @@media (min-width: 768px) {
            .rc-meta { grid-template-columns: 1.55fr 1.1fr 1fr 1.1fr; gap: 0; }
            .rc-cell { position: relative; padding: 0 1.25rem; min-width: 0; }
            .rc-cell:first-child { padding-left: 0; }
            .rc-cell:last-child { padding-right: 0; }
            .rc-cell + .rc-cell::before {
                content: ""; position: absolute; left: 0; top: -.25rem; bottom: -.25rem; width: 1px;
                background: linear-gradient(to bottom, transparent, #cdb98a 50%, transparent);
            }
            .rc-pair { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .rc-steps { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.5rem; }
        }
        @@media (min-width: 1024px) {
            .rc-grid { grid-template-columns: minmax(0, 4fr) minmax(0, 8fr); gap: 2rem; align-items: stretch; }
            .rc-last { flex: 1; }
        }

        /* ---- Panel kiri: stub struk ---- */
        .rc-stub { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
        .rc-stub-top { padding: 1.5rem 1.5rem 1.25rem; }
        .rc-stub-bottom { flex: 1; display: flex; flex-direction: column; padding: 1.25rem 1.5rem 1.5rem; min-height: 20rem; }
        .rc-tear { position: relative; border-top: 2px dashed #e6d7ae; }
        .rc-tear::before, .rc-tear::after {
            content: ""; position: absolute; top: -12px; width: 22px; height: 22px; border-radius: 9999px;
            background: var(--rc-page-bg, #fdfaf4); border: 2px solid #f0e3c0;
        }
        .rc-tear::before { left: -13px; clip-path: inset(0 0 0 50%); }
        .rc-tear::after { right: -13px; clip-path: inset(0 50% 0 0); }

        .rc-photo { position: relative; flex: 1; min-height: 14rem; border-radius: 1rem; overflow: hidden; }
        .rc-photo.has-photo { background: #f6efdd; border: 1px solid #e6d7ae; }
        .rc-photo.no-photo { border: 2px dashed #e6d7ae; background: #fffdf8; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 1rem; }
        .rc-photo img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }

        /* ---- Stempel status ---- */
        .rc-stamp { display: inline-flex; flex-direction: column; align-items: center; padding: .6rem 1.25rem; border-radius: .75rem;
            border: 3px double; transform: rotate(-3deg) translateZ(0); backface-visibility: hidden;
            animation: rc-stamp .55s cubic-bezier(.34, 1.56, .64, 1) .2s both; }
        .rc-stamp b { font-size: 1.125rem; font-weight: 900; letter-spacing: -.01em; line-height: 1.1; }
        .rc-stamp span { font-size: .6875rem; font-weight: 600; margin-top: .25rem; }
        .rc-stamp.is-held     { color: #6b4700; border-color: #c48a12; background: #fdf1cf; }
        .rc-stamp.is-returned { color: #2f5a14; border-color: #5f9a1e; background: #e9f5d7; }
        .rc-stamp.is-none     { color: #5c4a33; border-color: #b9a47c; background: #f8f1e0; border-style: dashed; }
        @@keyframes rc-stamp { from { opacity: 0; transform: rotate(-12deg) scale(1.5); } to { opacity: 1; transform: rotate(-3deg) scale(1); } }

        .rc-rise { animation: rc-rise .6s cubic-bezier(.2, .8, .2, 1) both; }
        @@keyframes rc-rise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

        .rc-num { width: 1.5rem; height: 1.5rem; flex-shrink: 0; border-radius: 9999px; background: #d4a017; color: #fff; font-size: .75rem; font-weight: 800; display: flex; align-items: center; justify-content: center; }

        @@media (prefers-reduced-motion: reduce) { .rc-rise, .rc-stamp { animation: none; } }

        @@media print {
            .no-print { display: none !important; }
            .rc-grid { display: block; }
            .rc-card { box-shadow: none !important; break-inside: avoid; margin-bottom: 1rem; }
            .rc-rise, .rc-stamp { animation: none; }
        }
    </style>

    <div class="py-4 sm:py-6 max-w-6xl mx-auto">
        <div class="rc-grid">

            <!-- ============ KIRI: STUB STRUK ============ -->
            <aside class="rc-aside">
                <div class="rc-card rc-stub rc-rise">
                    <div class="rc-stub-top space-y-5">
                        <div class="flex items-center gap-2.5">
                            <x-application-logo size="md" :withText="false" />
                            <div class="leading-tight">
                                <span class="block text-lg font-black text-darkbrown-900 tracking-tight">TENDA<span class="text-avocado-600">KU</span></span>
                                <span class="block text-[11px] text-darkbrown-500">Outdoor rental platform</span>
                            </div>
                        </div>

                        <div class="flex justify-center">
                            <div class="rc-stamp {{ $stampClass }}">
                                <b>{{ $stampText }}</b>
                                <span>{{ $stampSub }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rc-tear" aria-hidden="true"></div>

                    <div class="rc-stub-bottom">
                        <h4 class="font-bold text-sm text-darkbrown-900">Foto verifikasi KTP</h4>
                        <p class="text-xs text-darkbrown-500 mt-0.5 mb-3">Diambil petugas saat serah terima alat.</p>

                        @if($rental->ktp_collateral_photo_url)
                            <div class="rc-photo has-photo">
                                <img src="{{ route('booking.receipt.photo', $rental->booking_code) }}" alt="Foto verifikasi KTP penyewa">
                                <span class="absolute bottom-2 right-2 inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold bg-avocado-600 text-white shadow-sm">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Terverifikasi
                                </span>
                            </div>
                        @else
                            <div class="rc-photo no-photo">
                                <svg class="w-9 h-9 text-wheat-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <p class="mt-2 text-sm font-semibold text-darkbrown-700">Foto belum diunggah</p>
                                <p class="text-xs text-darkbrown-500 mt-0.5">Petugas toko akan memotretnya saat alat diserahkan.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </aside>

            <!-- ============ KANAN ============ -->
            <div class="rc-main">

                <!-- Tombol aksi -->
                <div class="no-print flex flex-wrap items-center justify-end gap-2.5">
                    <a href="{{ route('booking.status', $rental->booking_code) }}"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-darkbrown-800 bg-white border border-wheat-300 hover:bg-wheat-100 transition shadow-sm">
                        <svg class="w-4 h-4 text-darkbrown-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Kembali
                    </a>
                    <button type="button" onclick="window.print()"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-darkbrown-800 bg-white border border-wheat-300 hover:bg-wheat-100 transition shadow-sm">
                        <svg class="w-4 h-4 text-darkbrown-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        Cetak
                    </button>
                    @if($rental->vendor?->phone)
                        <a href="tel:{{ $rental->vendor->phone }}"
                           class="inline-flex items-center justify-center gap-2 px-6 py-2 rounded-xl text-xs font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 border border-goldenrod-400 shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            Hubungi vendor
                        </a>
                    @endif
                </div>

                <!-- Banner: nomor bukti, masa sewa, serah terima, petugas -->
                <section class="rc-card rc-rise px-6 py-5 sm:px-8 sm:py-6" style="animation-delay:.08s">
                    <div class="rc-meta">
                        <div class="rc-cell">
                            <span class="rc-label">Nomor bukti</span>
                            <span class="rc-mono block text-[15px] font-extrabold text-darkbrown-900 leading-tight whitespace-nowrap">REC-{{ $rental->booking_code }}</span>
                            <span class="rc-sub">Booking <a href="{{ route('booking.status', $rental->booking_code) }}" class="underline underline-offset-2 hover:text-avocado-700">#{{ $rental->booking_code }}</a></span>
                        </div>
                        <div class="rc-cell">
                            <span class="rc-label">Masa sewa</span>
                            <span class="rc-value">{{ $pickup->translatedFormat('d M') }} – {{ $return->translatedFormat('d M Y') }}</span>
                            <span class="rc-sub">{{ $rental->rental_days }} hari</span>
                        </div>
                        <div class="rc-cell">
                            <span class="rc-label">Serah terima KTP</span>
                            @if($rental->ktp_received_at)
                                <span class="rc-value">{{ $rental->ktp_received_at->translatedFormat('d M Y') }}</span>
                                <span class="rc-sub">{{ $rental->ktp_received_at->translatedFormat('H:i') }} WIB</span>
                            @else
                                <span class="rc-value" style="color:#7a6a52;font-weight:600">Belum dilakukan</span>
                                <span class="rc-sub">Saat alat diserahkan</span>
                            @endif
                        </div>
                        <div class="rc-cell">
                            <span class="rc-label">Petugas penerima</span>
                            <span class="rc-value">{{ $staffName }}</span>
                            <span class="rc-sub">{{ $rental->vendor?->name }}</span>
                        </div>
                    </div>
                </section>

                <!-- Penyewa & Vendor -->
                <div class="rc-pair">
                    <section class="rc-card rc-rise p-6 space-y-4" style="animation-delay:.16s">
                        <div class="flex items-center gap-3 pb-3 border-b border-wheat-200">
                            <div class="w-10 h-10 rounded-full bg-avocado-800 text-wheat-100 flex items-center justify-center text-base font-extrabold font-heading shrink-0">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($customerName, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-extrabold text-darkbrown-900 leading-tight">Pemilik KTP</h4>
                                <p class="text-xs text-darkbrown-500">Penyewa</p>
                            </div>
                        </div>
                        <dl class="space-y-3 text-sm">
                            <div><dt class="rc-label" style="margin:0">Nama lengkap</dt><dd class="font-bold text-darkbrown-900">{{ $customerName }}</dd></div>
                            <div><dt class="rc-label" style="margin:0">NIK</dt><dd class="rc-mono font-bold text-darkbrown-900 break-all">{{ $customerNik }}</dd></div>
                            <div><dt class="rc-label" style="margin:0">WhatsApp / HP</dt><dd class="font-bold text-darkbrown-900">{{ $customerPhone }}</dd></div>
                            <div class="pt-3 border-t border-wheat-100"><dt class="rc-label" style="margin:0">Alamat / catatan</dt><dd class="font-medium text-darkbrown-800 leading-relaxed">{{ $customerAddress }}</dd></div>
                        </dl>
                    </section>

                    <section class="rc-card rc-rise p-6 space-y-4" style="animation-delay:.24s">
                        <div class="flex items-center gap-3 pb-3 border-b border-wheat-200">
                            <div class="w-10 h-10 rounded-full bg-goldenrod-100 text-goldenrod-800 border border-goldenrod-300 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-extrabold text-darkbrown-900 leading-tight">Vendor penanggung jawab</h4>
                                <p class="text-xs text-darkbrown-500">Penyimpan KTP selama masa sewa</p>
                            </div>
                        </div>
                        <dl class="space-y-3 text-sm">
                            <div><dt class="rc-label" style="margin:0">Nama toko</dt><dd class="font-bold text-darkbrown-900">{{ $rental->vendor?->name }}</dd></div>
                            <div><dt class="rc-label" style="margin:0">Telepon</dt><dd class="font-bold text-darkbrown-900">{{ $rental->vendor?->phone ?? '-' }}</dd></div>
                            <div><dt class="rc-label" style="margin:0">Kota</dt><dd class="font-bold text-darkbrown-900">{{ $rental->vendor?->city ?? '-' }}</dd></div>
                            <div class="pt-3 border-t border-wheat-100"><dt class="rc-label" style="margin:0">Alamat toko</dt><dd class="font-medium text-darkbrown-800 leading-relaxed">{{ $rental->vendor?->address ?? '-' }}</dd></div>
                        </dl>
                    </section>
                </div>

                <!-- Cara mengambil KTP kembali (mengisi sisa tinggi supaya sejajar dengan panel kiri) -->
                <section class="rc-card rc-rise rc-last p-6" style="animation-delay:.32s">
                    <h4 class="font-extrabold text-darkbrown-900 mb-4">Cara mengambil KTP kembali</h4>
                    <ol class="rc-steps text-sm text-darkbrown-700">
                        <li class="flex gap-3">
                            <span class="rc-num">1</span>
                            <span class="leading-relaxed">KTP asli disimpan di brankas vendor selama masa sewa, <strong>{{ $pickup->translatedFormat('d M Y') }}</strong> sampai <strong>{{ $return->translatedFormat('d M Y') }}</strong>.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="rc-num">2</span>
                            <span class="leading-relaxed">Saat mengembalikan alat, tunjukkan nomor <strong class="rc-mono">REC-{{ $rental->booking_code }}</strong> ke kasir.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="rc-num">3</span>
                            <span class="leading-relaxed">Petugas memeriksa kondisi alat, lalu menyerahkan KTP asli Anda.</span>
                        </li>
                    </ol>

                    @if($rental->ktp_collateral_notes)
                        <div class="mt-4 p-3 rounded-xl bg-wheat-100 border border-wheat-200 text-xs leading-relaxed text-darkbrown-700">
                            <strong class="text-darkbrown-900">Catatan penahanan:</strong> {{ $rental->ktp_collateral_notes }}
                        </div>
                    @endif
                </section>

            </div>
        </div>
    </div>
</x-layouts.customer>
