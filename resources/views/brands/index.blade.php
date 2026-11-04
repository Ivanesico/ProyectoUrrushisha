@extends('layouts.app')

@section('title', 'Marcas')

@section('content')
<div class="container">
    <header class="brands-header">
        <h1 class="brands-title">Marcas</h1>
        @if($brands->isNotEmpty())
        <p class="brands-subtitle">Elige una marca para ver todos sus sabores.</p>
        @endif
    </header>

    @if($brands->isEmpty())
    <p class="brands-empty">No hay marcas disponibles.</p>
    @else
    <div class="row g-3 g-lg-4">
        @foreach($brands as $brand)
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('brands.show', $brand) }}" class="brand-card">
                <div class="brand-tins" aria-hidden="true">
                    @forelse($brand->flavors->take(3) as $flavor)
                    <span class="brand-tin">
                        {{ $flavor->initial }}
                        <img src="{{ asset('storage/' . $flavor->image_url) }}" alt="" loading="lazy" onerror="this.remove()">
                    </span>
                    @empty
                    <span class="brand-tin brand-tin-empty">{{ mb_strtoupper(mb_substr($brand->name, 0, 1)) }}</span>
                    @endforelse
                </div>

                <h2 class="brand-card-name">{{ $brand->name }}</h2>
                <p class="brand-card-count">
                    @if($brand->public_flavors_count === 0)
                    Sin sabores todavía
                    @elseif($brand->public_flavors_count === 1)
                    1 sabor
                    @else
                    {{ $brand->public_flavors_count }} sabores
                    @endif
                </p>
            </a>
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

    .brands-header {
        margin: 0.75rem 0 1.75rem;
    }

    .brands-title {
        margin-bottom: 0.35rem;
        color: #ffffff;
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 800;
        line-height: 1.05;
    }

    .brands-subtitle,
    .brands-empty {
        margin-bottom: 0;
        color: var(--us-text);
    }

    .brand-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 1.1rem;
        border: 1px solid var(--us-line);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.05);
        color: var(--us-text-strong);
        text-decoration: none;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }

    .brand-card:hover {
        border-color: rgba(236, 72, 153, 0.55);
        background: rgba(255, 255, 255, 0.09);
        color: var(--us-text-strong);
    }

    .brand-card:focus-visible {
        outline: 2px solid #f472b6;
        outline-offset: 2px;
    }

    /* Tins of the brand, overlapping like on a shelf. */
    .brand-tins {
        display: flex;
        margin-bottom: 1rem;
    }

    .brand-tin {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 4.75rem;
        height: 4.75rem;
        flex-shrink: 0;
        overflow: hidden;
        border: 3px solid #1d1a29;
        border-radius: 50%;
        background: #f5f3ff;
        color: #6d28d9;
        font-size: 1.4rem;
        font-weight: 800;
    }

    .brand-tin + .brand-tin {
        margin-left: -1.35rem;
    }

    .brand-tin img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        background: #f5f3ff;
        object-fit: cover;
        transform: scale(1.12);
    }

    .brand-tin-empty {
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: #ffffff;
    }

    .brand-card-name {
        margin-bottom: 0.25rem;
        font-size: 1.1rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .brand-card-count {
        margin: auto 0 0;
        color: var(--us-text);
        font-size: 0.88rem;
    }

    @media (max-width: 575.98px) {
        .brand-card {
            padding: 0.85rem;
            border-radius: 18px;
        }

        .brand-tin {
            width: 3.25rem;
            height: 3.25rem;
            font-size: 1.1rem;
        }

        .brand-tin + .brand-tin {
            margin-left: -1.2rem;
        }

        .brand-card-name {
            font-size: 1rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .brand-card {
            transition: none;
        }
    }
</style>
@endpush
