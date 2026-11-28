                <!-- 4. DATA PENYEWA -->
                <section id="section-identity" class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm scroll-mt-24">
                    @php
                        $identitySubtitle = ($isGuest || $needsName || $needsPhone || $needsNik)
                            ? 'Dipakai vendor untuk mencocokkan identitas saat serah terima alat.'
                            : 'Data diambil dari akun Anda.';
                    @endphp
                    @include('customer.booking.partials.create.section-heading', [
                        'step' => 4,
                        'title' => 'Data Penyewa',
                        'subtitle' => $identitySubtitle,
                        'aside' => $isGuest ? '<a href="'.e(route('login')).'" class="font-bold text-avocado-700 hover:text-avocado-800 underline underline-offset-2 sm:whitespace-nowrap">Sudah punya akun? Masuk</a>' : null,
                    ])

                    <div class="p-5 sm:p-6 space-y-4">

                        {{-- Member: tampilkan data yang sudah ada --}}
                        @if ($isLoggedIn && (! $needsName || ! $needsPhone || ! $needsNik))
                        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 rounded-xl border border-wheat-200 bg-wheat-50/60 px-4 py-3.5 text-sm">
                            @unless ($needsName)
                            <div class="flex items-center gap-3 min-w-0">
                                <dt class="sr-only">Nama</dt>
                                <i data-lucide="user" class="w-5 h-5 text-avocado-800 shrink-0"></i>
                                <dd class="font-bold text-darkbrown-900 truncate">{{ $user->name }}</dd>
                            </div>
                            @endunless
                            @unless ($needsPhone)
                            <div class="flex items-center gap-3 min-w-0 sm:border-l sm:border-wheat-200 sm:pl-4">
                                <dt class="sr-only">WhatsApp</dt>
                                <i data-lucide="message-circle" class="w-5 h-5 text-avocado-800 shrink-0"></i>
                                <dd class="font-bold text-darkbrown-900">{{ $user->phone }}</dd>
                            </div>
                            @endunless
                            @unless ($needsNik)
                            <div class="flex items-center gap-3 min-w-0 sm:border-l sm:border-wheat-200 sm:pl-4">
                                <dt class="sr-only">NIK</dt>
                                <i data-lucide="id-card" class="w-5 h-5 text-avocado-800 shrink-0"></i>
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
                                <div class="flex items-baseline justify-between mb-1.5">
                                    <label for="f_nik" class="text-sm font-semibold text-darkbrown-800">NIK (nomor KTP)</label>
                                    <span class="text-xs tabular-nums" :class="nik.length === 16 ? 'text-avocado-700 font-semibold' : 'text-darkbrown-400'" x-text="nik.length + '/16'"></span>
                                </div>
                                <div class="relative">
                                    <input type="text" id="f_nik"
                                        x-model="nik"
                                        @input="nik = nik.replace(/\D/g, '').slice(0, 16)"
                                        inputmode="numeric"
                                        maxlength="16"
                                        autocomplete="off"
                                        placeholder="16 digit sesuai KTP" aria-describedby="hint_nik"
                                        :class="errors.nik ? '{{ $errBorder }}' : '{{ $okBorder }}'"
                                        class="{{ $inputBase }} font-mono tracking-wide">
                                </div>
                                <p x-show="!errors.nik" id="hint_nik" class="mt-1 text-xs text-darkbrown-500">Sesuai KTP yang dibawa saat ambil alat.</p>
                                <p x-show="errors.nik" x-text="errors.nik" x-cloak class="mt-1 text-sm text-red-700"></p>
                            </div>
                            @endif

                            @if ($needsPhone)
                            <div>
                                <label for="f_phone" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">No. WhatsApp aktif</label>
                                <input type="tel" id="f_phone" x-model="phone" inputmode="numeric" autocomplete="tel" placeholder="08xxxxxxxxxx" aria-describedby="hint_phone" maxlength="13" @input="phone = phone.replace(/\D/g, '').slice(0, 13)"
                                       :class="errors.phone ? '{{ $errBorder }}' : '{{ $okBorder }}'"
                                       class="{{ $inputBase }}">
                                <p x-show="!errors.phone" id="hint_phone" class="mt-1 text-xs text-darkbrown-500">Pastikan nomor aktif di WhatsApp.</p>
                                <p x-show="errors.phone" x-text="errors.phone" x-cloak class="mt-1 text-sm text-red-700"></p>
                            </div>
                            @endif
                        </div>
                        @endif

                        {{-- Tamu: ajakan masuk akun agar proses booking lebih cepat --}}
                        @if ($isGuest)
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-2xl bg-sunglow-100/70 border border-sunglow-200 px-4 py-3.5">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-10 h-10 rounded-full bg-sunglow-200/80 text-goldenrod-700 flex items-center justify-center shrink-0">
                                    <x-icon name="lightbulb" class="w-5 h-5" />
                                </span>
                                <div class="text-sm min-w-0">
                                    <p class="font-bold text-darkbrown-900">Proses pemesanan lebih cepat dengan akun.</p>
                                    <p class="text-darkbrown-700">Daftar atau masuk untuk pengalaman terbaik.</p>
                                </div>
                            </div>
                            <a href="{{ route('login') }}"
                               class="inline-flex min-h-[44px] sm:min-h-0 items-center justify-center rounded-xl border border-goldenrod-400 bg-white px-4 py-2 text-sm font-bold text-darkbrown-900 hover:bg-sunglow-100 transition shrink-0">
                                Masuk Akun
                            </a>
                        </div>
                        @endif
                    </div>
                </section>
