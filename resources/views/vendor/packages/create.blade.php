<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('vendor.packages.index') }}" class="p-2 rounded-xl bg-wheat-100 hover:bg-wheat-200 text-darkbrown-700 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Tambah Paket Camping
                </h1>
                <p class="text-xs text-darkbrown-500 mt-0.5">
                    Pilih produk, tentukan harga paket, dan atur durasi sewa
                </p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl">
        <x-card class="bg-white">
            @include('vendor.packages._form', ['package' => null, 'products' => $products])
        </x-card>
    </div>
</x-layouts.vendor>
