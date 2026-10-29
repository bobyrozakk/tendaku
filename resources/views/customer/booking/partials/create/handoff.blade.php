        <!-- booking dibuat -->
        <div class="co-preview" :class="{ 'is-open': phase !== 'form' }" :inert="phase === 'form'" :aria-hidden="phase === 'form' ? 'true' : 'false'">
            <div class="co-inner">
                <div class="flex flex-col items-center justify-center text-center gap-5 px-4" style="min-height: 62vh;" role="status" aria-live="polite">
                    <template x-if="phase !== 'form'">
                        <div class="flex flex-col items-center gap-5">
                            <div class="relative w-32 h-32 flex items-center justify-center">
                                <span class="co-halo absolute inset-0 rounded-full bg-avocado-200/70" aria-hidden="true"></span>
                            </div>

                            <div class="co-copy space-y-2">
                                <h2 class="text-3xl sm:text-4xl font-extrabold text-darkbrown-900 tracking-tight font-heading">Booking dibuat</h2>
                                <p x-show="createdCode" x-cloak class="font-mono text-base font-extrabold text-darkbrown-800" x-text="'#' + createdCode"></p>
                                <p class="text-sm text-darkbrown-600">Menyiapkan halaman pembayaran Anda…</p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
