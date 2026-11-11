{{--
    Filas de sabor y porcentaje de los formularios de mezcla, con buscador en el catálogo (spec 009).
    El <select> sigue siendo el campo que se envía. El buscador lo sustituye con JavaScript;
    sin JavaScript, el formulario funciona igual con el desplegable, agrupado en favoritos y resto.
    Variables: $flavorGroups (grupos con label, favorite y flavors), $rows.
--}}
<div id="flavor-list">
    @foreach($rows as $row)
    <div class="mix-row flavor-row">
        <div class="mix-row-flavor">
            <select name="flavors[]" class="form-select" aria-label="Sabor" required>
                <option value="">Selecciona sabor</option>
                @foreach($flavorGroups as $group)
                <optgroup label="{{ $group['label'] }}">
                    @foreach($group['flavors'] as $flavor)
                    <option value="{{ $flavor->id }}" @selected($row['flavor_id'] == $flavor->id) data-name="{{ $flavor->name }}" data-brand="{{ $flavor->brand->name ?? '' }}" data-image="{{ $flavor->image_url ? asset('storage/' . $flavor->image_url) : '' }}"@if($group['favorite']) data-favorite="1"@endif>
                        {{ $flavor->name }} ({{ $flavor->brand->name ?? '-' }})
                    </option>
                    @endforeach
                </optgroup>
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
    <div class="mix-total-bar" id="mix-total-bar" hidden></div>
    <p class="mix-total-text" id="mix-total-text" aria-live="polite">La suma de los porcentajes debe ser exactamente 100%.</p>
</div>

<template id="flavor-row-template">
    <div class="mix-row flavor-row">
        <div class="mix-row-flavor">
            <select name="flavors[]" class="form-select" aria-label="Sabor" required>
                <option value="">Selecciona sabor</option>
                @foreach($flavorGroups as $group)
                <optgroup label="{{ $group['label'] }}">
                    @foreach($group['flavors'] as $flavor)
                    <option value="{{ $flavor->id }}" data-name="{{ $flavor->name }}" data-brand="{{ $flavor->brand->name ?? '' }}" data-image="{{ $flavor->image_url ? asset('storage/' . $flavor->image_url) : '' }}"@if($group['favorite']) data-favorite="1"@endif>
                        {{ $flavor->name }} ({{ $flavor->brand->name ?? '-' }})
                    </option>
                    @endforeach
                </optgroup>
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

    /* Spec 009: marca de favorito en el buscador */
    .mix-combo-favorite {
        color: #f472b6;
        font-size: 0.85em;
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
        position: relative;
        height: 0.6rem;
        margin-bottom: 0.45rem;
    }

    .mix-total-bar[hidden] {
        display: none;
    }

    /* Spec 014: con tiradores, la barra es más alta y deja sitio a su zona táctil. */
    .mix-total-bar.is-adjustable {
        height: 0.75rem;
        margin: 1.4rem 0 1.3rem;
    }

    .mix-total-fill {
        display: flex;
        height: 100%;
        overflow: hidden;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.1);
    }

    .mix-total-segment {
        box-sizing: border-box;
        min-width: 0;
    }

    .mix-total-segment + .mix-total-segment {
        border-left: 2px solid #15171e;
    }

    .mix-total-handle {
        position: absolute;
        top: 50%;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        transform: translate(-50%, -50%);
        cursor: ew-resize;
        touch-action: none;
        user-select: none;
        -webkit-user-select: none;
    }

    .mix-total-handle::before {
        content: '';
        width: 1.35rem;
        height: 1.35rem;
        border: 3px solid #171a22;
        border-radius: 50%;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
    }

    .mix-total-handle:focus {
        outline: none;
        z-index: 2;
    }

    .mix-total-handle:focus-visible::before,
    .mix-total-handle.is-dragging::before {
        box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.6);
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
        input.placeholder = 'Escribe el nombre de un sabor';
        input.autocomplete = 'off';
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-label', 'Buscar sabor en el catálogo');
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
                empty.textContent = 'Ningún sabor disponible coincide con la búsqueda.';
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

                // Spec 009: los favoritos salen primero y llevan un ♥.
                if (option.dataset.favorite) {
                    const heart = document.createElement('span');
                    heart.className = 'mix-combo-favorite';
                    heart.setAttribute('aria-hidden', 'true');
                    heart.textContent = '♥';

                    const label = document.createElement('span');
                    label.className = 'visually-hidden';
                    label.textContent = ' (favorito)';

                    name.append(' ', heart, label);
                }

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

    // Spec 014: reparto automático de porcentajes.
    const ratioInput = (row) => row.querySelector('input[type="number"]');
    const sum = (values) => values.reduce((total, value) => total + value, 0);

    // Porcentaje entero, o NaN si está vacío o no es entero.
    function ratioValue(value) {
        const number = Number(value);
        return String(value ?? '').trim() !== '' && Number.isInteger(number) ? number : NaN;
    }

    const currentRatios = () => rows().map((row) => ratioValue(ratioInput(row).value));

    // El reparto solo actúa cuando todas las filas tienen un entero de 1 o más y suman 100.
    const isComplete = (ratios) => ratios.length > 0 && ratios.every((ratio) => ratio >= 1) && sum(ratios) === 100;

    // Guarda los valores confirmados: al cambiar un número se compara con lo que había antes.
    function commit() {
        rows().forEach((row) => {
            row.dataset.committed = ratioInput(row).value;
        });
    }

    function flavorName(row, index) {
        const select = row.querySelector('select');
        return select.value !== '' ? select.options[select.selectedIndex].dataset.name : `sabor ${index + 1}`;
    }

    // Mueve el reparto entre las filas index e index + 1: una sube y la otra baja lo mismo.
    function moveSplit(index, value) {
        const currentRows = rows();
        const first = ratioInput(currentRows[index]);
        const second = ratioInput(currentRows[index + 1]);
        const pool = ratioValue(first.value) + ratioValue(second.value);
        const next = Math.min(Math.max(value, 1), pool - 1);

        if (next !== ratioValue(first.value)) {
            first.value = next;
            second.value = pool - next;
            updateTotal();
        }
    }

    const totalFill = document.createElement('div');
    totalFill.className = 'mix-total-fill';
    totalBar.append(totalFill);

    function createHandle() {
        const handle = document.createElement('div');
        handle.className = 'mix-total-handle';
        handle.tabIndex = 0;
        handle.setAttribute('role', 'slider');
        handle.setAttribute('aria-orientation', 'horizontal');
        handle.setAttribute('aria-valuemin', '1');

        handle.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) {
                return;
            }
            event.preventDefault();
            handle.focus();
            handle.setPointerCapture(event.pointerId);
            handle.classList.add('is-dragging');
        });

        handle.addEventListener('pointermove', (event) => {
            if (!handle.hasPointerCapture(event.pointerId)) {
                return;
            }
            const index = Number(handle.dataset.index);
            const rect = totalFill.getBoundingClientRect();
            const percent = (event.clientX - rect.left) / rect.width * 100;
            const before = sum(currentRatios().slice(0, index));
            moveSplit(index, Math.round(percent - before));
        });

        handle.addEventListener('lostpointercapture', () => {
            handle.classList.remove('is-dragging');
            commit();
        });

        handle.addEventListener('keydown', (event) => {
            const step = { ArrowLeft: -1, ArrowDown: -1, ArrowRight: 1, ArrowUp: 1 }[event.key];
            if (!step) {
                return;
            }
            event.preventDefault();
            const index = Number(handle.dataset.index);
            moveSplit(index, currentRatios()[index] + step);
            commit();
        });

        return handle;
    }

    // Reutiliza los segmentos y tiradores para no perder el foco ni el arrastre al redibujar.
    function renderBar(currentRows, ratios, total) {
        while (totalFill.children.length < ratios.length) {
            const segment = document.createElement('span');
            segment.className = 'mix-total-segment';
            segment.setAttribute('aria-hidden', 'true');
            totalFill.append(segment);
        }
        while (totalFill.children.length > ratios.length) {
            totalFill.lastElementChild.remove();
        }

        [...totalFill.children].forEach((segment, index) => {
            segment.hidden = ratios[index] <= 0;
            segment.style.width = `${Math.min(Math.max(ratios[index], 0), 100)}%`;
            segment.style.flexShrink = total > 100 ? '1' : '0';
            segment.style.background = barColors[index % barColors.length];
        });

        // Tiradores solo si la mezcla suma 100; si no, la barra es una vista previa.
        const adjustable = currentRows.length > 1 && isComplete(currentRatios());
        const handles = [...totalBar.querySelectorAll('.mix-total-handle')];
        const handleCount = adjustable ? currentRows.length - 1 : 0;

        while (handles.length < handleCount) {
            const handle = createHandle();
            totalBar.append(handle);
            handles.push(handle);
        }
        while (handles.length > handleCount) {
            handles.pop().remove();
        }

        totalBar.classList.toggle('is-adjustable', adjustable);

        let position = 0;
        handles.forEach((handle, index) => {
            const nameA = flavorName(currentRows[index], index);
            const nameB = flavorName(currentRows[index + 1], index + 1);
            position += ratios[index];

            handle.dataset.index = index;
            handle.style.left = `${position}%`;
            handle.setAttribute('aria-label', `Reparto entre ${nameA} y ${nameB}`);
            handle.setAttribute('aria-valuemax', ratios[index] + ratios[index + 1] - 1);
            handle.setAttribute('aria-valuenow', ratios[index]);
            handle.setAttribute('aria-valuetext', `${nameA} ${ratios[index]}%, ${nameB} ${ratios[index + 1]}%`);
        });
    }

    // Porcentaje del sabor nuevo: lo que falta hasta 100 o la mitad de la última fila.
    function ratioForNewRow() {
        const total = sum(rows().map((row) => parseInt(ratioInput(row).value, 10) || 0));
        const ratios = currentRatios();

        if (total >= 0 && total < 100) {
            return String(100 - total);
        }

        if (!isComplete(ratios)) {
            return '';
        }

        // Si la última tiene 1 %, la mitad sale del sabor con más % (el primero, si empatan).
        const last = ratios.length - 1;
        const donor = ratios[last] >= 2 ? last : ratios.indexOf(Math.max(...ratios));

        if (ratios[donor] < 2) {
            return '';
        }

        const share = Math.floor(ratios[donor] / 2);
        ratioInput(rows()[donor]).value = ratios[donor] - share;

        return String(share);
    }

    // Al confirmar un número, la diferencia la absorbe la fila siguiente (la anterior, si es la última).
    function onRatioChange(row) {
        const currentRows = rows();
        const index = currentRows.indexOf(row);
        const committed = currentRows.map((current) => ratioValue(current.dataset.committed));
        const value = ratioValue(ratioInput(row).value);

        if (currentRows.length > 1 && isComplete(committed) && !Number.isNaN(value)) {
            const neighbor = index < currentRows.length - 1 ? index + 1 : index - 1;
            const pool = committed[index] + committed[neighbor];
            const next = Math.min(Math.max(value, 1), pool - 1);

            ratioInput(row).value = next;
            ratioInput(currentRows[neighbor]).value = pool - next;
        }

        commit();
        updateTotal();
    }

    function updateTotal() {
        const currentRows = rows();
        const ratios = currentRows.map((row) => parseInt(ratioInput(row).value, 10) || 0);
        const total = sum(ratios);

        totalBar.hidden = false;
        renderBar(currentRows, ratios, total);

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

        // Una mezcla necesita al menos 2 sabores: con 2 filas o menos no se puede quitar ninguna.
        currentRows.forEach((row) => {
            row.querySelector('.remove-row').disabled = currentRows.length <= 2;
        });
    }

    rows().forEach(enhanceRow);
    commit();
    updateTotal();

    document.getElementById('add-row').addEventListener('click', () => {
        const ratio = ratioForNewRow();
        const row = template.content.firstElementChild.cloneNode(true);
        ratioInput(row).value = ratio;
        list.append(row);
        const input = enhanceRow(row);
        commit();
        updateTotal();
        input.focus();
    });

    list.addEventListener('input', (event) => {
        if (event.target.type === 'number') {
            updateTotal();
        }
    });

    list.addEventListener('change', (event) => {
        if (event.target.type === 'number') {
            onRatioChange(event.target.closest('.flavor-row'));
        }
    });

    list.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-row');
        if (button && rows().length > 2) {
            const currentRows = rows();
            const row = button.closest('.flavor-row');
            const index = currentRows.indexOf(row);
            const ratios = currentRatios();

            // Si sumaba 100, su % pasa a la fila anterior (a la siguiente, si se quita la primera).
            if (isComplete(ratios)) {
                const heir = index > 0 ? index - 1 : index + 1;
                ratioInput(currentRows[heir]).value = ratios[heir] + ratios[index];
            }

            row.remove();
            commit();
            updateTotal();
        }
    });
})();
</script>
@endpush
