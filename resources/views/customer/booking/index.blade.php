<x-layouts.customer>
    <x-slot name="title">
        Riwayat Sewa | Tendaku
    </x-slot>

    <x-slot name="header">
        <div
            class="flex flex-col gap-4 sm:flex-row
                   sm:items-center sm:justify-between"
        >
            <div>
                <nav
                    class="mb-2 flex items-center gap-2 text-xs
                           text-darkbrown-500"
                    aria-label="Breadcrumb"
                >
                    <a
                        href="{{ route('home') }}"
                        class="transition hover:text-avocado-600"
                    >
                        Beranda
                    </a>

                    <span aria-hidden="true">/</span>

                    <span
                        aria-current="page"
                        class="font-semibold text-darkbrown-800"
                    >
                        Riwayat Sewa
                    </span>
                </nav>

                <h1
                    class="flex items-center gap-2.5
                           font-heading text-2xl font-extrabold
                           tracking-tight text-darkbrown-900
                           sm:text-3xl"
                >
                    <i
                        data-lucide="package"
                        class="h-7 w-7"
                        aria-hidden="true"
                    ></i>

                    <span>Riwayat Sewa Saya</span>
                </h1>

                <p class="mt-1 text-sm text-darkbrown-600">
                    Pantau pesanan, jadwal sewa, dan status
                    peralatan camping Anda di satu tempat.
                </p>
            </div>

            <a
                href="{{ route('customer.catalog.index') }}"
                class="btn-tendaku-primary inline-flex
                       items-center justify-center gap-2
                       self-start rounded-xl px-4 py-2.5
                       text-sm font-bold sm:self-auto"
            >
                <i
                    data-lucide="plus"
                    class="h-4 w-4"
                    aria-hidden="true"
                ></i>

                Sewa Peralatan
            </a>
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
                    {{ $rentals->links() }}
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
