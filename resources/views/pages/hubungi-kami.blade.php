<x-layouts.customer :title="'Hubungi Kami - Tendaku.id Malang'">
    {{-- Hero Banner --}}
    <div class="relative overflow-hidden rounded-3xl border border-wheat-200 bg-gradient-to-br from-wheat-100 via-[#FDFBF7] to-sunglow-100/70 px-6 py-10 sm:px-10 sm:py-14 shadow-tendaku-sm mb-10">
        <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-sunglow-300/30 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-24 left-1/4 h-64 w-64 rounded-full bg-avocado-200/30 blur-3xl" aria-hidden="true"></div>

        <div class="relative max-w-3xl">
            <nav class="flex items-center gap-2 text-xs font-semibold text-darkbrown-500 mb-4" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-avocado-700 transition-colors">Beranda</a>
                <span aria-hidden="true">/</span>
                <span class="text-darkbrown-800">Hubungi Kami</span>
            </nav>

            <span class="inline-block rounded-full border border-avocado-300/80 bg-white/80 px-3.5 py-1 text-xs font-bold uppercase tracking-[.18em] text-avocado-800 backdrop-blur-sm shadow-sm">
                Layanan Bantuan &amp; Kontak
            </span>

            <h1 class="mt-3 font-heading text-3xl font-extrabold tracking-tight text-darkbrown-900 sm:text-4xl lg:text-5xl leading-tight">
                Hubungi Tim Tendaku.id
            </h1>

            <p class="mt-3 text-base sm:text-lg leading-relaxed text-darkbrown-600 font-normal">
                Punya pertanyaan tentang ketersediaan alat, sewa rombongan, atau butuh bantuan saat proses checkout? Tim customer service kami di Malang siap membantumu.
            </p>
        </div>
    </div>

    {{-- Contact Cards & Info --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16">
        {{-- Left: Contact Detail Cards --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="p-6 rounded-2xl border border-wheat-200 bg-white shadow-sm flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl bg-avocado-100 text-avocado-700 flex items-center justify-center shrink-0">
                    <i data-lucide="map-pin" class="w-5 h-5" aria-hidden="true"></i>
                </div>
                <div>
                    <h2 class="font-heading text-base font-bold text-darkbrown-900 mb-1">Lokasi Basecamp</h2>
                    <p class="text-sm text-darkbrown-800 font-semibold">Malang, Jawa Timur, Indonesia</p>
                    <p class="text-xs text-darkbrown-500 mt-1 leading-relaxed">
                        Melayani seluruh area Malang Raya &amp; Kota Wisata Batu dengan jaringan outlet vendor terverifikasi.
                    </p>
                </div>
            </div>

            <div class="p-6 rounded-2xl border border-wheat-200 bg-white shadow-sm flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl bg-sunglow-100 text-darkbrown-800 flex items-center justify-center shrink-0">
                    <i data-lucide="phone-call" class="w-5 h-5 text-sunglow-700" aria-hidden="true"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="font-heading text-base font-bold text-darkbrown-900 mb-1">WhatsApp &amp; Telepon</h2>
                    <a href="https://wa.me/6281234567890?text=Halo%20Tendaku,%20saya%20ingin%20bertanya%20seputar%20sewa%20alat"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="text-sm text-avocado-700 font-bold hover:underline block truncate">
                        0812-3456-7890
                    </a>
                    <a href="https://wa.me/6281234567890?text=Halo%20Tendaku,%20saya%20ingin%20bertanya%20seputar%20sewa%20alat"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="mt-3 inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-bold bg-avocado-600 text-white hover:bg-avocado-700 transition-colors shadow-sm">
                        Chat WhatsApp
                    </a>
                </div>
            </div>

            <div class="p-6 rounded-2xl border border-wheat-200 bg-white shadow-sm flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl bg-wheat-100 text-darkbrown-800 flex items-center justify-center shrink-0">
                    <i data-lucide="mail" class="w-5 h-5 text-darkbrown-700" aria-hidden="true"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="font-heading text-base font-bold text-darkbrown-900 mb-1">Email Resmi</h2>
                    <a href="mailto:admintendaku@gmail.com" class="text-sm text-darkbrown-800 font-semibold hover:text-avocado-700 hover:underline block truncate">
                        admintendaku@gmail.com
                    </a>
                    <p class="text-xs text-darkbrown-500 mt-1">
                        Respon cepat untuk pertanyaan umum, kemitraan vendor, dan konfirmasi transaksi.
                    </p>
                </div>
            </div>

            <div class="p-6 rounded-2xl border border-wheat-200 bg-white shadow-sm flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl bg-wheat-100 text-darkbrown-800 flex items-center justify-center shrink-0">
                    <i data-lucide="clock" class="w-5 h-5 text-darkbrown-700" aria-hidden="true"></i>
                </div>
                <div>
                    <h2 class="font-heading text-base font-bold text-darkbrown-900 mb-1">Jam Operasional</h2>
                    <p class="text-sm text-darkbrown-800 font-semibold">Setiap Hari, 08.00 – 20.00 WIB</p>
                    <p class="text-xs text-darkbrown-500 mt-1">
                        Outlet vendor dan layanan bantuan tetap buka di hari libur nasional &amp; akhir pekan.
                    </p>
                </div>
            </div>
        </div>

        {{-- Right: Direct Contact Form & Quick Links --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="p-6 sm:p-8 rounded-3xl border border-wheat-200 bg-white shadow-sm" x-data="{ sent: false }">
                <h2 class="font-heading text-xl sm:text-2xl font-bold text-darkbrown-900 mb-2">
                    Kirim Pesan atau Pertanyaan
                </h2>
                <p class="text-sm text-darkbrown-600 mb-6">
                    Isi formulir di bawah ini dan tim kami akan segera membalas pertanyaanmu via email atau WhatsApp.
                </p>

                <template x-if="sent">
                    <div role="status" class="p-5 rounded-2xl bg-avocado-50 border border-avocado-200 text-avocado-800 mb-6">
                        <p class="font-bold text-sm">Pesanmu Berhasil Terkirim!</p>
                        <p class="text-xs mt-1 text-avocado-700">Terima kasih telah menghubungi Tendaku. Tim kami akan segera menanggapi pertanyaanmu melalui email/WhatsApp.</p>
                    </div>
                </template>

                <form @submit.prevent="sent = true" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="contact-name" class="block text-xs font-bold text-darkbrown-800 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                            <input type="text" id="contact-name" name="name" required placeholder="Contoh: Raka Pratama"
                                   class="w-full rounded-xl border border-wheat-300 px-4 py-2.5 text-sm text-darkbrown-900 placeholder:text-darkbrown-400 focus:border-avocado-500 focus:ring-avocado-500 bg-[#FDFBF7]">
                        </div>
                        <div>
                            <label for="contact-email" class="block text-xs font-bold text-darkbrown-800 uppercase tracking-wider mb-1.5">Email / No. WhatsApp</label>
                            <input type="text" id="contact-email" name="contact" required placeholder="Contoh: 0812xxxx atau raka@email.com"
                                   class="w-full rounded-xl border border-wheat-300 px-4 py-2.5 text-sm text-darkbrown-900 placeholder:text-darkbrown-400 focus:border-avocado-500 focus:ring-avocado-500 bg-[#FDFBF7]">
                        </div>
                    </div>

                    <div>
                        <label for="contact-subject" class="block text-xs font-bold text-darkbrown-800 uppercase tracking-wider mb-1.5">Topik Pertanyaan</label>
                        <select id="contact-subject" name="subject" class="w-full rounded-xl border border-wheat-300 px-4 py-2.5 text-sm text-darkbrown-900 focus:border-avocado-500 focus:ring-avocado-500 bg-[#FDFBF7]">
                            <option value="sewa">Pertanyaan Prosedur &amp; Ketersediaan Sewa</option>
                            <option value="pembayaran">Bantuan Pembayaran (QRIS / Virtual Account)</option>
                            <option value="kurir">Pengantaran Kurir di Malang Raya</option>
                            <option value="vendor">Pendaftaran Kemitraan Toko Vendor</option>
                            <option value="lainnya">Pertanyaan Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <label for="contact-message" class="block text-xs font-bold text-darkbrown-800 uppercase tracking-wider mb-1.5">Pesan Anda</label>
                        <textarea id="contact-message" name="message" rows="4" required placeholder="Tuliskan detail pertanyaan atau kebutuhan sewa Anda di sini..."
                                  class="w-full rounded-xl border border-wheat-300 px-4 py-2.5 text-sm text-darkbrown-900 placeholder:text-darkbrown-400 focus:border-avocado-500 focus:ring-avocado-500 bg-[#FDFBF7]"></textarea>
                    </div>

                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-full text-sm font-bold bg-darkbrown-900 text-white hover:bg-darkbrown-800 transition-colors shadow-sm">
                        Kirim Pesan
                    </button>
                </form>
            </div>

            {{-- Quick Links Helpers --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="{{ url('/layanan#faq') }}"
                   class="p-5 rounded-2xl border border-wheat-200 bg-white hover:border-avocado-300 transition-all flex items-center justify-between group">
                    <div>
                        <h3 class="text-sm font-bold text-darkbrown-900">Lihat FAQ Lengkap</h3>
                        <p class="text-xs text-darkbrown-500 mt-0.5">Pertanyaan umum seputar sewa</p>
                    </div>
                    <i data-lucide="arrow-right" class="w-4 h-4 text-darkbrown-400 group-hover:translate-x-1 transition-transform" aria-hidden="true"></i>
                </a>

                <a href="{{ route('customer.rental-status.index') }}"
                   class="p-5 rounded-2xl border border-wheat-200 bg-white hover:border-avocado-300 transition-all flex items-center justify-between group">
                    <div>
                        <h3 class="text-sm font-bold text-darkbrown-900">Lacak Status Pesanan</h3>
                        <p class="text-xs text-darkbrown-500 mt-0.5">Cek status kode booking sewa</p>
                    </div>
                    <i data-lucide="arrow-right" class="w-4 h-4 text-darkbrown-400 group-hover:translate-x-1 transition-transform" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</x-layouts.customer>
