<x-layouts.customer>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-xs font-semibold text-darkbrown-600 mb-2">
            <a href="{{ route('customer.packages.show', $package) }}" class="hover:text-avocado-700 transition flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Kembali ke Detail Paket</span>
            </a>
            <span>/</span>
            <span class="text-darkbrown-900 font-bold">Konfirmasi Sewa</span>
        </div>
        <h1 class="font-extrabold text-2xl md:text-3xl text-darkbrown-900 tracking-tight font-heading">
            Sewa Paket Camping
        </h1>
        <p class="text-sm text-darkbrown-600 mt-1">
            Lengkapi data penyewaan, lalu lanjutkan ke pembayaran.
        </p>
    </x-slot>

    @php
        $price = (float) $package->price;
        $vendorName = $package->vendor?->name ?? 'Tendaku Vendor';
        $deliveryFeeAmount = (int) \App\Http\Controllers\Customer\PackageCheckoutController::DELIVERY_FEE;
        $image = $package->image_url
            ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80';
        $user = auth()->user();
        $maskedNik = ($isLoggedIn && filled($user?->nik))
            ? substr($user->nik, 0, 4) . str_repeat('•', max(0, strlen($user->nik) - 8)) . substr($user->nik, -4)
            : null;
        $needsPhone = $isLoggedIn && blank($user?->phone);
        $needsNik = $isLoggedIn && blank($user?->nik);
        $selectedAddrId = old('customer_address_id', $defaultAddress?->id ?? ($addresses->first()?->id ?? null));
        $addressListJson = $addresses->map(fn ($a) => [
            'id' => $a->id,
            'label' => $a->label,
            'recipient_name' => $a->recipient_name,
            'phone_number' => $a->phone_number,
            'full_address' => $a->full_address,
            'province' => $a->province,
            'city' => $a->city,
            'district' => $a->district,
            'village' => $a->village,
            'postal_code' => $a->postal_code,
            'notes' => $a->notes,
            'formatted_address' => $a->formatted_address,
            'delivery_snapshot' => $a->toDeliverySnapshot(),
            'is_default' => (bool) $a->is_default,
        ])->values();
    @endphp

    <form method="POST" action="{{ route('customer.packages.checkout.store', $package) }}"
          class="grid grid-cols-1 lg:grid-cols-12 gap-8 py-2"
          x-data="{
              method: '{{ $pickupMethod }}',
              deliveryFee: {{ $deliveryFeeAmount }},
              packagePrice: {{ $price }},
              pickupDate: '{{ old('pickup_date', $pickupDate->format('Y-m-d')) }}',
              pickupTime: '{{ old('pickup_time', $pickupTime) }}',
              returnDate: '{{ old('return_date', $returnDate->format('Y-m-d')) }}',
              returnTime: '{{ old('return_time', $returnTime) }}',
              durationDays: {{ old('duration_days', $rentalDays) }},
              totalHours: {{ $hours }},
              subtotal: {{ $price * $rentalDays }},
              errorMessage: '',
              isValid: true,

              csrf: '{{ csrf_token() }}',
              storeAddressUrl: '{{ route('customer.addresses.store') }}',
              addresses: @js($addressListJson),
              selectedAddressId: {{ $selectedAddrId ?: 'null' }},
              showAddAddressModal: false,
              newAddressSubmitting: false,
              newAddressErrors: {},
              newAddressForm: {
                  label: 'Rumah',
                  recipient_name: '{{ addslashes($user?->name ?? '') }}',
                  phone_number: '{{ addslashes($user?->phone ?? '') }}',
                  full_address: '',
                  province: 'Jawa Timur',
                  city: 'Malang',
                  district: '',
                  village: '',
                  postal_code: '',
                  notes: '',
                  is_default: false,
              },
              nikInput: '{{ old('nik', $user?->nik ?? '') }}',

              init() {
                  this.recalculate();
                  if (this.selectedAddressId) {
                      const exists = this.addresses.some(a => parseInt(a.id) === parseInt(this.selectedAddressId));
                      if (!exists && this.addresses.length > 0) {
                          this.selectedAddressId = this.addresses[0].id;
                      }
                  } else if (this.addresses.length > 0) {
                      this.selectedAddressId = this.addresses[0].id;
                  }
              },

              openAddAddressModal() {
                  this.newAddressErrors = {};
                  this.showAddAddressModal = true;
                  this.$nextTick(() => {
                      if (typeof window.renderLucideIcons === 'function') {
                          window.renderLucideIcons();
                      }
                  });
              },

              closeAddAddressModal() {
                  this.showAddAddressModal = false;
                  this.newAddressErrors = {};
              },

              async saveNewAddress() {
                  this.newAddressSubmitting = true;
                  this.newAddressErrors = {};
                  try {
                      const resp = await fetch(this.storeAddressUrl, {
                          method: 'POST',
                          headers: {
                              'Content-Type': 'application/json',
                              'Accept': 'application/json',
                              'X-CSRF-TOKEN': this.csrf
                          },
                          body: JSON.stringify(this.newAddressForm)
                      });

                      const data = await resp.json();

                      if (!resp.ok) {
                          this.newAddressErrors = data.errors || { general: data.message || 'Gagal menyimpan alamat.' };
                          this.newAddressSubmitting = false;
                          return;
                      }

                      const created = data.data;
                      if (created.is_default) {
                          this.addresses.forEach(a => a.is_default = false);
                      }
                      this.addresses.unshift(created);
                      this.selectedAddressId = created.id;
                      this.closeAddAddressModal();

                      this.newAddressForm = {
                          label: 'Rumah',
                          recipient_name: '{{ addslashes($user?->name ?? '') }}',
                          phone_number: '{{ addslashes($user?->phone ?? '') }}',
                          full_address: '',
                          province: 'Jawa Timur',
                          city: 'Malang',
                          district: '',
                          village: '',
                          postal_code: '',
                          notes: '',
                          is_default: false,
                      };

                      this.$nextTick(() => {
                          if (typeof window.renderLucideIcons === 'function') {
                              window.renderLucideIcons();
                          }
                      });
                  } catch (err) {
                      this.newAddressErrors = { general: 'Terjadi gangguan jaringan saat menyimpan alamat.' };
                  } finally {
                      this.newAddressSubmitting = false;
                  }
              },

              setDuration(days) {
                  this.durationDays = Math.max(1, parseInt(days) || 1);
                  if (!this.pickupDate) return;
                  const [y, m, d] = this.pickupDate.split('-').map(Number);
                  const [h, min] = (this.pickupTime || '09:00').split(':').map(Number);
                  const pickup = new Date(y, m - 1, d, h, min, 0);

                  if (isNaN(pickup.getTime())) return;

                  const ret = new Date(pickup.getTime() + this.durationDays * 24 * 60 * 60 * 1000);
                  const rY = ret.getFullYear();
                  const rM = String(ret.getMonth() + 1).padStart(2, '0');
                  const rD = String(ret.getDate()).padStart(2, '0');
                  const rH = String(ret.getHours()).padStart(2, '0');
                  const rMin = String(ret.getMinutes()).padStart(2, '0');

                  this.returnDate = `${rY}-${rM}-${rD}`;
                  this.returnTime = `${rH}:${rMin}`;
                  this.recalculate();
              },

              onPickupChange() {
                  this.setDuration(this.durationDays);
              },

              onReturnChange() {
                  this.recalculate();
              },

              recalculate() {
                  this.errorMessage = '';
                  if (!this.pickupDate || !this.pickupTime || !this.returnDate || !this.returnTime) {
                      this.isValid = false;
                      return;
                  }

                  const [pY, pM, pD] = this.pickupDate.split('-').map(Number);
                  const [pH, pMin] = this.pickupTime.split(':').map(Number);
                  const pickup = new Date(pY, pM - 1, pD, pH, pMin, 0);

                  const [rY, rM, rD] = this.returnDate.split('-').map(Number);
                  const [rH, rMin] = this.returnTime.split(':').map(Number);
                  const ret = new Date(rY, rM - 1, rD, rH, rMin, 0);

                  if (isNaN(pickup.getTime()) || isNaN(ret.getTime())) {
                      this.isValid = false;
                      return;
                  }

                  const now = new Date();
                  if (pickup.getTime() < now.getTime() - 60000) {
                      this.errorMessage = 'Waktu pengambilan tidak boleh di masa lalu.';
                      this.isValid = false;
                      return;
                  }

                  const diffMs = ret.getTime() - pickup.getTime();
                  const diffHours = diffMs / (1000 * 60 * 60);

                  if (diffMs <= 0) {
                      this.errorMessage = 'Waktu pengembalian wajib setelah waktu pengambilan (tidak boleh sama atau lebih awal).';
                      this.isValid = false;
                      this.durationDays = 0;
                      this.totalHours = 0;
                      this.subtotal = 0;
                      return;
                  }

                  this.totalHours = Math.round(diffHours * 10) / 10;
                  this.durationDays = Math.max(1, Math.ceil(diffHours / 24));
                  this.subtotal = this.durationDays * this.packagePrice;
                  this.isValid = true;
              },

              formatRupiah(val) {
                  return 'Rp ' + (Math.round(val) || 0).toLocaleString('id-ID');
              },

              formatDateIndo(dateStr) {
                  if (!dateStr) return '';
                  const parts = dateStr.split('-');
                  if (parts.length !== 3) return dateStr;
                  return `${parts[2]}/${parts[1]}/${parts[0]}`;
              }
          }">
        @csrf
        <input type="hidden" name="package_id" value="{{ $package->id }}" />

        <!-- Kolom Kiri: Form Data Penyewa -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Jadwal Sewa -->
            <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku space-y-4">
                <h2 class="text-base font-extrabold text-darkbrown-900 border-b border-wheat-200 pb-3 font-heading flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <i data-lucide="calendar-days" class="w-4 h-4 text-avocado-600"></i>
                        Jadwal Sewa & Waktu Pengambilan
                    </span>
                </h2>

                <!-- Waktu Pengambilan -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-700">
                            Waktu Pengambilan Barang *
                        </label>
                        <span class="text-[10px] text-darkbrown-500 font-medium">Mulai tanggal & jam pengambilan</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <input type="date" id="pickup_date" name="pickup_date"
                                   x-model="pickupDate"
                                   @change="onPickupChange()"
                                   min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                                   required
                                   class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300" />
                            <x-input-error :messages="$errors->get('pickup_date')" class="mt-1" />
                        </div>
                        <div>
                            <input type="time" id="pickup_time" name="pickup_time"
                                   x-model="pickupTime"
                                   @change="onPickupChange()"
                                   required
                                   class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300" />
                            <x-input-error :messages="$errors->get('pickup_time')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Durasi Sewa -->
                <div class="flex items-center justify-between py-2 px-3 rounded-xl bg-wheat-50 border border-wheat-200">
                    <span class="text-[11px] font-bold text-darkbrown-800 flex items-center gap-1.5">
                        <i data-lucide="clock" class="w-3.5 h-3.5 text-avocado-600"></i>
                        Durasi Sewa
                    </span>
                    <div class="flex items-center gap-1">
                        <button type="button"
                                @click="setDuration(Math.max(1, durationDays - 1))"
                                class="w-7 h-7 rounded-md border border-wheat-300 bg-white hover:bg-wheat-100 text-darkbrown-800 font-bold flex items-center justify-center transition shadow-sm text-xs">
                            -
                        </button>
                        <div class="w-9 h-7 text-center font-bold text-xs text-darkbrown-900 bg-white flex items-center justify-center rounded-md border border-wheat-300 shadow-inner">
                            <span x-text="durationDays">{{ $rentalDays }}</span>
                        </div>
                        <button type="button"
                                @click="setDuration(durationDays + 1)"
                                class="w-7 h-7 rounded-md border border-wheat-300 bg-white hover:bg-wheat-100 text-darkbrown-800 font-bold flex items-center justify-center transition shadow-sm text-xs">
                            +
                        </button>
                    </div>
                </div>

                <!-- Waktu Pengembalian -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-700">
                            Waktu Pengembalian Barang *
                        </label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <input type="date" id="return_date" name="return_date"
                                   x-model="returnDate"
                                   @change="onReturnChange()"
                                   :min="pickupDate"
                                   required
                                   class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300" />
                            <x-input-error :messages="$errors->get('return_date')" class="mt-1" />
                        </div>
                        <div>
                            <input type="time" id="return_time" name="return_time"
                                   x-model="returnTime"
                                   @change="onReturnChange()"
                                   required
                                   class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300" />
                            <x-input-error :messages="$errors->get('return_time')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Pesan Error Validasi Tanggal/Waktu -->
                <div x-show="errorMessage" x-cloak class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-start gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0 mt-0.5"></i>
                    <span x-text="errorMessage"></span>
                </div>

                <div>
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                        Metode Pengambilan
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                               :class="method === 'self_pickup' ? 'border-avocado-500 bg-avocado-50' : 'border-wheat-300 bg-white hover:bg-wheat-50'">
                            <input type="radio" name="pickup_method" value="self_pickup" x-model="method"
                                   class="mt-0.5 text-avocado-600 focus:ring-avocado-500" />
                            <span>
                                <span class="block text-xs font-bold text-darkbrown-900">Ambil di Toko</span>
                                <span class="block text-[11px] text-darkbrown-500">Gratis, ambil langsung di {{ $vendorName }}.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                               :class="method === 'delivery' ? 'border-avocado-500 bg-avocado-50' : 'border-wheat-300 bg-white hover:bg-wheat-50'">
                            <input type="radio" name="pickup_method" value="delivery" x-model="method"
                                   class="mt-0.5 text-avocado-600 focus:ring-avocado-500" />
                            <span>
                                <span class="block text-xs font-bold text-darkbrown-900">
                                    Diantar (+Rp {{ number_format($deliveryFeeAmount, 0, ',', '.') }})
                                </span>
                                <span class="block text-[11px] text-darkbrown-500">
                                    Pilih alamat pengantaran yang sudah tersimpan di akun Anda.
                                </span>
                            </span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('pickup_method')" class="mt-1" />

                    @if ($isLoggedIn)
                        <div x-show="method === 'delivery'" x-cloak class="mt-4 pt-4 border-t border-wheat-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-700">
                                    Pilih Alamat Pengantaran *
                                </label>
                                <button type="button" @click="openAddAddressModal()"
                                        class="text-[11px] font-bold text-avocado-700 hover:text-avocado-800 underline">
                                    Tambah Alamat Baru
                                </button>
                            </div>

                            <div x-show="addresses.length > 0" class="space-y-2">
                                <template x-for="addr in addresses" :key="addr.id">
                                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition hover:bg-wheat-50"
                                           :class="parseInt(selectedAddressId) === parseInt(addr.id) ? 'border-avocado-600 bg-avocado-50/50 ring-1 ring-avocado-600' : 'border-wheat-200 bg-white'">
                                        <input type="radio" name="customer_address_id" :value="addr.id"
                                               x-model="selectedAddressId"
                                               class="mt-1 text-avocado-600 focus:ring-avocado-500" />
                                        <div class="min-w-0 flex-1 text-xs">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-wheat-100 text-darkbrown-800" x-text="addr.label"></span>
                                                <span class="font-bold text-darkbrown-900" x-text="addr.recipient_name"></span>
                                                <span class="text-darkbrown-500" x-text="'(' + addr.phone_number + ')'"></span>
                                                <template x-if="addr.is_default">
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-avocado-100 text-avocado-800">Utama</span>
                                                </template>
                                            </div>
                                            <p class="text-darkbrown-600 mt-1 leading-relaxed" x-text="addr.formatted_address"></p>
                                        </div>
                                    </label>
                                </template>
                            </div>

                            <div x-show="addresses.length === 0" x-cloak class="rounded-xl border border-dashed border-wheat-300 p-4 bg-wheat-50 space-y-3 text-center">
                                <p class="text-xs text-darkbrown-700 font-semibold">Anda belum memiliki alamat tersimpan di profil.</p>
                                <button type="button" @click="openAddAddressModal()"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-avocado-700 hover:bg-avocado-800 rounded-xl shadow-sm transition">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    <span>Tambah Alamat Baru</span>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('customer_address_id')" class="mt-1" />
                            <x-input-error :messages="$errors->get('delivery_address')" class="mt-1" />
                        </div>
                    @endif
                </div>
            </div>

            <!-- Data Penyewa -->
            <section id="section-identity" class="card-tendaku rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm">
                <div class="flex items-center gap-3 px-5 sm:px-6 pt-5 sm:pt-6">
                    <span class="w-8 h-8 rounded-full bg-avocado-700 text-white font-bold flex items-center justify-center shrink-0">1</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-darkbrown-900 font-heading leading-tight">Data penyewa</h2>
                            @if ($isLoggedIn)
                                <span class="text-[10px] font-semibold text-avocado-800 bg-avocado-100 border border-avocado-200 px-2 py-0.5 rounded-md">
                                    Akun Terhubung
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-darkbrown-500">
                            @if ($isLoggedIn)
                                Terhubung dengan {{ $user->email }}. Dipakai vendor untuk mencocokkan identitas saat serah terima alat.
                            @else
                                Dipakai vendor untuk mencocokkan identitas saat serah terima alat.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="p-5 sm:p-6 space-y-4">
                    @if ($isLoggedIn)
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div class="rounded-xl bg-wheat-50 border border-wheat-200 px-3.5 py-2.5">
                                <dt class="text-darkbrown-500 text-xs">Nama</dt>
                                <dd class="font-bold text-darkbrown-900 truncate mt-0.5">{{ $user->name }}</dd>
                            </div>
                            @if (filled($user->phone))
                                <div class="rounded-xl bg-wheat-50 border border-wheat-200 px-3.5 py-2.5">
                                    <dt class="text-darkbrown-500 text-xs">WhatsApp</dt>
                                    <dd class="font-bold text-darkbrown-900 mt-0.5">{{ $user->phone }}</dd>
                                </div>
                            @endif
                            @if (filled($user->nik))
                                <div class="rounded-xl bg-wheat-50 border border-wheat-200 px-3.5 py-2.5 sm:col-span-2">
                                    <dt class="text-darkbrown-500 text-xs">NIK</dt>
                                    <dd class="font-mono font-bold text-darkbrown-900 mt-0.5">{{ $maskedNik }}</dd>
                                </div>
                            @endif
                        </dl>

                        @if ($needsPhone || $needsNik)
                            <div class="space-y-3">
                                @if ($needsNik)
                                    <div>
                                        <label for="nik" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">NIK (nomor KTP)</label>
                                        <div class="relative">
                                            <input type="text" id="nik" name="nik" x-model="nikInput"
                                                   @input="nikInput = nikInput.replace(/\D/g, '').slice(0, 16)"
                                                   inputmode="numeric" maxlength="16" autocomplete="off" placeholder="16 digit angka"
                                                   value="{{ old('nik') }}"
                                                   class="input-tendaku w-full py-2.5 px-3 rounded-xl text-sm font-mono tracking-wide border-wheat-300 pr-14" required>
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono"
                                                  :class="nikInput.length === 16 ? 'text-avocado-700 font-bold' : 'text-darkbrown-400'"
                                                  x-text="nikInput.length + '/16'"></span>
                                        </div>
                                        <p x-show="nikInput.length > 0 && nikInput.length < 16" x-cloak class="mt-1 text-sm text-red-700">NIK harus 16 digit angka.</p>
                                        <x-input-error :messages="$errors->get('nik')" class="mt-1" />
                                    </div>
                                @endif

                                @if ($needsPhone)
                                    <div>
                                        <label for="phone" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">No. WhatsApp aktif</label>
                                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                                               inputmode="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" required
                                               class="input-tendaku w-full py-2.5 px-3 rounded-xl text-sm font-medium border-wheat-300">
                                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="flex items-start gap-2.5 rounded-xl bg-sunglow-100/70 border border-sunglow-300/70 px-3.5 py-3 text-sm text-darkbrown-800">
                            <i data-lucide="credit-card" class="w-5 h-5 text-goldenrod-700 shrink-0 mt-0.5"></i>
                            <span>Bawa <strong>KTP asli</strong> dan nomor booking saat mengambil alat. KTP ditahan toko selama masa sewa dan dikembalikan setelah alat diperiksa.</span>
                        </div>

                        @if (filled($user->phone))
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 rounded-xl bg-avocado-50 border border-avocado-200 px-3.5 py-3 text-sm text-avocado-950">
                                <span>
                                    Pengingat pengembalian (H-1 &amp; hari pengembalian) dikirim ke <strong>{{ $user->phone }}</strong>.
                                </span>
                                <a href="{{ route('profile') }}" class="font-bold text-avocado-800 underline underline-offset-2 hover:text-avocado-900 shrink-0">
                                    Ubah di profil
                                </a>
                            </div>
                        @endif
                    @else
                        {{-- Guest form --}}
                        <div class="p-3 rounded-xl bg-sunglow-50 border border-sunglow-200">
                            <p class="text-xs font-bold text-darkbrown-900">Pemesanan tanpa akun (guest)</p>
                            <p class="text-[11px] text-darkbrown-600 mt-0.5">
                                Sudah punya akun?
                                <a href="{{ route('login') }}" class="font-bold text-avocado-700 underline">Masuk di sini</a>
                                agar data terisi otomatis dan riwayat sewa tersimpan.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label for="guest_name" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                                    Nama Lengkap *
                                </label>
                                <input type="text" id="guest_name" name="guest_name" value="{{ old('guest_name') }}"
                                       required placeholder="Sesuai KTP"
                                       class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300" />
                                <x-input-error :messages="$errors->get('guest_name')" class="mt-1" />
                            </div>

                            <div>
                                <label for="guest_phone" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                                    Nomor WhatsApp *
                                </label>
                                <input type="text" id="guest_phone" name="guest_phone" value="{{ old('guest_phone') }}"
                                       required placeholder="08xxxxxxxxxx"
                                       class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300" />
                                <x-input-error :messages="$errors->get('guest_phone')" class="mt-1" />
                            </div>

                            <div>
                                <label for="guest_id_number" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                                    NIK (KTP) *
                                </label>
                                <input type="text" id="guest_id_number" name="guest_id_number" value="{{ old('guest_id_number') }}"
                                       required placeholder="16 digit NIK"
                                       class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300" />
                                <x-input-error :messages="$errors->get('guest_id_number')" class="mt-1" />
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <!-- Metode Pembayaran -->
            <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku space-y-4">
                <h2 class="text-base font-extrabold text-darkbrown-900 border-b border-wheat-200 pb-3 font-heading flex items-center gap-2">
                    <i data-lucide="credit-card" class="w-4 h-4 text-avocado-600"></i>
                    Metode Pembayaran
                </h2>

                <div>
                    <label for="payment_channel" class="block text-[11px] font-bold uppercase tracking-wider text-darkbrown-600 mb-1.5">
                        Pilih Kanal Pembayaran
                    </label>
                    <select id="payment_channel" name="payment_channel"
                            class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                        <option value="bca_va" @selected(old('payment_channel') === 'bca_va')>BCA Virtual Account</option>
                        <option value="bri_va" @selected(old('payment_channel') === 'bri_va')>BRI Virtual Account (BRIVA)</option>
                    </select>
                    <p class="text-[11px] text-darkbrown-400 mt-1">
                        Instruksi pembayaran tampil pada halaman status setelah pesanan dibuat.
                    </p>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Ringkasan Pesanan -->
        <div class="lg:col-span-5">
            <div class="lg:sticky lg:top-24 space-y-4">
                <div class="card-tendaku rounded-2xl bg-white border border-wheat-200 shadow-tendaku overflow-hidden">
                    <div class="relative aspect-[16/9] bg-wheat-100">
                        <img src="{{ $image }}" alt="{{ $package->name }}" class="w-full h-full object-cover" />
                        <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-darkbrown-900/85 text-wheat-200 backdrop-blur-md"
                              x-text="durationDays > 0 ? durationDays + ' Hari Sewa' : 'Pilih Jadwal'">
                            {{ $rentalDays }} Hari Sewa
                        </span>
                    </div>

                    <div class="p-5 space-y-4">
                        <div>
                            <p class="text-[11px] font-semibold text-goldenrod-800 flex items-center gap-1.5">
                                <i data-lucide="store" class="w-3.5 h-3.5"></i>
                                {{ $vendorName }}
                            </p>
                            <h2 class="text-base font-extrabold text-darkbrown-900 font-heading mt-1">
                                {{ $package->name }}
                            </h2>
                        </div>

                        <ul class="text-[11px] text-darkbrown-600 space-y-1 max-h-40 overflow-y-auto pr-1">
                            @foreach ($package->packageItems as $packageItem)
                                <li class="flex items-center justify-between gap-2 py-1 border-b border-wheat-100 last:border-0">
                                    <span class="truncate">{{ $packageItem->masterItem?->name ?? 'Produk' }}</span>
                                    <span class="font-bold text-darkbrown-900 shrink-0">{{ $packageItem->quantity }}x</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="pt-3 border-t border-wheat-200 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-darkbrown-600">Harga paket / 24 jam</span>
                                <span class="font-bold text-darkbrown-900">Rp {{ number_format($price, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-darkbrown-600">
                                    Subtotal (<span x-text="durationDays + ' hari sewa'">{{ $rentalDays }} hari sewa</span>)
                                </span>
                                <span class="font-bold text-darkbrown-900" x-text="formatRupiah(subtotal)">
                                    Rp {{ number_format($price * $rentalDays, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between" x-show="method === 'delivery'" x-cloak>
                                <span class="text-darkbrown-600">Biaya pengantaran</span>
                                <span class="font-bold text-darkbrown-900">Rp {{ number_format($deliveryFeeAmount, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-wheat-200">
                                <span class="font-bold text-darkbrown-900">Total Bayar</span>
                                <span class="text-lg font-extrabold text-avocado-800 font-heading"
                                      x-text="formatRupiah(subtotal + (method === 'delivery' ? deliveryFee : 0))">
                                    Rp {{ number_format(($price * $rentalDays) + ($pickupMethod === 'delivery' ? $deliveryFeeAmount : 0), 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <button type="submit"
                                :disabled="!isValid"
                                class="btn-tendaku-primary w-full py-3 rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            Buat Pesanan & Lanjut Bayar
                        </button>

                        <p class="text-[10px] text-darkbrown-500 text-center leading-relaxed">
                            Dengan menekan tombol di atas, Anda menyetujui syarat sewa Tendaku.
                            Pembayaran dilunasi sebelum pengambilan unit.
                        </p>
                    </div>
                </div>

                <div class="card-tendaku rounded-2xl bg-white p-4 border border-wheat-200 text-[11px] text-darkbrown-600 space-y-2">
                    <p class="flex items-center gap-2">
                        <i data-lucide="calendar-check" class="w-3.5 h-3.5 text-avocado-600 shrink-0"></i>
                        <span>
                            Mulai <strong x-text="formatDateIndo(pickupDate) + ' ' + pickupTime"></strong> &middot; kembali <strong x-text="formatDateIndo(returnDate) + ' ' + returnTime"></strong>
                        </span>
                    </p>
                    <p class="flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-avocado-600 shrink-0"></i>
                        Jaminan KTP asli, tanpa deposit uang tunai.
                    </p>
                </div>
            </div>
        </div>

        <!-- Modal Tambah Alamat Baru -->
        <div x-show="showAddAddressModal" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-darkbrown-950/60 backdrop-blur-sm transition-opacity"
                 @click="closeAddAddressModal()"></div>

            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl border border-wheat-200 text-left transition-all"
                     @click.away="closeAddAddressModal()">
                    <div class="flex items-center justify-between border-b border-wheat-200 pb-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-5 h-5 text-avocado-700"></i>
                            <h3 class="text-base font-bold text-darkbrown-900 font-heading">Tambah Alamat Pengantaran</h3>
                        </div>
                        <button type="button" @click="closeAddAddressModal()" class="text-darkbrown-400 hover:text-darkbrown-700 p-1.5 rounded-lg hover:bg-wheat-100 transition">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div x-show="newAddressErrors.general" x-cloak class="mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700" x-text="newAddressErrors.general"></div>

                    <div class="mt-4 space-y-4" @keydown.enter.prevent="saveNewAddress()">
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
                                   class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                            <p x-show="newAddressErrors.label" x-text="newAddressErrors.label[0]" class="text-[11px] text-red-600 mt-1"></p>
                        </div>

                        <!-- Nama Penerima & Telepon -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Nama Penerima *</label>
                                <input type="text" x-model="newAddressForm.recipient_name" required placeholder="Nama lengkap penerima"
                                       class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                                <p x-show="newAddressErrors.recipient_name" x-text="newAddressErrors.recipient_name[0]" class="text-[11px] text-red-600 mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">No. WhatsApp Penerima *</label>
                                <input type="tel" x-model="newAddressForm.phone_number" required placeholder="08xxxxxxxxxx"
                                       class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                                <p x-show="newAddressErrors.phone_number" x-text="newAddressErrors.phone_number[0]" class="text-[11px] text-red-600 mt-1"></p>
                            </div>
                        </div>

                        <!-- Alamat Lengkap -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Alamat Lengkap (Jalan, No, RT/RW) *</label>
                            <textarea rows="2" x-model="newAddressForm.full_address" required placeholder="Nama jalan, nomor rumah/gedung, RT/RW"
                                      class="input-tendaku w-full py-2 px-3 rounded-xl resize-none text-xs font-medium border-wheat-300"></textarea>
                            <p x-show="newAddressErrors.full_address" x-text="newAddressErrors.full_address[0]" class="text-[11px] text-red-600 mt-1"></p>
                        </div>

                        <!-- Provinsi & Kota -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Provinsi *</label>
                                <input type="text" x-model="newAddressForm.province" required placeholder="Cth: Jawa Timur"
                                       class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                                <p x-show="newAddressErrors.province" x-text="newAddressErrors.province[0]" class="text-[11px] text-red-600 mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Kota / Kabupaten *</label>
                                <input type="text" x-model="newAddressForm.city" required placeholder="Cth: Malang"
                                       class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                                <p x-show="newAddressErrors.city" x-text="newAddressErrors.city[0]" class="text-[11px] text-red-600 mt-1"></p>
                            </div>
                        </div>

                        <!-- Kecamatan & Kelurahan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Kecamatan *</label>
                                <input type="text" x-model="newAddressForm.district" required placeholder="Cth: Lowokwaru"
                                       class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                                <p x-show="newAddressErrors.district" x-text="newAddressErrors.district[0]" class="text-[11px] text-red-600 mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Kelurahan / Desa</label>
                                <input type="text" x-model="newAddressForm.village" placeholder="Cth: Ketawanggede"
                                       class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                            </div>
                        </div>

                        <!-- Kode Pos & Patokan -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Kode Pos</label>
                                <input type="text" x-model="newAddressForm.postal_code" placeholder="65145" maxlength="10"
                                       class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-700 mb-1">Patokan Lokasi</label>
                                <input type="text" x-model="newAddressForm.notes" placeholder="Cth: Seberang minimarket, pagar hitam"
                                       class="input-tendaku w-full py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                            </div>
                        </div>

                        <!-- Jadikan Utama -->
                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="modal_pkg_is_default" x-model="newAddressForm.is_default"
                                   class="rounded border-wheat-300 text-avocado-700 focus:ring-avocado-600">
                            <label for="modal_pkg_is_default" class="text-xs text-darkbrown-800 font-medium">Jadikan alamat utama untuk pengantaran berikutnya</label>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-wheat-200">
                            <button type="button" @click="closeAddAddressModal()"
                                    class="px-4 py-2 text-xs font-bold text-darkbrown-700 bg-white border border-wheat-300 hover:bg-wheat-100 rounded-xl transition">
                                Batal
                            </button>
                            <button type="button" @click="saveNewAddress()" :disabled="newAddressSubmitting"
                                    class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-avocado-700 hover:bg-avocado-800 rounded-xl shadow-sm disabled:opacity-50 transition">
                                <span x-show="!newAddressSubmitting">Simpan & Gunakan</span>
                                <span x-show="newAddressSubmitting">Menyimpan...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-layouts.customer>
