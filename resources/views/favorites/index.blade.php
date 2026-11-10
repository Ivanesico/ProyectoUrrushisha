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
        <p class="fav-count" data-total="{{ $totalCount }}" data-matching="{{ $matchingCount }}" data-filtering="{{ $isFiltering ? '1' : '0' }}">
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
                    <select name="brand_id" id="brand_id" class="form-select" onchange="this.form.submit()" data-filter-dropdown>
                        <option value="">Todas</option>
                        @foreach($brands as $brand)
                        <option value="{{ $brand->id }}"{{ $filters['brand_id'] === (string) $brand->id ? ' selected' : '' }}>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="category_id" class="form-label">Categoría</label>
                    <select name="category_id" id="category_id" class="form-select" onchange="this.form.submit()" data-filter-dropdown>
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}"{{ $filters['category_id'] === (string) $category->id ? ' selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tobacco_type" class="form-label">Tipo</label>
                    <select name="tobacco_type" id="tobacco_type" class="form-select" onchange="this.form.submit()" data-filter-dropdown>
                        <option value="">Todos</option>
                        @foreach($tobaccoTypes as $type => $label)
                        <option value="{{ $type }}"{{ $filters['tobacco_type'] === $type ? ' selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="sort" class="form-label">Ordenar por</label>
                    <select name="sort" id="sort" class="form-select" onchange="this.form.submit()" data-filter-dropdown>
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
    @include('partials._filter-dropdown')

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

        <div class="col-6 col-md-4 col-lg-3" data-favorite-item>
            <article class="flavor-card"
                 role="button"
                 tabindex="0"
                 aria-label="Ver detalles de {{ $flavor->name }}"
                 onclick="openFlavorModal({{ $flavor->id }})"
                 onkeydown="if (event.key === 'Enter' && event.target === this) openFlavorModal({{ $flavor->id }})"
                 data-name="{{ $flavor->name }}">

                {{-- El corazón no abre la ventana --}}
                <form class="card-top-actions" action="{{ route('favorites.store', $flavor) }}" method="POST" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();" data-favorite-toggle data-favorite-remove data-flavor-id="{{ $flavor->id }}" data-flavor-name="{{ $flavor->name }}">
                    @csrf
                    <button type="submit" class="favorite-btn active" title="Quitar de favoritos" aria-label="Quitar {{ $flavor->name }} de favoritos" aria-pressed="true">♥</button>
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

    {{-- Una ventana por sabor, fuera de las tarjetas para que sus clics no lleguen a ellas --}}
    @foreach($groupFavorites as $favorite)
    @include('flavors._detail-modal', ['flavor' => $favorite->flavor])
    @endforeach
    @endforeach
    @include('partials._favorite-toggle')
    @endif
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
        /* Si no cabe, la etiqueta pasa entera a la línea siguiente; solo se corta entre palabras. */
        max-width: 100%;
        overflow-wrap: normal;
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
    }

    @media (prefers-reduced-motion: reduce) {
        .flavor-card {
            transition: none;
        }
    }
</style>
@endpush

