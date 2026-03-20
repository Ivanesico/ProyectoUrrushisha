@extends('layouts.app')

@section('title', 'Panel Admin')

@section('content')
<div class="container py-5">
    <div class="mb-4">
        <h1 class="fw-bold text-white">Panel de administración</h1>
        <p class="text-light-emphasis">Gestiona marcas, categorías y sabores.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow rounded-4 h-100">
                <div class="card-body p-4">
                    <h3 class="fw-bold">Marcas</h3>
                    <p class="text-muted">Crear, ver y gestionar marcas.</p>
                    <a href="{{ route('admin.brands.index') }}" class="btn btn-dark rounded-pill px-4">
                        Ir a marcas
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow rounded-4 h-100">
                <div class="card-body p-4">
                    <h3 class="fw-bold">Categorías</h3>
                    <p class="text-muted">Crear y gestionar categorías.</p>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-dark rounded-pill px-4">
                        Ir a categorías
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow rounded-4 h-100">
                <div class="card-body p-4">
                    <h3 class="fw-bold">Sabores</h3>
                    <p class="text-muted">Crear y administrar sabores.</p>
                    <a href="{{ route('admin.flavors.index') }}" class="btn btn-dark rounded-pill px-4">
                        Ir a sabores
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
