@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-3 text-sm">
        <p class="hidden text-ink-500 sm:block">
            Showing <span class="font-semibold text-ink-800">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-ink-800">{{ $paginator->lastItem() }}</span>
            of <span class="font-semibold text-ink-800">{{ number_format($paginator->total()) }}</span>
        </p>
        <div class="flex flex-1 items-center justify-between gap-1 sm:flex-none">
            @if ($paginator->onFirstPage())
                <span class="btn btn-outline btn-sm opacity-50">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-outline btn-sm">Previous</a>
            @endif

            <div class="hidden gap-1 md:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-2 py-1.5 text-ink-400">{{ $element }}</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="grid size-9 place-items-center rounded-full bg-ink-900 font-semibold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="grid size-9 place-items-center rounded-full text-ink-600 hover:bg-ink-100">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-outline btn-sm">Next</a>
            @else
                <span class="btn btn-outline btn-sm opacity-50">Next</span>
            @endif
        </div>
    </nav>
@endif
