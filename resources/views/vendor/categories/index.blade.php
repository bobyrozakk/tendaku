<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Kategori Owner
                </h1>
                <p class="text-xs text-darkbrown-500 mt-0.5">
                    Pengelompokan perlengkapan camping (Tenda, Matras, Alat Masak, Lampu, dll)
                </p>
            </div>
            <button @click="$dispatch('open-add-category-modal')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs text-white bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Kategori Owner</span>
            </button>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{
        showAddModal: false,
        showEditModal: false,
        editId: null,
        editName: '',
        editDescription: '',
        editIcon: 'tent',
        openEdit(cat) {
            this.editId = cat.id;
            this.editName = cat.name;
            this.editDescription = cat.description || '';
            this.editIcon = cat.icon || 'tent';
            this.showEditModal = true;
        }
    }" @open-add-category-modal.window="showAddModal = true">

        <!-- Search Bar -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-wheat-200 shadow-tendaku-sm">
            <form method="GET" action="{{ route('vendor.categories.index') }}" class="flex items-center gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari nama atau deskripsi kategori..."
                           class="w-full pl-9 pr-4 py-2 text-xs bg-wheat-50 border border-wheat-300 rounded-xl focus:ring-2 focus:ring-avocado-500/20 focus:border-avocado-500 text-darkbrown-900 placeholder:text-darkbrown-400 font-medium transition" />
                </div>
                <button type="submit" class="py-2 px-4 rounded-xl bg-darkbrown-800 hover:bg-darkbrown-900 text-white font-bold text-xs transition">
                    Cari
                </button>
                @if (!empty($search))
                    <a href="{{ route('vendor.categories.index') }}" class="py-2 px-3 rounded-xl bg-wheat-100 hover:bg-wheat-200 text-darkbrown-700 font-bold text-xs transition">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Categories Table -->
        <x-card padding="p-0" class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-wheat-100/70 border-b border-wheat-200 text-darkbrown-700 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-5">Kategori</th>
                            <th class="py-3.5 px-5">Slug URL</th>
                            <th class="py-3.5 px-5">Deskripsi</th>
                            <th class="py-3.5 px-5">Total Produk</th>
                            <th class="py-3.5 px-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-wheat-200/70 bg-white">
                        @forelse ($categories as $cat)
                            <tr class="hover:bg-wheat-50/70 transition">
                                <!-- Kategori & Icon -->
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-wheat-200/80 text-darkbrown-800 flex items-center justify-center font-bold text-sm shrink-0">
                                            @if ($cat->icon === 'tent') ⛺
                                            @elseif ($cat->icon === 'moon') 🌙
                                            @elseif ($cat->icon === 'flame') 🔥
                                            @elseif ($cat->icon === 'sun') ☀️
                                            @elseif ($cat->icon === 'bag') 🎒
                                            @else 📦
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-bold text-darkbrown-900 text-sm">{{ $cat->name }}</p>
                                            <span class="text-[10px] text-darkbrown-400">Icon: {{ $cat->icon ?: 'tent' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Slug -->
                                <td class="py-4 px-5 font-mono text-darkbrown-600 text-[11px]">
                                    {{ $cat->slug }}
                                </td>

                                <!-- Deskripsi -->
                                <td class="py-4 px-5 text-darkbrown-600 max-w-sm">
                                    <p class="line-clamp-2">{{ $cat->description ?: 'Tidak ada deskripsi' }}</p>
                                </td>

                                <!-- Total Produk -->
                                <td class="py-4 px-5">
                                    <span class="text-xs font-semibold {{ $cat->items_count > 0 ? 'text-avocado-800' : 'text-darkbrown-500' }}">
                                        {{ $cat->items_count }} Produk
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="py-4 px-5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        <button @click="openEdit({{ json_encode($cat) }})"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-avocado-700 bg-avocado-50 hover:bg-avocado-100 transition"
                                                title="Edit Kategori">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </button>

                                        <form method="POST" action="{{ route('vendor.categories.destroy', $cat) }}" class="inline-block"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 transition"
                                                    title="Hapus Kategori">
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
                                <td colspan="5" class="py-12 text-center text-darkbrown-500">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="w-12 h-12 text-wheat-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                        <p class="font-bold text-sm text-darkbrown-800">Belum ada kategori yang terdaftar.</p>
                                        <p class="text-xs text-darkbrown-500">Buat kategori baru untuk mengorganisasi katalog produk toko Anda.</p>
                                        <button @click="showAddModal = true" class="btn-tendaku-primary !py-2 !px-4 text-xs mt-2">
                                            Tambah Kategori Baru
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- Modal Tambah Kategori -->
        <div x-show="showAddModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-darkbrown-950/60 p-4 animate-fade-in backdrop-blur-sm">
            <x-card class="w-full max-w-md shadow-tendaku-lg border-wheat-300 relative bg-white">
                <div class="flex justify-between items-center border-b border-wheat-200 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-avocado-100 text-avocado-700 font-bold">📂</span>
                        <h3 class="text-base font-extrabold text-darkbrown-900">Tambah Kategori Owner</h3>
                    </div>
                    <button @click="showAddModal = false" class="text-darkbrown-400 hover:text-darkbrown-700 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('vendor.categories.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="cat_name" value="Nama Kategori *" class="font-bold text-xs text-darkbrown-800" />
                        <x-text-input id="cat_name" name="name" type="text" class="w-full mt-1.5 text-xs" required placeholder="Contoh: Tenda & Shelter" />
                    </div>

                    <div>
                        <x-input-label for="cat_icon" value="Icon Kategori" class="font-bold text-xs text-darkbrown-800" />
                        <select id="cat_icon" name="icon" class="w-full mt-1.5 text-xs font-semibold bg-white border border-wheat-300 rounded-xl py-2 px-3 text-darkbrown-800">
                            <option value="tent">⛺ Tenda & Shelter (tent)</option>
                            <option value="moon">🌙 Sleeping Gear (moon)</option>
                            <option value="flame">🔥 Alat Masak & Gas (flame)</option>
                            <option value="sun">☀️ Lampu & Daya (sun)</option>
                            <option value="bag">🎒 Tas & Carrier (bag)</option>
                            <option value="box">📦 Aksesoris / Lainnya (box)</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="cat_desc" value="Deskripsi Kategori (Opsional)" class="font-bold text-xs text-darkbrown-800" />
                        <textarea id="cat_desc" name="description" rows="3"
                                  class="w-full mt-1.5 text-xs bg-white border border-wheat-300 rounded-xl p-2.5 text-darkbrown-800"
                                  placeholder="Keterangan kategori ini..."></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-wheat-200">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-avocado-600 hover:bg-avocado-700 shadow-sm transition">
                            Simpan Kategori
                        </button>
                    </div>
                </form>
            </x-card>
        </div>

        <!-- Modal Edit Kategori -->
        <div x-show="showEditModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-darkbrown-950/60 p-4 animate-fade-in backdrop-blur-sm">
            <x-card class="w-full max-w-md shadow-tendaku-lg border-wheat-300 relative bg-white">
                <div class="flex justify-between items-center border-b border-wheat-200 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-goldenrod-100 text-goldenrod-700 font-bold">✏️</span>
                        <h3 class="text-base font-extrabold text-darkbrown-900">Edit Kategori Owner</h3>
                    </div>
                    <button @click="showEditModal = false" class="text-darkbrown-400 hover:text-darkbrown-700 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form method="POST" :action="'{{ url('vendor/categories') }}/' + editId" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <x-input-label value="Nama Kategori *" class="font-bold text-xs text-darkbrown-800" />
                        <x-text-input name="name" type="text" x-model="editName" class="w-full mt-1.5 text-xs" required />
                    </div>

                    <div>
                        <x-input-label value="Icon Kategori" class="font-bold text-xs text-darkbrown-800" />
                        <select name="icon" x-model="editIcon" class="w-full mt-1.5 text-xs font-semibold bg-white border border-wheat-300 rounded-xl py-2 px-3 text-darkbrown-800">
                            <option value="tent">⛺ Tenda & Shelter (tent)</option>
                            <option value="moon">🌙 Sleeping Gear (moon)</option>
                            <option value="flame">🔥 Alat Masak & Gas (flame)</option>
                            <option value="sun">☀️ Lampu & Daya (sun)</option>
                            <option value="bag">🎒 Tas & Carrier (bag)</option>
                            <option value="box">📦 Aksesoris / Lainnya (box)</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label value="Deskripsi Kategori (Opsional)" class="font-bold text-xs text-darkbrown-800" />
                        <textarea name="description" rows="3" x-model="editDescription"
                                  class="w-full mt-1.5 text-xs bg-white border border-wheat-300 rounded-xl p-2.5 text-darkbrown-800"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-wheat-200">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-avocado-600 hover:bg-avocado-700 shadow-sm transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-layouts.vendor>
