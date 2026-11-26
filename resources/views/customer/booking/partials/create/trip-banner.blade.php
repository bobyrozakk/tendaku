        <!-- ============ TRIP BANNER ============ -->
        @php
            $bannerSteps = [
                ['id' => 'section-rental', 'label' => $isGuest ? 'Jadwal Sewa' : 'Jadwal & Perkiraan Cuaca'],
                ['id' => 'section-equipment', 'label' => 'Peralatan'],
                ['id' => 'section-delivery', 'label' => 'Pengambilan Alat'],
                ['id' => 'section-identity', 'label' => 'Data Penyewa'],
            ];
        @endphp
        <div class="relative overflow-hidden rounded-2xl bg-avocado-900 text-white shadow-tendaku">
            <svg class="absolute -right-10 -bottom-16 w-96 h-64 text-avocado-800/60 pointer-events-none" viewBox="0 0 400 260" fill="none" aria-hidden="true">
                <path d="M400 20c-60 10-90 60-150 80s-110-10-170 40S30 260 0 260h400V20Z" fill="currentColor"/>
            </svg>
            <div class="relative p-4 sm:p-5 space-y-4">
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-avocado-100">
                        Checkout
                    </span>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight font-heading leading-tight text-avocado-50">
                        Lengkapi pemesanan
                    </h2>
                </div>

                <ol class="flex flex-wrap items-center gap-x-2 gap-y-2 text-sm" aria-label="Tahapan pemesanan">
                    @foreach ($bannerSteps as $i => $step)
                        <li class="inline-flex items-center gap-2">
                            <a href="#{{ $step['id'] }}"
                               @click.prevent="document.getElementById('{{ $step['id'] }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                               @if ($i === 0) aria-current="step" @endif
                               class="inline-flex items-center gap-2 rounded-full pl-1 pr-3 py-1 transition {{ $i === 0 ? 'text-white' : 'text-avocado-100 hover:text-white' }}">
                                <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 {{ $i === 0 ? 'bg-white text-avocado-900' : 'border border-white/40' }}">{{ $i + 1 }}</span>
                                <span class="whitespace-nowrap">{{ $step['label'] }}</span>
                            </a>
                            @unless ($loop->last)
                                <span class="hidden sm:inline-block w-5 border-t border-dashed border-white/40" aria-hidden="true"></span>
                            @endunless
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
