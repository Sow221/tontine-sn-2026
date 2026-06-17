@if ($paginator->hasPages())
<nav aria-label="Pagination" class="d-flex justify-content-between align-items-center mt-2">
    @if ($paginator->onFirstPage())
        <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true">
            <i class="fas fa-chevron-left me-1" style="font-size:11px;"></i>Précédent
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-chevron-left me-1" style="font-size:11px;"></i>Précédent
        </a>
    @endif

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-sm btn-outline-primary">
            Suivant<i class="fas fa-chevron-right ms-1" style="font-size:11px;"></i>
        </a>
    @else
        <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true">
            Suivant<i class="fas fa-chevron-right ms-1" style="font-size:11px;"></i>
        </span>
    @endif
</nav>
@endif
