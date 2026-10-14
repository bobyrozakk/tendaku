<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Produk Owner
                </h1>
                <p class="text-xs text-darkbrown-500 mt-0.5">
                    Katalog master perlengkapan sewa, penetapan tarif harian, dan denda keterlambatan
                </p>
            </div>
            <a href="{{ route('vendor.products.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs text-white bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Produk Owner</span>
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filter Card -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-wheat-200 shadow-tendaku-sm">
            <form method="GET" action="{{ route('vendor.products.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                <!-- Search Input -->
                <div class="lg:col-span-5 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="Cari nama atau deskripsi produk..."
                           class="w-full pl-9 pr-4 py-2 text-xs bg-wheat-50 border border-wheat-300 rounded-xl focus:ring-2 focus:ring-avocado-500/20 focus:border-avocado-500 text-darkbrown-900 placeholder:text-darkbrown-400 font-medium transition" />
                </div>

                <!-- Category Filter -->
                <div class="lg:col-span-3">
                    <select name="category_id"
                            onchange="this.form.submit()"
                            class="w-full py-2 px-3 text-xs bg-wheat-50 border border-wheat-300 rounded-xl focus:ring-2 focus:ring-avocado-500/20 focus:border-avocado-500 text-darkbrown-900 font-medium transition">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="lg:col-span-2">
                    <select name="status"
                            onchange="this.form.submit()"
                            class="w-full py-2 px-3 text-xs bg-wheat-50 border border-wheat-300 rounded-xl focus:ring-2 focus:ring-avocado-500/20 focus:border-avocado-500 text-darkbrown-900 font-medium transition">
                        <option value="">Semua Status</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <!-- Actions Button -->
                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="w-full py-2 px-3 rounded-xl bg-darkbrown-800 hover:bg-darkbrown-900 text-white font-bold text-xs transition">
                        Filter
                    </button>
                    @if (!empty($filters['search']) || !empty($filters['category_id']) || !empty($filters['status']))
                        <a href="{{ route('vendor.products.index') }}" class="py-2 px-3 rounded-xl bg-wheat-100 hover:bg-wheat-200 text-darkbrown-700 font-bold text-xs transition text-center" title="Reset filter">
                            ↺
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <x-card padding="p-0" class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-wheat-100/70 border-b border-wheat-200 text-darkbrown-700 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-5">Produk</th>
                            <th class="py-3.5 px-5">Kategori</th>
                            <th class="py-3.5 px-5">Tarif Sewa</th>
                            <th class="py-3.5 px-5">Denda Telat</th>
                            <th class="py-3.5 px-5">Unit Fisik</th>
                            <th class="py-3.5 px-5">Status</th>
                            <th class="py-3.5 px-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-wheat-200/70 bg-white">
                        @forelse ($products as $product)
                            <tr class="hover:bg-wheat-50/70 transition">
                                <!-- Info Produk -->
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl overflow-hidden bg-wheat-100 shrink-0 border border-wheat-200">
                                            @if ($product->image_url)
                                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-wheat-400">
                                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <a href="{{ route('vendor.products.show', $product) }}" class="font-bold text-darkbrown-900 text-sm hover:text-avocado-700 transition">
                                                {{ $product->name }}
                                            </a>
                                            <p class="text-[11px] text-darkbrown-500 line-clamp-1 max-w-xs mt-0.5">
                                                {{ $product->description ?: 'Tidak ada deskripsi' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kategori -->
                                <td class="py-3.5 px-5">
                                    <span class="text-xs font-semibold text-darkbrown-700">
                                        {{ $product->category->name ?? 'Tanpa Kategori' }}
                                    </span>
                                </td>

                                <!-- Tarif Harian -->
                                <td class="py-3.5 px-5">
                                    <span class="font-extrabold text-sm text-avocado-800">
                                        Rp {{ number_format($product->daily_rate, 0, ',', '.') }}
                                    </span>
                                    <span class="text-[10px] text-darkbrown-400 block font-normal">/ hari</span>
                                </td>

                                <!-- Denda Harian -->
                                <td class="py-3.5 px-5">
                                    <span class="font-bold text-xs text-red-600">
                                        Rp {{ number_format($product->late_fee_per_day, 0, ',', '.') }}
                                    </span>
                                    <span class="text-[10px] text-darkbrown-400 block font-normal">/ hari</span>
                                </td>

                                <!-- Jumlah Unit Fisik -->
                                <td class="py-3.5 px-5">
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-1.5 font-bold text-darkbrown-900">
                                            <span>{{ $product->total_units_count }} Unit Total</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-[10px]">
                                            <span class="text-avocado-600 font-semibold">{{ $product->available_units_count }} siap</span>
                                            <span class="text-darkbrown-300">•</span>
                                            <span class="text-sunglow-600 font-semibold">{{ $product->rented_units_count }} disewa</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status Aktif -->
                                <td class="py-3.5 px-5">
                                    @if ($product->is_active)
                                        <span class="text-xs font-semibold text-avocado-700">Aktif</span>
                                    @else
                                        <span class="text-xs font-semibold text-darkbrown-400">Nonaktif</span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        <a href="{{ route('vendor.products.show', $product) }}"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-darkbrown-600 bg-wheat-100 hover:bg-wheat-200 transition"
                                           title="Lihat Detail & Unit">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        <a href="{{ route('vendor.products.edit', $product) }}"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-avocado-700 bg-avocado-50 hover:bg-avocado-100 transition"
                                           title="Edit Produk">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </a>

                                        <form method="POST" action="{{ route('vendor.products.destroy', $product) }}" class="inline-block"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini? Unit fisik terkait akan ikut terhapus.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 transition"
                                                    title="Hapus Produk">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-darkbrown-500">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="w-12 h-12 text-wheat-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <p class="font-bold text-sm text-darkbrown-800">Tidak ada produk yang ditemukan.</p>
                                        <p class="text-xs text-darkbrown-500">Mulai tambahkan produk perlengkapan camping pertama toko Anda.</p>
                                        <a href="{{ route('vendor.products.create') }}" class="btn-tendaku-primary !py-2 !px-4 text-xs mt-2">
                                            Tambah Produk Sekarang
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="p-4 border-t border-wheat-200">
                    {{ $products->links() }}
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.vendor>
