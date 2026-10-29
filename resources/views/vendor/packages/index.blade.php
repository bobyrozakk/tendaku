<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Paket Camping Owner
                </h1>
                <p class="text-xs text-darkbrown-500 mt-0.5">
                    Susun paket camping sendiri: pilih produk, atur harga paket, dan tentukan durasi sewanya
                </p>
            </div>
            <a href="{{ route('vendor.packages.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs text-white bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 shadow-sm transition">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Paket Camping</span>
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filter -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-wheat-200 shadow-tendaku-sm">
            <form method="GET" action="{{ route('vendor.packages.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                <div class="lg:col-span-7 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i data-lucide="search" class="w-4 h-4 text-darkbrown-400"></i>
                    </div>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Cari nama atau deskripsi paket..."
                           class="w-full pl-9 pr-4 py-2 text-xs bg-wheat-50 border border-wheat-300 rounded-xl focus:ring-2 focus:ring-avocado-500/20 focus:border-avocado-500 text-darkbrown-900 placeholder:text-darkbrown-400 font-medium transition" />
                </div>

                <div class="lg:col-span-3">
                    <select name="status" onchange="this.form.submit()"
                            class="w-full py-2 px-3 text-xs bg-wheat-50 border border-wheat-300 rounded-xl focus:ring-2 focus:ring-avocado-500/20 focus:border-avocado-500 text-darkbrown-900 font-medium transition">
                        <option value="">Semua Status</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="w-full py-2 px-3 rounded-xl bg-darkbrown-800 hover:bg-darkbrown-900 text-white font-bold text-xs transition">
                        Filter
                    </button>
                    @if (! empty($filters['search']) || ! empty($filters['status']))
                        <a href="{{ route('vendor.packages.index') }}"
                           class="py-2 px-3 rounded-xl bg-wheat-100 hover:bg-wheat-200 text-darkbrown-700 font-bold text-xs transition text-center"
                           title="Reset filter">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tabel Paket -->
        <x-card padding="p-0" class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-wheat-100/70 border-b border-wheat-200 text-darkbrown-700 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-5">Paket Camping</th>
                            <th class="py-3.5 px-5">Isi Paket</th>
                            <th class="py-3.5 px-5">Durasi</th>
                            <th class="py-3.5 px-5">Harga Paket</th>
                            <th class="py-3.5 px-5">Status</th>
                            <th class="py-3.5 px-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-wheat-200/70 bg-white">
                        @forelse ($packages as $package)
                            <tr class="hover:bg-wheat-50/70 transition">
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl overflow-hidden bg-wheat-100 shrink-0 border border-wheat-200">
                                            @if ($package->image_url)
                                                <img src="{{ $package->image_url }}" alt="{{ $package->name }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-wheat-400">
                                                    <i data-lucide="tent" class="w-6 h-6"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-darkbrown-900 text-sm">{{ $package->name }}</p>
                                            <p class="text-[11px] text-darkbrown-500 line-clamp-1 max-w-xs mt-0.5">
                                                {{ $package->description ?: 'Tidak ada deskripsi' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-5">
                                    <span class="badge-wheat">{{ (int) $package->total_items_count }} unit</span>
                                    <span class="text-[11px] text-darkbrown-500 block mt-1">
                                        {{ $package->package_items_count }} jenis produk
                                    </span>
                                </td>

                                <td class="py-3.5 px-5">
                                    <span class="font-bold text-darkbrown-900">{{ $package->duration_days }} hari</span>
                                    @if ($package->max_people)
                                        <span class="text-[11px] text-darkbrown-500 block">maks. {{ $package->max_people }} orang</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-5">
                                    <span class="font-extrabold text-sm text-avocado-800">
                                        Rp {{ number_format((float) $package->price, 0, ',', '.') }}
                                    </span>
                                    <span class="text-[10px] text-darkbrown-400 block font-normal">/ paket</span>
                                </td>

                                <td class="py-3.5 px-5">
                                    @if ($package->is_active)
                                        <span class="badge-avocado">Aktif</span>
                                    @else
                                        <span class="badge-dark">Nonaktif</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-5">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('vendor.packages.edit', $package) }}"
                                           class="p-2 rounded-lg text-avocado-700 bg-avocado-50 hover:bg-avocado-100 transition"
                                           title="Edit paket">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </a>
                                        <form method="POST" action="{{ route('vendor.packages.destroy', $package) }}"
                                              onsubmit="return confirm('Hapus paket {{ addslashes($package->name) }}? Tindakan ini tidak dapat dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-2 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 transition"
                                                    title="Hapus paket">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i data-lucide="tent-tree" class="w-10 h-10 text-wheat-400"></i>
                                        <p class="font-bold text-sm text-darkbrown-800">Belum ada paket camping</p>
                                        <p class="text-xs text-darkbrown-500">
                                            Buat paket pertama Anda agar tampil di halaman customer.
                                        </p>
                                        <a href="{{ route('vendor.packages.create') }}"
                                           class="mt-2 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-avocado-600 hover:bg-avocado-700 transition">
                                            <i data-lucide="plus" class="w-4 h-4"></i>
                                            Tambah Paket Camping
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        @if ($packages->hasPages())
            <div>{{ $packages->links() }}</div>
        @endif
    </div>
</x-layouts.vendor>
