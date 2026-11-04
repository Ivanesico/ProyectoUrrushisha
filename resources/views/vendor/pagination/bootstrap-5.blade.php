@if ($paginator->hasPages())
    <nav class="us-pagination" aria-label="Paginación">
        <p class="us-pagination-summary">
            Mostrando <span class="fw-semibold">{{ $paginator->firstItem() }}</span> a <span class="fw-semibold">{{ $paginator->lastItem() }}</span> de <span class="fw-semibold">{{ $paginator->total() }}</span> resultados
        </p>

        <ul class="us-pagination-list">
            {{-- Página anterior --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span class="us-page us-page-step is-disabled" aria-disabled="true" aria-label="Anterior">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>
                        Anterior
                    </span>
                @else
                    <a class="us-page us-page-step" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>
                        Anterior
                    </a>
                @endif
            </li>

            {{-- En móvil, la posición sustituye a los números --}}
            <li class="us-page-position d-sm-none">
                Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="d-none d-sm-block"><span class="us-page is-disabled" aria-hidden="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="d-none d-sm-block">
                            @if ($page == $paginator->currentPage())
                                <span class="us-page is-current" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="us-page" href="{{ $url }}" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Página siguiente --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a class="us-page us-page-step" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente">
                        Siguiente
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>
                    </a>
                @else
                    <span class="us-page us-page-step is-disabled" aria-disabled="true" aria-label="Siguiente">
                        Siguiente
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
