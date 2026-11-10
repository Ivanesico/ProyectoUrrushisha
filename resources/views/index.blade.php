@extends('layouts.app')

@section('title', 'Sabores')

@section('content')
<div class="container py-4">
    @if(session('success'))
    <div class="alert alert-success rounded-4 shadow-sm">
        {{ session('success') }}
    </div>
    @endif

    <section class="hero-section mb-5">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div>
                <span class="hero-badge">Explora nuevos sabores</span>
                <h1 class="hero-title mt-3 mb-2">Tu biblioteca de sabores de shisha</h1>
                <p class="hero-subtitle mb-0">
                    Busca por marca, categoría, ingredientes o tipo de tabaco y guarda tus favoritos.
                </p>
            </div>
        </div>
    </section>

    <section class="filters-panel mb-5">
        <div class="filters-header d-flex justify-content-between align-items-center gap-3">
            <div>
                <h2 class="filters-title mb-1">Filtrar sabores</h2>
                <p class="filters-subtitle mb-0">Encuentra exactamente el perfil que buscas.</p>
            </div>

            <button
                type="button"
                class="btn btn-outline-light btn-modern d-md-none"
                id="toggleFiltersBtn"
                onclick="toggleFiltersMobile()">
                Filtros
            </button>
        </div>

        <div id="filtersContent" class="filters-content-mobile">
            <form method="GET" action="{{ route('home') }}" class="filters-form row g-3 mt-1">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <div class="col-md-6 col-xl-2">
                    <label for="name" class="form-label filter-label">Nombre</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control form-control-modern"
                        value="{{ $filters['name'] }}"
                        placeholder="Ej. Blue Toteta"
                        >
                </div>

                <div class="col-md-6 col-xl-2">
                    <label for="brand_id" class="form-label filter-label">Marca</label>
                    <select name="brand_id" id="brand_id" class="form-select form-control-modern" data-filter-dropdown>
                        <option value="">Todas las marcas</option>
                        @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ $filters['brand_id'] === (string) $brand->id ? 'selected' : '' }}>
                            {{ $brand->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label for="category_id" class="form-label filter-label">Categoría</label>
                    <select name="category_id" id="category_id" class="form-select form-control-modern" data-filter-dropdown>
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $filters['category_id'] === (string) $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label for="ingredient" class="form-label filter-label">Ingrediente</label>
                    <input
                        type="text"
                        name="ingredient"
                        id="ingredient"
                        class="form-control form-control-modern"
                        value="{{ $filters['ingredient'] }}"
                        placeholder="Arándano"
                        >
                </div>

                <div class="col-md-6 col-xl-2">
                    <label for="tobacco_type" class="form-label filter-label">Tipo</label>
                    <input
                        type="text"
                        name="tobacco_type"
                        id="tobacco_type"
                        class="form-control form-control-modern"
                        value="{{ $filters['tobacco_type'] }}"
                        placeholder="Ej. rubio"
                        >
                </div>

                {{-- Spec 011: media mínima, redondeada a un decimal --}}
                <div class="col-md-6 col-xl-2">
                    <label for="min_rating" class="form-label filter-label">Valoración</label>
                    <select name="min_rating" id="min_rating" class="form-select form-control-modern" data-filter-dropdown>
                        @foreach(['' => 'Cualquiera', '2' => '2+', '3' => '3+', '4' => '4+'] as $value => $label)
                        <option value="{{ $value }}"{{ $filters['min_rating'] === (string) $value ? ' selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                    <button type="submit" class="btn btn-primary btn-modern btn-gradient">
                        Aplicar filtros
                    </button>
                    {{-- Quita la búsqueda y los filtros, pero mantiene el orden elegido --}}
                    <a href="{{ route('home', $sort !== 'recent' ? ['sort' => $sort] : []) }}" class="btn btn-outline-light btn-modern">
                        Limpiar
                    </a>
                </div>
            </form>
            @include('partials._filter-dropdown')
        </div>
    </section>

    <section id="catalogo" class="catalog-heading mb-3 d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <h2 class="section-title mb-1">Sabores disponibles</h2>
            <p class="section-subtitle mb-0">
                {{ $flavors->total() }} resultados encontrados
            </p>
        </div>

        {{-- Al elegir un orden se envía al momento, con la búsqueda y los filtros activos y sin page (vuelve a la página 1) --}}
        <form method="GET" action="{{ route('home') }}#catalogo" class="sort-form">
            @foreach($filters as $filter => $value)
            @if($value !== '')
            <input type="hidden" name="{{ $filter }}" value="{{ $value }}">
            @endif
            @endforeach
            <label for="sort" class="form-label filter-label mb-1">Ordenar por:</label>
            <div class="d-flex gap-2">
                <div class="flex-grow-1">
                    <select name="sort" id="sort" class="form-select form-control-modern" onchange="this.form.submit()" data-filter-dropdown>
                        @foreach($sorts as $value => $label)
                        <option value="{{ $value }}"{{ $sort === $value ? ' selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <noscript>
                    <button type="submit" class="btn btn-primary btn-modern btn-gradient">Aplicar</button>
                </noscript>
            </div>
        </form>
    </section>

    @if($flavors->isEmpty())
    <div class="row g-4">
        <div class="col-12">
            <div class="empty-state text-center py-5 px-4">
                <div class="empty-icon mb-3">✦</div>
                <h3 class="empty-title">No hay sabores disponibles</h3>
                <p class="empty-subtitle mb-0">
                    Prueba a cambiar los filtros o añade nuevos sabores desde el panel de administración.
                </p>
            </div>
        </div>
    </div>
    @endif

    @foreach($flavorGroups as $groupLabel => $groupFlavors)
    @if((string) $groupLabel !== '')
    <h3 class="catalog-group-title">{{ $groupLabel }}</h3>
    @endif

    <div class="row g-4">
        @foreach($groupFlavors as $flavor)
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card flavor-card border-0 h-100 position-relative"
                 role="button"
                 tabindex="0"
                 aria-label="Ver detalles de {{ $flavor->name }}"
                 onclick="openFlavorModal({{ $flavor->id }})"
                 onkeydown="if (event.key === 'Enter' && event.target === this) openFlavorModal({{ $flavor->id }})">
                <div class="card-top-actions">
                    {{-- El corazón no abre la ventana --}}
                    @guest
                    {{-- Sin sesión, el corazón lleva a iniciar sesión (spec 006) --}}
                    <a href="{{ route('login') }}" class="favorite-btn" title="Inicia sesión para guardar favoritos" aria-label="Inicia sesión para añadir {{ $flavor->name }} a favoritos" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">♥</a>
                    @else
                    @php($isFavorite = in_array($flavor->id, $favoriteIds))
                    <form action="{{ route('favorites.store', $flavor) }}" method="POST" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();" data-favorite-toggle data-flavor-id="{{ $flavor->id }}" data-flavor-name="{{ $flavor->name }}">
                        @csrf
                        <button type="submit" class="favorite-btn {{ $isFavorite ? 'active' : '' }}" title="{{ $isFavorite ? 'Quitar de favoritos' : 'Añadir a favoritos' }}" aria-label="{{ $isFavorite ? "Quitar {$flavor->name} de favoritos" : "Añadir {$flavor->name} a favoritos" }}" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}">
                            ♥
                        </button>
                    </form>
                    @endguest
                </div>

                <div class="flavor-image-wrapper">
                    @if($flavor->image_url)
                    <img
                        src="{{ asset('storage/' . $flavor->image_url) }}"
                        class="card-img-top flavor-image"
                        alt="{{ $flavor->name }}"
                        >
                    @else
                    <div class="flavor-placeholder d-flex align-items-center justify-content-center">
                        <span class="placeholder-text">
                            {{ $flavor->initial }}
                        </span>
                    </div>
                    @endif
                </div>

                <div class="card-body d-flex flex-column p-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <h5 class="card-title fw-bold mb-0">{{ $flavor->name }}</h5>

                        @if($flavor->category)
                        <span class="badge badge-soft">
                            {{ $flavor->category->name }}
                        </span>
                        @endif
                    </div>

                    <div class="meta-list mb-3">
                        <div class="meta-item">
                            <span class="meta-label">Marca</span>
                            <span class="meta-value">{{ $flavor->brand->name ?? 'Sin marca' }}</span>
                        </div>

                        <div class="meta-item">
                            <span class="meta-label">Tipo</span>
                            <span class="meta-value">{{ $flavor->tobacco_type ?? 'No definido' }}</span>
                        </div>
                    </div>

                    <p class="card-text flex-grow-1 mb-3">
                        {{ \Illuminate\Support\Str::limit($flavor->description ?? 'Sin descripción disponible.', 110) }}
                    </p>

                    <div class="ingredients-chip mb-3">
                        {{ \Illuminate\Support\Str::limit($flavor->ingredients_text ?? 'Sin ingredientes definidos', 50) }}
                    </div>

                    {{-- Una fila: a la izquierda el precio más bajo (spec 010), a la derecha la media de valoraciones (spec 011) --}}
                    <div class="flavor-card-foot">
                        @if($flavor->prices->isNotEmpty())
                        <p class="flavor-price">desde {{ \App\Models\FlavorPrice::formatPrice($flavor->prices->min('price')) }}</p>
                        @endif
                        {{-- Solo la media; sin valoraciones, vacía y oculta (la ventana la rellena al puntuar) --}}
                        @if($flavor->ratings_count > 0)
                        <p class="flavor-rating" data-rating-card="{{ $flavor->id }}">★ {{ \App\Models\FlavorRating::formatAverage(\App\Models\FlavorRating::roundedAverage($flavor->ratings_avg_stars)) }}</p>
                        @else
                        <p class="flavor-rating" data-rating-card="{{ $flavor->id }}" hidden></p>
                        @endif
                    </div>


                </div>


            </div>
        </div>
        @endforeach
    </div>
    @endforeach

    {{-- Una ventana por sabor, fuera de las tarjetas para que sus clics no lleguen a ellas --}}
    @foreach($flavors as $flavor)
    @include('flavors._detail-modal', ['flavor' => $flavor])
    @endforeach
    {{-- Sin sesión el corazón es un enlace: no hace falta el script de favoritos --}}
    @auth
    @include('partials._favorite-toggle')
    @endauth

    @if($flavors->hasPages())
    <div class="d-flex justify-content-center mt-5">
        <div class="pagination-wrapper">
            {{ $flavors->links() }}
        </div>
    </div>
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

    .hero-section {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        padding: 2rem;
        background:
            linear-gradient(135deg, rgba(30, 41, 59, 0.88), rgba(17, 24, 39, 0.82)),
            linear-gradient(120deg, #8b5cf6, #ec4899);
        box-shadow: 0 18px 45px rgba(0, 0, 0, 0.22);
        border: 1px solid rgba(255,255,255,0.08);
    }

    .hero-overlay {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 80% 20%, rgba(255,255,255,0.14), transparent 28%),
            radial-gradient(circle at 10% 90%, rgba(255,255,255,0.08), transparent 25%);
        pointer-events: none;
    }

    .hero-content {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        gap: 1.5rem;
        align-items: end;
        flex-wrap: wrap;
    }

    .hero-badge {
        display: inline-block;
        padding: 0.45rem 0.9rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.14);
        color: #f9fafb;
        font-size: 0.85rem;
        font-weight: 600;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.12);
    }

    .hero-title {
        color: #ffffff;
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 800;
        line-height: 1.05;
    }

    .hero-subtitle {
        color: rgba(255,255,255,0.8);
        max-width: 680px;
        font-size: 1rem;
    }

    .btn-modern {
        border-radius: 999px;
        padding: 0.72rem 1.25rem;
        font-weight: 600;
        border-width: 1px;
    }

    .btn-gradient {
        border: none;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        box-shadow: 0 8px 20px rgba(139, 92, 246, 0.28);
    }

    .btn-gradient:hover {
        background: linear-gradient(135deg, #7c3aed, #db2777);
    }

    .filters-panel {
        /* backdrop-filter crea un contexto de apilamiento: así la lista abierta queda por encima de las tarjetas. */
        position: relative;
        z-index: 30;
        background: rgba(255,255,255,0.07);
        border: 1px solid rgba(255,255,255,0.09);
        border-radius: 24px;
        padding: 1.5rem;
        backdrop-filter: blur(14px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    }

    .filters-title {
        color: #fff;
        font-weight: 700;
        font-size: 1.2rem;
    }

    .filters-subtitle {
        color: rgba(255,255,255,0.62);
        font-size: 0.95rem;
    }

    .filter-label {
        color: rgba(255,255,255,0.9);
        font-weight: 600;
        font-size: 0.92rem;
    }

    .form-control-modern {
        min-height: 48px;
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.94);
        color: #111827;
        box-shadow: none;
    }

    .form-control-modern:focus {
        border-color: #8b5cf6;
        box-shadow: 0 0 0 0.2rem rgba(139, 92, 246, 0.18);
        background: #fff;
        color: #111827;
    }

    .section-title {
        color: #fff;
        font-size: 1.4rem;
        font-weight: 700;
    }

    .section-subtitle {
        color: rgba(255,255,255,0.62);
        font-size: 0.95rem;
    }

    /* La lista abierta del orden queda por encima de las tarjetas (que se elevan al pasar el ratón). */
    .catalog-heading {
        position: relative;
        z-index: 25;
        /* Al llegar por #catalogo (orden o paginación), la barra superior fija no tapa el título. */
        scroll-margin-top: 5.5rem;
    }

    /* Si la lista se abre hacia arriba, queda también por encima del panel de filtros (z-index 30). */
    .catalog-heading:has(.dropdown-menu.show) {
        z-index: 35;
    }

    .sort-form {
        width: 15rem;
        max-width: 100%;
    }

    /* Con JS (ya existe el dropdown): "Ordenar por: <opción> ▾" en línea y sin caja. Sin JS se queda el <select> nativo. */
    .sort-form:has(.filter-dropdown) {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        column-gap: 0.4rem;
        width: auto;
    }

    .sort-form:has(.filter-dropdown) .form-label {
        margin-bottom: 0 !important;
        white-space: nowrap;
    }

    .sort-form .filter-dropdown-toggle.form-select,
    .sort-form .filter-dropdown-toggle.form-select:hover,
    .sort-form .filter-dropdown-toggle.form-select:focus,
    .sort-form .filter-dropdown-toggle.form-select.show {
        display: inline-flex;
        align-items: center;
        width: auto;
        max-width: 100%;
        min-height: 0;
        padding: 0 1.15rem 0 0;
        border: 0;
        border-radius: 4px;
        background-color: transparent;
        background-image: var(--bs-form-select-bg-img);
        background-repeat: no-repeat;
        background-position: right center;
        background-size: 14px 10px;
        box-shadow: none;
        color: #f472b6;
        font-size: 0.92rem;
        font-weight: 700;
        line-height: 1.5;
        cursor: pointer;
    }

    .sort-form .filter-dropdown-toggle.form-select:focus-visible {
        outline: 2px solid #f472b6;
        outline-offset: 2px;
    }

    /* El botón ya no ocupa 15rem: la lista mantiene un ancho para que las opciones no se partan. */
    .sort-form .filter-dropdown-menu {
        min-width: 14rem;
    }

    /* Mismo estilo que los títulos de grupo de Favoritos. */
    .catalog-group-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 1.75rem 0 0.9rem;
        color: #ffffff;
        font-size: 1.1rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .catalog-group-title:first-of-type {
        margin-top: 0.5rem;
    }

    .catalog-group-title::after {
        content: "";
        flex: 1;
        min-width: 1.5rem;
        height: 2px;
        border-radius: 2px;
        background: linear-gradient(90deg, rgba(139, 92, 246, 0.8), rgba(236, 72, 153, 0));
    }

    .flavor-card {
        border-radius: 26px;
        overflow: visible;
        background: rgba(255,255,255,0.98);
        box-shadow: 0 14px 34px rgba(0, 0, 0, 0.16);
        transition: transform 0.28s ease, box-shadow 0.28s ease;
        cursor: pointer;
    }

    .flavor-card:hover {
        transform: translateY(-6px) scale(1.02);
        box-shadow: 0 24px 44px rgba(0, 0, 0, 0.24);
    }

    .card-top-actions {
        position: absolute;
        top: 14px;
        right: 14px;
        z-index: 20;
    }

    .favorite-btn {
        /* También es un enlace para los visitantes: centrado y sin subrayado */
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        width: 44px;
        height: 44px;
        border: none;
        border-radius: 50%;
        background: rgba(255,255,255,0.96);
        box-shadow: 0 8px 18px rgba(0,0,0,0.16);
        color: #9ca3af;
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1;
        transition: transform 0.2s ease, color 0.2s ease, background 0.2s ease;
    }

    .favorite-btn:hover {
        transform: scale(1.08);
        background: #fff;
        color: #6b7280;
    }

    .favorite-btn.active {
        color: #e11d48;
    }

    /* Precio a la izquierda y media de valoraciones a la derecha, en la misma fila */
    .flavor-card-foot {
        display: flex;
        flex-wrap: nowrap;
        align-items: baseline;
        gap: 0.5rem;
    }

    .flavor-card-foot .flavor-price {
        min-width: 0;
        white-space: nowrap;
    }

    /* Móvil: dos tarjetas por fila; texto algo menor para que precio y media quepan en la misma fila */
    @media (max-width: 575.98px) {
        .flavor-card-foot .flavor-price,
        .flavor-card-foot .flavor-rating {
            font-size: 0.78rem;
        }
    }

    /* Spec 011: media de valoraciones en la tarjeta */
    .flavor-rating {
        margin: 0 0 0 auto;
        color: #b45309;
        font-size: 0.92rem;
        font-weight: 700;
        white-space: nowrap;
    }

    /* Spec 010: precio más bajo en la tarjeta */
    .flavor-price {
        margin: 0;
        /* La tarjeta es clara: rosa oscuro para que se lea bien */
        color: #be185d;
        font-size: 0.92rem;
        font-weight: 700;
    }

    .flavor-image-wrapper {
        height: 230px;
        overflow: hidden;
        border-top-left-radius: 26px;
        border-top-right-radius: 26px;
        background: linear-gradient(135deg, #eef2ff, #f5f3ff);
    }

    .flavor-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .flavor-card:focus-visible {
        outline: 3px solid #f472b6;
        outline-offset: 2px;
    }

    .flavor-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
    }

    .placeholder-text {
        font-size: 4rem;
        font-weight: 800;
        color: rgba(255,255,255,0.95);
    }

    .flavor-card .card-title {
        color: #111827;
        font-size: 1.05rem;
    }

    .flavor-card .card-text {
        color: #1f2937;
        line-height: 1.6;
        font-size: 0.96rem;
    }

    .badge-soft {
        background: #f3f4f6;
        color: #374151;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        padding: 0.48rem 0.75rem;
        white-space: normal;
        overflow-wrap: normal;
        text-align: left;
        min-width: 0;
    }

    /* Si la categoría no cabe junto al nombre, pasa entera a la línea siguiente. */
    .flavor-card .badge-soft {
        flex-shrink: 0;
        max-width: 100%;
    }

    .meta-list {
        display: grid;
        gap: 0.8rem;
    }

    .meta-item {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        padding-bottom: 0.25rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .meta-label {
        color: #6b7280;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .meta-value {
        color: #374151;
        font-size: 0.9rem;
        font-weight: 700;
        text-align: right;
    }

    .ingredients-chip {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        max-width: 100%;
        padding: 0.55rem 0.85rem;
        border-radius: 999px;
        background: #f8fafc;
        color: #475569;
        font-size: 0.82rem;
        font-weight: 600;
        border: 1px solid #e5e7eb;
    }

    .empty-state {
        border-radius: 28px;
        background: rgba(255,255,255,0.07);
        border: 1px solid rgba(255,255,255,0.08);
    }

    .empty-icon {
        font-size: 2.2rem;
        color: rgba(255,255,255,0.72);
    }

    .empty-title {
        color: #fff;
        font-weight: 800;
    }

    .empty-subtitle {
        color: rgba(255,255,255,0.68);
        max-width: 620px;
        margin: 0 auto;
    }

    .pagination-wrapper {
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .us-pagination {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
    }

    .us-pagination-summary {
        margin: 0;
        color: var(--us-text);
        font-size: 0.88rem;
    }

    .us-pagination-list {
        display: flex;
        align-items: center;
        gap: 0.2rem;
        max-width: 100%;
        margin: 0;
        padding: 0.3rem;
        list-style: none;
        background: var(--us-bar);
        border: 1px solid var(--us-line);
        border-radius: 999px;
    }

    .us-page {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.2rem;
        min-width: 2.6rem;
        height: 2.6rem;
        padding: 0 0.5rem;
        border-radius: 999px;
        color: var(--us-text);
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
        transition: color 0.15s ease, background-color 0.15s ease;
    }

    a.us-page:hover {
        background: var(--us-hover);
        color: var(--us-text-strong);
    }

    a.us-page:focus-visible {
        outline: 2px solid #f472b6;
        outline-offset: 2px;
    }

    .us-page.is-current {
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        box-shadow: 0 0 14px rgba(236, 72, 153, 0.55);
        color: var(--us-text-strong);
    }

    .us-page.is-disabled {
        opacity: 0.4;
    }

    .us-page-step {
        padding: 0 0.8rem;
    }

    .us-page-step svg {
        width: 1rem;
        height: 1rem;
        fill: none;
        stroke: currentColor;
        stroke-width: 2.2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .us-page-position {
        padding: 0 0.35rem;
        color: var(--us-text-strong);
        font-size: 0.88rem;
        font-weight: 600;
        white-space: nowrap;
    }

    @media (prefers-reduced-motion: reduce) {
        .us-page {
            transition: none;
        }
    }

    @media (max-width: 767.98px) {
        .container.py-4 {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .hero-section {
            padding: 1.5rem;
        }

        .hero-content {
            align-items: start;
        }

        .sort-form,
        .sort-form:has(.filter-dropdown) {
            width: 100%;
        }

        .filters-content-mobile {
            display: none;
            margin-top: 1rem;
        }

        .filters-content-mobile.show {
            display: block;
        }

        .row.g-4 {
            --bs-gutter-x: 0.75rem;
            --bs-gutter-y: 0.75rem;
        }

        .flavor-card {
            border-radius: 18px;
        }

        .flavor-card:hover {
            transform: translateY(-4px) scale(1.01);
        }

        .flavor-image-wrapper {
            height: 130px;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
        }

        .card-top-actions {
            top: 8px;
            right: 8px;
        }

        .favorite-btn {
            width: 34px;
            height: 34px;
            font-size: 1rem;
        }

        .flavor-card .card-body {
            padding: 0.8rem !important;
        }

        .flavor-card .card-title {
            font-size: 0.9rem;
            line-height: 1.2;
        }

        .flavor-card .card-text {
            font-size: 0.8rem;
            line-height: 1.35;
            margin-bottom: 0.65rem !important;
        }

        .badge-soft {
            font-size: 0.68rem;
            padding: 0.35rem 0.55rem;
        }

        .meta-list {
            gap: 0.45rem;
            margin-bottom: 0.75rem !important;
        }

        .meta-item {
            gap: 0.5rem;
        }

        .meta-label,
        .meta-value {
            font-size: 0.72rem;
        }

        .ingredients-chip {
            font-size: 0.7rem;
            padding: 0.38rem 0.6rem;
            border-radius: 999px;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .placeholder-text {
            font-size: 2.4rem;
        }
    }

    @media (min-width: 768px) {
        .filters-content-mobile {
            display: block !important;
        }
    }
</style>
@endpush



@push('scripts')
<script>
    function toggleFiltersMobile() {
    const filters = document.getElementById('filtersContent');
    filters.classList.toggle('show');
    }
</script>
@endpush