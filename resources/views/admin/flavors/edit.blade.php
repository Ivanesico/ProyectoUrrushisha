@extends('layouts.app')

@section('title', 'Editar sabor')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 fw-bold mb-4">Editar sabor</h1>

                    <form action="{{ route('admin.flavors.update', $flavor) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="brand_id" class="form-label">Marca</label>
                                <select name="brand_id" id="brand_id" class="form-select">
                                    <option value="">Selecciona una marca</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ old('brand_id', $flavor->brand_id) == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('brand_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Categoría</label>
                                <select name="category_id" id="category_id" class="form-select">
                                    <option value="">Selecciona una categoría</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $flavor->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="name" class="form-label">Nombre del sabor</label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $flavor->name) }}">
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="tobacco_type" class="form-label">Tipo de tabaco</label>
                                <input type="text" name="tobacco_type" id="tobacco_type" class="form-control" value="{{ old('tobacco_type', $flavor->tobacco_type) }}">
                                @error('tobacco_type')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="image" class="form-label">Imagen</label>
                                <input type="file" name="image" id="image" class="form-control" accept="image/*">
                                @error('image')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            @if($flavor->image_url)
                                <div class="col-12">
                                    <label class="form-label d-block">Imagen actual</label>
                                    <img src="{{ asset('storage/' . $flavor->image_url) }}" alt="{{ $flavor->name }}" style="max-width: 180px; border-radius: 12px;">
                                </div>
                            @endif

                            <div class="col-12">
                                <label for="ingredients_text" class="form-label">Ingredientes</label>
                                <textarea name="ingredients_text" id="ingredients_text" rows="3" class="form-control">{{ old('ingredients_text', $flavor->ingredients_text) }}</textarea>
                                @error('ingredients_text')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea name="description" id="description" rows="4" class="form-control">{{ old('description', $flavor->description) }}</textarea>
                                @error('description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_public" id="is_public" value="1" {{ old('is_public', $flavor->is_public) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_public">
                                        Hacer público
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-dark px-4">Actualizar sabor</button>
                            <a href="{{ route('admin.flavors.index') }}" class="btn btn-outline-secondary px-4">Cancelar</a>
                        </div>
                    </form>

                    @if ($errors->any())
                        <div class="alert alert-danger mt-4 mb-0">
                            Revisa los campos del formulario.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection