{{--
    Desplegables de filtros con el estilo de la página.
    Convierte cada <select data-filter-dropdown> en un dropdown de Bootstrap.
    El <select> queda oculto y sigue siendo el campo que se envía; sin JS se ve el nativo.
--}}
@once
@push('head')
<style>
    .filter-dropdown {
        position: relative;
    }

    .filter-dropdown-toggle {
        display: block;
        width: 100%;
        overflow: hidden;
        text-align: left;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .filter-dropdown-menu {
        min-width: 100%;
        max-width: min(20rem, calc(100vw - 2rem));
        max-height: 18rem;
        overflow-y: auto;
        padding: 0.35rem;
        border: 1px solid var(--us-line);
        border-radius: 16px;
        background: #1b1426;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.45);
        overscroll-behavior: contain;
    }

    .filter-dropdown-menu .dropdown-item {
        padding: 0.5rem 0.75rem;
        border-radius: 10px;
        color: var(--us-text);
        font-weight: 500;
        white-space: normal;
    }

    .filter-dropdown-menu .dropdown-item:hover,
    .filter-dropdown-menu .dropdown-item:focus {
        background: var(--us-hover);
        color: var(--us-text-strong);
    }

    .filter-dropdown-menu .dropdown-item.active {
        background: var(--us-ember);
        color: var(--us-text-strong);
        font-weight: 700;
    }

    .filter-dropdown-menu .dropdown-item:focus-visible {
        outline: 2px solid #f472b6;
        outline-offset: -2px;
    }
</style>
@endpush

@push('scripts')
<script>
    function initFilterDropdowns() {
        // Sin el JS de Bootstrap no hay dropdown: se deja el <select> nativo.
        if (!window.bootstrap || !bootstrap.Dropdown) return;

        document.querySelectorAll('select[data-filter-dropdown]').forEach(function (select) {
            const buttonId = select.id + '-dropdown';
            const label = document.querySelector('label[for="' + select.id + '"]');

            const wrapper = document.createElement('div');
            wrapper.className = 'dropdown filter-dropdown';

            const button = document.createElement('button');
            button.type = 'button';
            button.id = buttonId;
            // Copia las clases del <select> para que se vea igual que el campo original.
            button.className = select.className + ' filter-dropdown-toggle';
            button.setAttribute('data-bs-toggle', 'dropdown');
            button.setAttribute('aria-expanded', 'false');

            const menu = document.createElement('ul');
            menu.className = 'dropdown-menu filter-dropdown-menu';

            Array.from(select.options).forEach(function (option) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'dropdown-item';
                item.textContent = option.textContent.trim();
                item.dataset.value = option.value;

                item.addEventListener('click', function () {
                    const changed = select.value !== option.value;
                    select.value = option.value;
                    refresh();
                    if (changed) {
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    button.focus();
                });

                const li = document.createElement('li');
                li.appendChild(item);
                menu.appendChild(li);
            });

            function refresh() {
                const selected = select.options[select.selectedIndex];
                button.textContent = selected ? selected.textContent.trim() : '';
                menu.querySelectorAll('.dropdown-item').forEach(function (item) {
                    const active = item.dataset.value === select.value;
                    item.classList.toggle('active', active);
                    if (active) {
                        item.setAttribute('aria-current', 'true');
                    } else {
                        item.removeAttribute('aria-current');
                    }
                });
            }

            if (label) {
                if (!label.id) label.id = select.id + '-label';
                label.htmlFor = buttonId;
                button.setAttribute('aria-labelledby', label.id + ' ' + buttonId);
            }

            // Al abrir, la opción elegida queda a la vista aunque la lista sea larga.
            button.addEventListener('shown.bs.dropdown', function () {
                const active = menu.querySelector('.dropdown-item.active');
                if (active) active.scrollIntoView({ block: 'nearest' });
            });

            wrapper.appendChild(button);
            wrapper.appendChild(menu);
            select.classList.add('d-none');
            select.setAttribute('tabindex', '-1');
            select.after(wrapper);
            refresh();
        });
    }

    document.addEventListener('DOMContentLoaded', initFilterDropdowns);
</script>
@endpush
@endonce
