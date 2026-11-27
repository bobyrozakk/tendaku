<x-layouts.customer>
    <x-slot name="title">
        Riwayat Sewa | Tendaku
    </x-slot>

    <x-slot name="headerBg">
        <x-page-header-art image="forest_fall.jpeg" />
    </x-slot>

    <x-slot name="header">
        <div
            class="flex flex-col gap-4 sm:flex-row
                   sm:items-center sm:justify-between"
        >
            <div class="min-w-0 max-w-2xl">
                <x-breadcrumb :items="[
                    ['label' => 'Beranda', 'url' => route('home')],
                    ['label' => 'Riwayat Sewa'],
                ]" />

                <h1 class="font-display font-normal tracking-tight flex items-center gap-2.5 text-3xl text-darkbrown-900 sm:text-4xl">
                    <i data-lucide="clipboard-list" class="h-7 w-7 shrink-0" aria-hidden="true"></i>
                    <span>Riwayat Sewa</span>
                </h1>

                <p class="mt-0.5 text-sm font-semibold text-avocado-800 sm:text-base">
                    Semua perjalanan sewamu, tersimpan di sini.
                </p>

                <p class="mt-2 max-w-xl text-sm leading-relaxed text-darkbrown-600">
                    Pantau pesanan dan jadwal sewa dengan mudah.
                    Lihat perkembangan status setiap penyewaan perlengkapan camping di satu tempat.
                </p>
            </div>
        </div>
    </x-slot>

    @php
        $currentStatus = $status ?? '';
    @endphp

    <div class="space-y-6 py-4 sm:py-6">
        {{-- Status filters --}}
        @include(
            'customer.booking.partials.status-filters',
            ['status' => $currentStatus]
        )

        {{-- Booking history --}}
        @forelse ($rentals as $rental)
            @if ($loop->first)
                <div class="space-y-4">
            @endif

            @include(
                'customer.booking.partials.booking-card',
                ['rental' => $rental]
            )

            @if ($loop->last)
                </div>

                <div class="pt-2">
                    {{ $rentals->links('customer.partials.pagination') }}
                </div>
            @endif
        @empty
            @include(
                'customer.booking.partials.empty-state',
                ['status' => $currentStatus]
            )
        @endforelse
    </div>
</x-layouts.customer>
