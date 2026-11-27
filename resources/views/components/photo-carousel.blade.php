{{--
    Carousel foto untuk halaman detail produk & paket.

    - Foto bisa digeser (swipe di HP, scroll horizontal / trackpad di desktop).
    - Auto-scroll tiap $interval ms; di bagian bawah foto (menyatu dengan foto, warna bening)
      ada garis yang dipotong jadi N segmen
      (N = jumlah foto, maks. $max), segmen aktif terisi sesuai durasi sebelum pindah foto.
    - Auto-scroll berhenti saat di-hover / disentuh / difokus, saat tab tidak aktif,
      dan tidak jalan kalau OS minta reduced-motion.
    - Slot dipakai untuk overlay di atas foto (header toko, label, dll).
--}}
@props([
    'images' => [],
    'alt' => '',
    'interval' => 5000,
    'max' => 5,
])

@php
    $slides = collect($images)->filter()->unique()->values()->take((int) $max)->all();
    $count = count($slides);
@endphp

<div
    wire:ignore
    role="region"
    aria-roledescription="carousel"
    aria-label="Foto {{ $alt }}"
    @if ($count > 0)
    x-data="{
        images: @js($slides),
        interval: {{ max(1500, (int) $interval) }},
        index: 0,
        progress: 0,
        paused: false,
        reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        raf: null,
        last: 0,
        lock: false,
        lockTimer: null,
        init() {
            if (this.images.length < 2 || this.reduced) return;
            const tick = (now) => {
                const delta = Math.min(now - (this.last || now), 100);
                this.last = now;
                if (!this.paused && !document.hidden) {
                    this.progress += delta / this.interval;
                    if (this.progress >= 1) this.go(this.index + 1);
                }
                this.raf = requestAnimationFrame(tick);
            };
            this.raf = requestAnimationFrame(tick);
        },
        destroy() {
            cancelAnimationFrame(this.raf);
            clearTimeout(this.lockTimer);
        },
        go(i) {
            const n = this.images.length;
            this.index = ((i % n) + n) % n;
            this.progress = 0;
            this.lock = true;
            clearTimeout(this.lockTimer);
            this.lockTimer = setTimeout(() => { this.lock = false; }, 650);
            const track = this.$refs.track;
            track.scrollTo({ left: this.index * track.clientWidth, behavior: this.reduced ? 'auto' : 'smooth' });
        },
        onScroll() {
            if (this.lock) return;
            const track = this.$refs.track;
            const i = Math.round(track.scrollLeft / track.clientWidth);
            if (i !== this.index && i >= 0 && i < this.images.length) {
                this.index = i;
                this.progress = 0;
            }
        },
        fill(i) {
            if (i < this.index) return 100;
            if (i > this.index) return 0;
            return this.reduced ? 100 : Math.min(100, this.progress * 100);
        },
    }"
    @mouseenter="paused = true"
    @mouseleave="paused = false"
    @touchstart.passive="paused = true"
    @touchend="paused = false"
    @touchcancel="paused = false"
    @focusin="paused = true"
    @focusout="paused = false"
    @endif
    {{ $attributes }}
>
    <div class="rounded-[2rem] border border-wheat-200/90 bg-white p-2.5 shadow-tendaku-sm">
        <div class="relative aspect-[4/3] overflow-hidden rounded-3xl bg-gradient-to-br from-wheat-100 to-sunglow-100">
            <span class="absolute inset-0 flex items-center justify-center text-darkbrown-300" aria-hidden="true">
                <i data-lucide="tent" class="h-14 w-14"></i>
            </span>

            @if ($count > 0)
                <div
                    x-ref="track"
                    @scroll.passive="onScroll()"
                    class="absolute inset-0 flex snap-x snap-mandatory overflow-x-auto overscroll-x-contain [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    @foreach ($slides as $i => $src)
                        <div
                            class="h-full w-full shrink-0 snap-center"
                            role="group"
                            aria-roledescription="slide"
                            aria-label="{{ $i + 1 }} dari {{ $count }}"
                        >
                            <img
                                src="{{ $src }}"
                                alt="{{ $alt }}{{ $count > 1 ? ' - foto '.($i + 1) : '' }}"
                                decoding="async"
                                draggable="false"
                                onerror="this.style.display='none'"
                                class="h-full w-full select-none object-cover"
                            />
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Overlay dari halaman (header toko, label paket, dll) --}}
            {{ $slot }}

            {{-- Garis durasi: menyatu dengan foto, warna bening. Klik segmen untuk lompat ke foto itu --}}
            @if ($count > 1)
                <div class="absolute z-20 flex gap-1.5" style="left:1rem;right:1rem;bottom:0.5rem" role="group" aria-label="Pilih foto">
                    <template x-for="(img, i) in images" :key="i">
                        <button
                            type="button"
                            @click="go(i)"
                            :aria-label="'Tampilkan foto ' + (i + 1)"
                            :aria-current="i === index ? 'true' : 'false'"
                            class="group flex flex-1 cursor-pointer items-center focus-visible:outline-none"
                            style="height:1.25rem"
                        >
                            <span class="relative block w-full overflow-hidden rounded-full" style="height:3px;background:rgba(255,255,255,0.55);box-shadow:0 0 3px rgba(0,0,0,0.25)">
                                <span class="absolute inset-y-0 left-0 rounded-full" style="background:#fff" :style="{ width: fill(i) + '%' }"></span>
                            </span>
                        </button>
                    </template>
                </div>
            @endif
        </div>
    </div>
</div>
