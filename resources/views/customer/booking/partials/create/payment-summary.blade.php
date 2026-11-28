            <div id="checkout-summary" class="space-y-4 lg:sticky lg:top-24 scroll-mt-24">

                <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm overflow-hidden">
                    <div class="px-5 sm:px-6 py-4 bg-avocado-900">
                        <h3 class="text-lg font-extrabold text-white font-heading">Ringkasan Pembayaran</h3>
                    </div>

                    <div class="p-5 sm:p-6 space-y-5">
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 rounded-lg bg-avocado-100/70 px-3 py-2 font-bold text-avocado-900">
                                <i data-lucide="receipt-text" class="w-4 h-4"></i>
                                <span>Ringkasan Sewa</span>
                            </div>

                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-bold text-darkbrown-900">Sewa alat</p>
                                    <p class="mt-0.5 text-xs text-darkbrown-600" x-text="fmtRangeDay()"></p>
                                </div>
                                <span class="shrink-0 rounded-full bg-darkbrown-900 text-wheat-100 px-2.5 py-0.5 text-xs font-bold whitespace-nowrap" x-text="rentalDays + ' hari'"></span>
                            </div>

                            <ul class="divide-y divide-wheat-200 rounded-xl border border-wheat-200 bg-wheat-50/60 px-3">
                                <template x-for="item in items" :key="item.id">
                                    <li class="flex items-center gap-3 py-3">
                                        <span class="w-11 h-11 rounded-lg bg-avocado-100 text-avocado-800 flex items-center justify-center shrink-0 overflow-hidden">
                                            <template x-if="item.image_url">
                                                <img :src="item.image_url" :alt="item.name" loading="lazy" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!item.image_url">
                                                <i data-lucide="tent" class="w-5 h-5"></i>
                                            </template>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-darkbrown-900 leading-snug line-clamp-2" x-text="item.name"></span>
                                            <span class="block mt-0.5 text-xs text-darkbrown-500" x-text="item.qty + ' unit × ' + formatRupiah(item.price_per_day) + ' / hari'"></span>
                                        </span>
                                        <span class="shrink-0 text-sm font-bold text-darkbrown-900" x-text="formatRupiah((Number(item.qty) || 0) * item.price_per_day * rentalDays)"></span>
                                    </li>
                                </template>
                            </ul>

                            <div class="flex items-center justify-between gap-3 text-darkbrown-700">
                                <div>
                                    <span>Ongkos kirim</span>
                                    <span x-show="pickupMethod === 'self_pickup'" class="block text-xs text-darkbrown-500">Ambil di toko</span>
                                    <span x-show="pickupMethod === 'delivery'" x-cloak class="block text-xs text-goldenrod-700 font-semibold">Kurir vendor ke lokasi</span>
                                </div>
                                <span class="font-bold text-right" :class="pickupMethod === 'self_pickup' ? 'text-avocado-700' : 'text-darkbrown-900'" x-text="pickupMethod === 'self_pickup' ? 'Gratis' : formatRupiah(deliveryFee)">—</span>
                            </div>

                            <div x-show="pickupMethod === 'delivery' && shippingReady" x-cloak class="flex items-center justify-between text-darkbrown-600">
                                <span>Total berat pesanan</span>
                                <span x-text="formatMeasurement(totalWeightKg) + ' kg'"></span>
                            </div>
                            <div x-show="pickupMethod === 'delivery' && shippingReady" x-cloak class="flex items-center justify-between text-darkbrown-600">
                                <span>Jarak pengiriman</span>
                                <span x-text="shipping?.distance_km == null ? '—' : formatMeasurement(shipping.distance_km) + ' km'"></span>
                            </div>
                            <div x-show="!shippingReady" x-cloak role="status" aria-live="polite" class="rounded-xl border p-3" :class="shippingState === 'out_of_range' ? 'bg-red-50 border-red-200 text-red-800 font-semibold' : 'bg-wheat-50 border-wheat-200 text-darkbrown-600'">
                                <span x-text="shippingMessage"></span>
                                <button type="button" x-show="shippingState === 'error' && !shippingReloadRequired" @click="scheduleShipping()" class="inline-flex min-h-[44px] sm:min-h-0 items-center mt-2 font-bold text-avocado-700">Coba lagi</button>
                                <button type="button" x-show="shippingReloadRequired" @click="window.location.reload()" class="inline-flex min-h-[44px] sm:min-h-0 items-center mt-2 font-bold text-avocado-700">Muat ulang checkout</button>
                            </div>

                            <div x-show="discount > 0" x-cloak class="flex items-center justify-between text-avocado-700">
                                <span>Diskon promo</span>
                                <span class="font-bold" x-text="'- ' + formatRupiah(discount)"></span>
                            </div>
                        </div>

                        <p x-show="touched" x-cloak class="-mt-2 text-[11px] text-darkbrown-500 leading-relaxed">
                            Total dihitung ulang dari perubahan jadwal atau jumlah unit.
                        </p>

                        <!-- Opsi Pembayaran-->
                        <fieldset :disabled="isProcessing">
                            <legend class="flex items-center gap-2 text-sm font-bold text-darkbrown-900 mb-2.5">
                                <i data-lucide="wallet" class="w-4 h-4 text-avocado-800"></i>
                                Pilihan Pembayaran
                            </legend>
                            <div class="space-y-2.5">
                                <label :class="paymentOption === 'full' ? 'border-avocado-700 bg-avocado-100/60' : 'border-wheat-200 bg-white hover:border-avocado-400'"
                                       class="relative flex items-center gap-3 rounded-xl border-2 p-3.5 cursor-pointer transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-avocado-600 has-[:focus-visible]:ring-offset-2">
                                    <input type="radio" name="ui_payment_option" value="full" x-model="paymentOption" class="sr-only">
                                    <span class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition"
                                          :class="paymentOption === 'full' ? 'bg-avocado-800 border-avocado-800' : 'border-wheat-400 bg-white'" aria-hidden="true">
                                        <span x-show="paymentOption === 'full'" class="w-2 h-2 rounded-full bg-white"></span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-bold text-sm text-darkbrown-900">Bayar lunas</span>
                                        <span class="block mt-0.5 text-xs text-darkbrown-600 leading-snug">Tidak ada tambahan biaya</span>
                                    </span>
                                </label>
                                <label :class="paymentOption === 'dp' ? 'border-avocado-700 bg-avocado-100/60' : 'border-wheat-200 bg-white hover:border-avocado-400'"
                                       class="relative flex items-center gap-3 rounded-xl border-2 p-3.5 cursor-pointer transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-avocado-600 has-[:focus-visible]:ring-offset-2">
                                    <input type="radio" name="ui_payment_option" value="dp" x-model="paymentOption" class="sr-only">
                                    <span class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition"
                                          :class="paymentOption === 'dp' ? 'bg-avocado-800 border-avocado-800' : 'border-wheat-400 bg-white'" aria-hidden="true">
                                        <span x-show="paymentOption === 'dp'" class="w-2 h-2 rounded-full bg-white"></span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-bold text-sm text-darkbrown-900">Uang muka (DP)</span>
                                        <span class="block mt-0.5 text-xs text-darkbrown-600 leading-snug">Bayar <span x-text="dpPercentage + '%'"></span> dulu, sisanya saat ambil alat.</span>
                                    </span>
                                    <span class="shrink-0 text-sm font-extrabold text-darkbrown-900" x-text="formatRupiah(dpAmount)"></span>
                                </label>
                            </div>
                        </fieldset>

                        <div x-show="formError" x-cloak role="alert" class="flex items-start gap-2 rounded-xl bg-red-50 border border-red-200 px-3.5 py-3 text-sm text-red-800">
                            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 mt-0.5"></i>
                            <span x-text="formError"></span>
                        </div>

                        <div x-show="shippingNeedsApproval" x-cloak class="rounded-xl border border-wheat-300 bg-wheat-50 p-3 text-sm text-darkbrown-700 space-y-2">
                            <p>Periksa ongkir dan total terbaru di atas sebelum melanjutkan.</p>
                            <button type="button" @click="approveShippingChange()" class="inline-flex min-h-[44px] sm:min-h-0 items-center font-bold text-avocado-700">Saya setuju dengan total terbaru</button>
                        </div>

                        <div class="rounded-xl border border-wheat-200 bg-wheat-50/60 p-4 text-sm text-darkbrown-800 space-y-2.5">
                            <div class="flex items-center gap-2 font-bold text-darkbrown-900">
                                <i data-lucide="info" class="w-4 h-4 text-avocado-700 shrink-0"></i>
                                <span>Yang perlu disiapkan saat ambil alat:</span>
                            </div>
                            <ul class="space-y-1.5 text-darkbrown-700 leading-relaxed">
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-4 h-4 text-avocado-700 shrink-0 mt-0.5"></i>
                                    <span><strong>KTP asli</strong> dan nomor booking. KTP ditahan selama masa sewa dan dikembalikan setelah alat diperiksa.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-4 h-4 text-avocado-700 shrink-0 mt-0.5"></i>
                                    <span>Tunjukkan struk atau kode pemesanan.</span>
                                </li>
                                <li x-show="paymentOption === 'dp'" x-cloak class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-4 h-4 text-avocado-700 shrink-0 mt-0.5"></i>
                                    <span><strong>Sisa tagihan</strong> dilunasi di kasir toko saat serah terima.</span>
                                </li>
                            </ul>
                        </div>

                        <div class="rounded-xl bg-avocado-100 border border-avocado-200 text-avocado-900 px-4 py-3.5">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-bold">Total Sewa</span>
                                <span class="text-2xl font-extrabold tracking-tight font-heading" x-text="formatRupiah(grandTotal)"></span>
                            </div>
                            <div x-show="paymentOption === 'dp'" x-cloak
                                 class="mt-2.5 pt-2.5 border-t border-avocado-200 flex items-center justify-between gap-3">
                                <span class="text-sm font-bold">DP</span>
                                <span class="text-lg font-extrabold tracking-tight font-heading" x-text="formatRupiah(dpAmount)"></span>
                            </div>
                        </div>

                        {{-- Desktop saja. Di mobile/tablet CTA memakai sticky bar (mobile-checkout-bar). --}}
                        <div class="hidden lg:block space-y-2.5">
                            <button type="button" x-on:click="submitBooking()" :disabled="!canSubmitBooking"
                                    :class="!canSubmitBooking ? 'opacity-70 cursor-not-allowed' : 'hover:bg-sunglow-400 active:bg-goldenrod-400 hover:shadow-tendaku-lg'"
                                    class="w-full py-3.5 px-5 rounded-xl font-extrabold text-base text-darkbrown-900 bg-sunglow-300 shadow-tendaku transition duration-200 flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-goldenrod-500 focus:ring-offset-2">
                                <i x-show="isProcessing" x-cloak data-lucide="loader" class="w-5 h-5 animate-spin"></i>
                                <span x-text="isProcessing ? 'Membuat booking...' : 'Buat booking'"></span>
                            </button>
                            <p class="flex items-center justify-center gap-1.5 text-xs text-center text-darkbrown-500 leading-relaxed">
                                <i
                                    data-lucide="shield-check"
                                    class="w-4 h-4 text-avocado-700 fill-avocado-200 stroke-avocado-700 shrink-0"
                                ></i>
                                <span>Pembayaran dilakukan dengan aman.</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm px-5 py-4 flex items-center justify-between gap-3 text-sm">
                    <div>
                        <div class="font-bold text-darkbrown-900">Butuh bantuan?</div>
                        <div class="text-darkbrown-500">Kendala bayar atau ubah jadwal.</div>
                    </div>
                    <a href="https://wa.me/6281234567890" target="_blank" rel="noopener" class="shrink-0 inline-flex min-h-[44px] sm:min-h-0 items-center gap-1.5 rounded-xl border border-wheat-300 bg-wheat-50 hover:bg-wheat-100 px-3 py-2 text-xs font-bold text-avocado-900 transition">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        WhatsApp CS
                    </a>
                </div>
            </div>
