{{-- Daftar alamat tersimpan (kartu terpilih + dropdown alamat lainnya + empty state). --}}
    {{-- ============================================================
         2. DAFTAR ALAMAT TERSIMPAN
         ============================================================ --}}
    <div class="mt-4 space-y-3">
        @if ($addresses->isNotEmpty())

            {{-- Kartu Alamat Terpilih --}}
            @if ($selectedAddress)
                <div
                    wire:key="addr-{{ $selectedAddress->id }}"
                    class="pf-addr-card"
                    data-default="{{ $selectedAddress->is_default ? 'true' : 'false' }}"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1.5 min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="pf-chip">{{ $selectedAddress->label }}</span>

                                <i
                                    data-lucide="star"
                                    class="w-4 h-4 shrink-0 {{ $selectedAddress->is_default ? 'text-goldenrod-500 fill-current' : 'text-darkbrown-400' }}"
                                    title="{{ $selectedAddress->is_default ? 'Alamat Utama' : 'Bukan alamat utama' }}"
                                    aria-label="{{ $selectedAddress->is_default ? 'Alamat Utama' : 'Bukan alamat utama' }}"
                                ></i>
                            </div>

                            <p class="text-sm text-darkbrown-800 leading-relaxed">
                                {{ $selectedAddress->formatted_address }}
                            </p>

                            @if ($selectedAddress->notes)
                                <p class="text-xs text-darkbrown-600 italic">
                                    Catatan: {{ $selectedAddress->notes }}
                                </p>
                            @endif
                        </div>

                        {{-- Tombol Aksi per Alamat --}}
                        <div class="flex items-center gap-0.5 shrink-0">
                            <button
                                type="button"
                                wire:click="startEditing({{ $selectedAddress->id }})"
                                data-preload-leaflet
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-60 cursor-wait"
                                wire:target="startEditing"
                                class="pf-icon-btn"
                                title="Ubah Alamat"
                                aria-label="Ubah Alamat"
                            >
                                <i data-lucide="edit-3"></i>
                            </button>

                            <button
                                type="button"
                                wire:confirm="Yakin ingin menghapus alamat ini?"
                                wire:click="deleteAddress({{ $selectedAddress->id }})"
                                class="pf-icon-btn pf-icon-btn--danger"
                                title="Hapus Alamat"
                                aria-label="Hapus Alamat"
                            >
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Alamat Lainnya --}}
                @if ($otherAddresses->isNotEmpty())
                    <div class="pf-more px-1" x-data="{ open: false }">
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

                        <ul
                            id="daftar-alamat-lainnya"
                            x-show="open"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2"
                            class="mt-2 divide-y divide-wheat-100"
                        >
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
