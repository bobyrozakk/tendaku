@props(['package' => null, 'products'])

@php
    $initialItems = old('items');

    if ($initialItems === null) {
        $initialItems = $package
            ? $package->packageItems->map(fn ($item) => [
                'master_item_id' => (string) $item->master_item_id,
                'quantity' => (int) $item->quantity,
            ])->values()->all()
            : [];
    }

    if ($initialItems === []) {
        $initialItems = [['master_item_id' => '', 'quantity' => 1]];
    }

    $productOptions = $products->map(fn ($product) => [
        'id' => (string) $product->id,
        'name' => $product->name,
        'category' => $product->category->name ?? 'Tanpa Kategori',
        'daily_rate' => (float) $product->daily_rate,
    ])->values()->all();
@endphp

<form method="POST"
      action="{{ $package ? route('vendor.packages.update', $package) : route('vendor.packages.store') }}"
      enctype="multipart/form-data"
      class="space-y-6"
      x-data="{
          rows: @js($initialItems),
          products: @js($productOptions),
          price: '{{ old('price', $package->price ?? '') }}',
          duration: '{{ old('duration_days', $package->duration_days ?? 2) }}',
          addRow() {
              this.rows.push({ master_item_id: '', quantity: 1 });
          },
          removeRow(index) {
              if (this.rows.length > 1) {
                  this.rows.splice(index, 1);
              }
          },
          productById(id) {
              return this.products.find((product) => product.id === String(id)) || null;
          },
          itemSubtotal(row) {
              const product = this.productById(row.master_item_id);
              if (!product) return 0;
              return product.daily_rate * (parseInt(row.quantity) || 0) * (parseInt(this.duration) || 0);
          },
          normalTotal() {
              return this.rows.reduce((total, row) => total + this.itemSubtotal(row), 0);
          },
          packagePrice() {
              return parseFloat(this.price) || 0;
          },
          savings() {
              return Math.max(0, this.normalTotal() - this.packagePrice());
          },
          formatRupiah(value) {
              return 'Rp ' + (Math.round(value) || 0).toLocaleString('id-ID');
          }
      }">
    @csrf
    @if ($package)
        @method('PUT')
    @endif

    <!-- Informasi Paket -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <x-input-label for="name" value="Nama Paket Camping *" class="font-bold text-xs text-darkbrown-800" />
            <x-text-input id="name" name="name" type="text" class="w-full mt-1.5 text-xs"
                          :value="old('name', $package->name ?? '')" required autofocus
                          placeholder="Contoh: Paket Camping Hemat 2 Hari 1 Malam" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="duration_days" value="Durasi Sewa (Hari) *" class="font-bold text-xs text-darkbrown-800" />
            <x-text-input id="duration_days" name="duration_days" type="number" min="1" max="30" step="1"
                          class="w-full mt-1.5 text-xs font-bold" x-model="duration"
                          :value="old('duration_days', $package->duration_days ?? 2)" required />
            <p class="text-[11px] text-darkbrown-400 mt-1">Lama pemakaian paket dalam satu kali sewa.</p>
            <x-input-error :messages="$errors->get('duration_days')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="max_people" value="Kapasitas (Orang)" class="font-bold text-xs text-darkbrown-800" />
            <x-text-input id="max_people" name="max_people" type="number" min="1" max="100" step="1"
                          class="w-full mt-1.5 text-xs"
                          :value="old('max_people', $package->max_people ?? '')"
                          placeholder="Contoh: 4" />
            <x-input-error :messages="$errors->get('max_people')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="price" value="Harga Paket (Rp) *" class="font-bold text-xs text-darkbrown-800" />
            <div class="relative mt-1.5">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-xs text-darkbrown-500">Rp</span>
                <x-text-input id="price" name="price" type="number" min="0" step="1000"
                              class="w-full pl-10 text-xs font-bold text-avocado-800" x-model="price"
                              :value="old('price', $package->price ?? '')" required placeholder="Contoh: 250000" />
            </div>
            <p class="text-[11px] text-darkbrown-400 mt-1">Harga total paket untuk seluruh durasi sewa.</p>
            <x-input-error :messages="$errors->get('price')" class="mt-1" />
        </div>

        <div class="flex items-center pt-6">
            <label class="relative flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $package->is_active ?? true) ? 'checked' : '' }}
                       class="sr-only peer">
                <div class="w-11 h-6 bg-wheat-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-wheat-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-avocado-600"></div>
                <span class="text-xs font-bold text-darkbrown-800">Paket Aktif (Tampil di Halaman Customer)</span>
            </label>
        </div>
    </div>

    <!-- Isi Paket -->
    <div class="p-4 rounded-2xl bg-wheat-50/80 border border-wheat-200 space-y-3">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="p-1.5 rounded-lg bg-avocado-100 text-avocado-700">
                    <i data-lucide="package" class="w-4 h-4"></i>
                </span>
                <h3 class="font-extrabold text-xs text-darkbrown-900 uppercase tracking-wider">
                    Isi Paket Camping *
                </h3>
            </div>
            <button type="button" @click="addRow()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold text-avocado-700 bg-white border border-avocado-200 hover:bg-avocado-50 transition">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                Tambah Produk
            </button>
        </div>

        @if ($products->isEmpty())
            <div class="p-4 rounded-xl bg-white border border-dashed border-wheat-300 text-center">
                <p class="text-xs font-bold text-darkbrown-800">Belum ada produk aktif.</p>
                <p class="text-[11px] text-darkbrown-500 mt-0.5">
                    Tambahkan produk terlebih dahulu di menu
                    <a href="{{ route('vendor.products.create') }}" class="font-bold text-avocado-700 underline">Produk Owner</a>.
                </p>
            </div>
        @else
            <div class="space-y-2">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 p-2.5 rounded-xl bg-white border border-wheat-200">
                        <div class="flex-1 min-w-0">
                            <select :name="`items[${index}][master_item_id]`" x-model="row.master_item_id"
                                    class="w-full text-xs font-semibold bg-white border border-wheat-300 focus:border-avocado-500 focus:ring-avocado-500 rounded-xl py-2 px-3 text-darkbrown-800">
                                <option value="">Pilih produk...</option>
                                <template x-for="product in products" :key="product.id">
                                    <option :value="product.id" x-text="`${product.name} — ${product.category}`"></option>
                                </template>
                            </select>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <div class="flex items-center gap-1.5">
                                <label class="text-[10px] font-bold uppercase text-darkbrown-500">Qty</label>
                                <input type="number" min="1" max="99" step="1"
                                       :name="`items[${index}][quantity]`" x-model="row.quantity"
                                       class="w-16 text-xs font-bold text-center bg-white border border-wheat-300 focus:border-avocado-500 focus:ring-avocado-500 rounded-xl py-2 px-1 text-darkbrown-800">
                            </div>

                            <span class="hidden sm:inline-block text-[11px] font-semibold text-darkbrown-500 w-28 text-right"
                                  x-text="formatRupiah(itemSubtotal(row))"></span>

                            <button type="button" @click="removeRow(index)"
                                    :disabled="rows.length === 1"
                                    class="p-2 rounded-lg text-red-500 hover:bg-red-50 transition disabled:opacity-30 disabled:cursor-not-allowed"
                                    title="Hapus produk dari paket">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            <x-input-error :messages="$errors->get('items')" class="mt-1" />
            <x-input-error :messages="$errors->get('items.*.master_item_id')" class="mt-1" />
            <x-input-error :messages="$errors->get('items.*.quantity')" class="mt-1" />

            <!-- Ringkasan Harga -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2">
                <div class="rounded-xl bg-white border border-wheat-200 px-3 py-2">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-darkbrown-500">Harga Satuan Alat</p>
                    <p class="text-sm font-extrabold text-darkbrown-900" x-text="formatRupiah(normalTotal())"></p>
                </div>
                <div class="rounded-xl bg-avocado-50 border border-avocado-100 px-3 py-2">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-avocado-700">Harga Paket</p>
                    <p class="text-sm font-extrabold text-darkbrown-900" x-text="formatRupiah(packagePrice())"></p>
                </div>
                <div class="rounded-xl bg-sunglow-100/70 border border-sunglow-200 px-3 py-2">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-goldenrod-700">Hemat Customer</p>
                    <p class="text-sm font-extrabold text-darkbrown-900" x-text="formatRupiah(savings())"></p>
                </div>
            </div>
        @endif
    </div>

    <!-- Foto Paket -->
    <div>
        <x-input-label for="image" value="Foto Paket (Opsional)" class="font-bold text-xs text-darkbrown-800" />
        <div class="mt-2 flex items-center gap-4" x-data="{ imagePreview: null }">
            <div class="w-20 h-20 rounded-2xl bg-wheat-100 border-2 border-dashed border-wheat-300 overflow-hidden flex items-center justify-center shrink-0">
                <template x-if="imagePreview">
                    <img :src="imagePreview" class="w-full h-full object-cover">
                </template>
                <template x-if="!imagePreview">
                    @if ($package?->image_url)
                        <img src="{{ $package->image_url }}" class="w-full h-full object-cover">
                    @else
                        <i data-lucide="image" class="w-8 h-8 text-wheat-400"></i>
                    @endif
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

    <!-- Deskripsi -->
    <div>
        <x-input-label for="description" value="Deskripsi Paket" class="font-bold text-xs text-darkbrown-800" />
        <textarea id="description" name="description" rows="4"
                  class="w-full mt-1.5 text-xs bg-white border border-wheat-300 focus:border-avocado-500 focus:ring-avocado-500 rounded-xl p-3 text-darkbrown-800"
                  placeholder="Jelaskan pengalaman camping, lokasi rekomendasi, dan kelengkapan paket...">{{ old('description', $package->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-1" />
    </div>

    <!-- Buttons -->
    <div class="flex items-center justify-end gap-3 pt-4 border-t border-wheat-200">
        <a href="{{ route('vendor.packages.index') }}"
           class="px-4 py-2.5 rounded-xl text-xs font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 transition">
            Batal
        </a>
        <button type="submit"
                class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-avocado-600 hover:bg-avocado-700 shadow-sm transition">
            {{ $package ? 'Perbarui Paket' : 'Simpan Paket' }}
        </button>
    </div>
</form>
