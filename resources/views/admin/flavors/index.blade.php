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
                            <th>
                                <a href="{{ route('admin.flavors.index', [
                'sort' => 'id',
                'direction' => ($sort === 'id' && $direction === 'asc') ? 'desc' : 'asc'
            ]) }}" class="text-decoration-none text-dark">
                                    ID
                                    @if($sort === 'id')
                                    {{ $direction === 'asc' ? '↑' : '↓' }}
                                    @endif
                                </a>
                            </th>

                            <th>
                                <a href="{{ route('admin.flavors.index', [
                'sort' => 'name',
                'direction' => ($sort === 'name' && $direction === 'asc') ? 'desc' : 'asc'
            ]) }}" class="text-decoration-none text-dark">
                                    Nombre
                                    @if($sort === 'name')
                                    {{ $direction === 'asc' ? '↑' : '↓' }}
                                    @endif
                                </a>
                            </th>

                            <th>
                                <a href="{{ route('admin.flavors.index', [
                'sort' => 'brand',
                'direction' => ($sort === 'brand' && $direction === 'asc') ? 'desc' : 'asc'
            ]) }}" class="text-decoration-none text-dark">
                                    Marca
                                    @if($sort === 'brand')
                                    {{ $direction === 'asc' ? '↑' : '↓' }}
                                    @endif
                                </a>
                            </th>

                            <th>Categoría</th>
                            <th>Tipo</th>
                            <th>Público</th>
                            <th></th>
                            <th></th>
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
                            <td> 
                                <a href="{{ route('admin.flavors.edit', $flavor) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Editar</a>
                            </td>
                            <td> 
                                <form action="{{ route('admin.flavors.destroy', $flavor) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar este sabor?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">No hay sabores registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

