<div>
    <x-card class="h-full">
        <div class="flex h-full flex-col justify-between gap-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400">Rekening Tujuan</p>

                    @if ($bankSetting?->bank_account_number)
                        <p class="mt-2 truncate text-sm font-extrabold text-darkbrown-900">
                            {{ $bankSetting->bank_name ?: 'Bank' }}
                        </p>
                        <p class="font-mono text-sm font-bold text-darkbrown-800">
                            {{ $bankSetting->bank_account_number }}
                        </p>
                        <p class="mt-1 truncate text-[11px] text-darkbrown-500">
                            a.n. {{ $bankSetting->bank_account_name ?: $vendor->name }}
                        </p>
                    @else
                        <p class="mt-2 text-sm font-extrabold text-red-600">Belum diatur</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-darkbrown-500">
                            Lengkapi rekening agar payout dapat ditransfer.
                        </p>
                    @endif
                </div>

                <x-badge :variant="$bankSetting?->bank_account_number ? 'avocado' : 'danger'" size="sm" :dot="true">
                    {{ $bankSetting?->bank_account_number ? 'Aktif' : 'Perlu Diisi' }}
                </x-badge>
            </div>

            <div class="space-y-2">
                <button type="button" wire:click="openModal"
                        class="{{ $bankSetting?->bank_account_number ? 'btn-tendaku-secondary' : 'btn-tendaku-primary' }} w-full !px-3 !py-2 text-xs">
                    <i data-lucide="{{ $bankSetting?->bank_account_number ? 'pencil' : 'plus' }}" class="h-4 w-4"></i>
                    {{ $bankSetting?->bank_account_number ? 'Ubah Rekening' : 'Atur Rekening' }}
                </button>

                @if ($savedMessage)
                    <p class="text-[11px] font-semibold text-avocado-700">{{ $savedMessage }}</p>
                @endif
            </div>
        </div>
    </x-card>

    @if ($showModal)
        <div x-data
             @keydown.escape.window="$wire.closeModal()"
             @click.self="$wire.closeModal()"
             class="fixed inset-0 z-[80] flex items-center justify-center bg-darkbrown-900/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-2xl border border-wheat-200 bg-white shadow-tendaku">
                <div class="flex items-start justify-between gap-4 border-b border-wheat-200 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-darkbrown-900">Rekening Tujuan Pencairan</h3>
                        <p class="mt-1 text-xs leading-relaxed text-darkbrown-500">
                            Rekening ini dipakai untuk transfer settlement yang belum dibayar.
                        </p>
                    </div>

                    <button type="button" wire:click="closeModal"
                            class="rounded-lg p-1.5 text-darkbrown-400 transition hover:bg-wheat-100 hover:text-darkbrown-700"
                            title="Tutup">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4 px-6 py-5">
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-darkbrown-700">
                            Nama Bank <span class="text-red-500">*</span>
                        </span>
                        <input type="text"
                               wire:model="bankName"
                               list="vendor-bank-options"
                               autocomplete="off"
                               maxlength="50"
                               placeholder="Contoh: BCA"
                               class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2.5 text-sm text-darkbrown-800 focus:border-avocado-400 focus:outline-none focus:ring-2 focus:ring-avocado-200">
                        <datalist id="vendor-bank-options">
                            <option value="BCA"></option>
                            <option value="BRI"></option>
                            <option value="BNI"></option>
                            <option value="Mandiri"></option>
                            <option value="BSI"></option>
                            <option value="BTN"></option>
                            <option value="CIMB Niaga"></option>
                            <option value="Permata Bank"></option>
                            <option value="Danamon"></option>
                            <option value="Bank Jatim"></option>
                            <option value="Bank Jago"></option>
                            <option value="SeaBank"></option>
                        </datalist>
                        @error('bankName') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-darkbrown-700">
                            Nomor Rekening <span class="text-red-500">*</span>
                        </span>
                        <input type="text"
                               wire:model="bankAccountNumber"
                               inputmode="numeric"
                               autocomplete="off"
                               maxlength="30"
                               placeholder="Contoh: 1234567890"
                               class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2.5 font-mono text-sm text-darkbrown-800 focus:border-avocado-400 focus:outline-none focus:ring-2 focus:ring-avocado-200">
                        <span class="mt-1 block text-[11px] text-darkbrown-400">Hanya angka, tanpa spasi atau tanda hubung.</span>
                        @error('bankAccountNumber') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-darkbrown-700">
                            Nama Pemilik Rekening <span class="text-red-500">*</span>
                        </span>
                        <input type="text"
                               wire:model="bankAccountName"
                               autocomplete="off"
                               maxlength="150"
                               placeholder="Nama sesuai buku rekening"
                               class="w-full rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2.5 text-sm text-darkbrown-800 focus:border-avocado-400 focus:outline-none focus:ring-2 focus:ring-avocado-200">
                        <span class="mt-1 block text-[11px] text-darkbrown-400">Pastikan nama sama dengan data rekening bank.</span>
                        @error('bankAccountName') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <p class="rounded-xl border border-sunglow-300 bg-sunglow-50 px-3 py-2 text-[11px] leading-relaxed text-goldenrod-800">
                        Perubahan juga diterapkan ke settlement berstatus draft atau approved yang belum dibayar.
                        Settlement yang sudah lunas tidak diubah.
                    </p>

                    <div class="flex items-center justify-end gap-2 border-t border-wheat-200 pt-4">
                        <button type="button" wire:click="closeModal" class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                            Batal
                        </button>
                        <button type="submit"
                                wire:loading.attr="disabled"
                                wire:target="save"
                                class="btn-tendaku-primary !px-3.5 !py-2 text-xs">
                            <span wire:loading.remove wire:target="save">Simpan Rekening</span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
