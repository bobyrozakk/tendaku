            <div id="checkout-summary" class="space-y-4 lg:sticky lg:top-24 scroll-mt-24">

                <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm overflow-hidden">
                    <div class="px-5 sm:px-6 py-4 bg-wheat-50 border-b border-wheat-200">
                        <h3 class="text-lg font-extrabold text-darkbrown-900 font-heading">Ringkasan pembayaran</h3>
                    </div>

                    <div class="p-5 sm:p-6 space-y-5">
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between text-darkbrown-600">
                                <span x-text="'Sewa alat (' + totalUnits + ' unit, ' + rentalDays + ' hari)'"></span>
                                <span class="font-bold text-darkbrown-900" x-text="formatRupiah(subtotal)"></span>
                            </div>

                            <div class="flex items-start justify-between text-darkbrown-600">
                                <div>
                                    <span>Ongkos kirim</span>
                                    <span x-show="pickupMethod === 'delivery'" x-cloak class="block text-xs text-goldenrod-700 font-semibold">Kurir vendor ke lokasi</span>
                                </div>
                                <span class="font-bold text-darkbrown-900 text-right" x-text="pickupMethod === 'self_pickup' ? 'Gratis — Ambil di Toko' : formatRupiah(deliveryFee)">—</span>
                            </div>

                            <div x-show="pickupMethod === 'delivery' && shippingReady" x-cloak class="flex items-center justify-between text-darkbrown-600">
                                <span>Total berat pesanan</span>
                                <span x-text="formatMeasurement(totalWeightKg) + ' kg'"></span>
                            </div>
                            <div x-show="pickupMethod === 'delivery' && shippingReady" x-cloak class="flex items-center justify-between text-darkbrown-600">
                                <span>Jarak pengiriman</span>
                                <span x-text="shipping?.distance_km == null ? '—' : formatMeasurement(shipping.distance_km) + ' km'"></span>
                            </div>
                            <div x-show="!shippingReady" x-cloak role="status" aria-live="polite" class="rounded-xl bg-wheat-50 border border-wheat-200 p-3 text-darkbrown-600">
                                <span x-text="shippingMessage"></span>
                                <button type="button" x-show="shippingState === 'error' && !shippingReloadRequired" @click="scheduleShipping()" class="block mt-2 font-bold text-avocado-700">Coba lagi</button>
                                <button type="button" x-show="shippingReloadRequired" @click="window.location.reload()" class="block mt-2 font-bold text-avocado-700">Muat ulang checkout</button>
                            </div>

                            <div x-show="discount > 0" x-cloak class="flex items-center justify-between text-avocado-700">
                                <span>Diskon promo</span>
                                <span class="font-bold" x-text="'- ' + formatRupiah(discount)"></span>
                            </div>
                        </div>

                        <div class="rounded-xl bg-avocado-900 text-white px-4 py-3.5">
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="text-sm text-avocado-100">Total sewa</span>
                                <span class="text-2xl font-extrabold tracking-tight font-heading" x-text="formatRupiah(grandTotal)"></span>
                            </div>
                        </div>

                        <p x-show="touched" x-cloak class="text-xs text-darkbrown-500 leading-relaxed">
                            Total dihitung ulang dari perubahan jadwal atau jumlah unit.
                        </p>

                        <!-- Opsi Pembayaran-->
                        <fieldset :disabled="isProcessing">
                            <legend class="text-sm font-bold text-darkbrown-900 mb-2.5">Pilih cara bayar</legend>
                            <div class="grid grid-cols-2 gap-3">
                                <label :class="paymentOption === 'full' ? 'border-avocado-800 bg-avocado-100/60' : 'border-wheat-200 bg-white hover:border-avocado-400'"
                                       class="relative flex items-start gap-2.5 rounded-2xl border-2 p-3 cursor-pointer transition focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-avocado-600">
                                    <input type="radio" name="ui_payment_option" value="full" x-model="paymentOption" class="sr-only">
                                    <span class="mt-0.5 w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition"
                                          :class="paymentOption === 'full' ? 'bg-avocado-800 border-avocado-800' : 'border-wheat-400 bg-white'" aria-hidden="true">
                                        <span x-show="paymentOption === 'full'" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block font-bold text-sm text-darkbrown-900">Bayar lunas</span>
                                        <span class="block mt-0.5 text-[11px] text-darkbrown-600 leading-snug">Tidak ada tagihan lagi di toko.</span>
                                        <span class="block mt-1.5 text-sm font-extrabold text-avocado-800" x-text="formatRupiah(grandTotal)"></span>
                                    </span>
                                </label>
                                <label :class="paymentOption === 'dp' ? 'border-avocado-800 bg-avocado-100/60' : 'border-wheat-200 bg-white hover:border-avocado-400'"
                                       class="relative flex items-start gap-2.5 rounded-2xl border-2 p-3 cursor-pointer transition focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-avocado-600">
                                    <input type="radio" name="ui_payment_option" value="dp" x-model="paymentOption" class="sr-only">
                                    <span class="mt-0.5 w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition"
                                          :class="paymentOption === 'dp' ? 'bg-avocado-800 border-avocado-800' : 'border-wheat-400 bg-white'" aria-hidden="true">
                                        <span x-show="paymentOption === 'dp'" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block font-bold text-sm text-darkbrown-900">Uang muka (DP)</span>
                                        <span class="block mt-0.5 text-[11px] text-darkbrown-600 leading-snug">Bayar <span x-text="dpPercentage + '%'"></span> dulu, sisanya saat ambil alat.</span>
                                        <span class="block mt-1.5 text-sm font-extrabold text-goldenrod-700" x-text="formatRupiah(dpAmount)"></span>
                                    </span>
                                </label>
                            </div>
                        </fieldset>

                        <!-- Kejelasan nominal: bayar sekarang vs sisa -->
                        <div x-show="grandTotal !== null" x-cloak class="rounded-2xl border border-wheat-200 bg-white px-4 py-3.5 text-sm space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-darkbrown-900">Dibayar sekarang</span>
                                <span class="font-extrabold text-darkbrown-900" x-text="formatRupiah(payableAmount)"></span>
                            </div>
                            <div x-show="paymentOption === 'dp'" x-cloak class="flex items-center justify-between text-darkbrown-600">
                                <span>Sisa dibayar di toko saat ambil alat</span>
                                <span class="font-semibold" x-text="formatRupiah(Math.max(0, grandTotal - dpAmount))"></span>
                            </div>
                        </div>

<div x-show="formError" x-cloak role="alert" class="flex items-start gap-2 rounded-xl bg-red-50 border border-red-200 px-3.5 py-3 text-sm text-red-800">
                            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 mt-0.5"></i>
                            <span x-text="formError"></span>
                        </div>

                        <div x-show="shippingNeedsApproval" x-cloak class="rounded-xl border border-wheat-300 bg-wheat-50 p-3 text-sm text-darkbrown-700 space-y-2">
                            <p>Periksa ongkir dan total terbaru di atas sebelum melanjutkan.</p>
                            <button type="button" @click="approveShippingChange()" class="font-bold text-avocado-700">Saya setuju dengan total terbaru</button>
                        </div>

                        <!-- Yang perlu disiapkan saat ambil alat (netral, bukan peringatan merah/kuning) -->
                        <div class="rounded-2xl border border-wheat-200 bg-white p-4 text-sm text-darkbrown-800 space-y-2">
                            <div class="flex items-center gap-2 font-bold text-darkbrown-900">
                                <i data-lucide="info" class="w-4 h-4 text-avocado-700 shrink-0"></i>
                                <span>Yang perlu disiapkan saat ambil alat</span>
                            </div>
                            <ul class="list-disc pl-5 space-y-1 text-darkbrown-700 leading-relaxed">
                                <li><strong>KTP asli</strong> dan nomor booking. KTP ditahan selama masa sewa dan dikembalikan setelah alat diperiksa.</li>
                                <li x-show="paymentOption === 'dp'" x-cloak><strong>Sisa tagihan</strong> dilunasi di kasir toko saat serah terima.</li>
                            </ul>
                        </div>

                        <div class="space-y-2.5">
                            <button type="button" x-on:click="submitBooking()" :disabled="!canSubmitBooking"
                                    :class="!canSubmitBooking ? 'opacity-70 cursor-not-allowed' : 'hover:bg-sunglow-400 active:bg-goldenrod-400 hover:shadow-tendaku-lg'"
                                    class="w-full py-3.5 px-5 rounded-xl font-extrabold text-base text-darkbrown-900 bg-sunglow-300 shadow-tendaku transition duration-200 flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-goldenrod-500 focus:ring-offset-2">
                                <i x-show="isProcessing" x-cloak data-lucide="loader" class="w-5 h-5 animate-spin"></i>
                                <span x-text="isProcessing ? 'Membuat booking...' : 'Buat booking'"></span>
                                <span x-show="!isProcessing" x-text="'· ' + formatRupiah(payableAmount)"></span>
                            </button>
                            <p class="text-xs text-center text-darkbrown-500 leading-relaxed">
                                Pembayaran dilakukan di langkah berikutnya, setelah booking dibuat.
                            </p>
                            <p class="text-xs text-center text-darkbrown-500 leading-relaxed">
                                Dengan membuat booking, Anda menyetujui
                                <button type="button" @click="$dispatch('open-footer-doc', 'terms')" class="font-semibold text-avocado-700 hover:text-avocado-800 underline underline-offset-2">Syarat &amp; Ketentuan</button>
                                dan
                                <button type="button" @click="$dispatch('open-footer-doc', 'cancellation')" class="font-semibold text-avocado-700 hover:text-avocado-800 underline underline-offset-2">Kebijakan Pembatalan</button>.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm px-5 py-4 flex items-center justify-between gap-3 text-sm">
                    <div>
                        <div class="font-bold text-darkbrown-900">Butuh bantuan?</div>
                        <div class="text-darkbrown-500">Kendala bayar atau ubah jadwal.</div>
                    </div>
                    <a href="https://wa.me/6281234567890" target="_blank" rel="noopener" class="shrink-0 inline-flex items-center gap-1.5 rounded-xl border border-wheat-300 bg-wheat-50 hover:bg-wheat-100 px-3 py-2 text-xs font-bold text-avocado-900 transition">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        WhatsApp CS
                    </a>
                </div>
            </div>
