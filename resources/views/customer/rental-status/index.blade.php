<x-layouts.customer>
    <x-slot name="title">
        Cek Status Sewa - Tendaku
    </x-slot>

    <x-slot name="header">
        <div>
            <nav class="mb-1.5 flex items-center gap-2 text-xs text-darkbrown-500" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="transition hover:text-avocado-600">Beranda</a>
                <span class="text-wheat-400">/</span>
                <span class="font-semibold text-darkbrown-800">Status Sewa</span>
            </nav>

            <h1 class="font-heading text-2xl font-extrabold tracking-tight text-darkbrown-900 sm:text-3xl">
                Status Sewa
            </h1>
            <p class="mt-1 max-w-2xl text-xs text-darkbrown-600 sm:text-sm">
                Cek detail pesanan dan perkembangan penyewaan Anda menggunakan kode sewa pada receipt.
                Tidak perlu membuat akun atau login.
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-5 py-2 sm:py-4">
        @if (session('error'))
            <x-alert type="danger" title="Kode sewa tidak ditemukan">
                {{ session('error') }}
            </x-alert>
        @endif

        <x-alert type="info" title="Di mana kode sewa saya?">
            Kode sewa/order tercantum pada receipt pemesanan yang Anda terima setelah menyelesaikan checkout.
            Kode ini juga dapat Anda temukan pada bukti transaksi yang diberikan vendor.
        </x-alert>

        <section class="card-tendaku overflow-hidden">
            <div class="border-b border-wheat-200 bg-gradient-to-br from-avocado-50 via-wheat-50 to-sunglow-50 p-6 sm:p-8">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-avocado-200 bg-white text-avocado-700 shadow-tendaku-sm">
                        <i data-lucide="package-search" class="h-6 w-6" aria-hidden="true"></i>
                    </div>

                    <div class="min-w-0">
                        <h2 class="font-heading text-lg font-extrabold text-darkbrown-900 sm:text-xl">
                            Cek Status Penyewaan
                        </h2>
                        <p class="mt-1 text-sm leading-relaxed text-darkbrown-600">
                            Masukkan kode sewa dari receipt, lalu tekan <span class="font-semibold text-darkbrown-900">Cek Status</span>.
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                @include('customer.rental-status.partials.search-form')
            </div>
        </section>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-wheat-200 bg-white p-4 shadow-tendaku-sm">
                <i data-lucide="user-check" class="mb-2 h-5 w-5 text-avocado-600" aria-hidden="true"></i>
                <p class="text-sm font-bold text-darkbrown-900">Tanpa login</p>
                <p class="mt-0.5 text-xs leading-relaxed text-darkbrown-500">Berlaku untuk tamu maupun pelanggan terdaftar.</p>
            </div>

            <div class="rounded-2xl border border-wheat-200 bg-white p-4 shadow-tendaku-sm">
                <i data-lucide="shield-check" class="mb-2 h-5 w-5 text-avocado-600" aria-hidden="true"></i>
                <p class="text-sm font-bold text-darkbrown-900">Aman</p>
                <p class="mt-0.5 text-xs leading-relaxed text-darkbrown-500">Data hanya tampil jika kode sewa cocok dengan data kami.</p>
            </div>

            <div class="rounded-2xl border border-wheat-200 bg-white p-4 shadow-tendaku-sm">
                <i data-lucide="refresh-cw" class="mb-2 h-5 w-5 text-avocado-600" aria-hidden="true"></i>
                <p class="text-sm font-bold text-darkbrown-900">Selalu terbaru</p>
                <p class="mt-0.5 text-xs leading-relaxed text-darkbrown-500">Status mengikuti proses pembayaran, pickup, dan pengembalian.</p>
            </div>
        </div>
    </div>
</x-layouts.customer>
