<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
            @include('svg.logo', ['width' => 120])
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link {{ request()->is('/') ? 'active' : '' }}"
                        href="{{ url('/') }}">
                        Inicio
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->is('/mis-reservas') ? 'active' : '' }}"
                        href="{{ url('/mis-reservas') }}">
                        Mi reserva
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('/plaza-publicada') ? 'active' : '' }}"
                        href="{{ url('/plaza-publicada') }}">
                        Plaza publicada
                    </a>
                </li>
                <li class="nav-item">
                </li>
            </ul>

            @auth
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <a class="nav-link" href="#">
                    Tokens: {{ Auth::user()->saldo_tokens }}
                </a>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ Auth::user()->nombre }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <!-- <li>
                            <a class="dropdown-item" href="{{-- TODO: Ruta a mi perfil --}}">Mi perfil</a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li> -->
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">Cerrar sesión</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
            @endauth
        </div>
    </div>
</nav>