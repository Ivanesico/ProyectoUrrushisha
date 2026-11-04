@extends('layouts.app')

@section('title', 'Marcas')

@section('content')
<div class="container">
    <h1 class="h3 fw-bold mb-4">Marcas</h1>

    @if($brands->isEmpty())
    <p class="text-secondary mb-0">No hay marcas disponibles.</p>
    @else
    <div class="list-group">
        @foreach($brands as $brand)
        <a href="{{ route('brands.show', $brand) }}" class="list-group-item list-group-item-action">
            {{ $brand->name }}
        </a>
        @endforeach
    </div>
    @endif
</div>
@endsection
