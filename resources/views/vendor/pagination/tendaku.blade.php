@if ($paginator->hasPages())
    <nav
        role="navigation"
        aria-label="Navigasi halaman katalog"
        class="flex items-center justify-center pt-3"
    >
        <div
            class="inline-flex items-center gap-0.5 rounded-full border border-wheat-200 bg-white p-1 shadow-sm"
        >
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span
                    aria-disabled="true"
                    aria-label="Previous"
                    class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-full text-darkbrown-300"
                >
                    <i
                        data-lucide="chevron-left"
                        class="h-3.5 w-3.5"
                        aria-hidden="true"
                    ></i>
                </span>
            @else
                <button
                    type="button"
                    wire:click="previousPage('page')"
                    wire:loading.attr="disabled"
                    aria-label="Previous"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full text-darkbrown-600 transition hover:bg-wheat-100 hover:text-darkbrown-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 disabled:cursor-wait disabled:opacity-60"
                >
                    <i
                        data-lucide="chevron-left"
                        class="h-3.5 w-3.5"
                        aria-hidden="true"
                    ></i>
                </button>
            @endif

            {{-- Pagination --}}
            @foreach ($elements as $element)

                {{-- Dots --}}
                @if (is_string($element))
                    <span
                        aria-disabled="true"
                        class="inline-flex h-8 min-w-7 items-center justify-center px-1 text-xs font-semibold text-darkbrown-400"
                    >
                        {{ $element }}
                    </span>
                @endif

                {{-- Pages --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)

                        @if ($page == $paginator->currentPage())
                            <span
                                aria-current="page"
                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-avocado-600 px-2 text-xs font-bold text-white shadow-sm"
                            >
                                {{ $page }}
                            </span>
                        @else
                            <button
                                type="button"
                                wire:click="gotoPage({{ $page }}, 'page')"
                                wire:loading.attr="disabled"
                                aria-label="Page {{ $page }}"
                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-full px-2 text-xs font-semibold text-darkbrown-600 transition hover:bg-wheat-100 hover:text-darkbrown-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 disabled:cursor-wait disabled:opacity-60"
                            >
                                {{ $page }}
                            </button>
                        @endif

                    @endforeach
                @endif

            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <button
                    type="button"
                    wire:click="nextPage('page')"
                    wire:loading.attr="disabled"
                    aria-label="Next"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full text-darkbrown-600 transition hover:bg-wheat-100 hover:text-darkbrown-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 disabled:cursor-wait disabled:opacity-60"
                >
                    <i
                        data-lucide="chevron-right"
                        class="h-3.5 w-3.5"
                        aria-hidden="true"
                    ></i>
                </button>
            @else
                <span
                    aria-disabled="true"
                    aria-label="Next"
                    class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-full text-darkbrown-300"
                >
                    <i
                        data-lucide="chevron-right"
                        class="h-3.5 w-3.5"
                        aria-hidden="true"
                    ></i>
                </span>
            @endif
        </div>
    </nav>
@endif
