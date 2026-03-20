@extends('layouts.app')

@section('title', 'Crear mezcla')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h1 class="fw-bold text-white">Crear mezcla</h1>
        <p class="text-light-emphasis">Combina sabores y crea tu mezcla perfecta.</p>
    </div>

    <form action="{{ route('mixes.store') }}" method="POST">
        @csrf

        <div class="row g-4">

            {{-- DATOS PRINCIPALES --}}
            <div class="col-lg-4">
                <div class="card p-4 rounded-4 border-0 shadow-sm">

                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="is_public" class="form-check-input" value="1">
                        <label class="form-check-label">Hacer pública</label>
                    </div>

                </div>
            </div>

            {{-- SABORES --}}
            <div class="col-lg-8">
                <div class="card p-4 rounded-4 border-0 shadow-sm">

                    <h5 class="fw-bold mb-3">Selecciona sabores</h5>

                    <div id="flavor-list">
                        {{-- FILA INICIAL --}}
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
                    </div>

                    <button type="button" id="add-row" class="btn btn-outline-light mt-3">
                        + Añadir sabor
                    </button>

                </div>
            </div>

        </div>

        <div class="mt-4">
            <button class="btn btn-dark px-4">Guardar mezcla</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const addBtn = document.getElementById('add-row');
    const list = document.getElementById('flavor-list');

    addBtn.addEventListener('click', () => {
        const row = document.querySelector('.flavor-row').cloneNode(true);

        row.querySelectorAll('input').forEach(input => input.value = '');
        row.querySelectorAll('select').forEach(select => select.selectedIndex = 0);

        list.appendChild(row);
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
