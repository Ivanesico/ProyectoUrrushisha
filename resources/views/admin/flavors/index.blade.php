@extends('layouts.app')

@section('title', 'Administrar sabores')

@php
    // Hay algún filtro activo (aunque no coincida nada): decide qué estado vacío se muestra.
    $hasFilters = collect($filters)->contains(fn ($value) => $value !== '');
    // "Limpiar" quita los filtros pero mantiene el orden elegido.
    $clearUrl = route('admin.flavors.index', $sort !== 'recent' ? ['sort' => $sort] : []);
@endphp

@section('content')
<div class="container py-4 admin-flavors">
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 fw-bold mb-0">Sabores</h1>
        <a href="{{ route('admin.flavors.create') }}" class="btn btn-primary btn-modern btn-gradient">Añadir sabor</a>
    </div>

    <section class="filters-panel mb-4">
        <div class="d-flex justify-content-between align-items-center gap-3">
            <h2 class="filters-title mb-0">Filtrar sabores</h2>

            <button type="button" class="btn btn-outline-light btn-modern d-md-none" aria-controls="filtersContent" onclick="toggleFiltersMobile(this)" aria-expanded="false">
                Filtros
            </button>
        </div>

        <div id="filtersContent" class="filters-content-mobile">
            <form method="GET" action="{{ route('admin.flavors.index') }}" class="filters-form row g-3 mt-1">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <div class="col-md-6 col-xl-4">
                    <label for="name" class="form-label filter-label">Nombre</label>
                    <input type="text" name="name" id="name" class="form-control form-control-modern" value="{{ $filters['name'] }}" placeholder="Ej. Blue Toteta">
                </div>

                <div class="col-md-6 col-xl-4">
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

                <div class="col-md-6 col-xl-4">
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

                <div class="col-md-6 col-xl-4">
                    <label for="tobacco_type" class="form-label filter-label">Tipo</label>
                    <input type="text" name="tobacco_type" id="tobacco_type" class="form-control form-control-modern" value="{{ $filters['tobacco_type'] }}" placeholder="Ej. rubio">
                </div>

                <div class="col-md-6 col-xl-4">
                    <label for="visibility" class="form-label filter-label">Visibilidad</label>
                    <select name="visibility" id="visibility" class="form-select form-control-modern" data-filter-dropdown>
                        @foreach(['' => 'Todos', 'public' => 'Públicos', 'hidden' => 'No públicos'] as $value => $label)
                        <option value="{{ $value }}"{{ $filters['visibility'] === $value ? ' selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label for="missing" class="form-label filter-label">Datos incompletos</label>
                    <select name="missing" id="missing" class="form-select form-control-modern" data-filter-dropdown>
                        @foreach(['' => 'Todos', 'image' => 'Sin imagen', 'description' => 'Sin descripción'] as $value => $label)
                        <option value="{{ $value }}"{{ $filters['missing'] === $value ? ' selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                    <button type="submit" class="btn btn-primary btn-modern btn-gradient">Aplicar filtros</button>
                    <a href="{{ $clearUrl }}" class="btn btn-outline-light btn-modern">Limpiar</a>
                </div>
            </form>
        </div>
    </section>

    <section class="list-heading mb-3 d-flex flex-wrap justify-content-between align-items-end gap-2">
        <p class="list-count mb-0">{{ $flavors->total() }} {{ $flavors->total() === 1 ? 'sabor' : 'sabores' }}</p>

        {{-- Al elegir un orden se envía al momento, con los filtros activos y sin page (vuelve a la página 1) --}}
        <form method="GET" action="{{ route('admin.flavors.index') }}" class="sort-form">
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

    @if($flavors->isEmpty() && $hasFilters)
    <div class="empty-state text-center py-5 px-4">
        <p class="empty-title mb-3">No hay sabores que coincidan</p>
        <a href="{{ $clearUrl }}" class="btn btn-outline-light btn-modern">Limpiar</a>
    </div>
    @else
    <div class="admin-table-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th>Público</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($flavors as $flavor)
                    <tr>
                        <td>{{ $flavor->id }}</td>
                        <td>{{ $flavor->name }}</td>
                        <td>{{ $flavor->brand->name ?? '-' }}</td>
                        <td>{{ $flavor->category->name ?? '-' }}</td>
                        <td>{{ $flavor->tobacco_type ?? '-' }}</td>
                        <td>
                            @if($flavor->is_public)
                            <span class="badge text-bg-success">Sí</span>
                            @else
                            <span class="badge text-bg-secondary">No</span>
                            @endif
                        </td>
                        <td>
                            {{-- Editar y Eliminar llevan los filtros, el orden y la página para volver a la misma lista --}}
                            <a href="{{ route('admin.flavors.edit', ['flavor' => $flavor] + $listParams) }}" class="btn btn-sm btn-outline-light rounded-pill px-3">Editar</a>
                        </td>
                        <td>
                            <form action="{{ route('admin.flavors.destroy', $flavor) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar este sabor?')">
                                @csrf
                                @method('DELETE')
                                @foreach($listParams as $key => $value)
                                <input type="hidden" name="list[{{ $key }}]" value="{{ $value }}">
                                @endforeach
                                <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">No hay sabores registrados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($flavors->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $flavors->links() }}
    </div>
    @endif
</div>

@include('partials._filter-dropdown')
@endsection

@push('head')
<style>
    /* Mismo aspecto que el panel de filtros, el selector de orden y la paginación de la portada (index.blade.php). */
    .admin-flavors .btn-modern {
        border-radius: 999px;
        padding: 0.6rem 1.15rem;
        font-weight: 600;
    }

    .admin-flavors .btn-gradient {
        border: none;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        box-shadow: 0 8px 20px rgba(139, 92, 246, 0.28);
    }

    .admin-flavors .btn-gradient:hover {
        background: linear-gradient(135deg, #7c3aed, #db2777);
    }

    .filters-panel {
        /* backdrop-filter crea un contexto de apilamiento: así la lista abierta queda por encima de la tabla. */
        position: relative;
        z-index: 30;
        background: rgba(255, 255, 255, 0.07);
        border: 1px solid rgba(255, 255, 255, 0.09);
        border-radius: 24px;
        padding: 1.25rem 1.5rem;
        backdrop-filter: blur(14px);
    }

    .filters-title {
        color: #fff;
        font-weight: 700;
        font-size: 1.2rem;
    }

    .filter-label {
        color: rgba(255, 255, 255, 0.9);
        font-weight: 600;
        font-size: 0.92rem;
    }

    .form-control-modern {
        min-height: 48px;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background-color: rgba(255, 255, 255, 0.94);
        color: #111827;
    }

    .form-control-modern:focus {
        border-color: #8b5cf6;
        box-shadow: 0 0 0 0.2rem rgba(139, 92, 246, 0.18);
        background-color: #fff;
        color: #111827;
    }

    .list-heading {
        position: relative;
        z-index: 25;
    }

    .list-heading:has(.dropdown-menu.show) {
        z-index: 35;
    }

    .list-count {
        color: rgba(255, 255, 255, 0.62);
        font-size: 0.95rem;
    }

    .sort-form {
        width: 15rem;
        max-width: 100%;
    }

    /* Con JS: "Ordenar por: <opción> ▾" en línea y sin caja. Sin JS se queda el <select> nativo. */
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

    .sort-form .filter-dropdown-menu {
        min-width: 14rem;
    }

    .admin-table-card,
    .empty-state {
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.09);
        overflow: hidden;
    }

    .admin-table {
        --bs-table-bg: transparent;
        --bs-table-hover-bg: rgba(255, 255, 255, 0.05);
        white-space: nowrap;
    }

    .admin-table thead th {
        color: rgba(255, 255, 255, 0.62);
        font-size: 0.85rem;
        font-weight: 600;
    }

    .empty-title {
        color: #fff;
        font-weight: 700;
        font-size: 1.1rem;
    }

    .us-pagination {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
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
        min-width: 2.6rem;
        height: 2.6rem;
        padding: 0 0.5rem;
        border-radius: 999px;
        color: var(--us-text);
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
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

    @media (max-width: 767.98px) {
        .filters-panel {
            padding: 1rem;
        }

        .filters-content-mobile {
            display: none;
            margin-top: 0.5rem;
        }

        .filters-content-mobile.show {
            display: block;
        }

        .sort-form,
        .sort-form:has(.filter-dropdown) {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function toggleFiltersMobile(button) {
        const open = document.getElementById('filtersContent').classList.toggle('show');
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
</script>
@endpush
