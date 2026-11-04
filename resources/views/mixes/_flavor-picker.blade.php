{{--
    Filas de sabor y porcentaje de los formularios de mezcla, con buscador de favoritos.
    El <select> sigue siendo el campo que se envía. El buscador lo sustituye con JavaScript;
    sin JavaScript, el formulario funciona igual con el desplegable.
    Variables: $flavors, $rows.
--}}
@if($flavors->isEmpty())
<p class="mix-no-favorites">
    Todavía no tienes favoritos. <a href="{{ route('home') }}">Guarda sabores desde el catálogo</a> para añadirlos a tu mezcla.
</p>
@endif

<div id="flavor-list">
    @foreach($rows as $row)
    <div class="mix-row flavor-row">
        <div class="mix-row-flavor">
            <select name="flavors[]" class="form-select" aria-label="Sabor" required>
                <option value="">Selecciona sabor</option>
                @foreach($flavors as $flavor)
                <option value="{{ $flavor->id }}" @selected($row['flavor_id'] == $flavor->id) data-name="{{ $flavor->name }}" data-brand="{{ $flavor->brand->name ?? '' }}" data-image="{{ $flavor->image_url ? asset('storage/' . $flavor->image_url) : '' }}">
                    {{ $flavor->name }} ({{ $flavor->brand->name ?? '-' }})
                </option>
                @endforeach
            </select>
        </div>

        <div class="input-group mix-row-ratio">
            <input type="number" name="ratios[]" class="form-control" placeholder="0" aria-label="Porcentaje" min="1" max="100" required value="{{ $row['ratio'] }}">
            <span class="input-group-text">%</span>
        </div>

        <button type="button" class="mix-row-remove remove-row" aria-label="Quitar sabor">&times;</button>
    </div>
    @endforeach
</div>

<button type="button" id="add-row" class="mix-add-row">+ Añadir sabor</button>

<div class="mix-total">
    <div class="mix-total-bar" id="mix-total-bar" aria-hidden="true" hidden></div>
    <p class="mix-total-text" id="mix-total-text" aria-live="polite">La suma de los porcentajes debe ser exactamente 100%.</p>
</div>

<template id="flavor-row-template">
    <div class="mix-row flavor-row">
        <div class="mix-row-flavor">
            <select name="flavors[]" class="form-select" aria-label="Sabor" required>
                <option value="">Selecciona sabor</option>
                @foreach($flavors as $flavor)
                <option value="{{ $flavor->id }}" data-name="{{ $flavor->name }}" data-brand="{{ $flavor->brand->name ?? '' }}" data-image="{{ $flavor->image_url ? asset('storage/' . $flavor->image_url) : '' }}">
                    {{ $flavor->name }} ({{ $flavor->brand->name ?? '-' }})
                </option>
                @endforeach
            </select>
        </div>

        <div class="input-group mix-row-ratio">
            <input type="number" name="ratios[]" class="form-control" placeholder="0" aria-label="Porcentaje" min="1" max="100" required>
            <span class="input-group-text">%</span>
        </div>

        <button type="button" class="mix-row-remove remove-row" aria-label="Quitar sabor">&times;</button>
    </div>
</template>

@push('head')
<style>
    body {
        background:
            radial-gradient(circle at top left, rgba(139, 92, 246, 0.18) 0%, transparent 28%),
            radial-gradient(circle at bottom right, rgba(236, 72, 153, 0.16) 0%, transparent 25%),
            linear-gradient(135deg, #0f1117 0%, #171a22 45%, #111318 100%);
    }

    .mix-form-title {
        margin-bottom: 0.35rem;
        color: #ffffff;
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 800;
        line-height: 1.05;
    }

    .mix-form-subtitle {
        margin-bottom: 1.75rem;
        color: var(--us-text);
    }

    .mix-panel {
        height: 100%;
        padding: 1.5rem;
        border: 1px solid var(--us-line);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.05);
    }

    .mix-panel-title {
        margin-bottom: 1rem;
        color: #ffffff;
        font-size: 1.15rem;
        font-weight: 700;
    }

    .mix-save {
        padding: 0.65rem 1.5rem;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: #ffffff;
        font-weight: 700;
    }

    .mix-save:hover {
        background: linear-gradient(135deg, #7c3aed, #db2777);
        color: #ffffff;
    }

    .mix-cancel {
        padding: 0.65rem 1.5rem;
        border: 1px solid var(--us-line);
        border-radius: 999px;
        color: var(--us-text-strong);
        font-weight: 600;
        text-decoration: none;
    }

    .mix-cancel:hover {
        background: var(--us-hover);
        color: var(--us-text-strong);
    }

    .mix-no-favorites {
        padding: 0.8rem 1rem;
        border-radius: 14px;
        background: rgba(139, 92, 246, 0.16);
        color: var(--us-text-strong);
    }

    .mix-no-favorites a {
        color: #f9a8d4;
    }

    .mix-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 7.5rem 2.5rem;
        gap: 0.5rem;
        align-items: center;
        margin-bottom: 0.6rem;
    }

    .mix-row-flavor {
        position: relative;
        min-width: 0;
    }

    .mix-row-remove {
        width: 2.5rem;
        height: 2.5rem;
        border: 1px solid var(--us-line);
        border-radius: 50%;
        background: transparent;
        color: var(--us-text);
        font-size: 1.25rem;
        line-height: 1;
    }

    .mix-row-remove:hover:not(:disabled) {
        border-color: #f87171;
        color: #fca5a5;
    }

    .mix-row-remove:disabled {
        opacity: 0.35;
    }

    .mix-add-row {
        margin-top: 0.25rem;
        padding: 0.5rem 1rem;
        border: 1px dashed rgba(255, 255, 255, 0.28);
        border-radius: 999px;
        background: transparent;
        color: var(--us-text-strong);
        font-weight: 600;
    }

    .mix-add-row:hover {
        background: var(--us-hover);
    }

    .mix-combo-input {
        padding-left: 2.35rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23c9c0d8' stroke-width='2' stroke-linecap='round'%3E%3Ccircle cx='11' cy='11' r='6.5'/%3E%3Cpath d='M16 16l4.500 4.500'/%3E%3C/svg%3E");
        background-position: 0.75rem center;
        background-repeat: no-repeat;
        background-size: 1.05rem;
        text-overflow: ellipsis;
    }

    .mix-combo-list {
        position: absolute;
        top: calc(100% + 0.3rem);
        left: 0;
        right: 0;
        z-index: 1040;
        max-height: 16rem;
        margin: 0;
        padding: 0.3rem;
        overflow-y: auto;
        list-style: none;
        scroll-margin-bottom: 6rem;
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 14px;
        background: #221a33;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.5);
    }

    .mix-combo-option {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.4rem 0.5rem;
        border-radius: 10px;
        cursor: pointer;
    }

    .mix-combo-option.is-active {
        background: rgba(139, 92, 246, 0.34);
    }

    .mix-combo-thumb {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        flex-shrink: 0;
        overflow: hidden;
        border-radius: 10px;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: #ffffff;
        font-weight: 800;
    }

    .mix-combo-thumb img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        background: #f5f3ff;
        object-fit: contain;
    }

    .mix-combo-text {
        min-width: 0;
        line-height: 1.25;
    }

    .mix-combo-name {
        display: block;
        color: #ffffff;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .mix-combo-brand {
        display: block;
        color: var(--us-text);
        font-size: 0.82rem;
    }

    .mix-combo-empty {
        padding: 0.65rem 0.6rem;
        color: var(--us-text);
        font-size: 0.92rem;
    }

    .mix-total {
        margin-top: 1rem;
    }

    .mix-total-bar {
        display: flex;
        gap: 2px;
        height: 0.6rem;
        margin-bottom: 0.45rem;
        overflow: hidden;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.1);
    }

    .mix-total-bar[hidden] {
        display: none;
    }

    .mix-total-segment {
        min-width: 0;
    }

    .mix-total-text {
        margin: 0;
        color: var(--us-text);
        font-size: 0.92rem;
    }

    .mix-total-text.is-complete {
        color: #6ee7b7;
        font-weight: 600;
    }

    @media (max-width: 575.98px) {
        .mix-panel {
            padding: 1.1rem;
            border-radius: 18px;
        }

        .mix-row {
            grid-template-columns: minmax(0, 1fr) 2.5rem;
            padding-bottom: 0.6rem;
            border-bottom: 1px solid var(--us-line);
        }

        .mix-row-flavor {
            grid-column: 1 / -1;
        }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const list = document.getElementById('flavor-list');
    const template = document.getElementById('flavor-row-template');
    const totalBar = document.getElementById('mix-total-bar');
    const totalText = document.getElementById('mix-total-text');
    const barColors = ['#8b5cf6', '#ec4899', '#f59e0b', '#14b8a6', '#38bdf8', '#a3e635'];
    const pickFlavorMessage = 'Elige un sabor de la lista.';
    let comboCount = 0;

    // Igual que el catálogo: el buscador ignora mayúsculas y acentos.
    const normalize = (text) => text.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();

    const rows = () => [...list.querySelectorAll('.flavor-row')];

    const optionLabel = (option) => {
        const brand = option.dataset.brand;
        return brand ? `${option.dataset.name} (${brand})` : option.dataset.name;
    };

    function takenValues(currentSelect) {
        return rows()
            .map((row) => row.querySelector('select'))
            .filter((select) => select !== currentSelect && select.value !== '')
            .map((select) => select.value);
    }

    function buildThumb(option) {
        const thumb = document.createElement('span');
        thumb.className = 'mix-combo-thumb';
        thumb.textContent = option.dataset.name.charAt(0).toUpperCase();

        if (option.dataset.image) {
            const image = document.createElement('img');
            image.src = option.dataset.image;
            image.alt = '';
            image.loading = 'lazy';
            image.addEventListener('error', () => image.remove());
            thumb.append(image);
        }

        return thumb;
    }

    function enhanceRow(row) {
        const select = row.querySelector('select');
        const flavorCell = row.querySelector('.mix-row-flavor');
        const options = [...select.options].filter((option) => option.value !== '');
        const comboId = `mix-combo-${++comboCount}`;

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control mix-combo-input';
        input.placeholder = 'Escribe el nombre de un favorito';
        input.autocomplete = 'off';
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-label', 'Buscar sabor en tus favoritos');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', comboId);

        const results = document.createElement('ul');
        results.className = 'mix-combo-list';
        results.id = comboId;
        results.hidden = true;
        results.setAttribute('role', 'listbox');

        select.required = false;
        select.hidden = true;
        flavorCell.append(input, results);

        let shown = [];
        let activeIndex = -1;

        const syncValidity = () => input.setCustomValidity(select.value === '' ? pickFlavorMessage : '');

        function setActive(index) {
            const items = results.querySelectorAll('[role="option"]');
            items.forEach((item, position) => {
                item.classList.toggle('is-active', position === index);
                item.setAttribute('aria-selected', position === index ? 'true' : 'false');
            });
            activeIndex = index;

            if (index >= 0) {
                input.setAttribute('aria-activedescendant', items[index].id);
                items[index].scrollIntoView({ block: 'nearest' });
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        }

        function closeResults() {
            results.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            activeIndex = -1;
        }

        function pick(option) {
            select.value = option.value;
            input.value = optionLabel(option);
            syncValidity();
            closeResults();
            updateTotal();
            row.querySelector('input[type="number"]').focus();
        }

        function openResults(query) {
            const term = normalize(query.trim());
            const taken = takenValues(select);

            shown = options.filter((option) => !taken.includes(option.value)
                && normalize(optionLabel(option)).includes(term));

            results.replaceChildren();

            if (shown.length === 0) {
                const empty = document.createElement('li');
                empty.className = 'mix-combo-empty';
                empty.textContent = options.length === 0
                    ? 'Todavía no tienes favoritos.'
                    : 'Ningún favorito disponible coincide con la búsqueda.';
                results.append(empty);
            }

            shown.forEach((option, index) => {
                const item = document.createElement('li');
                item.id = `${comboId}-${index}`;
                item.className = 'mix-combo-option';
                item.setAttribute('role', 'option');

                const text = document.createElement('span');
                text.className = 'mix-combo-text';

                const name = document.createElement('span');
                name.className = 'mix-combo-name';
                name.textContent = option.dataset.name;
                text.append(name);

                if (option.dataset.brand) {
                    const brand = document.createElement('span');
                    brand.className = 'mix-combo-brand';
                    brand.textContent = option.dataset.brand;
                    text.append(brand);
                }

                item.append(buildThumb(option), text);
                // Sin esto, el input pierde el foco y la lista se cierra antes del clic.
                item.addEventListener('mousedown', (event) => event.preventDefault());
                item.addEventListener('click', () => pick(option));
                results.append(item);
            });

            results.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            setActive(term !== '' && shown.length > 0 ? 0 : -1);
            results.scrollIntoView({ block: 'nearest' });
        }

        input.addEventListener('focus', () => {
            input.select();
            openResults('');
        });

        input.addEventListener('click', () => {
            if (results.hidden) {
                openResults('');
            }
        });

        input.addEventListener('input', () => {
            select.value = '';
            syncValidity();
            updateTotal();
            openResults(input.value);
        });

        input.addEventListener('blur', () => {
            closeResults();
            if (select.value !== '') {
                input.value = optionLabel(select.options[select.selectedIndex]);
            }
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeResults();
            } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (results.hidden) {
                    openResults(select.value === '' ? input.value : '');
                } else if (shown.length > 0) {
                    const step = event.key === 'ArrowDown' ? 1 : -1;
                    setActive((activeIndex + step + shown.length) % shown.length);
                }
            } else if (event.key === 'Enter' && !results.hidden) {
                // Enter elige el resultado, no envía el formulario.
                event.preventDefault();
                if (activeIndex >= 0) {
                    pick(shown[activeIndex]);
                }
            }
        });

        if (select.value !== '') {
            input.value = optionLabel(select.options[select.selectedIndex]);
        }

        syncValidity();

        return input;
    }

    function updateTotal() {
        const currentRows = rows();
        const ratios = currentRows.map((row) => parseInt(row.querySelector('input[type="number"]').value, 10) || 0);
        const total = ratios.reduce((sum, ratio) => sum + ratio, 0);

        totalBar.hidden = false;
        totalBar.replaceChildren();

        ratios.forEach((ratio, index) => {
            if (ratio > 0) {
                const segment = document.createElement('span');
                segment.className = 'mix-total-segment';
                segment.style.width = `${Math.min(ratio, 100)}%`;
                segment.style.flexShrink = total > 100 ? '1' : '0';
                segment.style.background = barColors[index % barColors.length];
                totalBar.append(segment);
            }
        });

        totalText.classList.toggle('is-complete', total === 100);

        if (total === 100) {
            totalText.textContent = 'Suma 100%: la mezcla está completa.';
        } else if (total > 100) {
            totalText.textContent = `Sobran ${total - 100}%: la suma debe ser exactamente 100%.`;
        } else if (total === 0) {
            totalText.textContent = 'Reparte 100% entre los sabores.';
        } else {
            totalText.textContent = `Faltan ${100 - total}% para llegar a 100%.`;
        }

        currentRows.forEach((row) => {
            row.querySelector('.remove-row').disabled = currentRows.length === 1;
        });
    }

    rows().forEach(enhanceRow);
    updateTotal();

    document.getElementById('add-row').addEventListener('click', () => {
        const row = template.content.firstElementChild.cloneNode(true);
        list.append(row);
        const input = enhanceRow(row);
        updateTotal();
        input.focus();
    });

    list.addEventListener('input', (event) => {
        if (event.target.type === 'number') {
            updateTotal();
        }
    });

    list.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-row');
        if (button && rows().length > 1) {
            button.closest('.flavor-row').remove();
            updateTotal();
        }
    });
})();
</script>
@endpush
