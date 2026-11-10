@extends('layouts.app')

@section('title', 'Tipos de tabaco')

@section('content')
{{-- Spec 012: tipos de tabaco, con el número de sabores de cada uno --}}
<div class="container py-5">
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 fw-bold mb-0">Tipos de tabaco</h1>
        <a href="{{ route('admin.tobacco-types.create') }}" class="btn btn-dark">Añadir tipo</a>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th class="text-end">Sabores</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tobaccoTypes as $type)
                        <tr>
                            <td>{{ $type->name }}</td>
                            <td class="text-end">{{ $type->flavors_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.tobacco-types.edit', $type) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Editar</a>
                            </td>
                            <td>
                                <form action="{{ route('admin.tobacco-types.destroy', $type) }}" method="POST" class="m-0" onsubmit="return confirm('¿Seguro que quieres eliminar este tipo de tabaco?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4">No hay tipos de tabaco.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
