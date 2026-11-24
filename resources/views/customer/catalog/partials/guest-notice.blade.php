    <section class="flex flex-col gap-3 rounded-2xl border border-avocado-200 bg-avocado-50/80 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-avocado-700 shadow-sm">
                <i data-lucide="lock" class="h-4 w-4" aria-hidden="true"></i>
            </span>
            <div>
                <p class="text-xs font-bold text-avocado-900">Keranjang khusus member</p>
                <p class="mt-0.5 text-xs leading-relaxed text-avocado-800">
                    Masuk atau daftar dulu untuk menambahkan alat ke keranjang dan melanjutkan penyewaan.
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 sm:shrink-0">
            <a href="{{ route('login') }}" class="btn-tendaku-secondary min-h-[44px] px-4 text-xs">Masuk</a>
            <a href="{{ route('register') }}" class="btn-tendaku-primary min-h-[44px] px-4 text-xs">Daftar</a>
        </div>
    </section>
