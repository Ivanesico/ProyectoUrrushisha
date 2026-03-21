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

            <div class="hero-actions">
                <a href="{{ route('favorites.index') }}" class="btn btn-light btn-modern">
                    ♥ Ver favoritos
                </a>
                <a href="{{ route('mixes.index') }}" class="btn btn-outline-light btn-modern">
                    Mezclas
                </a>
            </div>
        </div>
    </section>

    <section class="filters-panel mb-5">
        <div class="filters-header">
            <div>
                <h2 class="filters-title mb-1">Filtrar sabores</h2>
                <p class="filters-subtitle mb-0">Encuentra exactamente el perfil que buscas.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('home') }}" class="row g-3 mt-1">
            <div class="col-md-6 col-xl-3">
                <label for="name" class="form-label filter-label">Nombre</label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    class="form-control form-control-modern"
                    value="{{ request('name') }}"
                    placeholder="Ej. Blue Toteta"
                    >
            </div>

            <div class="col-md-6 col-xl-3">
                <label for="brand_id" class="form-label filter-label">Marca</label>
                <select name="brand_id" id="brand_id" class="form-select form-control-modern">
                    <option value="">Todas las marcas</option>
                    @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                        {{ $brand->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 col-xl-2">
                <label for="category_id" class="form-label filter-label">Categoría</label>
                <select name="category_id" id="category_id" class="form-select form-control-modern">
                    <option value="">Todas</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
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
                    value="{{ request('ingredient') }}"
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
                    value="{{ request('tobacco_type') }}"
                    placeholder="Virginia"
                    >
            </div>

            <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                <button type="submit" class="btn btn-primary btn-modern btn-gradient">
                    Aplicar filtros
                </button>
                <a href="{{ route('home') }}" class="btn btn-outline-light btn-modern">
                    Limpiar
                </a>
            </div>
        </form>
    </section>

    <section class="mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="section-title mb-1">Sabores disponibles</h2>
            <p class="section-subtitle mb-0">
                {{ $flavors->total() }} resultados encontrados
            </p>
        </div>
    </section>

    <div class="row g-4">
        @forelse($flavors as $flavor)
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card flavor-card border-0 h-100 position-relative"
                 onclick="openFlavorModal({{ $flavor->id }})"
                 data-id="{{ $flavor->id }}"
                 data-name="{{ $flavor->name }}"
                 data-brand="{{ $flavor->brand->name ?? 'Sin marca' }}"
                 data-category="{{ $flavor->category->name ?? 'Sin categoría' }}"
                 data-type="{{ $flavor->tobacco_type ?? 'No definido' }}"
                 data-description="{{ $flavor->description ?? 'Sin descripción disponible.' }}"
                 data-ingredients="{{ $flavor->ingredients_text ?? 'Sin ingredientes definidos' }}"
                 data-image="{{ $flavor->image_url ? asset('storage/' . $flavor->image_url) : '' }}"
                 onclick="openFlavorModal({{ $flavor->id }})">
                <div class="card-top-actions">
                    <form action="{{ route('favorites.store', $flavor) }}" method="POST" onclick="event.stopPropagation();">
                        @csrf
                        <button type="submit" class="favorite-btn {{ in_array($flavor->id, $favoriteIds) ? 'active' : '' }}" title="Favorito">
                            ♥
                        </button>
                    </form>
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
                            {{ strtoupper(substr($flavor->name, 0, 1)) }}
                        </span>
                    </div>
                    @endif
                </div>

                <div class="card-body d-flex flex-column p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
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


                </div>

                <div class="flavor-dropdown" id="menu-{{ $flavor->id }}">
                    <a href="{{ route('mixes.index') }}" class="dropdown-action" onclick="event.stopPropagation();">
                        Crear mezcla
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="empty-state text-center py-5 px-4">
                <div class="empty-icon mb-3">✦</div>
                <h3 class="empty-title">No hay sabores disponibles</h3>
                <p class="empty-subtitle mb-0">
                    Prueba a cambiar los filtros o añade nuevos sabores desde el panel de administración.
                </p>
            </div>
        </div>
        @endforelse
    </div>
    <div id="flavorModalOverlay" class="flavor-modal-overlay" onclick="closeFlavorModal()">
        <div class="flavor-modal-card" onclick="event.stopPropagation()">
            <button class="flavor-modal-close" type="button" onclick="closeFlavorModal()">×</button>

            <div class="row g-0">
                <div class="col-lg-5">
                    <div class="flavor-modal-image-wrapper">
                        <img id="modalFlavorImage" src="" alt="" class="flavor-modal-image d-none">
                        <div id="modalFlavorPlaceholder" class="flavor-modal-placeholder d-flex align-items-center justify-content-center">
                            <span id="modalFlavorPlaceholderText" class="placeholder-text">?</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="p-4 p-lg-5">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
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
                            <h6 class="modal-section-title">Descripción</h6>
                            <p id="modalFlavorDescription" class="modal-description mb-0"></p>
                        </div>

                        <div class="mb-4">
                            <h6 class="modal-section-title">Ingredientes</h6>
                            <div id="modalFlavorIngredients" class="ingredients-chip"></div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('mixes.index') }}" class="btn btn-dark rounded-pill px-4">
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
        width: min(100%, 1100px);
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 28px;
        background: rgba(255,255,255,0.99);
        box-shadow: 0 30px 80px rgba(0,0,0,0.35);
    }

    .flavor-modal-close {
        position: absolute;
        top: 18px;
        right: 18px;
        z-index: 10;
        width: 46px;
        height: 46px;
        border: none;
        border-radius: 50%;
        background: rgba(255,255,255,0.96);
        color: #111827;
        font-size: 1.8rem;
        line-height: 1;
        box-shadow: 0 8px 18px rgba(0,0,0,0.16);
    }

    .flavor-modal-image-wrapper {
        height: 100%;
        min-height: 320px;
        background: linear-gradient(135deg, #eef2ff, #f5f3ff);
        border-top-left-radius: 28px;
        border-bottom-left-radius: 28px;
        overflow: hidden;
    }

    .flavor-modal-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .flavor-modal-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
    }

    .modal-flavor-title {
        color: #111827;
        font-weight: 800;
        font-size: clamp(1.6rem, 3vw, 2.4rem);
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
        color: #374151;
        font-weight: 700;
        margin-bottom: 0.6rem;
    }

    .modal-description {
        color: #1f2937;
        line-height: 1.7;
    }

    @media (max-width: 991px) {
        .flavor-modal-image-wrapper {
            min-height: 240px;
            border-bottom-left-radius: 0;
            border-top-right-radius: 28px;
        }
    }
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

    .hero-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
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
        white-space: nowrap;
    }

    .meta-list {
        display: grid;
        gap: 0.7rem;
    }

    .meta-item {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        padding-bottom: 0.2rem;
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

    .card-footer-modern {
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #6b7280;
        font-size: 0.85rem;
        font-weight: 600;
        padding-top: 0.4rem;
        border-top: 1px solid #f3f4f6;
    }

    .card-arrow {
        font-size: 1rem;
        color: #9ca3af;
    }

    .flavor-dropdown {
        display: none;
        position: absolute;
        bottom: 18px;
        right: 18px;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 16px 34px rgba(0,0,0,0.18);
        padding: 0.55rem;
        z-index: 30;
        min-width: 170px;
        border: 1px solid #f1f5f9;
    }

    .flavor-dropdown.show {
        display: block;
    }

    .dropdown-action {
        display: block;
        text-decoration: none;
        color: #111827;
        font-weight: 600;
        padding: 0.8rem 0.95rem;
        border-radius: 12px;
        transition: background 0.2s ease, color 0.2s ease;
    }

    .dropdown-action:hover {
        background: #f8fafc;
        color: #111827;
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

    .pagination-wrapper nav {
        background: rgba(255,255,255,0.06);
        padding: 0.75rem 1rem;
        border-radius: 18px;
        border: 1px solid rgba(255,255,255,0.08);
    }

    @media (max-width: 768px) {
        .hero-section {
            padding: 1.5rem;
        }

        .hero-content {
            align-items: start;
        }

        .flavor-card:hover {
            transform: translateY(-4px) scale(1.01);
        }
    }

    @media (max-width: 768px) {
        .container.py-4 {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .row.g-4 {
            --bs-gutter-x: 0.75rem;
            --bs-gutter-y: 0.75rem;
        }

        .flavor-card {
            border-radius: 18px;
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
    .pagination-wrapper {
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .pagination-wrapper nav {
        background: rgba(255,255,255,0.08);
        padding: 0.9rem 1rem;
        border-radius: 20px;
        border: 1px solid rgba(255,255,255,0.12);
        box-shadow: 0 10px 30px rgba(0,0,0,0.18);
    }

    .pagination {
        margin-bottom: 0;
        gap: 0.35rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .page-item .page-link {
        border: none;
        border-radius: 12px !important;
        background: rgba(255,255,255,0.95);
        color: #111827;
        font-weight: 700;
        padding: 0.65rem 0.9rem;
        min-width: 42px;
        text-align: center;
        box-shadow: none;
        transition: all 0.2s ease;
    }

    .page-item .page-link:hover {
        background: #ffffff;
        transform: translateY(-1px);
    }

    .page-item.active .page-link {
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: #fff;
    }

    .page-item.disabled .page-link {
        background: rgba(255,255,255,0.45);
        color: #6b7280;
    }

    @media (max-width: 768px) {
        .pagination-wrapper nav {
            width: 100%;
            padding: 0.75rem;
        }

        .pagination {
            gap: 0.25rem;
        }

        .page-item .page-link {
            padding: 0.55rem 0.75rem;
            min-width: 38px;
            font-size: 0.88rem;
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
    } else {
    imageEl.classList.add('d-none');
    placeholderEl.classList.remove('d-none');
    placeholderTextEl.textContent = (card.dataset.name || '?').charAt(0).toUpperCase();
    }

    document.getElementById('flavorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
    }

    function closeFlavorModal() {
    document.getElementById('flavorModalOverlay').classList.remove('show');
    document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
    closeFlavorModal();
    }
    });
</script>
@endpush