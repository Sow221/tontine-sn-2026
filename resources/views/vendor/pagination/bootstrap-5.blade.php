@if ($paginator->hasPages())
<nav aria-label="Pagination" class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">

    {{-- Résumé (masqué sur très petits écrans) --}}
    <p class="small text-muted mb-0 d-none d-sm-block">
        Résultats <strong>{{ $paginator->firstItem() }}</strong> à <strong>{{ $paginator->lastItem() }}</strong>
        sur <strong>{{ $paginator->total() }}</strong>
    </p>

    {{-- Pagination --}}
    <ul class="pagination mb-0">

        {{-- Précédent --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled" aria-disabled="true" aria-label="Page précédente">
                <span class="page-link"><i class="fas fa-chevron-left" style="font-size:11px;"></i></span>
            </li>
        @else
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Page précédente">
                    <i class="fas fa-chevron-left" style="font-size:11px;"></i>
                </a>
            </li>
        @endif

        {{-- Numéros de pages --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">{{ $element }}</span>
                </li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active" aria-current="page">
                            <span class="page-link">{{ $page }}</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Suivant --}}
        @if ($paginator->hasMorePages())
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Page suivante">
                    <i class="fas fa-chevron-right" style="font-size:11px;"></i>
                </a>
            </li>
        @else
            <li class="page-item disabled" aria-disabled="true" aria-label="Page suivante">
                <span class="page-link"><i class="fas fa-chevron-right" style="font-size:11px;"></i></span>
            </li>
        @endif

    </ul>
</nav>
@endif
