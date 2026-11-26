<x-layouts.customer>
    <x-slot name="title">
        Cek Status Sewa - Tendaku
    </x-slot>

    <x-slot name="headerBg">
        {{-- Gambar kemah di sisi kanan header, memudar ke kiri & bawah --}}
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-y-0 right-0 hidden w-[55%] md:block lg:w-[48%] [-webkit-mask-image:linear-gradient(to_right,transparent,#000_70%)] [mask-image:linear-gradient(to_right,transparent,#000_70%)]"
        >
            <img
                src="{{ asset('images/forest.jpeg') }}"
                alt=""
                width="800"
                height="448"
                decoding="async"
                class="h-full w-full object-cover object-[center_62%] opacity-50"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-[#FDF6E3]/70 via-transparent to-transparent"></div>
        </div>
    </x-slot>

    <x-slot name="header">
        <div class="relative">
            <div class="relative">
                <x-breadcrumb :items="[
                    ['label' => 'Beranda', 'url' => route('home')],
                    ['label' => 'Status Sewa'],
                ]" />

                <h1 class="flex items-center gap-2.5 font-heading text-2xl font-extrabold tracking-tight text-darkbrown-900 sm:text-3xl">
                    <i data-lucide="package-search" class="h-7 w-7 shrink-0" aria-hidden="true"></i>
                    <span>Status Sewa</span>
                </h1>
                <p class="mt-1 max-w-xl text-xs text-darkbrown-600 sm:text-sm">
                    Cek detail pesanan dan perkembangan penyewaan Anda menggunakan kode sewa pada receipt.
                    Tidak perlu membuat akun atau login.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8 py-1 sm:py-2">
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_21rem] lg:items-stretch xl:grid-cols-[minmax(0,1fr)_23rem]">
            {{-- Kolom utama --}}
            <div class="flex min-w-0 flex-col gap-5">
                @if (session('error'))
                    <x-alert type="danger" title="Kode sewa tidak ditemukan">
                        {{ session('error') }}
                    </x-alert>
                @endif

                {{-- Info: di mana kode sewa --}}
                <div class="relative overflow-hidden rounded-2xl border border-sunglow-200 bg-gradient-to-r from-sunglow-50 via-sunglow-50 to-sunglow-100/70 p-4 sm:p-5">
                    <div class="flex items-center gap-4 pr-0 sm:pr-24">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-sunglow-400 text-sunglow-500">
                            <i data-lucide="info" class="h-5 w-5" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-darkbrown-900 sm:text-base">Di mana kode sewa saya?</h2>
                            <p class="mt-0.5 text-xs leading-relaxed text-darkbrown-600">
                                Kode sewa/order tercantum pada receipt pemesanan yang Anda terima setelah checkout.
                            </p>
                        </div>
                    </div>

                    <svg aria-hidden="true" viewBox="0 0 80 72" class="pointer-events-none absolute right-4 top-1/2 hidden h-16 w-auto -translate-y-1/2 sm:block" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="#E59C1B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="34" cy="38" r="30" fill="#FDE8B3" stroke="none" opacity=".55"/>
                        <rect x="18" y="14" width="34" height="46" rx="4" fill="#FFFBF0"/>
                        <path d="M28 14v-3a3 3 0 0 1 3-3h8a3 3 0 0 1 3 3v3z" fill="#FFFBF0"/>
                        <path d="M25 28h20M25 36h20M25 44h12"/>
                        <path d="M60 14l3-5M66 22l6-2M64 31l6 3"/>
                    </svg>
                </div>

                {{-- Form pengecekan --}}
                <section class="card-tendaku flex flex-1 flex-col overflow-hidden">
                    <div class="border-b border-wheat-200 bg-gradient-to-br from-avocado-50 via-wheat-50 to-sunglow-50 p-5 sm:p-6">
                        <div class="flex items-center gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border border-avocado-200 bg-white text-avocado-700 shadow-tendaku-sm">
                                <i data-lucide="package-search" class="h-7 w-7" aria-hidden="true"></i>
                            </div>

                            <div class="min-w-0">
                                <h2 class="font-heading text-lg font-extrabold text-darkbrown-900 sm:text-xl">
                                    Cek Status Penyewaan
                                </h2>
                                <p class="mt-0.5 text-sm leading-relaxed text-darkbrown-600">
                                    Masukkan kode sewa dari receipt Anda, lalu tekan <span class="font-semibold text-darkbrown-900">Cek Status</span>.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 p-5 sm:p-6">
                        @include('customer.rental-status.partials.search-form')
                    </div>
                </section>
            </div>

            {{-- Panel kanan: cara mengecek --}}
            <aside class="card-tendaku flex flex-col overflow-hidden">
                <img
                    src="{{ asset('images/status-sewa-camp.jpg') }}"
                    alt="Area perkemahan di tengah hutan"
                    width="800"
                    height="448"
                    loading="lazy"
                    decoding="async"
                    class="h-44 w-full object-cover object-[center_65%]"
                />

                <div class="space-y-5 p-5">
                    <h2 class="font-heading text-xl font-extrabold tracking-tight text-darkbrown-900">Cara Mengecek Status</h2>

                    <ol class="space-y-3.5 text-sm text-darkbrown-700">
                        <li class="flex items-center gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-sunglow-200 bg-[#FFE3BF] text-xs font-extrabold text-[#9A5B0B]">1</span>
                            <span>Temukan kode sewa pada receipt.</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-sunglow-200 bg-[#FFE3BF] text-xs font-extrabold text-[#9A5B0B]">2</span>
                            <span>Masukkan kode sewa di kolom yang tersedia.</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-sunglow-200 bg-[#FFE3BF] text-xs font-extrabold text-[#9A5B0B]">3</span>
                            <span>Tekan tombol <span class="font-bold text-darkbrown-900">Cek Status</span>.</span>
                        </li>
                    </ol>

                    <div class="flex items-center gap-3 rounded-xl border border-wheat-200 bg-wheat-50 p-3.5">
                        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-9 w-9 shrink-0" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2 4 5v6c0 5 3.4 9.3 8 11 4.6-1.7 8-6 8-11V5l-8-3Z" fill="#6C8B08"/>
                            <path d="m8.5 12.2 2.4 2.4 4.6-4.8" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-darkbrown-900">Data Anda Aman</p>
                            <p class="mt-0.5 text-[11px] leading-snug text-darkbrown-500">
                                Informasi penyewaan Anda tetap aman dan hanya dapat diakses dengan kode penyewaan.
                            </p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        {{-- Keunggulan --}}
        <div class="grid grid-cols-1 divide-y divide-wheat-200 border-t border-wheat-200 pt-2 sm:grid-cols-3 sm:divide-x sm:divide-y-0 sm:pt-6">
            <div class="flex items-center gap-4 px-2 py-4 sm:justify-center sm:px-6 sm:py-1">
                <i data-lucide="user" class="h-9 w-9 shrink-0 stroke-[1.5] text-avocado-700" aria-hidden="true"></i>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-darkbrown-900">Tanpa Login</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-darkbrown-500">Cek status tanpa perlu akun.</p>
                </div>
            </div>

            <div class="flex items-center gap-4 px-2 py-4 sm:justify-center sm:px-6 sm:py-1">
                <i data-lucide="shield-check" class="h-9 w-9 shrink-0 stroke-[1.5] text-avocado-700" aria-hidden="true"></i>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-darkbrown-900">Aman</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-darkbrown-500">Data sesuai kode penyewaan.</p>
                </div>
            </div>

            <div class="flex items-center gap-4 px-2 py-4 sm:justify-center sm:px-6 sm:py-1">
                <i data-lucide="refresh-cw" class="h-9 w-9 shrink-0 stroke-[1.5] text-avocado-700" aria-hidden="true"></i>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-darkbrown-900">Selalu Terbaru</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-darkbrown-500">Status mengikuti proses sewa.</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.customer>
