@php
    foreach ([0, 4] as $key) {
        if (count($elements) >= 3 && isset($elements[$key]) && is_array($elements[$key]) && 2 === count($elements[$key])) {
            unset($elements[$key][array_keys($elements[$key])[($key ? 0 : 1)]]);
        }
    }
@endphp

@if ($paginator->hasPages())
<div class="dataTable-bottom">
    <nav class="dataTable-pagination">
        <ul class="dataTable-pagination-list">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="pager disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <a href="#">&lsaquo;</a>
                </li>
            @else
                <li class="pager">
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="active" aria-current="page"><a href="#">{{ $page }}</a></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="pager">
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="pager disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <a href="#">&rsaquo;</a>
                </li>
            @endif
        </ul>
    </nav>
</div>
@endif
