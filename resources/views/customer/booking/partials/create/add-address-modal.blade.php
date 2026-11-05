        <!-- Modal Tambah Alamat Baru (Tanpa Meninggalkan Checkout) -->
        <div x-show="showAddAddressModal" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showAddAddressModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 transition-opacity bg-darkbrown-950/60 backdrop-blur-sm"
                     @click="closeAddAddressModal()"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showAddAddressModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block w-full max-w-xl p-6 my-8 text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl border border-wheat-200 sm:my-8">

                    <div class="flex items-center justify-between pb-4 border-b border-wheat-200">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-avocado-100 text-avocado-800 flex items-center justify-center">
                                <i data-lucide="map-pin" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-darkbrown-900 font-heading">Tambah Alamat Pengantaran</h3>
                                <p class="text-xs text-darkbrown-500">Alamat otomatis tersimpan ke akun Anda untuk sewa berikutnya.</p>
                            </div>
                        </div>
                        <button type="button" @click="closeAddAddressModal()" class="text-darkbrown-400 hover:text-darkbrown-700 p-1.5 rounded-lg hover:bg-wheat-100 transition">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div x-show="newAddressErrors.general" x-cloak class="mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700" x-text="newAddressErrors.general"></div>

                    <form @submit.prevent="saveNewAddress()" class="mt-4 space-y-4">
                        <!-- Label Alamat -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1.5">Label Alamat *</label>
                            <div class="flex items-center gap-2 mb-2">
                                <template x-for="l in ['Rumah', 'Kos', 'Kantor']" :key="l">
                                    <button type="button" @click="newAddressForm.label = l"
                                            :class="newAddressForm.label === l ? 'bg-avocado-700 text-white border-avocado-700' : 'bg-wheat-50 text-darkbrown-700 border-wheat-300 hover:bg-wheat-100'"
                                            class="px-3 py-1 text-xs font-semibold rounded-lg border transition" x-text="l"></button>
                                </template>
                            </div>
                            <input type="text" x-model="newAddressForm.label" required placeholder="Cth: Rumah, Kos, Kantor, Villa"
                                   class="{{ $inputBase }} text-xs">
                            <p x-show="newAddressErrors.label" x-text="newAddressErrors.label[0]" class="text-[11px] text-red-600 mt-1"></p>
                        </div>

                        <!-- Nama Penerima & Telepon -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Nama Penerima *</label>
                                <input type="text" x-model="newAddressForm.recipient_name" required placeholder="Nama lengkap penerima"
                                       class="{{ $inputBase }} text-xs">
                                <p x-show="newAddressErrors.recipient_name" x-text="newAddressErrors.recipient_name[0]" class="text-[11px] text-red-600 mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">No. WhatsApp Penerima *</label>
                                <input type="tel" x-model="newAddressForm.phone_number" required placeholder="08xxxxxxxxxx"
                                       class="{{ $inputBase }} text-xs">
                                <p x-show="newAddressErrors.phone_number" x-text="newAddressErrors.phone_number[0]" class="text-[11px] text-red-600 mt-1"></p>
                            </div>
                        </div>

                        <!-- Alamat Lengkap -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Alamat Lengkap (Jalan, No, RT/RW) *</label>
                            <textarea rows="2" x-model="newAddressForm.full_address" required placeholder="Nama jalan, nomor rumah/gedung, RT/RW"
                                      class="{{ $inputBase }} resize-none text-xs"></textarea>
                            <p x-show="newAddressErrors.full_address" x-text="newAddressErrors.full_address[0]" class="text-[11px] text-red-600 mt-1"></p>
                        </div>

                        <!-- Provinsi & Kota -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Provinsi *</label>
                                <input type="text" x-model="newAddressForm.province" required placeholder="Cth: Jawa Barat"
                                       class="{{ $inputBase }} text-xs">
                                <p x-show="newAddressErrors.province" x-text="newAddressErrors.province[0]" class="text-[11px] text-red-600 mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Kota / Kabupaten *</label>
                                <input type="text" x-model="newAddressForm.city" required placeholder="Cth: Bandung"
                                       class="{{ $inputBase }} text-xs">
                                <p x-show="newAddressErrors.city" x-text="newAddressErrors.city[0]" class="text-[11px] text-red-600 mt-1"></p>
                            </div>
                        </div>

                        <!-- Kecamatan & Kelurahan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Kecamatan *</label>
                                <input type="text" x-model="newAddressForm.district" required placeholder="Cth: Coblong"
                                       class="{{ $inputBase }} text-xs">
                                <p x-show="newAddressErrors.district" x-text="newAddressErrors.district[0]" class="text-[11px] text-red-600 mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Kelurahan / Desa</label>
                                <input type="text" x-model="newAddressForm.village" placeholder="Cth: Dago"
                                       class="{{ $inputBase }} text-xs">
                            </div>
                        </div>

                        <!-- Kode Pos & Patokan -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Kode Pos</label>
                                <input type="text" x-model="newAddressForm.postal_code" placeholder="40135" maxlength="10"
                                       class="{{ $inputBase }} text-xs">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Patokan Lokasi</label>
                                <input type="text" x-model="newAddressForm.notes" placeholder="Cth: Depan minimarket, pagar hitam"
                                       class="{{ $inputBase }} text-xs">
                            </div>
                        </div>

                        <!-- Jadikan Utama -->
                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="modal_is_default" x-model="newAddressForm.is_default"
                                   class="rounded border-wheat-300 text-avocado-700 focus:ring-avocado-600">
                            <label for="modal_is_default" class="text-xs text-darkbrown-800 font-medium">Jadikan alamat utama untuk pengantaran berikutnya</label>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-wheat-200">
                            <button type="button" @click="closeAddAddressModal()"
                                    class="px-4 py-2 text-xs font-bold text-darkbrown-700 bg-white border border-wheat-300 hover:bg-wheat-100 rounded-xl transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="newAddressSubmitting"
                                    class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-avocado-700 hover:bg-avocado-800 rounded-xl shadow-sm disabled:opacity-50 transition">
                                <span x-show="!newAddressSubmitting">Simpan & Gunakan</span>
                                <span x-show="newAddressSubmitting">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
