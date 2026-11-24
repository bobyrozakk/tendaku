<section class="flex flex-col gap-2.5 rounded-xl border border-avocado-200/70 bg-avocado-50/50 px-3.5 py-2.5 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-start gap-2.5">
        <i data-lucide="lock" class="mt-0.5 h-4 w-4 shrink-0 text-avocado-700" aria-hidden="true"></i>
        <p class="text-xs leading-snug text-avocado-800">
            <span class="block font-bold text-avocado-900">Keranjang khusus member</span>
            Masuk atau daftar dulu untuk menambahkan alat ke keranjang dan melanjutkan penyewaan.
        </p>
    </div>
    <div class="flex items-center gap-2 sm:shrink-0">
        <a href="{{ route('login') }}" class="btn-tendaku-secondary min-h-[36px] px-3 py-1.5 text-xs">Masuk</a>
        <a href="{{ route('register') }}" class="btn-tendaku-primary min-h-[36px] px-3 py-1.5 text-xs">Daftar</a>
    </div>
</section>
