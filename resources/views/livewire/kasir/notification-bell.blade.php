<div wire:poll.10s class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <!-- Notification Bell Button -->
    <button @click="open = !open"
            type="button"
            class="relative p-2 rounded-xl transition-all duration-150 focus:outline-none"
            :class="open ? 'bg-[#f0e8d4] text-[#2b2202]' : 'text-[#7a6840] hover:bg-[#f0e8d4] hover:text-[#2b2202]'"
            title="Notifikasi Validasi Pesanan">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        @if($pendingCount > 0)
            <!-- Badge Badge Count -->
            <span class="absolute -top-1 -right-1 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-red-600 text-[10px] font-extrabold text-white ring-2 ring-white animate-pulse shadow-sm">
                {{ $pendingCount > 99 ? '99+' : $pendingCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown Panel Notifikasi -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-1"
         class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white shadow-2xl border border-wheat-200 z-50 overflow-hidden"
         style="display: none;">

        <!-- Header -->
        <div class="px-4 py-3 bg-[#fdfbf7] border-b border-wheat-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full {{ $pendingCount > 0 ? 'bg-amber-500 animate-pulse' : 'bg-green-500' }}"></div>
                <h3 class="text-xs font-bold text-darkbrown-900 uppercase tracking-wider">Antrean Validasi Pesanan</h3>
            </div>
            @if($pendingCount > 0)
                <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                    {{ $pendingCount }} Butuh Validasi
                </span>
            @endif
        </div>

        <!-- Notification List -->
        <div class="max-h-80 overflow-y-auto divide-y divide-wheat-100">
            @forelse($pendingRentals as $rental)
                <a href="{{ route('kasir.dashboard') }}"
                   wire:navigate
                   @click="open = false"
                   class="p-3.5 block hover:bg-wheat-50/80 transition-colors duration-150 group">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-xl bg-sunglow-100 text-sunglow-800 border border-sunglow-200 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-sunglow-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1">
                                <span class="font-bold text-xs text-darkbrown-900 group-hover:text-avocado-800 transition-colors">
                                    {{ $rental->booking_code }}
                                </span>
                                <span class="text-[10px] text-darkbrown-400">
                                    {{ $rental->created_at?->diffForHumans() }}
                                </span>
                            </div>
                            <p class="text-xs text-darkbrown-700 font-medium truncate mt-0.5">
                                {{ $rental->customer?->name ?? $rental->guest_name ?? 'Pelanggan' }}
                            </p>
                            <p class="text-[11px] text-sunglow-700 font-semibold mt-1 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-sunglow-500"></span>
                                Menunggu scan barcode alat
                            </p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="py-8 px-4 text-center">
                    <div class="w-10 h-10 rounded-2xl bg-wheat-100 text-darkbrown-400 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-xs font-bold text-darkbrown-800">Tidak ada antrean validasi</p>
                    <p class="text-[11px] text-darkbrown-500 mt-0.5">Semua pesanan yang masuk telah divalidasi</p>
                </div>
            @endforelse
        </div>

        <!-- Footer -->
        <div class="p-2.5 bg-wheat-50 border-t border-wheat-200 text-center">
            <a href="{{ route('kasir.dashboard') }}"
               wire:navigate
               @click="open = false"
               class="inline-flex items-center justify-center gap-1.5 text-xs font-bold text-avocado-700 hover:text-avocado-900 transition-colors py-1 px-3">
                <span>Buka Dashboard & Scan POS</span>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</div>
