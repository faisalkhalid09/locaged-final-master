@props(['items'])

@php
    // Check if we're in a Livewire component by checking if the paginator has Livewire methods
    $isLivewirePaginator = method_exists($items, 'hasPages') &&
                          (str_contains(get_class($items), 'Livewire') ||
                           isset($this) ||
                           request()->header('X-Livewire'));
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
            <button type="button" class="btn-page" wire:click="previousPage" wire:loading.attr="disabled">
                <i class="fa-solid fa-chevron-left"></i> {{ ui_t('tables.pagination.previous') }}
            </button>
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
                <button type="button" class="page-num" wire:click="gotoPage({{ $page }})" wire:loading.attr="disabled">{{ $page }}</button>
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($items->hasMorePages())
            <button type="button" class="btn-page" wire:click="nextPage" wire:loading.attr="disabled">
                {{ ui_t('tables.pagination.next') }} <i class="fa-solid fa-chevron-right"></i>
            </button>
        @else
            <button class="btn-page" disabled>
                {{ ui_t('tables.pagination.next') }} <i class="fa-solid fa-chevron-right"></i>
            </button>
        @endif
    </div>
</div>
