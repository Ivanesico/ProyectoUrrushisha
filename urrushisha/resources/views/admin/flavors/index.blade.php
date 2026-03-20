@extends('layouts.app')

@section('title', 'Administrar sabores')

@section('content')
<div class="container py-5">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Sabores</h1>
        <a href="{{ route('admin.flavors.create') }}" class="btn btn-dark">Añadir sabor</a>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Marca</th>
                            <th>Categoría</th>
                            <th>Tipo</th>
                            <th>Público</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($flavors as $flavor)
                            <tr>
                                <td>{{ $flavor->id }}</td>
                                <td>{{ $flavor->name }}</td>
                                <td>{{ $flavor->brand->name ?? '-' }}</td>
                                <td>{{ $flavor->category->name ?? '-' }}</td>
                                <td>{{ $flavor->tobacco_type ?? '-' }}</td>
                                <td>
                                    @if($flavor->is_public)
                                        <span class="badge text-bg-success">Sí</span>
                                    @else
                                        <span class="badge text-bg-secondary">No</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">No hay sabores registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

