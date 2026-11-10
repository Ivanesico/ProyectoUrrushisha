{{--
    Ventana de detalle de un sabor, con el diseño de la tarjeta del catálogo.
    Se incluye una vez por sabor, fuera de las tarjetas, en Inicio y en Favoritos.
    La tarjeta la abre con openFlavorModal(id); se cierra con la X, Esc o pulsando fuera.
--}}
<div class="modal fade flavor-detail-modal" id="flavorModal-{{ $flavor->id }}" tabindex="-1" aria-labelledby="flavorModalTitle-{{ $flavor->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <button type="button" class="flavor-detail-close" data-bs-dismiss="modal" aria-label="Cerrar">×</button>

            <div class="modal-body p-0">
                <div class="flavor-detail-image">
                    <span class="flavor-detail-initial" aria-hidden="true">{{ $flavor->initial }}</span>
                    @if($flavor->image_url)
                    <img src="{{ asset('storage/' . $flavor->image_url) }}" alt="{{ $flavor->name }}" loading="lazy" onerror="this.remove()">
                    @endif
                </div>

                <div class="flavor-detail-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <h2 class="flavor-detail-name" id="flavorModalTitle-{{ $flavor->id }}">{{ $flavor->name }}</h2>

                        @if($flavor->category)
                        <span class="flavor-detail-badge">{{ $flavor->category->name }}</span>
                        @endif
                    </div>

                    <div class="flavor-detail-meta mb-3">
                        <div class="flavor-detail-meta-item">
                            <span class="flavor-detail-meta-label">Marca</span>
                            <span class="flavor-detail-meta-value">{{ $flavor->brand->name ?? 'Sin marca' }}</span>
                        </div>

                        <div class="flavor-detail-meta-item">
                            <span class="flavor-detail-meta-label">Tipo</span>
                            <span class="flavor-detail-meta-value">{{ $flavor->tobacco_type_label ?? 'No definido' }}</span>
                        </div>
                    </div>

                    <h3 class="flavor-detail-section">Descripción</h3>
                    <p class="flavor-detail-text mb-3">{{ $flavor->description ?: 'Sin descripción disponible.' }}</p>

                    <h3 class="flavor-detail-section">Ingredientes</h3>
                    <div class="flavor-detail-ingredients mb-4">{{ $flavor->ingredients_text ?: 'Sin ingredientes definidos' }}</div>

                    {{-- Spec 010: cada formato de menor a mayor cantidad; sin precios, no aparece --}}
                    @if($flavor->prices->isNotEmpty())
                    <h3 class="flavor-detail-section">Precios</h3>
                    <ul class="flavor-detail-prices mb-4">
                        @foreach($flavor->prices as $price)
                        <li>{{ $price->grams }} g · {{ \App\Models\FlavorPrice::formatPrice($price->price) }}</li>
                        @endforeach
                    </ul>
                    @endif

                    <div class="d-flex flex-wrap gap-2">
                        {{-- Sin sesión lleva a iniciar sesión (spec 006) --}}
                        <a href="{{ auth()->check() ? route('mixes.create') : route('login') }}" class="btn flavor-detail-primary">Crear mezcla</a>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('head')
<style>
    /* Spec 010: formatos y precios del sabor */
    .flavor-detail-prices {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 0;
        list-style: none;
    }

    .flavor-detail-prices li {
        padding: 0.35rem 0.85rem;
        /* La ventana es clara: rosa oscuro para que se lea bien */
        border: 1px solid rgba(190, 24, 93, 0.35);
        border-radius: 999px;
        background: #fdf2f8;
        color: #be185d;
        font-weight: 700;
        white-space: nowrap;
    }

    .flavor-detail-modal .modal-dialog {
        max-width: 480px;
        margin-left: auto;
        margin-right: auto;
        padding: 0 0.75rem;
    }

    /* La misma tarjeta blanca del catálogo, sobre el fondo oscuro de la página. */
    .flavor-detail-modal .modal-content {
        position: relative;
        overflow: hidden;
        border: 0;
        border-radius: 26px;
        background: #ffffff;
        color: #111827;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
    }

    .flavor-detail-close {
        position: absolute;
        top: 14px;
        right: 14px;
        z-index: 5;
        width: 44px;
        height: 44px;
        border: none;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.16);
        color: #111827;
        font-size: 1.6rem;
        line-height: 1;
    }

    .flavor-detail-close:focus-visible {
        outline: 3px solid #f472b6;
        outline-offset: 2px;
    }

    .flavor-detail-image {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 260px;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
    }

    .flavor-detail-image img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        background: #ffffff;
        object-fit: cover;
        object-position: center;
    }

    .flavor-detail-initial {
        color: rgba(255, 255, 255, 0.95);
        font-size: 4rem;
        font-weight: 800;
    }

    .flavor-detail-body {
        padding: 1.25rem 1.25rem 1.4rem;
    }

    .flavor-detail-name {
        margin: 0;
        color: #111827;
        font-size: 1.4rem;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .flavor-detail-badge {
        max-width: 100%;
        padding: 0.48rem 0.75rem;
        border-radius: 999px;
        background: #f3f4f6;
        color: #374151;
        font-size: 0.78rem;
        font-weight: 700;
        overflow-wrap: normal;
    }

    .flavor-detail-meta {
        display: grid;
        gap: 0.8rem;
    }

    .flavor-detail-meta-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding-bottom: 0.25rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .flavor-detail-meta-label {
        color: #6b7280;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .flavor-detail-meta-value {
        color: #374151;
        font-size: 0.9rem;
        font-weight: 700;
        text-align: right;
        overflow-wrap: anywhere;
    }

    .flavor-detail-section {
        margin-bottom: 0.4rem;
        color: #374151;
        font-size: 0.95rem;
        font-weight: 700;
    }

    .flavor-detail-text {
        color: #1f2937;
        font-size: 0.96rem;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .flavor-detail-ingredients {
        width: fit-content;
        max-width: 100%;
        padding: 0.55rem 0.85rem;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #f8fafc;
        color: #475569;
        font-size: 0.85rem;
        font-weight: 600;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .flavor-detail-primary {
        padding: 0.5rem 1.25rem;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: #ffffff;
        font-weight: 700;
    }

    .flavor-detail-primary:hover,
    .flavor-detail-primary:focus {
        background: linear-gradient(135deg, #7c3aed, #db2777);
        color: #ffffff;
    }

    .flavor-detail-modal .btn-outline-secondary {
        color: #374151;
        border-color: #d1d5db;
    }

    .flavor-detail-modal .btn-outline-secondary:hover {
        background: #f3f4f6;
        color: #111827;
    }

    @media (max-width: 575.98px) {
        .flavor-detail-image {
            height: 220px;
        }

        .flavor-detail-name {
            font-size: 1.25rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function openFlavorModal(id) {
        const modal = document.getElementById('flavorModal-' + id);
        if (modal) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    }
</script>
@endpush
@endonce
