@if ($paginator->hasPages())
    <nav
        role="navigation"
        aria-label="Navigasi halaman katalog"
        class="flex flex-col items-center justify-between gap-4 rounded-2xl border border-wheat-200 bg-white px-4 py-3 shadow-tendaku-sm sm:flex-row"
    >
        <p class="text-xs font-medium text-darkbrown-600">
            Halaman <span class="font-bold text-darkbrown-900">{{ $paginator->currentPage() }}</span>
            dari <span class="font-bold text-darkbrown-900">{{ $paginator->lastPage() }}</span>
        </p>

        <div class="flex flex-wrap items-center justify-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span
                    aria-disabled="true"
                    aria-label="Halaman sebelumnya"
                    class="inline-flex h-10 cursor-not-allowed items-center gap-1.5 rounded-xl border border-wheat-200 bg-wheat-50 px-3 text-xs font-semibold text-darkbrown-300"
                >
                    <i data-lucide="chevron-left" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Sebelumnya</span>
                </span>
            @else
                <button
                    type="button"
                    wire:click="previousPage('page')"
                    wire:loading.attr="disabled"
                    aria-label="Halaman sebelumnya"
                    class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-wheat-300 bg-white px-3 text-xs font-semibold text-darkbrown-700 transition hover:bg-wheat-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60"
                >
                    <i data-lucide="chevron-left" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Sebelumnya</span>
                </button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true" class="inline-flex h-10 w-10 items-center justify-center text-xs font-bold text-darkbrown-400">
                        {{ $element }}
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span
                                aria-current="page"
                                class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-xl bg-avocado-600 px-2 text-xs font-bold text-white shadow-sm"
                            >
                                {{ $page }}
                            </span>
                        @else
                            <button
                                type="button"
                                wire:click="gotoPage({{ $page }}, 'page')"
                                wire:loading.attr="disabled"
                                aria-label="Ke halaman {{ $page }}"
                                class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-xl border border-wheat-300 bg-white px-2 text-xs font-semibold text-darkbrown-700 transition hover:bg-wheat-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60"
                            >
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button
                    type="button"
                    wire:click="nextPage('page')"
                    wire:loading.attr="disabled"
                    aria-label="Halaman berikutnya"
                    class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-wheat-300 bg-white px-3 text-xs font-semibold text-darkbrown-700 transition hover:bg-wheat-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60"
                >
                    <span class="hidden sm:inline">Berikutnya</span>
                    <i data-lucide="chevron-right" class="h-3.5 w-3.5" aria-hidden="true"></i>
                </button>
            @else
                <span
                    aria-disabled="true"
                    aria-label="Halaman berikutnya"
                    class="inline-flex h-10 cursor-not-allowed items-center gap-1.5 rounded-xl border border-wheat-200 bg-wheat-50 px-3 text-xs font-semibold text-darkbrown-300"
                >
                    <span class="hidden sm:inline">Berikutnya</span>
                    <i data-lucide="chevron-right" class="h-3.5 w-3.5" aria-hidden="true"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
