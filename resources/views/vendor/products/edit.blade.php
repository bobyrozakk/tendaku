<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('vendor.products.index') }}" class="p-2 rounded-xl bg-wheat-100 hover:bg-wheat-200 text-darkbrown-700 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                        Edit Produk Owner
                    </h1>
                    <p class="text-xs text-darkbrown-500 mt-0.5">
                        Perbarui data master, tarif sewa harian, atau denda keterlambatan
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <x-card class="bg-white">
            <form method="POST" action="{{ route('vendor.products.update', $product) }}" enctype="multipart/form-data" class="space-y-6"
                  x-data="{ imagePreview: '{{ $product->image_url }}' }">
                @csrf
                @method('PUT')

                <!-- Nama Produk -->
                <div>
                    <x-input-label for="name" value="Nama Produk *" class="font-bold text-xs text-darkbrown-800" />
                    <x-text-input id="name" name="name" type="text" class="w-full mt-1.5 text-xs" :value="old('name', $product->name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <!-- Kategori & Status Aktif -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="category_id" value="Kategori Produk *" class="font-bold text-xs text-darkbrown-800" />
                        <select id="category_id" name="category_id" required
                                class="w-full mt-1.5 text-xs font-semibold bg-white border border-wheat-300 focus:border-avocado-500 focus:ring-avocado-500 rounded-xl py-2.5 px-3 text-darkbrown-800">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                    </div>

                    <div class="flex items-center pt-6">
                        <label class="relative flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-wheat-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-wheat-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-avocado-600"></div>
                            <span class="text-xs font-bold text-darkbrown-800">Status Produk Aktif (Bisa Disewa)</span>
                        </label>
                    </div>
                </div>

                <!-- Pengaturan Tarif Sewa & Denda -->
                <div class="p-4 rounded-2xl bg-wheat-50/80 border border-wheat-200 space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-goldenrod-100 text-goldenrod-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <h3 class="font-extrabold text-xs text-darkbrown-900 uppercase tracking-wider">
                            Pengaturan Harga Sewa & Denda Harian
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="daily_rate" value="Tarif Sewa Per Hari (Rp) *" class="font-bold text-xs text-darkbrown-800" />
                            <div class="relative mt-1.5">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-xs text-darkbrown-500">Rp</span>
                                <x-text-input id="daily_rate" name="daily_rate" type="number" min="0" step="1000" class="w-full pl-10 text-xs font-bold text-avocado-800" :value="old('daily_rate', (int)$product->daily_rate)" required />
                            </div>
                            <x-input-error :messages="$errors->get('daily_rate')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="late_fee_per_day" value="Denda Telat Per Hari (Rp) *" class="font-bold text-xs text-darkbrown-800" />
                            <div class="relative mt-1.5">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-xs text-darkbrown-500">Rp</span>
                                <x-text-input id="late_fee_per_day" name="late_fee_per_day" type="number" min="0" step="1000" class="w-full pl-10 text-xs font-bold text-red-600" :value="old('late_fee_per_day', (int)$product->late_fee_per_day)" required />
                            </div>
                            <x-input-error :messages="$errors->get('late_fee_per_day')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Foto Produk -->
                <div>
                    <x-input-label for="image" value="Ganti Foto Produk (Biarkan kosong jika tidak ingin mengubah)" class="font-bold text-xs text-darkbrown-800" />
                    <div class="mt-2 flex items-center gap-4">
                        <div class="w-20 h-20 rounded-2xl bg-wheat-100 border-2 border-dashed border-wheat-300 overflow-hidden flex items-center justify-center shrink-0">
                            <template x-if="imagePreview">
                                <img :src="imagePreview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!imagePreview">
                                <svg class="w-8 h-8 text-wheat-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </template>
                        </div>
                        <div class="flex-1">
                            <input id="image" name="image" type="file" accept="image/*"
                                   @change="const file = $event.target.files[0]; if (file) { const reader = new FileReader(); reader.onload = (e) => imagePreview = e.target.result; reader.readAsDataURL(file); }"
                                   class="block w-full text-xs text-darkbrown-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-wheat-200 file:text-darkbrown-800 hover:file:bg-wheat-300 cursor-pointer" />
                            <p class="text-[11px] text-darkbrown-400 mt-1">Format: JPG, PNG, WEBP. Maksimal 2MB.</p>
                            <x-input-error :messages="$errors->get('image')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Deskripsi Produk -->
                <div>
                    <x-input-label for="description" value="Deskripsi & Spesifikasi Produk" class="font-bold text-xs text-darkbrown-800" />
                    <textarea id="description" name="description" rows="4"
                              class="w-full mt-1.5 text-xs bg-white border border-wheat-300 focus:border-avocado-500 focus:ring-avocado-500 rounded-xl p-3 text-darkbrown-800">{{ old('description', $product->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-wheat-200">
                    <a href="{{ route('vendor.products.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-avocado-600 hover:bg-avocado-700 shadow-sm transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.vendor>
