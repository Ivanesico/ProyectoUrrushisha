{{--
    Spec 010: filas de precios (cantidad en gramos + precio en euros) del formulario de sabor.
    Se incluye en crear y editar. Muestra los precios guardados (o lo escrito, tras un error)
    y siempre una fila vacía más; sin JavaScript se envían las filas que haya.
    Variable opcional: $flavor (al editar).
--}}
@php
    if ($errors->any()) {
        // Tras un error, lo que escribió el admin (solo valores de texto).
        $oldPrices = old('prices');
        $priceRows = collect(is_array($oldPrices) ? $oldPrices : [])
            ->map(fn ($row) => [
                'grams' => is_array($row) && is_scalar($row['grams'] ?? null) ? (string) $row['grams'] : '',
                'price' => is_array($row) && is_scalar($row['price'] ?? null) ? (string) $row['price'] : '',
            ])
            ->values()
            ->all();
    } else {
        // Precios guardados, con coma decimal y sin separador de miles (el campo admite coma o punto).
        $priceRows = isset($flavor)
            ? $flavor->prices->map(fn ($price) => [
                'grams' => (string) $price->grams,
                'price' => number_format((float) $price->price, 2, ',', ''),
            ])->all()
            : [];
    }

    $priceRows[] = ['grams' => '', 'price' => ''];
    $priceErrors = collect($errors->get('prices*'))->flatten()->unique();
@endphp

<div class="col-12">
    <h2 class="h6 fw-bold mb-1">Precios</h2>
    <p class="small text-body-secondary mb-2">Cada cantidad en gramos con su precio. Las filas vacías se ignoran.</p>

    <div id="price-list">
        @foreach($priceRows as $index => $row)
        <div class="price-row">
            <div class="input-group">
                <input type="number" name="prices[{{ $index }}][grams]" class="form-control" min="1" max="5000" step="1" placeholder="50" aria-label="Cantidad en gramos" value="{{ $row['grams'] }}">
                <span class="input-group-text">g</span>
            </div>
            <div class="input-group">
                <input type="text" inputmode="decimal" name="prices[{{ $index }}][price]" class="form-control" placeholder="12,50" aria-label="Precio en euros" value="{{ $row['price'] }}">
                <span class="input-group-text">€</span>
            </div>
            <button type="button" class="btn btn-outline-danger remove-price" aria-label="Quitar precio">&times;</button>
        </div>
        @endforeach
    </div>

    <button type="button" id="add-price" class="btn btn-outline-secondary btn-sm mt-1">+ Añadir precio</button>

    @foreach($priceErrors as $message)
    <div class="text-danger small mt-1">{{ $message }}</div>
    @endforeach

    <template id="price-row-template">
        <div class="price-row">
            <div class="input-group">
                <input type="number" name="prices[__INDEX__][grams]" class="form-control" min="1" max="5000" step="1" placeholder="50" aria-label="Cantidad en gramos">
                <span class="input-group-text">g</span>
            </div>
            <div class="input-group">
                <input type="text" inputmode="decimal" name="prices[__INDEX__][price]" class="form-control" placeholder="12,50" aria-label="Precio en euros">
                <span class="input-group-text">€</span>
            </div>
            <button type="button" class="btn btn-outline-danger remove-price" aria-label="Quitar precio">&times;</button>
        </div>
    </template>
</div>

@push('head')
<style>
    .price-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 2.5rem;
        gap: 0.5rem;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .price-row .remove-price {
        padding: 0.375rem 0;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const list = document.getElementById('price-list');
    const template = document.getElementById('price-row-template');
    // Índices nuevos a partir de las filas pintadas; los huecos al quitar filas no importan.
    let nextIndex = list.querySelectorAll('.price-row').length;

    document.getElementById('add-price').addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
        list.insertAdjacentHTML('beforeend', html);
        list.lastElementChild.querySelector('input').focus();
    });

    list.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-price');
        if (button) {
            button.closest('.price-row').remove();
        }
    });
})();
</script>
@endpush
