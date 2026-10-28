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

              init() {
                  this.recalculate();
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
                                    Dikirim ke alamat pada profil akun Anda.
                                </span>
                            </span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('pickup_method')" class="mt-1" />
                </div>
            </div>

            <!-- Data Penyewa -->
            <div class="card-tendaku rounded-2xl bg-white p-6 border border-wheat-200 shadow-tendaku space-y-4">
                <h2 class="text-base font-extrabold text-darkbrown-900 border-b border-wheat-200 pb-3 font-heading flex items-center gap-2">
                    <i data-lucide="user-round" class="w-4 h-4 text-avocado-600"></i>
                    Data Penyewa
                </h2>

                <div class="p-3.5 rounded-xl bg-avocado-50 border border-avocado-200">
                    <p class="text-xs font-bold text-avocado-900">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-avocado-800 mt-0.5">
                        {{ auth()->user()->email }}
                        @if (auth()->user()->phone)
                            &middot; {{ auth()->user()->phone }}
                        @endif
                    </p>
                    <p class="text-[11px] text-avocado-700 mt-1">
                        Data penyewa otomatis menggunakan akun profil Anda.
                    </p>
                </div>
            </div>

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
    </form>
</x-layouts.customer>
