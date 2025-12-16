@props(['items'])

@php
    $prevUrl = $items->previousPageUrl();
    $nextUrl = $items->nextPageUrl();
    $isLivewirePaginator = false;
    // Heuristic: Livewire paginators generate /livewire/update URLs
    if ($prevUrl && str_contains($prevUrl, '/livewire/update')) { $isLivewirePaginator = true; }
    if ($nextUrl && str_contains($nextUrl, '/livewire/update')) { $isLivewirePaginator = true; }
@endphp


<div class="table-footer">
    <span>
        {{ ui_t('tables.pagination.showing', ['from' => $items->firstItem(), 'to' => $items->lastItem(), 'total' => $items->total()]) }}
    </span>

    <div class="table-pagination">
        {{-- Previous Page Link --}}
        @if ($items->onFirstPage())
            <button class="btn-page" disabled>
                <i class="fa-solid fa-chevron-left"></i> {{ ui_t('tables.pagination.previous') }}
            </button>
        @else
            @if ($isLivewirePaginator)
                <button type="button" class="btn-page" wire:click="previousPage">
                    <i class="fa-solid fa-chevron-left"></i> {{ ui_t('tables.pagination.previous') }}
                </button>
            @else
                <a href="{{ $items->previousPageUrl() }}" class="btn-page">
                    <i class="fa-solid fa-chevron-left"></i> {{ ui_t('tables.pagination.previous') }}
                </a>
            @endif
        @endif

        {{-- Page Numbers --}}
        @php
            $current = $items->currentPage();
            $last = $items->lastPage();
            $start = max(1, $current - 4);
            $end = min($last, $current + 4);
        @endphp
        @foreach ($items->getUrlRange($start, $end) as $page => $url)
            @if ($page == $items->currentPage())
                <span class="page-num active">{{ $page }}</span>
            @else
                @if ($isLivewirePaginator)
                    <button type="button" class="page-num" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                @else
                    <a href="{{ $url }}" class="page-num">{{ $page }}</a>
                @endif
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($items->hasMorePages())
            @if ($isLivewirePaginator)
                <button type="button" class="btn-page" wire:click="nextPage">
                    {{ ui_t('tables.pagination.next') }} <i class="fa-solid fa-chevron-right"></i>
                </button>
            @else
                <a href="{{ $items->nextPageUrl() }}" class="btn-page">
                    {{ ui_t('tables.pagination.next') }} <i class="fa-solid fa-chevron-right"></i>
                </a>
            @endif
        @else
            <button class="btn-page" disabled>
                {{ ui_t('tables.pagination.next') }} <i class="fa-solid fa-chevron-right"></i>
            </button>
        @endif
    </div>
</div>
