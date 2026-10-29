<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('vendor.products.index') }}" class="p-2 rounded-xl bg-wheat-100 hover:bg-wheat-200 text-darkbrown-700 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                        Detail Produk Owner: {{ $product->name }}
                    </h1>
                    <p class="text-xs text-darkbrown-500 mt-0.5">
                        Informasi lengkap spesifikasi, tarif sewa, serta daftar seluruh unit fisik
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('vendor.products.edit', $product) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-avocado-600 hover:bg-avocado-700 shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                    <span>Edit Produk</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Main Product Card & Specs -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Foto Produk & Galeri -->
            <div class="lg:col-span-4"
                 @php
                     $allImages = $product->images->isNotEmpty()
                         ? $product->images
                         : ($product->image_url ? collect([(object)['id' => 0, 'image_url' => $product->image_url, 'is_primary' => true]]) : collect());
                     $defaultActive = $product->primaryImage?->image_url ?? $product->image_url ?? ($allImages->first()->image_url ?? '');
                 @endphp
                 x-data="{ activePhoto: '{{ $defaultActive }}' }">
                <x-card padding="p-3" class="bg-white">
                    <div class="aspect-square w-full rounded-2xl overflow-hidden bg-wheat-100 border border-wheat-200 relative">
                        <template x-if="activePhoto">
                            <img :src="activePhoto" alt="{{ $product->name }}" class="w-full h-full object-cover transition-all duration-200">
                        </template>
                        <template x-if="!activePhoto">
                            <div class="w-full h-full flex flex-col items-center justify-center text-wheat-400">
                                <svg class="w-16 h-16 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-xs font-semibold">Belum ada foto</span>
                            </div>
                        </template>

                        <div class="absolute top-3 right-3">
                            @if ($product->is_active)
                                <x-badge variant="avocado" :dot="true">Aktif</x-badge>
                            @else
                                <x-badge variant="dark" :dot="true">Nonaktif</x-badge>
                            @endif
                        </div>
                    </div>

                    @if ($allImages->count() > 1)
                        <div class="grid grid-cols-5 gap-2 mt-3">
                            @foreach ($allImages as $idx => $img)
                                <button type="button"
                                        @click="activePhoto = '{{ $img->image_url }}'"
                                        :class="activePhoto === '{{ $img->image_url }}' ? 'ring-2 ring-avocado-500 border-transparent scale-105' : 'opacity-70 hover:opacity-100 border-wheat-200'"
                                        class="relative aspect-square rounded-xl overflow-hidden border bg-wheat-50 transition-all">
                                    <img src="{{ $img->image_url }}" alt="Thumbnail {{ $idx + 1 }}" class="w-full h-full object-cover">
                                    @if ($img->is_primary)
                                        <span class="absolute bottom-1 right-1 w-2 h-2 rounded-full bg-avocado-500" title="Foto Utama"></span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>

            <!-- Detail Spesifikasi & Harga -->
            <div class="lg:col-span-8">
                <x-card class="bg-white h-full flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="border-b border-wheat-200 pb-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-wheat-200 text-darkbrown-800">
                                {{ $product->category->name ?? 'Umum' }}
                            </span>
                            <h2 class="text-xl font-extrabold text-darkbrown-900 mt-2">{{ $product->name }}</h2>
                            <p class="text-xs text-darkbrown-500 font-mono mt-0.5">Slug: {{ $product->slug }}</p>
                        </div>

                        <!-- Harga Sewa & Denda -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-3.5 rounded-xl bg-avocado-50 border border-avocado-200">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-avocado-700 block">Tarif Sewa Harian</span>
                                <span class="text-2xl font-black text-avocado-900">
                                    Rp {{ number_format($product->daily_rate, 0, ',', '.') }}
                                </span>
                                <span class="text-xs text-avocado-700">/ hari sewa</span>
                            </div>

                            <div class="p-3.5 rounded-xl bg-red-50 border border-red-200">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-red-700 block">Denda Keterlambatan</span>
                                <span class="text-2xl font-black text-red-900">
                                    Rp {{ number_format($product->late_fee_per_day, 0, ',', '.') }}
                                </span>
                                <span class="text-xs text-red-700">/ hari telat</span>
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-darkbrown-600 mb-1">Deskripsi Produk</h3>
                            <div class="p-3.5 rounded-xl bg-wheat-50 border border-wheat-200 text-xs text-darkbrown-800 leading-relaxed whitespace-pre-line">
                                {{ $product->description ?: 'Tidak ada deskripsi rinci untuk produk ini.' }}
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan Unit Fisik Produk Ini -->
                    <div class="pt-4 mt-4 border-t border-wheat-200 grid grid-cols-4 gap-2 text-center text-xs">
                        <div class="p-2 rounded-xl bg-wheat-100">
                            <span class="block text-base font-black text-darkbrown-900">{{ $product->units->count() }}</span>
                            <span class="text-[10px] text-darkbrown-600 font-bold">Total Unit</span>
                        </div>
                        <div class="p-2 rounded-xl bg-avocado-100">
                            <span class="block text-base font-black text-avocado-800">{{ $product->units->where('status', 'available')->count() }}</span>
                            <span class="text-[10px] text-avocado-700 font-bold">Tersedia</span>
                        </div>
                        <div class="p-2 rounded-xl bg-sunglow-100">
                            <span class="block text-base font-black text-sunglow-800">{{ $product->units->where('status', 'rented')->count() }}</span>
                            <span class="text-[10px] text-sunglow-800 font-bold">Disewa</span>
                        </div>
                        <div class="p-2 rounded-xl bg-goldenrod-100">
                            <span class="block text-base font-black text-goldenrod-800">{{ $product->units->where('status', 'maintenance')->count() }}</span>
                            <span class="text-[10px] text-goldenrod-700 font-bold">Perawatan</span>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>

        <!-- Daftar Unit Fisik untuk Produk Ini -->
        <x-card title="Daftar Unit Fisik Terdaftar" subtitle="Unit serialized yang terikat pada produk ini">
            <div class="overflow-x-auto -mx-5 -mb-5 mt-3">
                <table class="w-full text-left text-xs">
                    <thead class="bg-wheat-100/70 border-y border-wheat-200 text-darkbrown-700 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-5">Kode Unit</th>
                            <th class="py-3 px-5">Barcode</th>
                            <th class="py-3 px-5">Status Operasional</th>
                            <th class="py-3 px-5">Kondisi Fisik</th>
                            <th class="py-3 px-5">Tanggal Pembelian</th>
                            <th class="py-3 px-5">Harga Beli</th>
                            <th class="py-3 px-5">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-wheat-200/70 bg-white">
                        @forelse ($product->units as $unit)
                            <tr class="hover:bg-wheat-50/70 transition">
                                <td class="py-3 px-5 font-mono font-bold text-darkbrown-900">
                                    {{ $unit->unit_code }}
                                </td>
                                <td class="py-3 px-5 font-mono text-darkbrown-600">
                                    {{ $unit->barcode ?: '-' }}
                                </td>
                                <td class="py-3 px-5">
                                    @if ($unit->status === 'available')
                                        <span class="text-xs font-semibold text-avocado-700">Tersedia</span>
                                    @elseif ($unit->status === 'rented')
                                        <span class="text-xs font-semibold text-amber-700">Sedang Disewa</span>
                                    @elseif ($unit->status === 'maintenance')
                                        <span class="text-xs font-semibold text-yellow-700">Perawatan</span>
                                    @elseif ($unit->status === 'retired')
                                        <span class="text-xs font-semibold text-darkbrown-400">Dipensiunkan</span>
                                    @endif
                                </td>
                                <td class="py-3 px-5 font-medium">
                                    @if ($unit->condition === 'excellent')
                                        <span class="text-emerald-700 font-bold">Sangat Baik</span>
                                    @elseif ($unit->condition === 'good')
                                        <span class="text-green-700 font-bold">Baik</span>
                                    @elseif ($unit->condition === 'fair')
                                        <span class="text-amber-700 font-bold">Cukup</span>
                                    @elseif ($unit->condition === 'damaged')
                                        <span class="text-red-700 font-bold">Rusak</span>
                                    @elseif ($unit->condition === 'lost')
                                        <span class="text-slate-700 font-bold">Hilang</span>
                                    @endif
                                </td>
                                <td class="py-3 px-5 text-darkbrown-600">
                                    {{ $unit->purchase_date ? $unit->purchase_date->format('d M Y') : '-' }}
                                </td>
                                <td class="py-3 px-5 font-semibold text-darkbrown-800">
                                    {{ $unit->purchase_price ? 'Rp ' . number_format($unit->purchase_price, 0, ',', '.') : '-' }}
                                </td>
                                <td class="py-3 px-5 text-darkbrown-500 italic max-w-xs truncate">
                                    {{ $unit->notes ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-darkbrown-500">
                                    Belum ada unit fisik untuk produk ini.
                                    <a href="{{ route('vendor.units') }}" class="font-bold text-avocado-700 underline ml-1">
                                        Tambahkan unit sekarang di Unit Owner
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-layouts.vendor>
