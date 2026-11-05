
<div class="card-tendaku-warm rounded-2xl border
            border-wheat-300 p-8 text-center sm:p-12">

    <div class="mx-auto mb-4 flex h-16 w-16 items-center
                justify-center rounded-full bg-wheat-200
                text-darkbrown-700">
        <i data-lucide="tent"
           class="h-8 w-8"
           aria-hidden="true"></i>
    </div>

    @if ($status !== '')
        <h2 class="font-heading text-lg font-bold
                   text-darkbrown-900">
            Belum Ada Transaksi di Status Ini
        </h2>

        <p class="mx-auto mt-2 max-w-md text-sm
                  leading-relaxed text-darkbrown-600">
            Belum ada pesanan dengan status yang kamu pilih.
            Coba lihat status lainnya untuk menemukan transaksi
            yang kamu cari.
        </p>

        <a href="{{ route('customer.booking.index') }}"
           class="mt-6 inline-flex items-center justify-center
                  gap-2 rounded-xl border border-wheat-300
                  bg-white px-5 py-2.5 text-sm font-bold
                  text-darkbrown-800 transition hover:bg-wheat-100">
            <i data-lucide="arrow-left"
               class="h-4 w-4"
               aria-hidden="true"></i>
            Lihat Semua Transaksi
        </a>
    @else
        <h2 class="font-heading text-lg font-bold
                   text-darkbrown-900">
            Petualanganmu Dimulai di Sini
        </h2>

        <p class="mx-auto mt-2 max-w-md text-sm
                  leading-relaxed text-darkbrown-600">
            Belum ada riwayat sewa. Temukan tenda, perlengkapan
            tidur, dan peralatan camping yang kamu butuhkan
            untuk perjalanan berikutnya.
        </p>

        <a href="{{ route('customer.catalog.index') }}"
           class="btn-tendaku-primary mt-6 inline-flex
                  items-center justify-center gap-2
                  rounded-xl px-6 py-2.5 text-sm font-bold">
            <i data-lucide="compass"
               class="h-4 w-4"
               aria-hidden="true"></i>
            Jelajahi Katalog
        </a>
    @endif
</div>
