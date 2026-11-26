<x-layouts.customer>
    <x-slot name="headerBg">
        <x-page-header-art image="forest_autumn.jpeg" />
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between lg:gap-10">
            <div class="min-w-0 max-w-2xl">
                <x-breadcrumb :items="[
                    ['label' => 'Beranda', 'url' => route('home')],
                    ['label' => 'Paket Camping'],
                ]" />

                <h1 class="flex items-center gap-2.5 font-heading text-2xl font-extrabold tracking-tight text-darkbrown-900 sm:text-3xl">
                    <i data-lucide="tent-tree" class="h-7 w-7 shrink-0" aria-hidden="true"></i>
                    <span>Paket Camping</span>
                </h1>

                <p class="mt-0.5 text-sm font-semibold text-avocado-800 sm:text-base">
                    Perlengkapan lengkap dalam satu paket hemat.
                </p>

                <p class="mt-2 max-w-xl text-sm leading-relaxed text-darkbrown-600">
                    Pilih paket dari vendor terverifikasi, tentukan tanggal, lalu langsung sewa. Semua unit dicek sebelum diserahkan, dengan jaminan KTP tanpa deposit uang.
                </p>
            </div>
        </div>
    </x-slot>

    <livewire:customer.package-index />
</x-layouts.customer>
