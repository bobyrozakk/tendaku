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
            <form method="POST" action="{{ route('vendor.products.update', $product) }}" enctype="multipart/form-data" class="space-y-6">
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

                <!-- Foto Produk (Kelola & Tambah Foto Baru) -->
                <div x-data="{
                    existingImages: {{ Js::from($product->images->map(fn($img) => [
                        'id' => $img->id,
                        'url' => $img->image_url,
                        'is_primary' => (bool)$img->is_primary,
                    ])) }},
                    deletedIds: [],
                    newFiles: [],
                    maxFiles: 5,
                    errorMessage: null,
                    isDragging: false,
                    formatSize(bytes) {
                        if (bytes < 1024) return bytes + ' B';
                        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                        return (bytes / 1048576).toFixed(1) + ' MB';
                    },
                    activeExistingCount() {
                        return this.existingImages.filter(img => !this.deletedIds.includes(img.id)).length;
                    },
                    totalCount() {
                        return this.activeExistingCount() + this.newFiles.length;
                    },
                    markForDeletion(id) {
                        if (!this.deletedIds.includes(id)) {
                            this.deletedIds.push(id);
                        }
                        this.errorMessage = null;
                    },
                    undoDeletion(id) {
                        if (this.totalCount() >= this.maxFiles) {
                            this.errorMessage = 'Tidak dapat membatalkan penghapusan karena total foto akan melebihi 5.';
                            return;
                        }
                        this.deletedIds = this.deletedIds.filter(item => item !== id);
                        this.errorMessage = null;
                    },
                    handleFiles(incomingFiles) {
                        this.errorMessage = null;
                        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
                        const maxSizeBytes = 2 * 1024 * 1024;

                        const incoming = Array.from(incomingFiles);
                        if (!incoming.length) return;

                        if (this.totalCount() + incoming.length > this.maxFiles) {
                            this.errorMessage = `Maksimal total ${this.maxFiles} foto. Saat ini tersisa kuota ${this.maxFiles - this.totalCount()} foto.`;
                        }

                        const availableSlots = this.maxFiles - this.totalCount();
                        const filesToProcess = incoming.slice(0, availableSlots);

                        for (const file of filesToProcess) {
                            if (!validTypes.includes(file.type) && !file.name.match(/\.(jpe?g|png|webp)$/i)) {
                                this.errorMessage = `File '${file.name}' tidak didukung. Format yang diperbolehkan: JPG, JPEG, PNG, WEBP.`;
                                continue;
                            }
                            if (file.size > maxSizeBytes) {
                                this.errorMessage = `File '${file.name}' (${this.formatSize(file.size)}) melebihi batas maksimal 2MB.`;
                                continue;
                            }
                            this.newFiles.push({
                                file: file,
                                name: file.name,
                                size: file.size,
                                url: URL.createObjectURL(file)
                            });
                        }

                        this.syncInputFiles();
                    },
                    removeNewFile(index) {
                        if (this.newFiles[index] && this.newFiles[index].url) {
                            URL.revokeObjectURL(this.newFiles[index].url);
                        }
                        this.newFiles.splice(index, 1);
                        this.syncInputFiles();
                        if (this.totalCount() < this.maxFiles) {
                            this.errorMessage = null;
                        }
                    },
                    syncInputFiles() {
                        const dt = new DataTransfer();
                        this.newFiles.forEach(item => dt.items.add(item.file));
                        this.$refs.fileInput.files = dt.files;
                    }
                }" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <x-input-label for="images" value="Foto Produk (Maksimal 5 Foto)" class="font-bold text-xs text-darkbrown-800" />
                            <p class="text-[11px] text-darkbrown-500 mt-0.5">Kelola foto yang sudah ada atau unggah foto tambahan (maksimal total 5 foto).</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold transition"
                              :class="totalCount() >= maxFiles ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-wheat-200 text-darkbrown-800'"
                              x-text="`${totalCount()}/${maxFiles} foto`">
                            {{ $product->images->count() }}/5 foto
                        </span>
                    </div>

                    <!-- Hidden Inputs untuk Foto yang Dihapus -->
                    <template x-for="id in deletedIds" :key="id">
                        <input type="hidden" name="deleted_images[]" :value="id">
                    </template>

                    <!-- Hidden Multiple Input untuk Foto Baru -->
                    <input x-ref="fileInput" id="images" name="images[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                           class="sr-only"
                           @change="handleFiles($event.target.files); $event.target.value = '';">

                    <!-- Alert Validasi Frontend -->
                    <template x-if="errorMessage">
                        <div class="p-3 rounded-xl bg-red-50 border border-red-200 flex items-start justify-between gap-2 text-xs text-red-700">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span x-text="errorMessage"></span>
                            </div>
                            <button type="button" @click="errorMessage = null" class="text-red-500 hover:text-red-800 font-bold">&times;</button>
                        </div>
                    </template>

                    <!-- Galeri Foto Saat Ini -->
                    <div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                            <!-- Foto Lama Terdaftar -->
                            <template x-for="(img, idx) in existingImages" :key="img.id">
                                <div class="relative group aspect-square rounded-2xl overflow-hidden border-2 bg-white shadow-sm transition"
                                     :class="deletedIds.includes(img.id)
                                         ? 'border-red-300 opacity-40 grayscale'
                                         : (idx === 0 && !deletedIds.includes(img.id) ? 'border-avocado-500 ring-2 ring-avocado-200' : 'border-wheat-200')">
                                    <img :src="img.url" alt="Foto Produk" class="w-full h-full object-cover">

                                    <!-- Status Badge -->
                                    <template x-if="deletedIds.includes(img.id)">
                                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-lg text-[10px] font-extrabold bg-red-600 text-white shadow-sm">
                                            Akan Dihapus
                                        </span>
                                    </template>
                                    <template x-if="!deletedIds.includes(img.id) && idx === 0">
                                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-lg text-[10px] font-extrabold bg-avocado-600 text-white shadow-sm">
                                            Foto Utama
                                        </span>
                                    </template>

                                    <!-- Tombol Hapus / Batal Hapus -->
                                    <template x-if="!deletedIds.includes(img.id)">
                                        <button type="button" @click.stop="markForDeletion(img.id)"
                                                title="Tandai untuk dihapus"
                                                class="absolute top-2 right-2 w-7 h-7 rounded-xl bg-white/90 hover:bg-red-600 text-darkbrown-700 hover:text-white shadow-md flex items-center justify-center transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </template>
                                    <template x-if="deletedIds.includes(img.id)">
                                        <button type="button" @click.stop="undoDeletion(img.id)"
                                                title="Batalkan penghapusan"
                                                class="absolute inset-0 m-auto w-24 h-8 rounded-xl bg-darkbrown-900/90 hover:bg-darkbrown-900 text-white font-bold text-[11px] shadow-lg flex items-center justify-center gap-1 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                            </svg>
                                            <span>Batal</span>
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <!-- Foto Baru yang Akan Diunggah -->
                            <template x-for="(item, newIdx) in newFiles" :key="newIdx">
                                <div class="relative group aspect-square rounded-2xl overflow-hidden border-2 border-avocado-400 bg-white shadow-sm transition">
                                    <img :src="item.url" :alt="item.name" class="w-full h-full object-cover">

                                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded-lg text-[10px] font-extrabold bg-goldenrod-600 text-white shadow-sm">
                                        Foto Baru
                                    </span>

                                    <!-- Tombol Hapus Foto Baru -->
                                    <button type="button" @click.stop="removeNewFile(newIdx)"
                                            title="Batalkan unggahan foto ini"
                                            class="absolute top-2 right-2 w-7 h-7 rounded-xl bg-white/90 hover:bg-red-600 text-darkbrown-700 hover:text-white shadow-md flex items-center justify-center transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>

                                    <!-- Info File -->
                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-darkbrown-950/80 via-darkbrown-950/40 to-transparent p-2 text-white">
                                        <p class="text-[10px] font-bold truncate" x-text="item.name"></p>
                                        <p class="text-[9px] text-wheat-200" x-text="formatSize(item.size)"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Dropzone Tambah Foto Baru (Aktif jika total < 5) -->
                    <div x-show="totalCount() < maxFiles"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; handleFiles($event.dataTransfer.files)"
                         @click="$refs.fileInput.click()"
                         :class="isDragging ? 'border-avocado-500 bg-avocado-50/50 scale-[1.005]' : 'border-wheat-300 hover:border-avocado-400 bg-wheat-50/50 hover:bg-wheat-50'"
                         class="border-2 border-dashed rounded-2xl p-5 text-center cursor-pointer transition-all duration-200">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-10 h-10 rounded-2xl bg-wheat-200/80 flex items-center justify-center text-darkbrown-700 mb-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <p class="text-xs font-bold text-darkbrown-900">
                                Tambah Foto Produk Lainnya
                            </p>
                            <p class="text-[11px] text-darkbrown-500 mt-0.5">
                                Tersisa kuota <span class="font-bold text-avocado-700" x-text="maxFiles - totalCount()"></span> foto lagi (JPG, JPEG, PNG, WEBP, maks 2MB)
                            </p>
                        </div>
                    </div>

                    <x-input-error :messages="$errors->get('images')" class="mt-1" />
                    <x-input-error :messages="$errors->get('images.*')" class="mt-1" />
                    <x-input-error :messages="$errors->get('deleted_images')" class="mt-1" />
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
