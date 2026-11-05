{{-- Barra de porcentajes y lista de sabores de una mezcla. $size: 'card' (listado) o 'modal' (ventana). --}}
@php
    $barColors = ['#8b5cf6', '#ec4899', '#f59e0b', '#14b8a6', '#38bdf8', '#a3e635'];
    $size = ($size ?? 'card') === 'modal' ? 'modal' : 'card';
@endphp
<div class="mix-items is-{{ $size }}">
    @if($mix->items->isNotEmpty())
    <div class="mix-bar" aria-hidden="true">
        @foreach($mix->items as $item)
        <span style="flex-grow: {{ (int) $item->ratio }}; background: {{ $barColors[$loop->index % count($barColors)] }};"></span>
        @endforeach
    </div>
    @endif

    <div class="mix-items-list">
        @forelse($mix->items as $item)
        <div class="mix-item-row">
            <div class="mix-flavor-thumb" style="--mix-color: {{ $barColors[$loop->index % count($barColors)] }};">
                <div class="mix-flavor-placeholder">{{ $item->flavor ? $item->flavor->initial : '?' }}</div>
                @if($item->flavor && $item->flavor->image_url)
                <img src="{{ asset('storage/' . $item->flavor->image_url) }}" alt="" loading="lazy" onerror="this.remove()">
                @endif
            </div>

            <div class="mix-flavor-info">
                <div class="mix-flavor-name">{{ $item->flavor->name ?? 'Sabor no disponible' }}</div>
                <div class="mix-flavor-brand">{{ $item->flavor->brand->name ?? 'Sin marca' }}</div>
            </div>

            <div class="mix-ratio">{{ $item->ratio }}%</div>
        </div>
        @empty
        <p class="mix-card-muted mb-0">Esta mezcla no tiene sabores.</p>
        @endforelse
    </div>
</div>
