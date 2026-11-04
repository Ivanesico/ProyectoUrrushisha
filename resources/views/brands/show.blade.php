@extends('layouts.app')

@section('title', $brand->name)

@section('content')
<div class="container">
    <h1 class="h3 fw-bold mb-4 text-break">{{ $brand->name }}</h1>

    @if($flavors->isEmpty())
    <p class="text-secondary mb-0">Esta marca no tiene sabores disponibles.</p>
    @else
    <ul class="list-group">
        @foreach($flavors as $flavor)
        <li class="list-group-item">{{ $flavor->name }}</li>
        @endforeach
    </ul>
    @endif
</div>
@endsection
