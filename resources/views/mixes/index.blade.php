@extends('layouts.app')

@section('title', 'Mezclas')

@section('content')
@php
    $barColors = ['#8b5cf6', '#ec4899', '#f59e0b', '#14b8a6', '#38bdf8', '#a3e635'];
@endphp
<div class="container py-4">
    @if(session('success'))
    <div class="alert alert-success rounded-4">
        {{ session('success') }}
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
        <div>
            <h1 class="mixes-title">Mis mezclas</h1>
            <p class="mixes-subtitle">Tus combinaciones de sabores, con la proporción de cada uno.</p>
        </div>

        @if($mixes->isNotEmpty())
        <a href="{{ route('mixes.create') }}" class="btn mixes-create">Crear mezcla</a>
        @endif
    </div>

    @if($mixes->isEmpty())
    <div class="mixes-empty">
        <h2 class="mixes-empty-title">Todavía no tienes mezclas</h2>
        <p class="mixes-subtitle mb-3">Combina varios de tus favoritos y guarda la proporción de cada uno.</p>
        <a href="{{ route('mixes.create') }}" class="btn mixes-create">Crear mezcla</a>
    </div>
    @else
    <div class="row g-3 g-lg-4">
        @foreach($mixes as $mix)
        <div class="col-md-6 col-xl-4">
            <article class="mix-card">
                <header class="mix-card-header">
                    <h2 class="mix-card-name">{{ $mix->name }}</h2>
                    <span class="mix-visibility {{ $mix->is_public ? 'is-public' : '' }}">{{ $mix->is_public ? 'Pública' : 'Privada' }}</span>
                </header>

                @if($mix->items->isNotEmpty())
                <div class="mix-bar" aria-hidden="true">
                    @foreach($mix->items as $item)
                    <span style="flex-grow: {{ (int) $item->ratio }}; background: {{ $barColors[$loop->index % count($barColors)] }};"></span>
                    @endforeach
                </div>
                @endif

                <div class="mix-items-list">
                    @forelse($mix->items as $item)
                    <div class="mix-item-row">
                        <div class="mix-flavor-thumb" style="--mix-color: {{ $barColors[$loop->index % count($barColors)] }};">
                            <div class="mix-flavor-placeholder">{{ $item->flavor ? $item->flavor->initial : '?' }}</div>
                            @if($item->flavor && $item->flavor->image_url)
                            <img src="{{ asset('storage/' . $item->flavor->image_url) }}" alt="" loading="lazy" onerror="this.remove()">
                            @endif
                        </div>

                        <div class="mix-flavor-info">
                            <div class="mix-flavor-name">{{ $item->flavor->name ?? 'Sabor no disponible' }}</div>
                            <div class="mix-flavor-brand">{{ $item->flavor->brand->name ?? 'Sin marca' }}</div>
                        </div>

                        <div class="mix-ratio">{{ $item->ratio }}%</div>
                    </div>
                    @empty
                    <p class="mix-card-muted mb-0">Esta mezcla no tiene sabores.</p>
                    @endforelse
                </div>

                @if($mix->notes)
                <p class="mix-notes">{{ $mix->notes }}</p>
                @endif

                <footer class="mix-card-footer">
                    <span class="mix-card-muted">{{ $mix->created_at ? 'Creada el ' . $mix->created_at->format('d/m/Y') : '' }}</span>

                    <div class="d-flex gap-2">
                        <a href="{{ route('mixes.edit', $mix) }}" class="mix-action">Editar</a>

                        <form action="{{ route('mixes.destroy', $mix) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta mezcla?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="mix-action is-danger">Eliminar</button>
                        </form>
                    </div>
                </footer>
            </article>
        </div>
        @endforeach
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
        width: 2.9rem;
        height: 2.9rem;
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
</style>
@endpush
