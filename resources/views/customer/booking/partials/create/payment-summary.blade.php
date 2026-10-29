            <!-- ============ KOLOM KANAN: RINGKASAN & BAYAR ============ -->
            <div class="lg:col-span-5 xl:col-span-4 space-y-5 lg:sticky lg:top-24">

                <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm overflow-hidden">
                    <div class="px-5 sm:px-6 py-4 bg-wheat-100 border-b border-wheat-200">
                        <h3 class="text-lg font-extrabold text-darkbrown-900 font-heading">Ringkasan pembayaran</h3>
                    </div>

                    <div class="p-5 sm:p-6 space-y-5">
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between text-darkbrown-600">
                                <span x-text="'Sewa alat (' + totalUnits + ' unit, ' + rentalDays + ' hari)'"></span>
                                <span class="font-bold text-darkbrown-900" x-text="formatRupiah(subtotal)"></span>
                            </div>

                            <div class="flex items-center justify-between text-darkbrown-600">
                                <span>Biaya admin</span>
                                <span class="font-bold text-darkbrown-900" x-text="formatRupiah(adminFee)"></span>
                            </div>

                            <div class="flex items-start justify-between text-darkbrown-600">
                                <div>
                                    <span>Ongkos kirim</span>
                                    <span x-show="pickupMethod === 'delivery'" x-cloak class="block text-xs text-goldenrod-700 font-semibold">Kurir vendor ke lokasi</span>
                                </div>
                                <span class="font-bold text-darkbrown-900" x-text="formatRupiah(deliveryFee)">Rp 0</span>
                            </div>

                            <div x-show="discount > 0" x-cloak class="flex items-center justify-between text-avocado-700">
                                <span>Diskon promo</span>
                                <span class="font-bold" x-text="'- ' + formatRupiah(discount)"></span>
                            </div>
                        </div>

                        <div class="rounded-xl bg-avocado-900 text-white px-4 py-3.5">
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="text-sm text-avocado-100">Total pembayaran</span>
                                <span class="text-2xl font-extrabold tracking-tight font-heading" x-text="formatRupiah(grandTotal)"></span>
                            </div>
                        </div>

                        <p x-show="touched" x-cloak class="text-xs text-darkbrown-500 leading-relaxed">
                            Total dihitung ulang dari perubahan jadwal atau jumlah unit.
                        </p>

                        <!-- Opsi Pembayaran-->
                        <fieldset>
                            <legend class="text-sm font-bold text-darkbrown-900 mb-2.5">Opsi pembayaran</legend>
                            <div class="grid grid-cols-2 gap-3">
                                <label :class="paymentOption === 'full' ? 'border-avocado-600 bg-avocado-50 ring-2 ring-avocado-600/20' : 'border-wheat-300 bg-white hover:border-avocado-400'"
                                       class="rounded-xl border p-3 cursor-pointer transition focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-avocado-600">
                                    <input type="radio" name="ui_payment_option" value="full" x-model="paymentOption" class="sr-only">
                                    <span class="block font-bold text-sm text-darkbrown-900">Bayar lunas</span>
                                    <span class="block mt-0.5 text-xs text-darkbrown-600 leading-relaxed">Langsung terkonfirmasi.</span>
                                    <span class="block mt-1.5 text-sm font-extrabold text-avocado-800" x-text="formatRupiah(grandTotal)"></span>
                                </label>
                                <label :class="paymentOption === 'dp' ? 'border-avocado-600 bg-avocado-50 ring-2 ring-avocado-600/20' : 'border-wheat-300 bg-white hover:border-avocado-400'"
                                       class="rounded-xl border p-3 cursor-pointer transition focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-avocado-600">
                                    <input type="radio" name="ui_payment_option" value="dp" x-model="paymentOption" class="sr-only">
                                    <span class="block font-bold text-sm text-darkbrown-900">Uang muka (DP)</span>
                                    <span class="block mt-0.5 text-xs text-darkbrown-600 leading-relaxed">Bayar <span x-text="dpPercentage + '%'"></span> dulu, sisanya saat ambil alat.</span>
                                    <span class="block mt-1.5 text-sm font-extrabold text-avocado-800" x-text="formatRupiah(dpAmount)"></span>
                                </label>
                            </div>
                        </fieldset>

                        <div x-show="formError" x-cloak role="alert" class="flex items-start gap-2 rounded-xl bg-red-50 border border-red-200 px-3.5 py-3 text-sm text-red-800">
                            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 mt-0.5"></i>
                            <span x-text="formError"></span>
                        </div>

                        <div>
                            <button type="button" x-on:click="submitBooking()" :disabled="isProcessing || expired"
                                    :class="(isProcessing || expired) ? 'opacity-70 cursor-not-allowed' : 'hover:bg-sunglow-400 active:bg-goldenrod-400 hover:shadow-tendaku-lg'"
                                    class="w-full py-3.5 px-5 rounded-xl font-extrabold text-base text-darkbrown-900 bg-sunglow-300 shadow-tendaku transition duration-200 flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-goldenrod-500 focus:ring-offset-2">
                                <template x-if="!isProcessing">
                                </template>
                                <template x-if="isProcessing">
                                    <i data-lucide="loader" class="w-5 h-5 animate-spin"></i>
                                </template>
                                <span x-text="isProcessing ? 'Membuat booking...' : 'Buat booking'"></span>
                                <span x-text="formatRupiah(payableAmount)"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm px-5 py-4 flex items-center justify-between gap-3 text-sm">
                    <div>
                        <div class="font-bold text-darkbrown-900">Butuh bantuan?</div>
                        <div class="text-darkbrown-500">Kendala bayar atau ubah jadwal.</div>
                    </div>
                    <a href="https://wa.me/6281234567890" target="_blank" rel="noopener" class="shrink-0 font-bold text-avocado-700 hover:text-avocado-800">WhatsApp CS</a>
                </div>
            </div>
        </div>
