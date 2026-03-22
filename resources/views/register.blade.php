@extends('layouts.app')

@section('title', 'Registrarse')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-4">

        <div class="mb-5 d-flex justify-content-center text-white">
            @include('svg.logo', ['width' => 400])
        </div>


        <form action="{{ route('auth.register') }}" method="POST">
            @csrf
            <div class="mb-3 mt-2">
                <label for="name" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-4" id="submitBtn">Registrarse</button>
        </form>

        <div class="text-center mt-4">
            <p>¿Ya tienes una cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>
        </div>
    </div>
</div>
@endsection

@push('head')
<style>
    body {
        padding-top: 2.5rem;
    }
</style>
@endpush

@push('scripts')

@endpush