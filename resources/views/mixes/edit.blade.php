@extends('layouts.app')

@section('title', 'Editar mezcla')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h1 class="fw-bold text-white">Editar mezcla</h1>
        <p class="text-light-emphasis">Modifica tu mezcla y ajusta los porcentajes.</p>
    </div>

    @if($errors->any())
    <div class="alert alert-danger rounded-4">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('mixes.update', $mix) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card p-4 rounded-4 border-0 shadow-sm">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name', $mix->name) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $mix->notes) }}</textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="is_public" class="form-check-input" value="1" {{ old('is_public', $mix->is_public) ? 'checked' : '' }}>
                        <label class="form-check-label">Hacer pública</label>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card p-4 rounded-4 border-0 shadow-sm">
                    <h5 class="fw-bold mb-3">Sabores</h5>

                    <div id="flavor-list">
                        @foreach($mix->items as $item)
                        <div class="row g-2 align-items-center flavor-row mb-2">
                            <div class="col-md-6">
                                <select name="flavors[]" class="form-select" required>
                                    <option value="">Selecciona sabor</option>
                                    @foreach($flavors as $flavor)
                                    <option value="{{ $flavor->id }}" {{ $item->flavor_id == $flavor->id ? 'selected' : '' }}>
                                        {{ $flavor->name }} ({{ $flavor->brand->name ?? '-' }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <input type="number" name="ratios[]" class="form-control" placeholder="%" min="1" max="100" required value="{{ $item->ratio }}">
                            </div>

                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger w-100 remove-row">X</button>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-row" class="btn btn-outline-dark mt-3">
                        + Añadir sabor
                    </button>

                    <div class="mt-3 small text-muted">
                        La suma total de porcentajes debe ser 100%.
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-dark px-4">Guardar cambios</button>
            <a href="{{ route('mixes.index') }}" class="btn btn-outline-light px-4">Cancelar</a>
        </div>
    </form>

    <template id="flavor-row-template">
        <div class="row g-2 align-items-center flavor-row mb-2">
            <div class="col-md-6">
                <select name="flavors[]" class="form-select" required>
                    <option value="">Selecciona sabor</option>
                    @foreach($flavors as $flavor)
                    <option value="{{ $flavor->id }}">
                        {{ $flavor->name }} ({{ $flavor->brand->name ?? '-' }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <input type="number" name="ratios[]" class="form-control" placeholder="%" min="1" max="100" required>
            </div>

            <div class="col-md-2">
                <button type="button" class="btn btn-danger w-100 remove-row">X</button>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
    const addBtn = document.getElementById('add-row');
    const list = document.getElementById('flavor-list');
    const template = document.getElementById('flavor-row-template');

    addBtn.addEventListener('click', () => {
        const clone = template.content.cloneNode(true);
        list.appendChild(clone);
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            const rows = document.querySelectorAll('.flavor-row');
            if (rows.length > 1) {
                e.target.closest('.flavor-row').remove();
            }
        }
    });
</script>
@endpush
