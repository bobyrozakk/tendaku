                <!-- 2. CARA MENERIMA ALAT -->
                <section id="section-delivery" class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm">
                    <div class="flex items-center gap-3 px-5 sm:px-6 pt-5 sm:pt-6">
                        <span class="w-8 h-8 rounded-full bg-avocado-700 text-white font-bold flex items-center justify-center shrink-0">2</span>
                        <div>
                            <h2 class="text-lg font-bold text-darkbrown-900 font-heading leading-tight">Cara menerima alat</h2>
                            <p class="text-sm text-darkbrown-500">Ambil sendiri di toko, atau diantar kurir vendor.</p>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" role="radiogroup" aria-label="Metode pengambilan">

                            <!-- Ambil di tempat: semua orang -->
                            <label :class="pickupMethod === 'self_pickup' ? 'border-avocado-600 bg-avocado-50 ring-2 ring-avocado-600/20' : 'border-wheat-300 bg-white hover:border-avocado-400'"
                                   class="relative flex gap-3 rounded-2xl border p-4 cursor-pointer transition focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-avocado-600">
                                <input type="radio" name="ui_pickup_method" value="self_pickup" x-model="pickupMethod" class="sr-only">
                                <div class="w-10 h-10 rounded-xl bg-avocado-100 text-avocado-700 flex items-center justify-center shrink-0">
                                    <i data-lucide="store" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0 flex-1 pr-6">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-darkbrown-900">Ambil di toko</span>
                                        <span class="rounded-md bg-avocado-100 border border-avocado-200 px-2 py-0.5 text-xs font-bold text-avocado-800">Gratis</span>
                                    </div>
                                    <p class="mt-1 text-sm text-darkbrown-600 leading-relaxed">Datang ke outlet vendor di {{ $booking['vendor']['city'] }}. Bisa untuk tamu maupun member.</p>
                                </div>
                                <span class="absolute top-3 right-3 w-5 h-5 rounded-full border-2 flex items-center justify-center transition"
                                      :class="pickupMethod === 'self_pickup' ? 'bg-avocado-600 border-avocado-600' : 'border-wheat-400 bg-white'" aria-hidden="true">
                                    <i data-lucide="check" class="w-3 h-3 text-white"></i>
                                </span>
                            </label>

                            <!-- Diantar: hanya member login -->
                            @if ($isLoggedIn)
                            <label :class="pickupMethod === 'delivery' ? 'border-avocado-600 bg-avocado-50 ring-2 ring-avocado-600/20' : 'border-wheat-300 bg-white hover:border-avocado-400'"
                                   class="relative flex gap-3 rounded-2xl border p-4 cursor-pointer transition focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-avocado-600">
                                <input type="radio" name="ui_pickup_method" value="delivery" x-model="pickupMethod" class="sr-only">
                                <div class="w-10 h-10 rounded-xl bg-sunglow-200 text-goldenrod-800 flex items-center justify-center shrink-0">
                                    <i data-lucide="truck" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0 flex-1 pr-6">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-darkbrown-900">Diantar ke lokasi</span>
                                        <span class="rounded-md bg-sunglow-100 border border-sunglow-300 px-2 py-0.5 text-xs font-bold text-darkbrown-900">Rp 20.000</span>
                                    </div>
                                    <p class="mt-1 text-sm text-darkbrown-600 leading-relaxed">Kurir vendor mengantar ke rumah atau titik kumpul trip. Area Malang Raya & Batu.</p>
                                </div>
                                <span class="absolute top-3 right-3 w-5 h-5 rounded-full border-2 flex items-center justify-center transition"
                                      :class="pickupMethod === 'delivery' ? 'bg-avocado-600 border-avocado-600' : 'border-wheat-400 bg-white'" aria-hidden="true">
                                    <i data-lucide="check" x-show="pickupMethod === 'delivery'" class="w-3 h-3 text-white"></i>
                                </span>
                            </label>
                            @else
                            <div class="flex gap-3 rounded-2xl border border-dashed border-wheat-400 bg-wheat-50 p-4" aria-disabled="true">
                                <div class="w-10 h-10 rounded-xl bg-wheat-200 text-darkbrown-500 flex items-center justify-center shrink-0">
                                    <i data-lucide="lock" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <span class="font-bold text-darkbrown-700">Diantar ke lokasi</span>
                                    <p class="mt-1 text-sm text-darkbrown-600 leading-relaxed">Khusus member, karena alamat dan serah terima kurir perlu akun terverifikasi.</p>
                                    <a href="{{ route('login') }}" class="mt-2 inline-block text-sm font-bold text-avocado-700 hover:text-avocado-800 underline underline-offset-2">Masuk atau daftar dulu</a>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Detail: ambil di toko -->
                        <div x-show="pickupMethod === 'self_pickup'" x-cloak class="flex items-start gap-3 rounded-xl bg-wheat-50 border border-wheat-200 p-4 text-sm">
                            <i data-lucide="map-pin" class="w-5 h-5 text-avocado-700 shrink-0 mt-0.5"></i>
                            <div class="space-y-0.5">
                                <div class="font-bold text-darkbrown-900">{{ $booking['vendor']['pickup_location'] }}</div>
                                <div class="text-darkbrown-600">{{ $booking['vendor']['address'] }}</div>
                                <div class="text-darkbrown-600 pt-1">Jam operasional: <strong class="text-darkbrown-800">{{ $booking['vendor']['operational_hours'] }}</strong></div>
                            </div>
                        </div>

                        <!-- Detail: diantar (member saja) -->
                        @if ($isLoggedIn)
                        <div x-show="pickupMethod === 'delivery'" x-cloak class="rounded-xl bg-wheat-50 border border-wheat-200 p-4 space-y-4 text-sm">
                            <div class="flex items-center justify-between gap-3 border-b border-wheat-200/80 pb-3">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="map-pin" class="w-4 h-4 text-avocado-700"></i>
                                    <span class="font-bold text-darkbrown-900">Alamat Pengantaran</span>
                                </div>
                                <button type="button" @click="openAddAddressModal()"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-avocado-700 hover:text-avocado-800 bg-white border border-wheat-300 hover:border-avocado-500 px-3 py-1.5 rounded-lg shadow-sm transition">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    <span>Tambah Alamat</span>
                                </button>
                            </div>

                            <!-- Bila customer memiliki alamat tersimpan -->
                            <template x-if="addresses.length > 0">
                                <div class="space-y-3">
                                    <!-- Kartu Alamat Terpilih -->
                                    <div class="rounded-xl bg-white border-2 border-avocado-600/60 p-3.5 shadow-sm space-y-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-wheat-100 text-darkbrown-800"
                                                      x-text="selectedAddress ? selectedAddress.label : 'Alamat'"></span>
                                                <template x-if="selectedAddress && selectedAddress.is_default">
                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-avocado-100 text-avocado-800">Utama</span>
                                                </template>
                                            </div>
                                            <button type="button" x-show="addresses.length > 1" @click="showAddressPicker = !showAddressPicker"
                                                    class="text-xs font-bold text-avocado-700 hover:text-avocado-800 underline underline-offset-2"
                                                    x-text="showAddressPicker ? 'Tutup Pilihan' : 'Pilih Alamat Lain'"></button>
                                        </div>

                                        <div class="text-xs text-darkbrown-900 space-y-0.5">
                                            <p class="font-bold text-sm text-darkbrown-900">
                                                <span x-text="selectedAddress ? selectedAddress.recipient_name : ''"></span>
                                                <span class="font-normal text-darkbrown-500" x-text="selectedAddress ? ' (' + selectedAddress.phone_number + ')' : ''"></span>
                                            </p>
                                            <p class="text-darkbrown-700 leading-relaxed" x-text="selectedAddress ? (selectedAddress.formatted_address || selectedAddress.full_address) : address"></p>
                                        </div>
                                    </div>

                                    <!-- Dropdown / List Pilihan Alamat Lain -->
                                    <div x-show="showAddressPicker" x-cloak class="rounded-xl bg-white border border-wheat-200 p-3 space-y-2 max-h-60 overflow-y-auto">
                                        <p class="text-xs font-bold text-darkbrown-700">Pilih dari daftar alamat tersimpan:</p>
                                        <div class="space-y-2">
                                            <template x-for="addr in addresses" :key="addr.id">
                                                <div @click="selectAddress(addr.id)"
                                                     :class="selectedAddressId === addr.id ? 'border-avocado-600 bg-avocado-50/50' : 'border-wheat-200 hover:border-wheat-400 bg-white'"
                                                     class="p-2.5 rounded-lg border cursor-pointer transition flex items-start justify-between gap-3">
                                                    <div class="min-w-0 space-y-0.5">
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="text-[10px] font-bold uppercase tracking-wider bg-wheat-100 text-darkbrown-700 px-1.5 py-0.5 rounded" x-text="addr.label"></span>
                                                            <span class="text-xs font-bold text-darkbrown-900" x-text="addr.recipient_name"></span>
                                                            <template x-if="addr.is_default">
                                                                <span class="text-[9px] font-bold uppercase bg-avocado-100 text-avocado-800 px-1.5 py-0.5 rounded">Utama</span>
                                                            </template>
                                                        </div>
                                                        <p class="text-xs text-darkbrown-600 truncate" x-text="addr.formatted_address || addr.full_address"></p>
                                                    </div>
                                                    <div class="shrink-0 mt-0.5">
                                                        <span class="w-4 h-4 rounded-full border flex items-center justify-center"
                                                              :class="selectedAddressId === addr.id ? 'border-avocado-600 bg-avocado-600 text-white' : 'border-wheat-400'">
                                                            <i x-show="selectedAddressId === addr.id" data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Bila belum ada alamat tersimpan -->
                            <template x-if="addresses.length === 0">
                                <div class="rounded-xl border border-dashed border-wheat-300 bg-white p-5 text-center space-y-2.5">
                                    <div class="w-10 h-10 rounded-full bg-wheat-100 text-darkbrown-500 mx-auto flex items-center justify-center">
                                        <i data-lucide="map-pin-off" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-darkbrown-800 text-xs">Belum Ada Alamat Tersimpan</p>
                                        <p class="text-[11px] text-darkbrown-500 mt-0.5">Simpan alamat pengantaran lengkap agar kurir vendor dapat mengirimkan alat ke lokasi Anda.</p>
                                    </div>
                                    <button type="button" @click="openAddAddressModal()"
                                            class="inline-flex items-center gap-1.5 text-xs font-bold text-white bg-avocado-700 hover:bg-avocado-800 px-3.5 py-2 rounded-xl shadow-sm transition">
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                        <span>Tambah Alamat Pengantaran</span>
                                    </button>
                                </div>
                            </template>

                            <!-- Catatan untuk Kurir -->
                            <div class="pt-2">
                                <label for="f_notes" class="block text-xs font-semibold text-darkbrown-800 mb-1">Catatan Tambahan untuk Kurir <span class="font-normal text-darkbrown-500">(opsional)</span></label>
                                <textarea id="f_notes" rows="2" x-model="notes" placeholder="Patokan, warna pagar, jam pengantaran, atau pesan khusus untuk kurir"
                                          :class="errors.notes ? '{{ $errBorder }}' : '{{ $okBorder }}'"
                                          class="{{ $inputBase }} resize-none text-xs"></textarea>
                                <p x-show="errors.notes" x-text="errors.notes" x-cloak class="mt-1 text-xs text-red-700"></p>
                                <p x-show="errors.address" x-text="errors.address" x-cloak class="mt-1 text-xs text-red-700"></p>
                            </div>
                        </div>
                        @endif
                    </div>
                </section>
