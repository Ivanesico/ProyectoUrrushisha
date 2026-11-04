@extends('layouts.app')

@section('title', 'Crear mezcla')

@section('content')
<div class="container py-4">
    <h1 class="mix-form-title">Crear mezcla</h1>
    <p class="mix-form-subtitle">Elige sabores de tus favoritos y reparte los porcentajes.</p>

    @if($errors->any())
    <div class="alert alert-danger rounded-4">
        <ul class="mb-0">
            @foreach(array_unique($errors->all()) as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('mixes.store') }}" method="POST">
        @csrf

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="mix-panel">
                    <h2 class="mix-panel-title">Datos</h2>

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre</label>
                        <input type="text" name="name" id="name" class="form-control" required value="{{ is_array(old('name')) ? '' : old('name') }}">
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3">{{ is_array(old('notes')) ? '' : old('notes') }}</textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="is_public" id="is_public" class="form-check-input" value="1" {{ old('is_public') ? 'checked' : '' }}>
                        <label for="is_public" class="form-check-label">Hacer pública</label>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="mix-panel">
                    <h2 class="mix-panel-title">Sabores</h2>
                    @include('mixes._flavor-picker')
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn mix-save">Guardar mezcla</button>
            <a href="{{ route('mixes.index') }}" class="mix-cancel">Cancelar</a>
        </div>
    </form>
</div>
@endsection
