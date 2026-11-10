@extends('layouts.app')

@section('title', 'Mezclas')

@php
    // Spec 007: la misma vista sirve para "Mis mezclas" ($tab = 'mine') y "Públicas" ($tab = 'public').
    $isMine = $tab === 'mine';

    if (!$isMine) {
        // Hay algún filtro activo (aunque no coincida nada): decide qué estado vacío se muestra.
        $hasFilters = collect($filters)->contains(fn ($value) => trim($value) !== '');
        // "Limpiar" quita los filtros pero mantiene el orden elegido.
        $clearUrl = route('mixes.public', $sort !== 'recent' ? ['sort' => $sort] : []);
    }
@endphp

@section('content')
<div class="container py-4 mixes-page">
    @if(session('success'))
    <div class="alert alert-success rounded-4">
        {{ session('success') }}
    </div>
    @endif

    <nav class="mixes-tabs mb-4" aria-label="Tipo de mezclas">
        @auth
        <a href="{{ route('mixes.index') }}" class="mixes-tab{{ $isMine ? ' is-active' : '' }}"{!! $isMine ? ' aria-current="page"' : '' !!}>Mis mezclas</a>
        @endauth
        <a href="{{ route('mixes.public') }}" class="mixes-tab{{ $isMine ? '' : ' is-active' }}"{!! $isMine ? '' : ' aria-current="page"' !!}>Públicas</a>
    </nav>

    @if($isMine)
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
        <div>
            <h1 class="mixes-title">Mis mezclas</h1>
            <p class="mixes-subtitle">Tus combinaciones de sabores, con la proporción de cada uno.</p>
        </div>

        @if($mixes->isNotEmpty())
        <a href="{{ route('mixes.create') }}" class="btn mixes-create">Crear mezcla</a>
        @endif
    </div>
    @else
    <div class="mb-4">
        <h1 class="mixes-title">Mezclas públicas</h1>
        <p class="mixes-subtitle">Combinaciones que otros usuarios han compartido.</p>
    </div>

    <section class="filters-panel mb-4">
        <div class="d-flex justify-content-between align-items-center gap-3">
            <h2 class="filters-title mb-0">Buscar mezclas</h2>

            <button type="button" class="btn btn-outline-light btn-modern d-md-none" aria-controls="filtersContent" onclick="toggleFiltersMobile(this)" aria-expanded="false">
                Filtros
            </button>
        </div>

        <div id="filtersContent" class="filters-content-mobile">
            <form method="GET" action="{{ route('mixes.public') }}" class="row g-3 mt-1">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <div class="col-md-4">
                    <label for="name" class="form-label filter-label">Nombre de la mezcla</label>
                    <input type="text" name="name" id="name" class="form-control form-control-modern" value="{{ $filters['name'] }}" placeholder="Ej. Noche de verano">
                </div>

                <div class="col-md-4">
                    <label for="flavor" class="form-label filter-label">Lleva el sabor</label>
                    <input type="text" name="flavor" id="flavor" class="form-control form-control-modern" value="{{ $filters['flavor'] }}" placeholder="Ej. menta">
                </div>

                <div class="col-md-4">
                    <label for="category_id" class="form-label filter-label">Lleva la categoría</label>
                    <select name="category_id" id="category_id" class="form-select form-control-modern" data-filter-dropdown>
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}"{{ $filters['category_id'] === (string) $category->id ? ' selected' : '' }}>{{ $category->name }}</option>
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
        <p class="list-count mb-0">{{ $mixes->total() }} {{ $mixes->total() === 1 ? 'mezcla' : 'mezclas' }}</p>

        {{-- Al elegir un orden se envía al momento, con los filtros activos y sin page (vuelve a la página 1) --}}
        <form method="GET" action="{{ route('mixes.public') }}" class="sort-form">
            @foreach($filters as $filter => $value)
            @if(trim($value) !== '')
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
    @endif

    @if($mixes->isEmpty() && $isMine)
    <div class="mixes-empty">
        <h2 class="mixes-empty-title">Todavía no tienes mezclas</h2>
        <p class="mixes-subtitle mb-3">Combina varios de tus favoritos y guarda la proporción de cada uno.</p>
        <a href="{{ route('mixes.create') }}" class="btn mixes-create">Crear mezcla</a>
    </div>
    @elseif($mixes->isEmpty() && $hasFilters)
    <div class="mixes-empty">
        <h2 class="mixes-empty-title">No hay mezclas que coincidan</h2>
        <a href="{{ $clearUrl }}" class="btn btn-outline-light btn-modern mt-2">Limpiar</a>
    </div>
    @elseif($mixes->isEmpty())
    <div class="mixes-empty">
        <h2 class="mixes-empty-title">Todavía no hay mezclas públicas</h2>
        <p class="mixes-subtitle mb-0">Cuando alguien comparta una mezcla, aparecerá aquí.</p>
    </div>
    @else
    <div class="row g-3 g-lg-4">
        @foreach($mixes as $mix)
        <div class="col-md-6 col-xl-4">
            <article class="mix-card"
                     role="button"
                     tabindex="0"
                     aria-label="Ver la mezcla {{ $mix->name }}"
                     onclick="openMixModal({{ $mix->id }})"
                     onkeydown="if (event.key === 'Enter' && event.target === this) openMixModal({{ $mix->id }})">
                <header class="mix-card-header">
                    @if($isMine)
                    <h2 class="mix-card-name">{{ $mix->name }}</h2>
                    <span class="mix-visibility {{ $mix->is_public ? 'is-public' : '' }}">{{ $mix->is_public ? 'Pública' : 'Privada' }}</span>
                    @else
                    <div class="min-w-0">
                        <h2 class="mix-card-name">{{ $mix->name }}</h2>
                        <p class="mix-author">por {{ $mix->user->name ?? 'un usuario' }}</p>
                    </div>
                    @endif
                </header>

                @include('mixes._mix-items', ['mix' => $mix, 'size' => 'card'])

                @if($mix->notes)
                <p class="mix-notes">{{ $mix->notes }}</p>
                @endif

                <footer class="mix-card-footer">
                    <span class="mix-card-muted">{{ $mix->created_at ? 'Creada el ' . $mix->created_at->format('d/m/Y') : '' }}</span>

                    @if($isMine)
                    {{-- Las acciones no abren la ventana --}}
                    <div class="d-flex gap-2" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
                        <a href="{{ route('mixes.edit', $mix) }}" class="mix-action">Editar</a>

                        <form action="{{ route('mixes.destroy', $mix) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta mezcla?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="mix-action is-danger">Eliminar</button>
                        </form>
                    </div>
                    @endif
                </footer>
            </article>
        </div>
        @endforeach
    </div>

    @if(!$isMine)
    <div class="mt-4">
        {{ $mixes->links() }}
    </div>
    @endif

    {{-- Una ventana por mezcla, fuera de las tarjetas para que sus clics no lleguen a ellas --}}
    @foreach($mixes as $mix)
    <div class="modal fade mix-modal" id="mixModal-{{ $mix->id }}" tabindex="-1" aria-labelledby="mixModalTitle-{{ $mix->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="mix-modal-heading">
                        <h2 class="mix-card-name mix-modal-name" id="mixModalTitle-{{ $mix->id }}">{{ $mix->name }}</h2>
                        @if($isMine)
                        <span class="mix-visibility {{ $mix->is_public ? 'is-public' : '' }}">{{ $mix->is_public ? 'Pública' : 'Privada' }}</span>
                        @else
                        <p class="mix-author w-100">por {{ $mix->user->name ?? 'un usuario' }}</p>
                        @endif
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    @include('mixes._mix-items', ['mix' => $mix, 'size' => 'modal'])

                    @if($mix->notes)
                    <p class="mix-notes mt-3">{{ $mix->notes }}</p>
                    @endif
                </div>

                <div class="modal-footer mix-card-footer">
                    <span class="mix-card-muted">{{ $mix->created_at ? 'Creada el ' . $mix->created_at->format('d/m/Y') : '' }}</span>

                    @if($isMine)
                    <div class="d-flex gap-2">
                        <a href="{{ route('mixes.edit', $mix) }}" class="mix-action">Editar</a>

                        <form action="{{ route('mixes.destroy', $mix) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta mezcla?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="mix-action is-danger">Eliminar</button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
    @endif
</div>

@if(!$isMine)
@include('partials._filter-dropdown')
@endif
@endsection

@push('head')
<style>
    body {
        background:
            radial-gradient(circle at top left, rgba(139, 92, 246, 0.18) 0%, transparent 28%),
            radial-gradient(circle at bottom right, rgba(236, 72, 153, 0.16) 0%, transparent 25%),
            linear-gradient(135deg, #0f1117 0%, #171a22 45%, #111318 100%);
    }

    .mixes-title {
        margin-bottom: 0.35rem;
        color: #ffffff;
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 800;
        line-height: 1.05;
    }

    .mixes-subtitle {
        margin-bottom: 0;
        color: var(--us-text);
    }

    .mixes-create {
        padding: 0.65rem 1.4rem;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: #ffffff;
        font-weight: 700;
    }

    .mixes-create:hover {
        background: linear-gradient(135deg, #7c3aed, #db2777);
        color: #ffffff;
    }

    .mixes-empty {
        padding: 3rem 1.5rem;
        border: 1px solid var(--us-line);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.05);
        text-align: center;
    }

    .mixes-empty-title {
        color: #ffffff;
        font-size: 1.4rem;
        font-weight: 700;
    }

    .mix-card {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        height: 100%;
        padding: 1.25rem;
        border: 1px solid var(--us-line);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.05);
        color: var(--us-text-strong);
        cursor: pointer;
        transition: border-color 0.15s ease;
    }

    .mix-card:hover,
    .mix-card:focus-visible {
        border-color: rgba(139, 92, 246, 0.7);
    }

    .mix-card:focus-visible {
        outline: 2px solid #8b5cf6;
        outline-offset: 2px;
    }

    .mix-items {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    /* Ventana de la mezcla: el mismo aspecto oscuro y translúcido que la tarjeta */
    .mix-modal .modal-dialog {
        margin-left: 0.75rem;
        margin-right: 0.75rem;
    }

    @media (min-width: 576px) {
        .mix-modal .modal-dialog {
            margin-left: auto;
            margin-right: auto;
        }
    }

    .mix-modal .modal-content {
        border: 1px solid var(--us-line);
        border-radius: 22px;
        background:
            linear-gradient(rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.05)),
            rgba(17, 19, 24, 0.82);
        -webkit-backdrop-filter: blur(14px);
        backdrop-filter: blur(14px);
        color: var(--us-text-strong);
    }

    .mix-modal .modal-header {
        align-items: flex-start;
        gap: 0.75rem;
        padding: 1.25rem 1.25rem 0.75rem;
        border-bottom: 0;
    }

    .mix-modal-heading {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 0.75rem;
        min-width: 0;
    }

    .mix-modal-name {
        font-size: 1.5rem;
    }

    .mix-modal .modal-body {
        padding: 0.5rem 1.25rem 1rem;
    }

    .mix-modal .modal-footer {
        margin: 0 1.25rem;
        padding: 0.85rem 0 1.25rem;
    }

    .mix-modal .modal-footer > * {
        margin: 0;
    }

    .mix-items.is-modal .mix-flavor-thumb {
        width: 6rem;
        height: 6rem;
        border-radius: 16px;
    }

    .mix-items.is-modal .mix-flavor-name {
        font-size: 1.1rem;
    }

    .mix-items.is-modal .mix-ratio {
        font-size: 1.3rem;
    }

    .mix-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .mix-card-name {
        margin: 0;
        color: #ffffff;
        font-size: 1.25rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .mix-visibility {
        flex-shrink: 0;
        padding: 0.2rem 0.65rem;
        border: 1px solid var(--us-line);
        border-radius: 999px;
        color: var(--us-text);
        font-size: 0.78rem;
        font-weight: 600;
    }

    .mix-visibility.is-public {
        border-color: rgba(110, 231, 183, 0.45);
        color: #6ee7b7;
    }

    /* The proportions of the mix are the memorable part of each card. */
    .mix-bar {
        display: flex;
        gap: 2px;
        height: 0.7rem;
        overflow: hidden;
        border-radius: 999px;
    }

    .mix-bar span {
        flex-basis: 0;
        min-width: 3px;
    }

    .mix-items-list {
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
    }

    .mix-item-row {
        display: flex;
        align-items: center;
        gap: 0.7rem;
    }

    .mix-flavor-thumb {
        position: relative;
        width: 4rem;
        height: 4rem;
        flex-shrink: 0;
        overflow: hidden;
        border-radius: 12px;
        outline: 2px solid var(--mix-color);
        outline-offset: -2px;
    }

    .mix-flavor-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: #ffffff;
        font-weight: 800;
    }

    .mix-flavor-thumb img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        padding: 3px;
        background: #f5f3ff;
        object-fit: contain;
    }

    .mix-flavor-info {
        flex: 1;
        min-width: 0;
    }

    .mix-flavor-name {
        color: #ffffff;
        font-weight: 600;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .mix-flavor-brand,
    .mix-card-muted {
        color: var(--us-text);
        font-size: 0.84rem;
    }

    .mix-ratio {
        flex-shrink: 0;
        color: #ffffff;
        font-size: 1.1rem;
        font-weight: 800;
    }

    .mix-notes {
        margin: 0;
        padding-left: 0.75rem;
        border-left: 2px solid var(--us-line);
        color: var(--us-text);
        font-size: 0.92rem;
        overflow-wrap: anywhere;
    }

    .mix-card-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
        margin-top: auto;
        padding-top: 0.85rem;
        border-top: 1px solid var(--us-line);
    }

    .mix-action {
        padding: 0.3rem 0.85rem;
        border: 1px solid var(--us-line);
        border-radius: 999px;
        background: transparent;
        color: var(--us-text-strong);
        font-size: 0.88rem;
        font-weight: 600;
        text-decoration: none;
    }

    .mix-action:hover {
        background: var(--us-hover);
        color: var(--us-text-strong);
    }

    .mix-action.is-danger {
        color: #fca5a5;
    }

    .mix-action.is-danger:hover {
        border-color: #f87171;
        color: #fecaca;
    }

    /* Spec 007: pestañas "Mis mezclas" / "Públicas" */
    .mixes-tabs {
        display: inline-flex;
        gap: 0.25rem;
        max-width: 100%;
        padding: 0.3rem;
        border: 1px solid var(--us-line);
        border-radius: 999px;
        background: var(--us-bar);
    }

    .mixes-tab {
        padding: 0.45rem 1.1rem;
        border-radius: 999px;
        color: var(--us-text);
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
    }

    .mixes-tab:hover {
        background: var(--us-hover);
        color: var(--us-text-strong);
    }

    .mixes-tab.is-active {
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: #ffffff;
    }

    .mixes-tab:focus-visible {
        outline: 2px solid #f472b6;
        outline-offset: 2px;
    }

    .mix-author {
        margin: 0.2rem 0 0;
        color: var(--us-text);
        font-size: 0.88rem;
        overflow-wrap: anywhere;
    }

    .min-w-0 {
        min-width: 0;
    }

    /* Panel de filtros, "Ordenar por" y paginación: el mismo aspecto que la portada y el admin de sabores */
    .mixes-page .btn-modern {
        border-radius: 999px;
        padding: 0.6rem 1.15rem;
        font-weight: 600;
    }

    .mixes-page .btn-gradient {
        border: none;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        box-shadow: 0 8px 20px rgba(139, 92, 246, 0.28);
    }

    .mixes-page .btn-gradient:hover {
        background: linear-gradient(135deg, #7c3aed, #db2777);
    }

    .filters-panel {
        /* backdrop-filter crea un contexto de apilamiento: así la lista abierta queda por encima de las tarjetas. */
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
    function openMixModal(id) {
        const modal = document.getElementById('mixModal-' + id);
        if (modal) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    }

    // Panel de filtros plegable en el móvil (solo en "Públicas")
    function toggleFiltersMobile(button) {
        const open = document.getElementById('filtersContent').classList.toggle('show');
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
</script>
@endpush
