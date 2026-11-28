@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex justify-center">
        <div class="inline-flex items-center gap-1 rounded-full border border-wheat-200 bg-wheat-50 px-2 py-1.5 shadow-sm">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="inline-flex h-9 w-9 items-center justify-center text-darkbrown-300 cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-darkbrown-800 hover:bg-wheat-100 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </a>
            @endif

            {{-- Nomor halaman --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex h-9 w-6 items-center justify-center text-sm text-darkbrown-400">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-avocado-700 text-sm font-bold text-white shadow-sm">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold text-darkbrown-900 hover:bg-wheat-100 transition">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-darkbrown-800 hover:bg-wheat-100 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>
            @else
                <span aria-disabled="true" class="inline-flex h-9 w-9 items-center justify-center text-darkbrown-300 cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
