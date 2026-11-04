@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="row justify-content-center mt-5 mx-0">
    <div class="col-md-6 col-lg-4">
        <div class="mb-4 d-flex justify-content-center">
            <img src="{{ asset('images/logo-urrushisha-grande.png') }}" alt="UrruShisha" width="200" style="height: auto;">
        </div>

        

        @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
        @endif

        <form class="auth-form" method="POST" action="{{ route('auth.login') }}">
            @csrf

            <div class="mb-3 mt-2">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" required value="{{ is_string(old('email')) ? old('email') : '' }}">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn auth-submit w-100 mt-3">
                Iniciar sesión
            </button>
        </form>

        <div class="auth-switch text-center mt-4">
            <p>¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a></p>
        </div>
    </div>
</div>
@endsection

@push('head')
<style>
    body {
        padding-top: 2.5rem;
        background:
            radial-gradient(circle at top left, rgba(139, 92, 246, 0.18) 0%, transparent 28%),
            radial-gradient(circle at bottom right, rgba(236, 72, 153, 0.16) 0%, transparent 25%),
            linear-gradient(135deg, #0f1117 0%, #171a22 45%, #111318 100%);
    }

    .auth-form {
        padding: 1.5rem;
        border: 1px solid var(--us-line);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.05);
    }

    .auth-submit {
        padding: 0.65rem 1.5rem;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        color: #ffffff;
        font-weight: 700;
    }

    .auth-submit:hover,
    .auth-submit:focus-visible {
        background: linear-gradient(135deg, #7c3aed, #db2777);
        color: #ffffff;
    }

    .auth-switch {
        color: var(--us-text);
    }

    .auth-switch a {
        color: #f9a8d4;
        font-weight: 600;
    }
</style>
@endpush