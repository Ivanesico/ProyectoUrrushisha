@extends('layouts.app')

@section('title', 'Favoritos')

@section('content')
@php
    $activeFilters = collect([$filters['brand_id'], $filters['category_id'], $filters['tobacco_type']])->filter(fn ($value) => $value !== '')->count();
    $isFiltering = collect(['search', 'brand_id', 'category_id', 'tobacco_type'])->contains(fn ($filter) => request()->filled($filter));
    $showAdvanced = $activeFilters > 0 || $sort !== 'name_asc';
@endphp
<div class="container py-4">
    @if(session('success'))
    <div class="alert alert-success rounded-4 shadow-sm">
        {{ session('success') }}
    </div>
    @endif

    <header class="fav-header">
        <h1 class="fav-title">Mis favoritos</h1>
        <p class="fav-count">
            @if($isFiltering)
            {{ $matchingCount }} de {{ $totalCount }} favoritos
            @elseif($totalCount === 1)
            1 favorito guardado
            @elseif($totalCount > 1)
            {{ $totalCount }} favoritos guardados
            @else
            Los sabores que guardes aparecerán aquí.
            @endif
        </p>
    </header>

    @if($totalCount === 0)
    <div class="empty-state text-center py-5 px-4">
        <div class="empty-icon mb-3" aria-hidden="true">♥</div>
        <h2 class="empty-title">No tienes favoritos todavía</h2>
        <p class="empty-subtitle mb-3">Pulsa el corazón de un sabor en el catálogo para guardarlo.</p>
        <a href="{{ route('home') }}" class="btn fav-primary">Ver el catálogo</a>
    </div>
    @else
    <form class="fav-toolbar" method="GET" action="{{ route('favorites.index') }}">
        <div class="fav-search-row">
            <div class="fav-search">
                <label for="search" class="visually-hidden">Buscar por nombre o ingrediente</label>
                <input type="search" name="search" id="search" class="form-control" placeholder="Nombre o ingrediente" value="{{ $filters['search'] }}">
            </div>

            <button class="btn fav-filters-toggle d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#favAdvanced" aria-expanded="{{ $showAdvanced ? 'true' : 'false' }}" aria-controls="favAdvanced">
                Filtros{{ $activeFilters > 0 ? " ($activeFilters)" : '' }}
            </button>
        </div>

        <div class="collapse fav-advanced {{ $showAdvanced ? 'show' : '' }}" id="favAdvanced">
            <div class="fav-fields">
                <div>
                    <label for="brand_id" class="form-label">Marca</label>
                    <select name="brand_id" id="brand_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        @foreach($brands as $brand)
                        <option value="{{ $brand->id }}"{{ $filters['brand_id'] === (string) $brand->id ? ' selected' : '' }}>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="category_id" class="form-label">Categoría</label>
                    <select name="category_id" id="category_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}"{{ $filters['category_id'] === (string) $category->id ? ' selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tobacco_type" class="form-label">Tipo</label>
                    <select name="tobacco_type" id="tobacco_type" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        @foreach($tobaccoTypes as $type => $label)
                        <option value="{{ $type }}"{{ $filters['tobacco_type'] === $type ? ' selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="sort" class="form-label">Ordenar por</label>
                    <select name="sort" id="sort" class="form-select" onchange="this.form.submit()">
                        @foreach($sorts as $value => $label)
                        <option value="{{ $value }}"{{ $sort === $value ? ' selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="fav-actions">
                    <button type="submit" class="btn fav-primary">Aplicar</button>
                    @if($isFiltering || $sort !== 'name_asc')
                    <a href="{{ route('favorites.index') }}" class="fav-clear">Limpiar</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    @if($matchingCount === 0)
    <div class="empty-state text-center py-5 px-4">
        <h2 class="empty-title">Ningún favorito coincide</h2>
        <p class="empty-subtitle mb-3">Cambia la búsqueda o quita los filtros para ver todos tus favoritos.</p>
        <a href="{{ route('favorites.index') }}" class="btn fav-primary">Quitar filtros</a>
    </div>
    @endif

    @foreach($favoriteGroups as $groupLabel => $groupFavorites)
    @if((string) $groupLabel !== '')
    <h3 class="fav-group-title">{{ $groupLabel }}</h3>
    @endif

    <div class="row g-3 g-lg-4 fav-group">
        @foreach($groupFavorites as $favorite)
        @php
        $flavor = $favorite->flavor;
        @endphp

        <div class="col-6 col-md-4 col-lg-3">
            <article class="flavor-card"
                 role="button"
                 tabindex="0"
                 aria-label="Ver detalles de {{ $flavor->name }}"
                 onclick="openFlavorModal({{ $flavor->id }})"
                 onkeydown="if (event.key === 'Enter' && event.target === this) openFlavorModal({{ $flavor->id }})"
                 data-id="{{ $flavor->id }}"
                 data-name="{{ $flavor->name }}"
                 data-brand="{{ $flavor->brand->name ?? 'Sin marca' }}"
                 data-category="{{ $flavor->category->name ?? 'Sin categoría' }}"
                 data-type="{{ $flavor->tobacco_type_label ?? 'No definido' }}"
                 data-description="{{ $flavor->description ?? 'Sin descripción disponible.' }}"
                 data-ingredients="{{ $flavor->ingredients_text ?? 'Sin ingredientes definidos' }}"
                 data-image="{{ $flavor->image_url ? asset('storage/' . $flavor->image_url) : '' }}">

                <form class="card-top-actions" action="{{ route('favorites.store', $flavor) }}" method="POST" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                    @csrf
                    <button type="submit" class="favorite-btn active" title="Quitar de favoritos" aria-label="Quitar {{ $flavor->name }} de favoritos">♥</button>
                </form>

                <div class="flavor-image-wrapper">
                    <span class="placeholder-text">{{ $flavor->initial }}</span>
                    @if($flavor->image_url)
                    <img src="{{ asset('storage/' . $flavor->image_url) }}" class="flavor-image" alt="" loading="lazy" onerror="this.remove()">
                    @endif
                </div>

                <div class="flavor-card-body">
                    <h2 class="flavor-card-name">{{ $flavor->name }}</h2>
                    <p class="flavor-card-brand">{{ $flavor->brand->name ?? 'Sin marca' }}</p>

                    <div class="flavor-card-tags">
                        @if($flavor->tobacco_type_label)
                        <span class="flavor-tag">{{ $flavor->tobacco_type_label }}</span>
                        @endif
                        @if($flavor->category)
                        <span class="flavor-tag">{{ $flavor->category->name }}</span>
                        @endif
                    </div>
                </div>
            </article>
        </div>
        @endforeach
    </div>
    @endforeach
    @endif

    <div id="flavorModalOverlay" class="flavor-modal-overlay" onclick="closeFlavorModal()">
        <div class="flavor-modal-card" role="dialog" aria-modal="true" aria-labelledby="modalFlavorName" onclick="event.stopPropagation()">
            <button class="flavor-modal-close" type="button" aria-label="Cerrar" onclick="closeFlavorModal()">×</button>

            <div class="row g-0">
                <div class="col-lg-5">
                    <div class="flavor-modal-image-wrapper">
                        <img id="modalFlavorImage" src="" alt="" class="flavor-modal-image d-none">
                        <div id="modalFlavorPlaceholder" class="flavor-modal-placeholder d-flex align-items-center justify-content-center">
                            <span id="modalFlavorPlaceholderText" class="modal-placeholder-text">?</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="p-4 p-lg-5">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3 modal-heading">
                            <h2 id="modalFlavorName" class="modal-flavor-title mb-0"></h2>
                            <span id="modalFlavorCategory" class="badge badge-soft"></span>
                        </div>

                        <div class="modal-meta-list mb-4">
                            <div class="modal-meta-item">
                                <span class="modal-meta-label">Marca</span>
                                <span id="modalFlavorBrand" class="modal-meta-value"></span>
                            </div>

                            <div class="modal-meta-item">
                                <span class="modal-meta-label">Tipo</span>
                                <span id="modalFlavorType" class="modal-meta-value"></span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h3 class="modal-section-title">Descripción</h3>
                            <p id="modalFlavorDescription" class="modal-description mb-0"></p>
                        </div>

                        <div class="mb-4">
                            <h3 class="modal-section-title">Ingredientes</h3>
                            <p id="modalFlavorIngredients" class="modal-description mb-0"></p>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('mixes.create') }}" class="btn fav-primary">
                                Crear mezcla
                            </a>
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="closeFlavorModal()">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('head')
<style>
    body {
        background:
            radial-gradient(circle at top left, rgba(139, 92, 246, 0.18) 0%, transparent 28%),
            radial-gradient(circle at bottom right, rgba(236, 72, 153, 0.16) 0%, transparent 25%),
            linear-gradient(135deg, #0f1117 0%, #171a22 45%, #111318 100%);
        min-height: 100vh;
    }

    .fav-header {
        margin-bottom: 1.5rem;
    }

    .fav-title {
        margin-bottom: 0.35rem;
        color: #ffffff;
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 800;
        line-height: 1.05;
    }

    .fav-count {
        margin: 0;
        color: var(--us-text);
    }

    .fav-primary {
        padding: 0.5rem 1.25rem;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: #ffffff;
        font-weight: 700;
    }

    .fav-primary:hover {
        background: linear-gradient(135deg, #7c3aed, #db2777);
        color: #ffffff;
    }

    .fav-toolbar {
        margin-bottom: 1.75rem;
        padding: 1rem;
        border: 1px solid var(--us-line);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.05);
    }

    .fav-search-row {
        display: flex;
        gap: 0.5rem;
    }

    .fav-search {
        flex: 1;
        min-width: 0;
    }

    .fav-search input {
        padding-left: 2.35rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23c9c0d8' stroke-width='2' stroke-linecap='round'%3E%3Ccircle cx='11' cy='11' r='6.5'/%3E%3Cpath d='M16 16l4.500 4.500'/%3E%3C/svg%3E");
        background-position: 0.75rem center;
        background-repeat: no-repeat;
        background-size: 1.05rem;
    }

    .fav-filters-toggle {
        flex-shrink: 0;
        border: 1px solid var(--us-line);
        border-radius: 999px;
        color: var(--us-text-strong);
        font-weight: 600;
    }

    .fav-fields {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
        gap: 0.75rem;
        align-items: end;
        padding-top: 0.75rem;
    }

    .fav-fields .form-label {
        margin-bottom: 0.25rem;
        color: var(--us-text);
        font-size: 0.85rem;
        font-weight: 600;
    }

    .fav-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .fav-clear {
        color: var(--us-text);
        font-size: 0.92rem;
        font-weight: 600;
    }

    .fav-clear:hover {
        color: var(--us-text-strong);
    }

    .fav-group-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 1.75rem 0 0.9rem;
        color: #ffffff;
        font-size: 1.1rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .fav-group-title::after {
        content: "";
        flex: 1;
        min-width: 1.5rem;
        height: 2px;
        border-radius: 2px;
        background: linear-gradient(90deg, rgba(139, 92, 246, 0.8), rgba(236, 72, 153, 0));
    }

    .flavor-card {
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
        border-radius: 22px;
        background: #ffffff;
        color: #111827;
        cursor: pointer;
        transition: box-shadow 0.15s ease;
    }

    .flavor-card:hover {
        box-shadow: 0 0 0 2px #ec4899;
    }

    .flavor-card:focus-visible {
        outline: 3px solid #f472b6;
        outline-offset: 2px;
    }

    .card-top-actions {
        position: absolute;
        top: 0.6rem;
        right: 0.6rem;
        z-index: 2;
        margin: 0;
    }

    .favorite-btn {
        width: 2.5rem;
        height: 2.5rem;
        border: none;
        border-radius: 50%;
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
        color: #9ca3af;
        font-size: 1.2rem;
        line-height: 1;
    }

    .favorite-btn.active {
        color: #e11d48;
    }

    .favorite-btn:focus-visible {
        outline: 3px solid #f472b6;
        outline-offset: 2px;
    }

    .flavor-image-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        aspect-ratio: 4 / 3;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
    }

    .flavor-image {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        background: #ffffff;
        object-fit: cover;
        object-position: center;
    }

    .placeholder-text {
        color: #ffffff;
        font-size: 2.8rem;
        font-weight: 800;
    }

    .flavor-card-body {
        display: flex;
        flex: 1;
        flex-direction: column;
        padding: 0.9rem 1rem 1rem;
    }

    .flavor-card-name {
        margin: 0;
        font-size: 1.02rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .flavor-card-brand {
        margin: 0.1rem 0 0.7rem;
        color: #4b5563;
        font-size: 0.86rem;
        overflow-wrap: anywhere;
    }

    .flavor-card-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
        margin-top: auto;
    }

    .flavor-tag {
        padding: 0.22rem 0.6rem;
        border-radius: 999px;
        background: #f3f4f6;
        color: #374151;
        font-size: 0.74rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .badge-soft {
        background: #f3f4f6;
        color: #374151;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        padding: 0.48rem 0.75rem;
        white-space: normal;
        overflow-wrap: anywhere;
        min-width: 0;
    }

    .flavor-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 17, 23, 0.72);
        backdrop-filter: blur(8px);
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    .flavor-modal-overlay.show {
        display: flex;
    }

    .flavor-modal-card {
        position: relative;
        width: min(100%, 1000px);
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 28px;
        background: #ffffff;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
    }

    .flavor-modal-close {
        position: absolute;
        top: 16px;
        right: 16px;
        z-index: 10;
        width: 44px;
        height: 44px;
        border: none;
        border-radius: 50%;
        background: #ffffff;
        color: #111827;
        font-size: 1.7rem;
        line-height: 1;
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.16);
    }

    .flavor-modal-image-wrapper {
        height: 100%;
        min-height: 320px;
        background: linear-gradient(135deg, #eef2ff, #f5f3ff);
    }

    .flavor-modal-image {
        width: 100%;
        height: 100%;
        max-height: 70vh;
        padding: 1rem;
        object-fit: contain;
    }

    .flavor-modal-placeholder {
        width: 100%;
        height: 100%;
        min-height: inherit;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
    }

    .modal-placeholder-text {
        color: #ffffff;
        font-size: 4rem;
        font-weight: 800;
    }

    .modal-heading {
        padding-right: 2.5rem;
    }

    .modal-flavor-title {
        color: #111827;
        font-weight: 800;
        font-size: clamp(1.5rem, 3vw, 2.2rem);
        overflow-wrap: anywhere;
    }

    .modal-meta-list {
        display: grid;
        gap: 0.8rem;
    }

    .modal-meta-item {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: 0.35rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .modal-meta-label {
        color: #6b7280;
        font-weight: 600;
    }

    .modal-meta-value {
        color: #1f2937;
        font-weight: 700;
        text-align: right;
    }

    .modal-section-title {
        margin-bottom: 0.4rem;
        color: #374151;
        font-size: 1rem;
        font-weight: 700;
    }

    .modal-description {
        color: #1f2937;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .empty-state {
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--us-line);
    }

    .empty-icon {
        font-size: 2.2rem;
        color: #f472b6;
    }

    .empty-title {
        color: #ffffff;
        font-size: 1.4rem;
        font-weight: 700;
    }

    .empty-subtitle {
        color: var(--us-text);
    }

    @media (min-width: 768px) {
        /* In desktop the filters are always visible: the toggle is only for phones. */
        .fav-advanced.collapse {
            display: block !important;
            height: auto !important;
        }
    }

    @media (max-width: 991.98px) {
        .flavor-modal-image-wrapper {
            min-height: 220px;
        }

        .flavor-modal-image {
            max-height: 240px;
        }
    }

    @media (max-width: 767.98px) {
        .fav-fields {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .fav-actions {
            grid-column: 1 / -1;
        }

        .flavor-card {
            border-radius: 18px;
        }

        .favorite-btn {
            width: 2.2rem;
            height: 2.2rem;
            font-size: 1rem;
        }

        .flavor-card-body {
            padding: 0.75rem 0.8rem 0.85rem;
        }

        .flavor-card-name {
            font-size: 0.92rem;
        }

        .flavor-card-brand {
            font-size: 0.78rem;
        }

        .flavor-modal-overlay {
            padding: 12px;
        }

        .flavor-modal-card {
            border-radius: 22px;
            max-height: 92vh;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .flavor-card {
            transition: none;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function openFlavorModal(id) {
        const card = document.querySelector(`.flavor-card[data-id="${id}"]`);
        if (!card) return;

        document.getElementById('modalFlavorName').textContent = card.dataset.name || '';
        document.getElementById('modalFlavorBrand').textContent = card.dataset.brand || '';
        document.getElementById('modalFlavorCategory').textContent = card.dataset.category || '';
        document.getElementById('modalFlavorType').textContent = card.dataset.type || '';
        document.getElementById('modalFlavorDescription').textContent = card.dataset.description || '';
        document.getElementById('modalFlavorIngredients').textContent = card.dataset.ingredients || '';

        const image = card.dataset.image || '';
        const imageEl = document.getElementById('modalFlavorImage');
        const placeholderEl = document.getElementById('modalFlavorPlaceholder');
        const placeholderTextEl = document.getElementById('modalFlavorPlaceholderText');

        if (image) {
            imageEl.src = image;
            imageEl.alt = card.dataset.name || '';
            imageEl.classList.remove('d-none');
            placeholderEl.classList.add('d-none');
            placeholderEl.classList.remove('d-flex');
        } else {
            imageEl.classList.add('d-none');
            placeholderEl.classList.remove('d-none');
            placeholderEl.classList.add('d-flex');
            placeholderTextEl.textContent = (card.dataset.name || '?').charAt(0).toUpperCase();
        }

        document.getElementById('flavorModalOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeFlavorModal() {
        document.getElementById('flavorModalOverlay').classList.remove('show');
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeFlavorModal();
        }
    });
</script>
@endpush
