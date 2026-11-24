{{-- Daftar alamat tersimpan (kartu terpilih + dropdown alamat lainnya + empty state). --}}
    {{-- ============================================================
         2. DAFTAR ALAMAT TERSIMPAN
         ============================================================ --}}
    <div class="mt-6 space-y-4">
        @if ($addresses->isNotEmpty())

            {{-- Kartu Alamat Terpilih --}}
            @if ($selectedAddress)
                <div
                    wire:key="addr-{{ $selectedAddress->id }}"
                    class="p-4 sm:p-5 rounded-xl border transition-all {{ $selectedAddress->is_default ? 'bg-white border-avocado-400 ring-1 ring-avocado-400/30' : 'bg-white border-wheat-200 hover:border-wheat-300' }}"
                >
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                        <div class="space-y-1.5 min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-wheat-100 text-darkbrown-800 border border-wheat-300">
                                    {{ $selectedAddress->label }}
                                </span>

                                <i
                                    data-lucide="star"
                                    class="w-4 h-4 shrink-0 {{ $selectedAddress->is_default ? 'text-goldenrod-500 fill-current' : 'text-darkbrown-400' }}"
                                    title="{{ $selectedAddress->is_default ? 'Alamat Utama' : 'Bukan alamat utama' }}"
                                    aria-label="{{ $selectedAddress->is_default ? 'Alamat Utama' : 'Bukan alamat utama' }}"
                                ></i>
                            </div>

                            <p class="text-xs sm:text-sm text-darkbrown-800 leading-relaxed">
                                {{ $selectedAddress->formatted_address }}
                            </p>

                            @if ($selectedAddress->notes)
                                <p class="text-xs text-darkbrown-600 italic">
                                    Catatan: {{ $selectedAddress->notes }}
                                </p>
                            @endif
                        </div>

                        {{-- Tombol Aksi per Alamat --}}
                        <div class="flex items-center gap-2 shrink-0 pt-2 sm:pt-0 border-t sm:border-0 border-wheat-100">
                            <button
                                type="button"
                                wire:click="startEditing({{ $selectedAddress->id }})"
                                data-preload-leaflet
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-60 cursor-wait"
                                wire:target="startEditing"
                                class="p-1.5 rounded-lg text-darkbrown-600 hover:text-darkbrown-900 hover:bg-wheat-100 transition"
                                title="Ubah Alamat"
                            >
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>

                            <button
                                type="button"
                                wire:confirm="Yakin ingin menghapus alamat ini?"
                                wire:click="deleteAddress({{ $selectedAddress->id }})"
                                class="p-1.5 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 transition"
                                title="Hapus Alamat"
                            >
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Alamat Lainnya --}}
                @if ($otherAddresses->isNotEmpty())
                    <div class="px-1" x-data="{ open: false }">
                        <button
                            type="button"
                            id="btn-dropdown-alamat-lainnya"
                            @click="open = ! open"
                            :aria-expanded="open"
                            aria-controls="daftar-alamat-lainnya"
                            class="flex w-full items-center justify-between gap-3 text-left cursor-pointer text-xs sm:text-sm font-semibold text-darkbrown-800"
                        >
                            <span>Alamat lainnya ({{ $otherAddresses->count() }})</span>

                            <span class="shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }">
                                <i data-lucide="chevron-down" class="w-4 h-4 text-darkbrown-500"></i>
                            </span>
                        </button>

                        <ul id="daftar-alamat-lainnya" x-show="open" x-cloak class="mt-2 divide-y divide-wheat-100">
                            @foreach ($otherAddresses as $other)
                                <li wire:key="menu-addr-{{ $other->id }}">
                                    <button
                                        type="button"
                                        wire:click="selectAddress({{ $other->id }})"
                                        class="w-full text-left py-2.5 flex items-start gap-2.5 hover:bg-wheat-50 transition rounded-lg px-1"
                                    >
                                        <i
                                            data-lucide="star"
                                            class="w-4 h-4 shrink-0 mt-0.5 {{ $other->is_default ? 'text-goldenrod-500 fill-current' : 'text-darkbrown-400' }}"
                                        ></i>

                                        <span class="min-w-0">
                                            <span class="block text-xs font-bold text-darkbrown-900">
                                                {{ $other->label }}
                                            </span>

                                            <span class="block text-[11px] sm:text-xs text-darkbrown-600 leading-snug">
                                                {{ $other->formatted_address }}
                                            </span>
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif

        @else
            {{-- Tampilan Ketika Belum Ada Alamat --}}
            <div class="text-center py-8 px-4 rounded-xl bg-wheat-50 border border-wheat-200 space-y-3">
                <div class="w-12 h-12 mx-auto rounded-xl bg-wheat-100 text-darkbrown-400 flex items-center justify-center">
                    <i data-lucide="map-pin-off" class="w-6 h-6"></i>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-darkbrown-900">
                        Belum ada alamat tersimpan
                    </h3>

                    <p class="text-xs text-darkbrown-500 mt-0.5">
                        Tambahkan alamat agar Anda dapat memilih lokasi pengantaran saat memesan perlengkapan camping.
                    </p>
                </div>

                @if (! $isCreating)
                    <button
                        type="button"
                        wire:click="startCreating"
                        data-preload-leaflet
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-60 cursor-wait"
                        wire:target="startCreating"
                        class="btn-tendaku-primary inline-flex items-center gap-1.5 py-2 px-4 rounded-xl text-xs font-bold shadow-sm"
                    >
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Tambah Alamat Pertama</span>
                    </button>
                @endif
            </div>
        @endif
    </div>
