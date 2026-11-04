@extends('layouts.app')

@section('title', $brand->name)

@section('content')
<div class="container">
    <a href="{{ route('brands.index') }}" class="brand-back">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>
        Todas las marcas
    </a>

    <header class="brand-header">
        <h1 class="brand-title">{{ $brand->name }}</h1>
        @if($flavors->isNotEmpty())
        <p class="brand-subtitle">{{ $flavors->count() === 1 ? '1 sabor' : $flavors->count() . ' sabores' }}</p>
        @endif
    </header>

    @if($flavors->isEmpty())
    <p class="brand-subtitle">Esta marca no tiene sabores disponibles.</p>
    @else
    <div class="row g-3 g-lg-4">
        @foreach($flavors as $flavor)
        <div class="col-6 col-md-4 col-xl-3">
            <article class="brand-flavor">
                <div class="brand-flavor-image">
                    <span>{{ $flavor->initial }}</span>
                    @if($flavor->image_url)
                    <img src="{{ asset('storage/' . $flavor->image_url) }}" alt="{{ $flavor->name }}" loading="lazy" onerror="this.remove()">
                    @endif
                </div>
                <div class="brand-flavor-body">
                    <h2 class="brand-flavor-name">{{ $flavor->name }}</h2>
                    @if($flavor->tobacco_type)
                    <p class="brand-flavor-type">{{ ucfirst(str_replace('_', ' ', $flavor->tobacco_type)) }}</p>
                    @endif
                    @if($flavor->category)
                    <span class="brand-flavor-category">{{ $flavor->category->name }}</span>
                    @endif
                </div>
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

    .brand-back {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        margin-top: 0.25rem;
        color: var(--us-text);
        font-weight: 600;
        text-decoration: none;
    }

    .brand-back:hover {
        color: var(--us-text-strong);
    }

    .brand-back:focus-visible {
        outline: 2px solid #f472b6;
        outline-offset: 3px;
        border-radius: 4px;
    }

    .brand-back svg {
        width: 1rem;
        height: 1rem;
        fill: none;
        stroke: currentColor;
        stroke-width: 2.2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .brand-header {
        margin: 0.75rem 0 1.75rem;
    }

    .brand-title {
        margin-bottom: 0.35rem;
        color: #ffffff;
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 800;
        line-height: 1.05;
        overflow-wrap: anywhere;
    }

    .brand-subtitle {
        margin-bottom: 0;
        color: var(--us-text);
    }

    .brand-flavor {
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
        border-radius: 22px;
        background: #ffffff;
        color: #111827;
    }

    .brand-flavor-image {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        aspect-ratio: 4 / 3;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: #ffffff;
        font-size: 2.6rem;
        font-weight: 800;
    }

    .brand-flavor-image img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        padding: 0.5rem;
        background: linear-gradient(135deg, #eef2ff, #f5f3ff);
        object-fit: contain;
    }

    .brand-flavor-body {
        display: flex;
        flex: 1;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.4rem;
        padding: 0.9rem 1rem 1rem;
    }

    .brand-flavor-name {
        margin: 0;
        font-size: 1.02rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .brand-flavor-type {
        margin: 0;
        color: #4b5563;
        font-size: 0.86rem;
    }

    .brand-flavor-category {
        margin-top: auto;
        padding: 0.28rem 0.65rem;
        border-radius: 999px;
        background: #f3f4f6;
        color: #374151;
        font-size: 0.75rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    @media (max-width: 575.98px) {
        .brand-flavor {
            border-radius: 18px;
        }

        .brand-flavor-body {
            padding: 0.75rem 0.8rem 0.85rem;
        }

        .brand-flavor-name {
            font-size: 0.92rem;
        }
    }
</style>
@endpush
