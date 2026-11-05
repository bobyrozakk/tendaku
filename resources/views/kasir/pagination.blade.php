@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center gap-1.5 select-none">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="inline-flex items-center justify-center w-8 h-8 rounded-xl border border-wheat-200 bg-wheat-100/50 text-darkbrown-300 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </span>
        @else
            <button wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    type="button"
                    rel="prev"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl border border-wheat-300 bg-white text-darkbrown-700 hover:bg-wheat-100 hover:text-darkbrown-900 active:scale-95 transition-all duration-150 shadow-sm cursor-pointer"
                    aria-label="{{ __('pagination.previous') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="inline-flex items-center justify-center w-8 h-8 text-xs font-bold text-darkbrown-400">
                    {{ $element }}
                </span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-xs font-black text-white bg-avocado-600 border border-avocado-700 shadow-md shadow-avocado-600/30">
                            {{ $page }}
                        </span>
                    @else
                        <button wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                wire:loading.attr="disabled"
                                type="button"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-xs font-bold text-darkbrown-700 bg-white border border-wheat-300 hover:bg-wheat-100 hover:text-darkbrown-900 active:scale-95 transition-all duration-150 shadow-sm cursor-pointer">
                            {{ $page }}
                        </button>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <button wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    type="button"
                    rel="next"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl border border-wheat-300 bg-white text-darkbrown-700 hover:bg-wheat-100 hover:text-darkbrown-900 active:scale-95 transition-all duration-150 shadow-sm cursor-pointer"
                    aria-label="{{ __('pagination.next') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        @else
            <span aria-disabled="true" class="inline-flex items-center justify-center w-8 h-8 rounded-xl border border-wheat-200 bg-wheat-100/50 text-darkbrown-300 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
        @endif
    </nav>
@endif
