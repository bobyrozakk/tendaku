{{-- Timeline utama booking. Variabel berasal dari view induk. --}}
<div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-extrabold text-darkbrown-900 font-heading">Alur pemesanan</h2>
                    <span class="px-3 py-1 rounded-full text-sm font-bold border"
                          :class="current ? 'bg-sunglow-100 border-sunglow-300 text-darkbrown-900' : 'bg-red-50 border-red-200 text-red-800'"
                          x-text="statusLabels[status]">{{ $statusLabels[$status] ?? $status }}</span>
                </div>

                <ol class="relative">
                    @foreach($nodes as $key => $n)
                        <li id="node-{{ $key }}" class="relative flex gap-4 sm:gap-5">

                            <!-- Bulatan & garis -->
                            <div class="flex flex-col items-center shrink-0">
                                <span class="relative z-10 w-10 h-10 rounded-full border-2 flex items-center justify-center transition-colors duration-500"
                                      :class="[dotCls('{{ $key }}'), justDone === '{{ $key }}' ? 'dot-pop' : '']">
                                    <span x-show="state('{{ $key }}') === 'active'" class="absolute inset-0 rounded-full bg-sunglow-300/60 animate-ping motion-reduce:animate-none" aria-hidden="true"></span>
                                    <svg x-show="state('{{ $key }}') === 'done'" class="relative w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    <svg x-show="state('{{ $key }}') !== 'done'" class="relative w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $n['icon'] }}" /></svg>
                                </span>
                                @unless($loop->last)
                                    <span class="relative w-0.5 flex-1 bg-wheat-200 my-1 rounded">
                                        <span class="absolute inset-x-0 top-0 bg-avocado-500 rounded transition-[height] duration-700 ease-out"
                                              :style="'height:' + lineFill({{ $loop->index }}) + '%'"></span>
                                    </span>
                                @endunless
                            </div>

                            <!-- Kartu -->
                            <section class="flex-1 min-w-0 mb-5 rounded-2xl border transition-colors duration-500"
                                     :class="cardCls('{{ $key }}')"
                                     :aria-current="current === '{{ $key }}' ? 'step' : null">

                                <header class="flex items-start justify-between gap-3 p-4 sm:p-5">
                                    <div class="min-w-0">
                                        <h3 class="font-extrabold text-darkbrown-900 leading-snug">{{ $n['title'] }}</h3>
                                        <p class="mt-0.5 text-sm text-darkbrown-600" x-text="descs['{{ $key }}'][state('{{ $key }}')]"></p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0 text-right">
                                        <span x-show="times['{{ $key }}']" class="text-xs font-semibold text-darkbrown-500" x-text="times['{{ $key }}']">{{ $times[$key] }}</span>
                                        @unless($key === 'created')
                                            <svg x-show="state('{{ $key }}') === 'todo'" class="w-4 h-4 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-label="Belum terbuka"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                        @endunless
                                    </div>
                                </header>

                                @unless($key === 'created')
                                <div class="tl-collapse" :class="{ 'is-open': isOpen('{{ $key }}') }" :inert="!isOpen('{{ $key }}')">
                                    <div class="tl-inner">
                                        <div class="relative border-t border-wheat-200 p-4 sm:p-5 space-y-4">

                                            @switch($key)

                                                {{-- ================= PEMBAYARAN ================= --}}
                                                @case('payment')
                                                    @include('customer.booking.status.payment')
                                                    @break
                                                {{-- ================= SERAH TERIMA ================= --}}
                                                @case('pickup')
                                                    @include('customer.booking.status.steps.pickup')
                                                    @break

                                                {{-- ================= MASA SEWA ================= --}}
                                                @case('rental')
                                                    @include('customer.booking.status.steps.rental')
                                                    @break

                                                {{-- ================= PENGEMBALIAN ================= --}}
                                                @case('return')
                                                    @include('customer.booking.status.steps.return')
                                                    @break

                                                {{-- ================= SELESAI ================= --}}
                                                @case('done')
                                                    @include('customer.booking.status.steps.done')
                                                    @break

                                            @endswitch
                                        </div>
                                    </div>
                                </div>
                                @endunless
                            </section>
                        </li>
                    @endforeach
                </ol>
