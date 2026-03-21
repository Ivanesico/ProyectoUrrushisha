@extends('layouts.app')

@section('title', 'Editar categoría')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 fw-bold mb-4">Editar categoría</h1>

                    <form action="{{ route('admin.categories.update',$category) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre de la categoría</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name',$category->name) }}">
                            
                            @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-dark px-4">Actualizar</button>
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary px-4">Cancelar</a>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger mt-4 mb-0">
                                Revisa el formulario.
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection