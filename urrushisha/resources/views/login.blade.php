@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-4">

        

        @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('auth.login') }}">
            @csrf

            <div class="mb-3 mt-2">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" required value="{{ old('email') }}">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-4">
                Iniciar sesión
            </button>
        </form>

        <div class="text-center mt-4">
            <p>¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a></p>
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

@include('js.login')

@endpush