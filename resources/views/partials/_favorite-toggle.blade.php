{{--
    Corazón de favoritos sin recargar la página.
    Cada <form data-favorite-toggle> se envía con fetch (JSON) y muestra un toast.
    En Favoritos (data-favorite-remove) la tarjeta desaparece al quitar el sabor.
    Sin JS, o si fetch falla, el formulario se envía como siempre.
--}}
@once
<div id="favoriteToasts" class="toast-container position-fixed start-50 translate-middle-x favorite-toasts" aria-live="polite" aria-atomic="true"></div>

@push('head')
<style>
    .favorite-toasts {
        top: 4.75rem;
        z-index: 1090;
        width: max-content;
        max-width: calc(100vw - 2rem);
    }

    .favorite-toast {
        border: 1px solid var(--us-line);
        border-radius: 999px;
        background: #1b1426;
        color: var(--us-text-strong);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
        font-weight: 600;
    }

    .favorite-toast .toast-body {
        padding: 0.6rem 1rem;
    }

    .favorite-btn:disabled {
        opacity: 0.7;
    }
</style>
@endpush

@push('scripts')
<script>
    function showFavoriteToast(message) {
        const container = document.getElementById('favoriteToasts');
        if (!container || !window.bootstrap || !bootstrap.Toast) return;

        const toast = document.createElement('div');
        toast.className = 'toast favorite-toast';
        toast.setAttribute('role', 'status');

        const body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = message;
        toast.appendChild(body);

        container.appendChild(toast);
        toast.addEventListener('hidden.bs.toast', function () { toast.remove(); });
        new bootstrap.Toast(toast, { delay: 2500 }).show();
    }

    function updateFavoriteButton(form, favorited) {
        const button = form.querySelector('.favorite-btn');
        const name = form.dataset.flavorName;

        button.classList.toggle('active', favorited);
        button.title = favorited ? 'Quitar de favoritos' : 'Añadir a favoritos';
        button.setAttribute('aria-label', (favorited ? 'Quitar ' + name + ' de favoritos' : 'Añadir ' + name + ' a favoritos'));
        button.setAttribute('aria-pressed', favorited ? 'true' : 'false');
    }

    // Favoritos: quita la tarjeta y su ventana, el título del grupo si queda vacío y actualiza el contador.
    function removeFavoriteCard(form) {
        const item = form.closest('[data-favorite-item]');
        const group = item ? item.closest('.fav-group') : null;
        const modal = document.getElementById('flavorModal-' + form.dataset.flavorId);

        if (item) item.remove();
        if (modal) modal.remove();

        if (group && !group.querySelector('[data-favorite-item]')) {
            const title = group.previousElementSibling;
            if (title && title.classList.contains('fav-group-title')) title.remove();
            group.remove();
        }

        const counter = document.querySelector('.fav-count[data-total]');
        if (!counter) return;

        const total = Number(counter.dataset.total) - 1;
        const matching = Number(counter.dataset.matching) - 1;

        // Sin tarjetas a la vista se recarga para mostrar el estado vacío que pinta el servidor.
        if (total <= 0 || matching <= 0) {
            window.location.reload();
            return;
        }

        counter.dataset.total = total;
        counter.dataset.matching = matching;

        if (counter.dataset.filtering === '1') {
            counter.textContent = matching + ' de ' + total + ' favoritos';
        } else {
            counter.textContent = total === 1 ? '1 favorito guardado' : total + ' favoritos guardados';
        }
    }

    async function submitFavoriteToggle(event) {
        const form = event.currentTarget;
        if (!window.fetch || !window.FormData) return;

        event.preventDefault();
        if (form.dataset.busy === '1') return;

        const button = form.querySelector('.favorite-btn');
        form.dataset.busy = '1';
        button.disabled = true;

        let data;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            data = await response.json();
        } catch (error) {
            // Sesión caducada, error de red...: se envía el formulario como siempre.
            form.submit();
            return;
        }

        form.dataset.busy = '';
        button.disabled = false;

        if (!data.favorited && form.hasAttribute('data-favorite-remove')) {
            removeFavoriteCard(form);
        } else {
            updateFavoriteButton(form, data.favorited);
        }

        showFavoriteToast(data.message);
    }

    function initFavoriteToggles() {
        document.querySelectorAll('form[data-favorite-toggle]').forEach(function (form) {
            form.addEventListener('submit', submitFavoriteToggle);
        });
    }

    document.addEventListener('DOMContentLoaded', initFavoriteToggles);
</script>
@endpush
@endonce
