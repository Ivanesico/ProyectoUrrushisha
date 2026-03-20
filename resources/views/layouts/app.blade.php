<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'UrruShisha')</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">    @stack('head')

        <style>
            body {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
            }

            main {
                flex-grow: 1;
            }
        </style>
    </head>

    <body>
        @if (!request()->routeIs('login','register') )
        {{-- NAVBAR --}}
        <nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-secondary">
            <div class="container">
                {{-- LOGO / NOMBRE IZQUIERDA --}}
                <a class="navbar-brand fw-bold text-white" href="{{ route('home') }}">
                    URRUSHISHA
                </a>

                <div class="d-flex align-items-center gap-2">
                    @auth
                    @if(auth()->user()->is_admin)
                    <a href="{{ route('admin.index') }}" class="btn btn-dark border rounded-pill px-3">
                        ⚙️ Admin
                    </a>
                    @endif

                    <span class="text-white small">
                        {{ auth()->user()->name }}
                    </span>

                    <form action="{{ route('logout') }}" method="POST" class="mb-0">
                        @csrf
                        <button class="btn btn-outline-danger btn-sm">
                            Logout
                        </button>
                    </form>
                    @endauth
                </div>
            </div>

        </nav>
        @endif
        {{-- CONTENIDO --}}
        <main class="flex-grow-1 py-4">
            <div class="container">
                @yield('content')
            </div>
        </main>

        {{-- SCRIPTS --}}
        @stack('scripts')
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body>

</html>
