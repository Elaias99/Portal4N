@php
    $trackingOnlyUsers = [1, 4, 8, 22, 30, 36,375, 14, 27];
    $isTrackingOnlyUser = auth()->check() && in_array(auth()->id(), $trackingOnlyUsers);
@endphp


<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', '4Nortes') }}</title>

    {{-- <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}"> --}}
    <link rel="preload" as="image" href="{{ asset('images/logo.png') }}">

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0d6efd">


    <!-- Fonts & Icons -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


    <style>
        .page-loader{
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(17,24,39,.35);
            z-index: 9999;
            padding: 18px;
        }

        .page-loader.is-visible{
            display: flex;
        }

        .page-loader__card{
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 10px 30px rgba(0,0,0,.18);
            max-width: 360px;
            width: 100%;
        }

        .page-loader__spinner{
            width: 36px;
            height: 36px;
            border-radius: 999px;
            border: 4px solid #e5e7eb;
            border-top-color: #2563eb;
            animation: spin .9s linear infinite;
            flex: 0 0 auto;
        }

        .page-loader__title{
            font-weight: 900;
            font-size: 16px;
            color: #111827;
            margin: 0;
        }

        .page-loader__subtitle{
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>

    @viteReactRefresh
    

    @vite([
        'resources/css/app.css',
        'resources/css/appcustom.css',
        'resources/sass/app.scss',
        'resources/js/app.js',
        'resources/js/react/sidebar/main.jsx',
    ])
    
</head>
<body>
    <div id="app">


        <div id="pageLoader" class="page-loader" aria-hidden="true">
            <div class="page-loader__card" role="status" aria-live="polite" aria-label="Cargando">
                <div class="page-loader__spinner"></div>
                <div>
                    <div class="page-loader__title">Cargando…</div>
                    <div class="page-loader__subtitle">Por favor espera</div>
                </div>
            </div>
        </div>


        <nav class="navbar navbar-expand-md navbar-dark">
            <div class="container">
                <!-- Off-canvas Sidebar Menu React -->
                @auth

                    {{-- Definir que verá cada rol y que acceso tiene --}}
                    @if (
                        auth()->user()->hasRole(['admin', 'jefe']) ||
                        (auth()->user()->trabajador && auth()->user()->trabajador->area_id) ||
                        $isTrackingOnlyUser
                    )


                    @php
                        $sidebarRoutes = [
                            'empleadosIndex' => route('empleados.index'),
                            'empleadosLocalidades' => route('empleados.localidades'),
                            'hijosIndex' => route('hijos.index'),
                            'tallasIndex' => route('tallas.index'),
                            'areasIndex' => route('areas.index'),

                            'solicitudesIndex' => route('solicitudes.index'),
                            'solicitudesVacaciones' => route('solicitudes.vacaciones'),

                            'archivosRespaldo' => route('admin.archivos-respaldo'),
                            'adminIndex' => route('admin.index'),
                            'adminControlPanel' => route('admin.controlpanel.index'),
                            'historialVacacion' => route('historial-vacacion.index'),

                            'trackingDeliveryLinks' => url('/tracking/delivery-links'),
                            'labels' => url('/labels'),
                        ];
                    @endphp

                    <div
                        id="portal-sidebar-root"
                        data-user-name="{{ auth()->user()->name }}"
                        data-logo-url="{{ asset('images/logo.png') }}"
                        data-can-open-menu="true"
                        data-can-see-admin-menu="{{ auth()->user()->hasRole(['admin', 'jefe']) ? 'true' : 'false' }}"
                        data-can-see-admin-only="{{ auth()->user()->hasRole('admin') ? 'true' : 'false' }}"
                        data-can-see-admin-panel="{{ Auth::check() && Auth::id() === 1 ? 'true' : 'false' }}"
                        data-can-see-tracking-menu="{{ $isTrackingOnlyUser ? 'true' : 'false' }}"
                        data-routes='@json($sidebarRoutes)'
                    ></div>



                    @endif
                @endauth



                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">


                    

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">
                                        <i class="fas fa-sign-in-alt"></i> {{ __('Login') }}
                                    </a>
                                </li>
                            @endif
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">
                                        <i class="fas fa-user-plus"></i> {{ __('Register') }}
                                    </a>
                                </li>
                            @endif
                        @else


                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                <i class="fas fa-user"></i> {{ Auth::user()->name }}
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <!-- Si tiene rol de admin o jefe -->
                                @role('admin|jefe')
                                <a class="dropdown-item" href="{{ route('empleados.perfil') }}">
                                    <i class="fas fa-user-circle"></i> Mi Perfil
                                </a>
                                @endrole
                            
                                <!-- Opción Logout -->
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                                document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </li>
                        
                        
                        @endguest
                    </ul>





                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker registrado', reg))
            .catch(err => console.error('Error al registrar Service Worker', err));
        }
    </script>


    <script>
        (function () {
            const loader = document.getElementById('pageLoader');
            if (!loader) return;

            let visibleCount = 0;
            let safetyTimer = null;

            const DEFAULT_SAFETY_TIMEOUT = 15000;
            const LONG_SAFETY_TIMEOUT = 5 * 60 * 1000; // 5 minutos

            let activeSafetyTimeout = DEFAULT_SAFETY_TIMEOUT;

            const resolveSafetyTimeout = (trigger) => {
                const longLoaderElement = trigger?.closest?.('[data-long-loader]');

                if (!longLoaderElement) {
                    return DEFAULT_SAFETY_TIMEOUT;
                }

                const customTimeout = Number(longLoaderElement.dataset.longLoader);

                return Number.isFinite(customTimeout) && customTimeout > 0
                    ? customTimeout
                    : LONG_SAFETY_TIMEOUT;
            };

            const resetSafetyTimeout = () => {
                activeSafetyTimeout = DEFAULT_SAFETY_TIMEOUT;
            };

            const render = () => {
                if (visibleCount > 0) {
                    loader.classList.add('is-visible');
                    loader.setAttribute('aria-hidden', 'false');
                } else {
                    loader.classList.remove('is-visible');
                    loader.setAttribute('aria-hidden', 'true');
                }
            };

            const clearSafety = () => {
                if (safetyTimer) {
                    clearTimeout(safetyTimer);
                    safetyTimer = null;
                }
            };



            const startSafety = (timeout = DEFAULT_SAFETY_TIMEOUT) => {
                clearSafety();

                safetyTimer = setTimeout(() => {
                    visibleCount = 0;
                    resetSafetyTimeout();
                    clearSafety();
                    render();
                }, timeout);
            };




            const show = (options = {}) => {
                const requestedTimeout = Number(options.timeout) || DEFAULT_SAFETY_TIMEOUT;

                activeSafetyTimeout = Math.max(activeSafetyTimeout, requestedTimeout);

                visibleCount++;
                render();
                startSafety(activeSafetyTimeout);
            };

            const hide = () => {
                if (visibleCount > 0) {
                    visibleCount--;
                }

                if (visibleCount === 0) {
                    clearSafety();
                    resetSafetyTimeout();
                }

                render();
            };

            const forceHide = () => {
                visibleCount = 0;
                clearSafety();
                resetSafetyTimeout();
                render();
            };

            window.pageLoader = { show, hide, forceHide };



            // Formularios normales
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (form.hasAttribute('data-no-loader')) return;

                show({ timeout: resolveSafetyTimeout(form) });

                const submits = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                submits.forEach(btn => btn.disabled = true);
            }, true);




            // Links internos normales
            document.addEventListener('click', (e) => {
                const a = e.target.closest('a');
                if (!a) return;

                if (a.hasAttribute('data-no-loader')) return;
                if (a.hasAttribute('download')) return;
                if (a.target && a.target !== '_self') return;
                if (a.hasAttribute('data-bs-toggle')) return;

                const href = a.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('blob:')) return;

                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;

                try {
                    const url = new URL(href, window.location.href);
                    if (url.origin !== window.location.origin) return;
                } catch (_) {
                    return;
                }

                show({ timeout: resolveSafetyTimeout(a) });
            }, true);

            // Interceptor global para fetch
            const nativeFetch = window.fetch;
            if (nativeFetch) {
                window.fetch = async function (...args) {
                    const [, init = {}] = args;
                    const headers = new Headers(init.headers || {});
                    const skipLoader = headers.get('X-No-Loader') === '1';

                    if (!skipLoader) show();

                    try {
                        return await nativeFetch.apply(this, args);
                    } finally {
                        if (!skipLoader) {
                            setTimeout(() => hide(), 250);
                        }
                    }
                };
            }

            // Si usas jQuery AJAX en otras partes
            if (window.jQuery) {
                $(document).ajaxStart(() => show());
                $(document).ajaxStop(() => hide());
            }

            // Failsafes globales
            window.addEventListener('pageshow', () => forceHide());
            window.addEventListener('focus', () => forceHide());
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    forceHide();
                }
            });

            // Si se cierra cualquier modal, ocultar loader
            document.addEventListener('hidden.bs.modal', () => {
                forceHide();
            });
        })();


    </script>

    @stack('scripts')
</body>
</html>
