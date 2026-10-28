@props([
    'position' => 'top-right', // 'top-right' | 'top-center' | 'bottom-right'
])

<div
    x-data="tendakuToast()"
    @toast.window="add($event.detail)"
    @notify.window="add($event.detail)"
    class="fixed top-5 right-5 z-[9999] flex flex-col gap-3 max-w-sm w-full pointer-events-none"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-x-8 scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-x-0 scale-100"
            x-transition:leave-end="opacity-0 translate-x-8 scale-95"
            class="pointer-events-auto relative w-full overflow-hidden rounded-2xl border bg-white/95 p-4 shadow-tendaku-lg backdrop-blur-md transition"
            :class="{
                'border-avocado-200': toast.type === 'success',
                'border-red-200': toast.type === 'danger' || toast.type === 'error',
                'border-sunglow-300': toast.type === 'warning',
                'border-wheat-300': toast.type === 'info'
            }"
            @mouseenter="pauseToast(toast)"
            @mouseleave="resumeToast(toast)"
        >
            <div class="flex items-start gap-3">
                <!-- Icon -->
                <div class="mt-0.5 shrink-0">
                    <template x-if="toast.type === 'success'">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-avocado-100 text-avocado-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </template>
                    <template x-if="toast.type === 'danger' || toast.type === 'error'">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-red-100 text-red-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </template>
                    <template x-if="toast.type === 'warning'">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-sunglow-100 text-sunglow-800">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-wheat-200 text-darkbrown-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </template>
                </div>

                <!-- Text Content -->
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-darkbrown-900" x-text="toast.title"></p>
                    <p class="mt-0.5 text-xs text-darkbrown-600 leading-relaxed" x-text="toast.message"></p>
                </div>

                <!-- Dismiss Button -->
                <button
                    type="button"
                    @click="remove(toast.id)"
                    class="shrink-0 rounded-lg p-1 text-darkbrown-400 transition hover:bg-wheat-100 hover:text-darkbrown-700"
                    title="Tutup"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Auto-dismiss Progress Bar -->
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-wheat-100 overflow-hidden">
                <div
                    class="h-full transition-all linear"
                    :class="{
                        'bg-avocado-500': toast.type === 'success',
                        'bg-red-500': toast.type === 'danger' || toast.type === 'error',
                        'bg-sunglow-500': toast.type === 'warning',
                        'bg-goldenrod-500': toast.type === 'info'
                    }"
                    :style="'width: ' + toast.progress + '%; transition-duration: ' + (toast.remaining) + 'ms;'"
                ></div>
            </div>
        </div>
    </template>
</div>

<script>
    function tendakuToast() {
        return {
            toasts: [],
            init() {
                // Tangkap session flash dari Laravel redirect
                @if (session()->has('success'))
                    this.add({
                        type: 'success',
                        title: 'Berhasil!',
                        message: @json(session('success')),
                        duration: 4000
                    });
                @endif

                @if (session()->has('error'))
                    this.add({
                        type: 'danger',
                        title: 'Perhatian',
                        message: @json(session('error')),
                        duration: 5000
                    });
                @endif

                @if (session()->has('warning'))
                    this.add({
                        type: 'warning',
                        title: 'Info Penting',
                        message: @json(session('warning')),
                        duration: 4500
                    });
                @endif

                @if (session()->has('status'))
                    this.add({
                        type: 'info',
                        title: 'Informasi',
                        message: @json(session('status')),
                        duration: 4000
                    });
                @endif

                @if (session()->has('message'))
                    this.add({
                        type: 'success',
                        title: 'Berhasil!',
                        message: @json(session('message')),
                        duration: 4000
                    });
                @endif

                @if (session()->has('pos_message'))
                    this.add({
                        type: 'success',
                        title: 'Berhasil!',
                        message: @json(session('pos_message')),
                        duration: 4000
                    });
                @endif
            },
            add(payload) {
                if (!payload || !payload.message) return;

                const type = payload.type || 'info';
                const duplicate = this.toasts.find(
                    (toast) => toast.visible && toast.type === type && toast.message === payload.message
                );

                if (duplicate) return;

                const id = Date.now() + Math.floor(Math.random() * 1000);
                const duration = payload.duration || (type === 'danger' || type === 'error' ? 5000 : 4000);
                const title = payload.title || (
                    type === 'success' ? 'Berhasil!' :
                    (type === 'danger' || type === 'error' ? 'Perhatian' :
                    (type === 'warning' ? 'Info Penting' : 'Informasi'))
                );

                const toast = {
                    id: id,
                    type: type,
                    title: title,
                    message: payload.message,
                    duration: duration,
                    remaining: duration,
                    startTime: Date.now(),
                    progress: 100,
                    visible: true,
                    timer: null
                };

                this.toasts.push(toast);

                // Jalankan animasi progress bar
                this.$nextTick(() => {
                    toast.progress = 0;
                });

                // Set timer penghilang
                toast.timer = setTimeout(() => {
                    this.remove(toast.id);
                }, duration);
            },
            pauseToast(toast) {
                if (toast.timer) {
                    clearTimeout(toast.timer);
                    toast.remaining -= (Date.now() - toast.startTime);
                    toast.timer = null;
                }
            },
            resumeToast(toast) {
                if (!toast.timer && toast.remaining > 0) {
                    toast.startTime = Date.now();
                    toast.timer = setTimeout(() => {
                        this.remove(toast.id);
                    }, toast.remaining);
                }
            },
            remove(id) {
                const toast = this.toasts.find(t => t.id === id);
                if (toast) {
                    toast.visible = false;
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 250);
                }
            }
        };
    }
</script>
