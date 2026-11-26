@props(['storageKey' => 'tendaku:desktop-mode-notice-dismissed'])

<div
    x-data="{
        show: false,
        platform: 'android',
        key: @js($storageKey),

        init() {
            try {
                if (sessionStorage.getItem(this.key) === '1') return;
            } catch (e) {}

            const ua = navigator.userAgent || '';
            const touch =
                (navigator.maxTouchPoints || 0) > 0 ||
                window.matchMedia('(pointer: coarse)').matches;

            // screen.width tetap ukuran fisik perangkat meski browser memakai mode desktop.
            const smallDevice =
                Math.min(window.screen.width, window.screen.height) <= 820;

            const mobileView = window.innerWidth < 900;

            const isIos =
                /iPhone|iPad|iPod/i.test(ua) ||
                (/Macintosh/i.test(ua) && (navigator.maxTouchPoints || 0) > 1);

            this.platform = isIos ? 'ios' : 'android';
            this.show = touch && smallDevice && mobileView;
        },

        dismiss() {
            this.show = false;

            try {
                sessionStorage.setItem(this.key, '1');
            } catch (e) {}
        },
    }"
    x-cloak
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="-translate-y-6 opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="-translate-y-6 opacity-0"
    role="dialog"
    aria-live="polite"
    aria-label="Rekomendasi mode desktop"
    class="fixed inset-x-3 top-3 z-[9998] md:hidden"
    style="padding-top: env(safe-area-inset-top);"
>
    <div class="rounded-2xl border border-wheat-300 bg-white/95 p-4 shadow-tendaku-lg backdrop-blur-md">
        <div class="flex items-start gap-3">

            {{-- Icon --}}
            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sunglow-100 text-darkbrown-800">
                <i data-lucide="alert-triangle"></i>
            </div>

            {{-- Content --}}
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-darkbrown-900">
                    Gunakan mode desktop
                </p>

                <p class="mt-1 text-xs leading-relaxed text-darkbrown-600">
                    Untuk pengalaman terbaik saat menggunakan website ini di browser kamu.
                </p>

                {{-- Android --}}
                <p
                    x-show="platform === 'android'"
                    class="mt-2 text-xs leading-relaxed text-darkbrown-700"
                >
                    <span class="font-bold text-darkbrown-900">
                        Cara Menggunakan Mode Desktop
                    </span><br>
                    Buka menu <strong>⋮</strong> di browser, lalu aktifkan
                    <strong>“Situs desktop”</strong>.
                </p>

                {{-- iOS --}}
                <p
                    x-show="platform === 'ios'"
                    x-cloak
                    class="mt-2 text-xs leading-relaxed text-darkbrown-700"
                >
                    <span class="font-bold text-darkbrown-900">
                        Cara Menggunakan Mode Desktop
                    </span><br>
                    Ketuk ikon <strong>aA</strong> di address bar Safari, lalu pilih <strong>"Situs Desktop”</strong>.
                </p>

                {{-- Action --}}
                <div class="mt-3 flex justify-end">
                    <button
                        type="button"
                        @click="dismiss()"
                        class="inline-flex min-h-[2.25rem] items-center rounded-xl bg-avocado-600 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-avocado-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2"
                    >
                        Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
