@extends('layouts.app')

@section('title', 'Mezclas')

@section('content')
<div class="container py-4">
    @if(session('success'))
    <div class="alert alert-success rounded-4">
        {{ session('success') }}
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="fw-bold text-white mb-1">Mis mezclas</h1>
            <p class="text-light-emphasis mb-0">Crea y organiza tus mezclas favoritas.</p>
        </div>

        <a href="{{ route('mixes.create') }}" class="btn btn-dark rounded-pill px-4 py-2">
            + Crear mezcla
        </a>
    </div>

    <div class="row g-4">
        @forelse($mixes as $mix)
        <div class="col-md-6 col-xl-4">
            <div class="card mix-card border-0 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h4 class="fw-bold mb-0 text-dark">{{ $mix->name }}</h4>

                        @if($mix->is_public)
                        <span class="badge text-bg-success rounded-pill">Pública</span>
                        @else
                        <span class="badge text-bg-secondary rounded-pill">Privada</span>
                        @endif
                    </div>

                    <p class="mix-notes mb-3">
                        {{ $mix->notes ?: 'Sin notas para esta mezcla.' }}
                    </p>

                    <div class="mix-items-list mb-3">
                        @forelse($mix->items as $item)
                        <div class="mix-item-row">
                            <div class="d-flex align-items-center gap-3">
                                @if($item->flavor && $item->flavor->image_url)
                                <img
                                    src="{{ asset('storage/' . $item->flavor->image_url) }}"
                                    alt="{{ $item->flavor->name }}"
                                    class="mix-flavor-image"
                                    >
                                @else
                                <div class="mix-flavor-placeholder">
                                    {{ $item->flavor ? strtoupper(substr($item->flavor->name, 0, 1)) : '?' }}
                                </div>
                                @endif

                                <div class="mix-flavor-info">
                                    <div class="mix-flavor-name">
                                        {{ $item->flavor->name ?? 'Sabor no disponible' }}
                                    </div>
                                    <div class="mix-flavor-brand">
                                        {{ $item->flavor->brand->name ?? 'Sin marca' }}
                                    </div>
                                </div>
                            </div>

                            <div class="mix-ratio-badge">
                                {{ $item->ratio }}%
                            </div>
                        </div>
                        @empty
                        <p class="text-muted mb-0">Esta mezcla no tiene sabores.</p>
                        @endforelse
                    </div>

                    <div class="small text-secondary mb-3">
                        <strong>Creada:</strong>
                        {{ $mix->created_at ? $mix->created_at->format('d/m/Y') : '-' }}
                    </div>

                    <div class="d-flex gap-2 mt-auto">
                        <a href="{{ route('mixes.edit', $mix) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Editar</a>

                        <form action="{{ route('mixes.destroy', $mix) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar esta mezcla?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="empty-box text-center p-5">
                <h3 class="text-white fw-bold">Todavía no tienes mezclas</h3>
                <p class="text-light-emphasis mb-3">Empieza creando tu primera mezcla personalizada.</p>
                <a href="{{ route('mixes.create') }}" class="btn btn-dark rounded-pill px-4">Crear mezcla</a>
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection

@push('head')
<style>
    .mix-card {
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .mix-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 38px rgba(0, 0, 0, 0.28);
    }

    .mix-notes {
        color: #374151;
        line-height: 1.5;
    }

    .mix-items-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .mix-item-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
    }

    .mix-flavor-image {
        width: 54px;
        height: 54px;
        border-radius: 14px;
        object-fit: cover;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }

    .mix-flavor-placeholder {
        width: 54px;
        height: 54px;
        border-radius: 14px;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: white;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }

    .mix-flavor-info {
        display: flex;
        flex-direction: column;
    }

    .mix-flavor-name {
        color: #111827;
        font-weight: 700;
        line-height: 1.2;
    }

    .mix-flavor-brand {
        color: #6b7280;
        font-size: 0.85rem;
        margin-top: 2px;
    }

    .mix-ratio-badge {
        min-width: 58px;
        text-align: center;
        padding: 8px 10px;
        border-radius: 999px;
        background: #111827;
        color: white;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .empty-box {
        border-radius: 24px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1);
    }
</style>
@endpush