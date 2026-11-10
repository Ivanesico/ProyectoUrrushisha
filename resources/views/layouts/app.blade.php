<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'UrruShisha')</title>
        <link rel="icon" type="image/png" href="{{ asset('images/favicon-urrushisha.png') }}">

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

            :root {
                --us-bar: rgba(24, 16, 38, 0.86);
                --us-line: rgba(255, 255, 255, 0.09);
                --us-text: #c9c0d8;
                --us-text-strong: #ffffff;
                --us-hover: rgba(255, 255, 255, 0.07);
                --us-ember: linear-gradient(90deg, #8b5cf6, #ec4899);
            }

            .us-topbar {
                background: var(--us-bar);
                -webkit-backdrop-filter: blur(14px);
                backdrop-filter: blur(14px);
                border-bottom: 1px solid var(--us-line);
            }

            .us-topbar-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                min-height: 4rem;
            }

            .us-brand {
                display: inline-flex;
                align-items: center;
                gap: 0.55rem;
                color: var(--us-text-strong);
                font-size: 1.05rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-decoration: none;
            }

            .us-brand img {
                width: auto;
                height: 3.1rem;
                flex-shrink: 0;
            }

            /* The active section glows like a lit coal. */
            .us-link {
                position: relative;
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.5rem 0.8rem;
                border-radius: 0.65rem;
                color: var(--us-text);
                font-weight: 600;
                text-decoration: none;
                white-space: nowrap;
                transition: color 0.15s ease, background-color 0.15s ease;
            }

            .us-link:hover {
                color: var(--us-text-strong);
                background: var(--us-hover);
            }

            .us-link.is-active {
                color: var(--us-text-strong);
            }

            .us-link.is-active::after {
                content: "";
                position: absolute;
                left: 0.8rem;
                right: 0.8rem;
                bottom: -0.72rem;
                height: 3px;
                border-radius: 3px;
                background: var(--us-ember);
                box-shadow: 0 0 12px rgba(236, 72, 153, 0.75);
            }

            .us-icon {
                width: 1.2rem;
                height: 1.2rem;
                flex-shrink: 0;
                fill: none;
                stroke: currentColor;
                stroke-width: 1.8;
                stroke-linecap: round;
                stroke-linejoin: round;
            }

            .us-user {
                display: inline-flex;
                align-items: center;
                gap: 0.55rem;
                max-width: 14rem;
                padding: 0.25rem 0.6rem 0.25rem 0.25rem;
                border: 1px solid var(--us-line);
                border-radius: 999px;
                background: transparent;
                color: var(--us-text-strong);
                font-weight: 600;
            }

            .us-user:hover {
                background: var(--us-hover);
            }

            .us-avatar {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2rem;
                height: 2rem;
                flex-shrink: 0;
                border-radius: 50%;
                background: linear-gradient(135deg, #8b5cf6, #ec4899);
                color: #ffffff;
                font-weight: 800;
            }

            /* Botones de acceso para visitantes (spec 006) */
            .us-access {
                padding: 0.4rem 0.9rem;
                border-radius: 999px;
                font-size: 0.9rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .us-access-login {
                border: 1px solid var(--us-line);
                color: var(--us-text-strong);
            }

            .us-access-login:hover {
                background: var(--us-hover);
                color: var(--us-text-strong);
            }

            .us-access-register {
                border: 0;
                background: linear-gradient(135deg, #8b5cf6, #ec4899);
                color: #ffffff;
            }

            .us-access-register:hover {
                color: #ffffff;
                filter: brightness(1.08);
            }

            .us-subnav {
                display: flex;
                gap: 0.25rem;
                overflow-x: auto;
                padding: 0.6rem 0;
                scrollbar-width: none;
            }

            .us-subnav::-webkit-scrollbar {
                display: none;
            }

            .us-subnav-row {
                border-top: 1px solid var(--us-line);
            }

            .us-subnav .us-link {
                padding: 0.3rem 0.8rem;
                font-size: 0.92rem;
            }

            .us-subnav .us-link.is-active {
                background: rgba(139, 92, 246, 0.26);
            }

            .us-subnav .us-link.is-active::after {
                display: none;
            }

            .us-tabbar {
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 1030;
                display: flex;
                background: var(--us-bar);
                -webkit-backdrop-filter: blur(14px);
                backdrop-filter: blur(14px);
                border-top: 1px solid var(--us-line);
            }

            .us-tab {
                position: relative;
                flex: 1 1 0;
                min-width: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.2rem;
                padding: 0.6rem 0.15rem 0.55rem;
                color: var(--us-text);
                font-size: 0.72rem;
                font-weight: 600;
                text-decoration: none;
            }

            .us-tab .us-icon {
                width: 1.4rem;
                height: 1.4rem;
            }

            .us-tab.is-active {
                color: var(--us-text-strong);
            }

            .us-tab.is-active::before {
                content: "";
                position: absolute;
                top: -1px;
                left: 22%;
                right: 22%;
                height: 3px;
                border-radius: 0 0 3px 3px;
                background: var(--us-ember);
                box-shadow: 0 0 12px rgba(236, 72, 153, 0.75);
            }

            .us-password-toggle {
                border: 1px solid var(--bs-border-color);
                background: transparent;
                color: var(--us-text);
            }

            .us-password-toggle:hover,
            .us-password-toggle[aria-pressed="true"] {
                background: var(--us-hover);
                color: var(--us-text-strong);
            }

            .us-password-toggle:focus-visible,
            .us-link:focus-visible,
            .us-tab:focus-visible,
            .us-brand:focus-visible,
            .us-access:focus-visible,
            .us-user:focus-visible {
                outline: 2px solid #f472b6;
                outline-offset: 2px;
            }

            @media (max-width: 991.98px) {
                .us-has-tabbar {
                    padding-bottom: 5.5rem !important;
                }

                .us-topbar-row {
                    min-height: 3.5rem;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .us-link {
                    transition: none;
                }
            }
        </style>
    </head>

    <body>
        @php
            $navItems = [
                ['route' => 'home', 'active' => 'home', 'label' => 'Sabores', 'icon' => 'flavors'],
                ['route' => 'favorites.index', 'active' => 'favorites.*', 'label' => 'Favoritos', 'icon' => 'favorites'],
                ['route' => 'mixes.index', 'active' => 'mixes.*', 'label' => 'Mezclas', 'icon' => 'mixes'],
                ['route' => 'brands.index', 'active' => 'brands.*', 'label' => 'Marcas', 'icon' => 'brands'],
            ];

            // Sin sesión solo se pueden ver Sabores y Marcas (spec 006)
            if (!auth()->check()) {
                $navItems = array_values(array_filter($navItems, fn ($item) => in_array($item['route'], ['home', 'brands.index'], true)));
            }

            if (auth()->user()?->is_admin) {
                $navItems[] = ['route' => 'admin.index', 'active' => 'admin.*', 'label' => 'Admin', 'icon' => 'admin'];
            }

            $adminItems = [
                ['route' => 'admin.index', 'active' => 'admin.index', 'label' => 'Panel'],
                ['route' => 'admin.flavors.index', 'active' => 'admin.flavors.*', 'label' => 'Sabores'],
                ['route' => 'admin.brands.index', 'active' => 'admin.brands.*', 'label' => 'Marcas'],
                ['route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'label' => 'Categorías'],
            ];
        @endphp

        @if (!request()->routeIs('login','register'))
        <svg width="0" height="0" class="position-absolute" aria-hidden="true">
            <symbol id="us-icon-flavors" viewBox="0 0 24 24"><path d="M5 19C4 10 10 4 20 4c.5 10-5.500 16-15 15zM3 21l10-10M9 15v-4M9 15h4.500"/></symbol>
            <symbol id="us-icon-favorites" viewBox="0 0 24 24"><path d="M12 20s-7.5-4.6-9.2-9.2C1.6 7.5 3.8 4.5 7 4.5c2 0 3.7 1 5 3 1.3-2 3-3 5-3 3.2 0 5.4 3 4.2 6.3C19.5 15.4 12 20 12 20z"/></symbol>
            <symbol id="us-icon-mixes" viewBox="0 0 24 24"><path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/></symbol>
            <symbol id="us-icon-brands" viewBox="0 0 24 24"><path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.2"/></symbol>
            <symbol id="us-icon-admin" viewBox="0 0 24 24"><path d="M12 3l7.5 3v6c0 4.5-3.2 7.6-7.5 9-4.3-1.4-7.5-4.5-7.5-9V6z"/></symbol>
        </svg>

        <header class="us-topbar sticky-top">
            <div class="container">
                <div class="us-topbar-row">
                    <a class="us-brand" href="{{ route('home') }}">
                        <img src="{{ asset('images/logo-urrushisha.png') }}" alt="" width="176" height="151">
                        {{-- Sin sesión, en el móvil se oculta el nombre para que quepan los botones de acceso --}}
                        <span class="@guest d-none d-sm-inline @endguest">URRUSHISHA</span>
                    </a>

                    <nav class="d-none d-lg-flex align-items-center gap-1" aria-label="Navegación principal">
                        @foreach($navItems as $item)
                        @php $isActive = request()->routeIs($item['active']); @endphp
                        <a href="{{ route($item['route']) }}" class="us-link {{ $isActive ? 'is-active' : '' }}" @if($isActive) aria-current="page" @endif>
                            <svg class="us-icon" aria-hidden="true"><use href="#us-icon-{{ $item['icon'] }}"></use></svg>
                            {{ $item['label'] }}
                        </a>
                        @endforeach
                    </nav>

                    @guest
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('login') }}" class="btn us-access us-access-login">Iniciar sesión</a>
                        <a href="{{ route('register') }}" class="btn us-access us-access-register">Registrarse</a>
                    </div>
                    @else
                    <div class="dropdown">
                        <button class="us-user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menú de {{ auth()->user()->name }}">
                            <span class="us-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="d-none d-sm-inline text-truncate">{{ auth()->user()->name }}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <span class="dropdown-item-text small text-body-secondary text-truncate d-block" style="max-width: 16rem;">{{ auth()->user()->email }}</span>
                            <hr class="dropdown-divider">
                            <form action="{{ route('logout') }}" method="POST" class="mb-0">
                                @csrf
                                <button type="submit" class="dropdown-item">Cerrar sesión</button>
                            </form>
                        </div>
                    </div>
                    @endguest
                </div>
            </div>

            @if (auth()->user()?->is_admin && request()->routeIs('admin.*'))
            <div class="us-subnav-row">
                <div class="container">
                    <nav class="us-subnav" aria-label="Secciones de administración">
                        @foreach($adminItems as $item)
                        @php $isActive = request()->routeIs($item['active']); @endphp
                        <a href="{{ route($item['route']) }}" class="us-link {{ $isActive ? 'is-active' : '' }}" @if($isActive) aria-current="page" @endif>
                            {{ $item['label'] }}
                        </a>
                        @endforeach
                    </nav>
                </div>
            </div>
            @endif
        </header>
        @endif

        @if (request()->routeIs('login','register'))
        <svg width="0" height="0" class="position-absolute" aria-hidden="true">
            <symbol id="us-icon-eye" viewBox="0 0 24 24"><path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></symbol>
            <symbol id="us-icon-eye-off" viewBox="0 0 24 24"><path d="M9.900 5.200A9.800 9.800 0 0 1 12 5c6.400 0 10 7 10 7a17.500 17.500 0 0 1-3.200 4.100M6.500 6.600A17.200 17.200 0 0 0 2 12s3.600 7 10 7a9.800 9.800 0 0 0 5.400-1.600M3 3l18 18M9.900 9.900a3 3 0 0 0 4.200 4.200"/></symbol>
        </svg>
        @endif

        {{-- CONTENIDO --}}
        {{-- La barra inferior sale en todas las páginas salvo login y registro; sin sesión, solo con Sabores y Marcas --}}
        @php $hasTabbar = !request()->routeIs('login', 'register'); @endphp
        <main class="flex-grow-1 py-4 {{ $hasTabbar ? 'us-has-tabbar' : '' }}">
            @yield('content')
        </main>

        @if ($hasTabbar)
        <nav class="us-tabbar d-lg-none" aria-label="Navegación principal">
            @foreach($navItems as $item)
            @php $isActive = request()->routeIs($item['active']); @endphp
            <a href="{{ route($item['route']) }}" class="us-tab {{ $isActive ? 'is-active' : '' }}" @if($isActive) aria-current="page" @endif>
                <svg class="us-icon" aria-hidden="true"><use href="#us-icon-{{ $item['icon'] }}"></use></svg>
                {{ $item['label'] }}
            </a>
            @endforeach
        </nav>
        @endif

        @if (request()->routeIs('login','register'))
        <script>
            document.querySelectorAll('[data-password-toggle]').forEach((button) => {
                const input = document.getElementById(button.dataset.passwordToggle);
                const icon = button.querySelector('use');

                const setVisible = (visible) => {
                    input.type = visible ? 'text' : 'password';
                    icon.setAttribute('href', visible ? '#us-icon-eye-off' : '#us-icon-eye');
                    button.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
                    button.setAttribute('aria-pressed', visible ? 'true' : 'false');
                };

                // Sin JavaScript el botón no serviría de nada, así que solo se muestra aquí.
                button.hidden = false;
                button.addEventListener('click', () => setVisible(input.type === 'password'));
                // Se vuelve a ocultar al enviar, para que el navegador no la guarde como texto.
                input.form.addEventListener('submit', () => setVisible(false));
            });
        </script>
        @endif

        {{-- SCRIPTS --}}
        @stack('scripts')
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body>

</html>
