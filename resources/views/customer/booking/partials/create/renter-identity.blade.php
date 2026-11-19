                <!-- DATA PENYEWA -->
                <section id="section-identity" class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm">
                    <div class="flex items-center gap-3 px-5 sm:px-6 pt-5 sm:pt-6">
                        <span class="w-8 h-8 rounded-full bg-avocado-700 text-white font-bold flex items-center justify-center shrink-0">1</span>
                        <div>
                            <h2 class="text-lg font-bold text-darkbrown-900 font-heading leading-tight">Data penyewa</h2>
                            <p class="text-sm text-darkbrown-500">
                                @if ($isGuest || $needsName || $needsPhone || $needsNik)
                                    Dipakai vendor untuk mencocokkan identitas saat serah terima alat.
                                @else
                                    Data diambil dari akun Anda. Sudah lengkap.
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 space-y-4">

                        {{-- Member: tampilkan data yang sudah ada --}}
                        @if ($isLoggedIn && (! $needsName || ! $needsPhone || ! $needsNik))
                        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                            @unless ($needsName)
                            <div class="rounded-xl bg-wheat-50 border border-wheat-200 px-3.5 py-2.5">
                                <dt class="text-darkbrown-500">Nama</dt>
                                <dd class="font-bold text-darkbrown-900 truncate">{{ $user->name }}</dd>
                            </div>
                            @endunless
                            @unless ($needsPhone)
                            <div class="rounded-xl bg-wheat-50 border border-wheat-200 px-3.5 py-2.5">
                                <dt class="text-darkbrown-500">WhatsApp</dt>
                                <dd class="font-bold text-darkbrown-900">{{ $user->phone }}</dd>
                            </div>
                            @endunless
                            @unless ($needsNik)
                            <div class="rounded-xl bg-wheat-50 border border-wheat-200 px-3.5 py-2.5">
                                <dt class="text-darkbrown-500">NIK</dt>
                                <dd class="font-mono font-bold text-darkbrown-900">{{ $maskedNik }}</dd>
                            </div>
                            @endunless
                        </dl>
                        @endif

                        {{-- Input hanya untuk data yang belum ada (tamu: selalu semua) --}}
                        @if ($needsName || $needsPhone || $needsNik)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @if ($needsName)
                            <div class="sm:col-span-2">
                                <label for="f_name" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">Nama lengkap</label>
                                <input type="text" id="f_name" x-model="name" autocomplete="name" placeholder="Nama sesuai KTP"
                                       :class="errors.name ? '{{ $errBorder }}' : '{{ $okBorder }}'"
                                       class="{{ $inputBase }}">
                                <p x-show="errors.name" x-text="errors.name" x-cloak class="mt-1 text-sm text-red-700"></p>
                            </div>
                            @endif

                            @if ($needsNik)
                            <div>
                                <label for="f_nik" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">NIK (nomor KTP)</label>
                                <div class="relative">
                                    <input type="text" id="f_nik"
                                        x-model="nik"
                                        @input="nik = nik.replace(/\D/g, '').slice(0, 16)"
                                        inputmode="numeric"
                                        maxlength="16"
                                        autocomplete="off"
                                        placeholder="Masukkan NIK"
                                        :class="errors.nik ? '{{ $errBorder }}' : '{{ $okBorder }}'"
                                        class="{{ $inputBase }} font-mono tracking-wide">
                                </div>
                                <p x-show="errors.nik" x-text="errors.nik" x-cloak class="mt-1 text-sm text-red-700"></p>
                            </div>
                            @endif

                            @if ($needsPhone)
                            <div>
                                <label for="f_phone" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">No. WhatsApp aktif</label>
                                <input type="tel" id="f_phone" x-model="phone" inputmode="numeric" autocomplete="tel" placeholder="Masukkan nomor" maxlength="13" @input="phone = phone.replace(/\D/g, '').slice(0, 13)"
                                       :class="errors.phone ? '{{ $errBorder }}' : '{{ $okBorder }}'"
                                       class="{{ $inputBase }}">
                                <p x-show="errors.phone" x-text="errors.phone" x-cloak class="mt-1 text-sm text-red-700"></p>
                            </div>
                            @endif
                        </div>
                        @endif

                        <div class="flex items-start gap-2.5 rounded-xl bg-sunglow-100/70 border border-sunglow-300/70 px-3.5 py-3 text-sm text-darkbrown-800">
                            <i data-lucide="credit-card" class="w-5 h-5 text-goldenrod-700 shrink-0 mt-0.5"></i>
                            <span>Bawa <strong>KTP asli</strong> dan nomor booking saat mengambil alat. KTP ditahan toko selama masa sewa dan dikembalikan setelah alat diperiksa.</span>
                        </div>

                    </div>
                </section>
