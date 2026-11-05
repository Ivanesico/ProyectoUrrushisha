@extends('layouts.app')

@section('title', 'Mezclas')

@section('content')
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
            <article class="mix-card"
                     role="button"
                     tabindex="0"
                     aria-label="Ver la mezcla {{ $mix->name }}"
                     onclick="openMixModal({{ $mix->id }})"
                     onkeydown="if (event.key === 'Enter' && event.target === this) openMixModal({{ $mix->id }})">
                <header class="mix-card-header">
                    <h2 class="mix-card-name">{{ $mix->name }}</h2>
                    <span class="mix-visibility {{ $mix->is_public ? 'is-public' : '' }}">{{ $mix->is_public ? 'Pública' : 'Privada' }}</span>
                </header>

                @include('mixes._mix-items', ['mix' => $mix, 'size' => 'card'])

                @if($mix->notes)
                <p class="mix-notes">{{ $mix->notes }}</p>
                @endif

                <footer class="mix-card-footer">
                    <span class="mix-card-muted">{{ $mix->created_at ? 'Creada el ' . $mix->created_at->format('d/m/Y') : '' }}</span>

                    {{-- Las acciones no abren la ventana --}}
                    <div class="d-flex gap-2" onclick="event.stopPropagation();" onkeydown="event.stopPropagation();">
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

    {{-- Una ventana por mezcla, fuera de las tarjetas para que sus clics no lleguen a ellas --}}
    @foreach($mixes as $mix)
    <div class="modal fade mix-modal" id="mixModal-{{ $mix->id }}" tabindex="-1" aria-labelledby="mixModalTitle-{{ $mix->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="mix-modal-heading">
                        <h2 class="mix-card-name mix-modal-name" id="mixModalTitle-{{ $mix->id }}">{{ $mix->name }}</h2>
                        <span class="mix-visibility {{ $mix->is_public ? 'is-public' : '' }}">{{ $mix->is_public ? 'Pública' : 'Privada' }}</span>
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

                    <div class="d-flex gap-2">
                        <a href="{{ route('mixes.edit', $mix) }}" class="mix-action">Editar</a>

                        <form action="{{ route('mixes.destroy', $mix) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta mezcla?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="mix-action is-danger">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
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
</script>
@endpush
