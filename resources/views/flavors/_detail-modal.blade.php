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

                    {{-- Spec 011: media, número y estrellas para puntuar (sin sesión, enlaces a login) --}}
                    @php
                        $ratingCount = (int) ($flavor->ratings_count ?? 0);
                        $userStars = ($userRatings ?? [])[$flavor->id] ?? null;
                    @endphp
                    <h3 class="flavor-detail-section">Valoración</h3>
                    <div class="flavor-rating-box mb-4" data-rating-box="{{ $flavor->id }}">
                        <p class="flavor-rating-summary" data-rating-summary>@if($ratingCount > 0)★ {{ \App\Models\FlavorRating::formatAverage(\App\Models\FlavorRating::roundedAverage($flavor->ratings_avg_stars)) }}@else{{ 'Sin valoraciones todavía' }}@endif</p>

                        @auth
                        <form action="{{ route('ratings.update', $flavor) }}" method="POST" class="flavor-stars" data-rating-form="{{ $flavor->id }}">
                            @csrf
                            @method('PUT')
                            @for($star = 1; $star <= 5; $star++)
                            <button type="submit" name="stars" value="{{ $star }}" class="flavor-star{{ $userStars !== null && $star <= $userStars ? ' is-on' : '' }}" aria-label="Puntuar con {{ $star }} {{ $star === 1 ? 'estrella' : 'estrellas' }}" aria-pressed="{{ $userStars === $star ? 'true' : 'false' }}">★</button>
                            @endfor
                        </form>

                        <form action="{{ route('ratings.destroy', $flavor) }}" method="POST" data-rating-remove="{{ $flavor->id }}"{{ $userStars === null ? ' hidden' : '' }}>
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="flavor-rating-remove">Quitar mi valoración</button>
                        </form>
                        @else
                        <div class="flavor-stars">
                            @for($star = 1; $star <= 5; $star++)
                            <a href="{{ route('login') }}" class="flavor-star" aria-label="Inicia sesión para puntuar con {{ $star }} {{ $star === 1 ? 'estrella' : 'estrellas' }}">★</a>
                            @endfor
                        </div>
                        @endauth
                    </div><!-- /rating-box -->

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
                        {{-- Abre el formulario con este sabor elegido (spec 013). Sin sesión, Laravel pide entrar y después vuelve aquí. --}}
                        <a href="{{ route('mixes.create', ['flavor' => $flavor->id]) }}" class="btn flavor-detail-primary">Crear mezcla</a>
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
    /* Spec 011: valoración con estrellas */
    .flavor-rating-summary {
        margin-bottom: 0.5rem;
        color: #374151;
        font-weight: 700;
    }

    .flavor-stars {
        display: flex;
        gap: 0.25rem;
        margin: 0;
    }

    .flavor-star {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: #d1d5db;
        font-size: 1.9rem;
        line-height: 1;
        text-decoration: none;
        transition: color 0.15s ease, transform 0.15s ease;
    }

    .flavor-star.is-on {
        color: #f59e0b;
    }

    .flavor-star:hover {
        color: #fbbf24;
        transform: scale(1.1);
    }

    .flavor-star:focus-visible,
    .flavor-rating-remove:focus-visible {
        outline: 2px solid #8b5cf6;
        outline-offset: 2px;
    }

    .flavor-star:disabled {
        opacity: 0.6;
    }

    .flavor-rating-remove {
        margin-top: 0.25rem;
        padding: 0;
        border: 0;
        background: transparent;
        color: #6b7280;
        font-size: 0.88rem;
        text-decoration: underline;
    }

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

    // Spec 011: puntuar y quitar la valoración sin recargar. Si fetch falla, se envía el formulario normal.
    (function () {
        const formatAverage = (average) => average.toFixed(1).replace('.', ',');

        function showRating(flavorId, data) {
            const box = document.querySelector('[data-rating-box="' + flavorId + '"]');
            if (!box) return;

            box.querySelector('[data-rating-summary]').textContent = data.count > 0
                ? '★ ' + formatAverage(data.average)
                : 'Sin valoraciones todavía';

            box.querySelectorAll('[data-rating-form] .flavor-star').forEach((star) => {
                const value = Number(star.value);
                star.classList.toggle('is-on', data.stars !== null && value <= data.stars);
                star.setAttribute('aria-pressed', data.stars === value ? 'true' : 'false');
            });

            const remove = box.querySelector('[data-rating-remove]');
            if (remove) remove.hidden = data.stars === null;

            // La media de la tarjeta del catálogo, sin el número (en Favoritos no existe).
            const card = document.querySelector('[data-rating-card="' + flavorId + '"]');
            if (card) {
                card.hidden = data.count === 0;
                card.textContent = data.count > 0 ? '★ ' + formatAverage(data.average) : '';
            }
        }

        function send(form, extra) {
            const body = new FormData(form);
            Object.entries(extra).forEach(([name, value]) => body.set(name, value));
            const buttons = form.querySelectorAll('button');
            buttons.forEach((button) => button.disabled = true);

            fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: body,
                credentials: 'same-origin'
            })
                .then((response) => {
                    if (!response.ok) throw new Error('rating failed');
                    return response.json();
                })
                .then((data) => {
                    buttons.forEach((button) => button.disabled = false);
                    const flavorId = form.dataset.ratingForm || form.dataset.ratingRemove;
                    showRating(flavorId, data);
                    // El aviso es el mismo de los favoritos (solo existe con sesión, como las estrellas).
                    if (typeof showFavoriteToast === 'function') showFavoriteToast(data.message);
                })
                .catch(() => {
                    // Sin fetch, el botón pulsado no viaja con form.submit(): se añade como campo oculto.
                    Object.entries(extra).forEach(([name, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = value;
                        form.append(input);
                    });
                    form.submit();
                });
        }

        document.querySelectorAll('form[data-rating-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!window.fetch || !event.submitter) return;
                event.preventDefault();
                send(form, { stars: event.submitter.value });
            });
        });

        document.querySelectorAll('form[data-rating-remove]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!window.fetch) return;
                event.preventDefault();
                send(form, {});
            });
        });
    })();
</script>
@endpush
@endonce
