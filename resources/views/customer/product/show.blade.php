<x-layouts.customer>
    @push('styles')
        <style>
            @media (max-width: 1023px) {
                body {
                    padding-bottom: calc(3.5rem + max(0.75rem, env(safe-area-inset-bottom)));
                }
            }
        </style>
    @endpush

    @php
        $vendorName = $item->vendor?->name ?? 'Tendaku Vendor';
    @endphp

    <!-- Container Utama Detail Produk -->
    <div x-data="{ showGuestStoreModal: false }"
         @open-guest-store-modal.window="showGuestStoreModal = true"
         class="flex flex-col gap-6">

        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold text-darkbrown-500">
            <a href="{{ route('customer.catalog.index', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="inline-flex min-h-[44px] shrink-0 items-center gap-1.5 py-2 transition hover:text-avocado-700">
                <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                <span>Kembali ke Katalog</span>
            </a>
        </nav>

        <livewire:customer.product-booking :product="$item" :start-date="$startDate" :end-date="$endDate" />

        @guest
            <!-- Modal Pop-up "Login untuk Melanjutkan" di Halaman Detail Produk -->
            <div x-show="showGuestStoreModal"
                 x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 aria-labelledby="modal-guest-store-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showGuestStoreModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 transition-opacity bg-darkbrown-950/75 backdrop-blur-sm"
                         @click="showGuestStoreModal = false"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div x-show="showGuestStoreModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="inline-block w-full max-w-sm sm:max-w-md p-6 sm:p-8 my-8 text-center align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-wheat-200 sm:my-8 relative overflow-hidden">

                        <!-- Aksen Garis Emas di Atas -->
                        <div class="absolute top-0 inset-x-0 h-2 bg-gradient-to-r from-sunglow-300 via-avocado-500 to-goldenrod-400"></div>

                        <!-- Tombol Tutup X -->
                        <button type="button"
                                @click="showGuestStoreModal = false"
                                class="absolute top-4 right-4 flex h-11 w-11 items-center justify-center text-darkbrown-400 hover:text-darkbrown-700 rounded-xl hover:bg-wheat-100 transition"
                                aria-label="Tutup modal">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>

                        <div class="w-16 h-16 mx-auto rounded-2xl bg-sunglow-100 text-goldenrod-800 flex items-center justify-center shadow-inner mt-1 mb-4">
                            <i data-lucide="store" class="w-8 h-8 text-goldenrod-700"></i>
                        </div>

                        <h3 id="modal-guest-store-title" class="text-xl font-extrabold text-darkbrown-900 font-heading">
                            Lanjut sebagai Tamu atau Masuk
                        </h3>

                        <p class="text-xs sm:text-sm text-darkbrown-600 mt-2 leading-relaxed">
                            Anda bisa checkout langsung tanpa membuat akun di <strong>{{ $vendorName }}</strong>. Masuk jika ingin menyimpan riwayat sewa dan memakai keranjang untuk booking berikutnya.
                        </p>

                        <div class="mt-6 flex flex-col gap-2.5">
                            <a href="{{ route('login') }}"
                               class="btn-tendaku-primary w-full py-3 px-4 rounded-xl text-xs sm:text-sm font-bold text-center shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                                <i data-lucide="log-in" class="w-4 h-4"></i>
                                <span>Masuk</span>
                            </a>

                            <button type="button"
                                    @click="showGuestStoreModal = false"
                                    class="btn-tendaku-secondary w-full py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold text-center text-darkbrown-700 hover:bg-wheat-100 transition">
                                Lanjut Tanpa Akun
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endguest
    </div>

</x-layouts.customer>
