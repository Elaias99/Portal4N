<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', '4Nortes') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0d6efd">


    <!-- Fonts & Icons -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    

    @vite([
        'resources/css/app.css',
        'resources/css/appcustom.css',
        'resources/sass/app.scss',
        'resources/js/app.js',
    ])

    @livewireStyles
    
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark">
            <div class="container">
                <!-- Off-canvas Sidebar Menu Button (only for roles admin and jefe) -->
                @auth
                    @if (auth()->user()->hasRole(['admin', 'jefe']) || (auth()->user()->trabajador && auth()->user()->trabajador->area_id))
                        <button class="btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuSidebar" aria-controls="menuSidebar">
                            <i class="fas fa-bars"></i> Menú
                        </button>
                    @endif
                @endauth


                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    
                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">

                        @role('admin|jefe')
                            <li class="nav-item dropdown">
                                <button class="btn btn-link position-relative" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-bell"></i>
                                    <span class="badge bg-danger position-absolute top-0 start-100 translate-middle" style="display: none;">0</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end"></ul>
                            </li>
                        @endrole
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

        <!-- Offcanvas Sidebar (only for roles admin and jefe) -->
        @auth
        @if (auth()->user()->hasRole(['admin', 'jefe']) || (auth()->user()->trabajador && auth()->user()->trabajador->area_id))
        <div class="offcanvas offcanvas-start" tabindex="-1" id="menuSidebar" aria-labelledby="menuSidebarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="menuSidebarLabel">Navegación Rápida</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="logo-container">
                    <img src="{{ asset('images/logo.png') }}" alt="4Nortes" class="logo-guirnalda">
                </div>
                <ul class="list-group">

                    {{-- Secciones solo visibles para admin o jefe --}}
                    @role('admin|jefe')
                        <!-- Sección: Información de Empleados -->
                        <li class="list-group-item">
                            <a class="text-decoration-none dropdown-toggle" data-bs-toggle="collapse" href="#informacionEmpleados" role="button" aria-expanded="false" aria-controls="informacionEmpleados">
                                <i class="fas fa-address-book me-2"></i> Información de Empleados
                            </a>
                            <div class="collapse" id="informacionEmpleados">
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <a href="{{ route('empleados.index') }}" class="text-decoration-none">
                                            <i class="fas fa-user-friends me-2"></i> Trabajadores
                                        </a>
                                    </li>
                                    <li class="list-group-item">
                                        <a href="{{ route('empleados.localidades') }}" class="text-decoration-none">
                                            <i class="fas fa-map-marker-alt me-2"></i> Zonas de Residencia
                                        </a>
                                    </li>
                                    <li class="list-group-item">
                                        <a href="{{ route('hijos.index') }}" class="text-decoration-none">
                                            <i class="fas fa-child me-2"></i> Hijos de Empleados
                                        </a>
                                    </li>
                                    <li class="list-group-item">
                                        <a href="{{ route('tallas.index') }}" class="text-decoration-none">
                                            <i class="fas fa-tshirt me-2"></i> Tallas de Uniformes
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Sección: Solicitudes y Permisos -->
                        <li class="list-group-item">
                            <a class="text-decoration-none dropdown-toggle" data-bs-toggle="collapse" href="#solicitudesPermisos" role="button" aria-expanded="false" aria-controls="solicitudesPermisos">
                                <i class="fas fa-file-signature me-2"></i> Solicitudes y Permisos
                            </a>
                            <div class="collapse" id="solicitudesPermisos">
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <a href="{{ route('solicitudes.index') }}" class="text-decoration-none">
                                            <i class="fas fa-edit me-2"></i> Solicitudes de Modificación
                                        </a>
                                    </li>
                                    <li class="list-group-item">
                                        <a href="{{ route('solicitudes.vacaciones') }}" class="text-decoration-none">
                                            <i class="fas fa-calendar-day me-2"></i> Solicitudes de Días
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Archivos Adjuntos y Proveedores -->
                        <li class="list-group-item">
                            <a href="{{ route('admin.archivos-respaldo') }}" class="text-decoration-none">
                                <i class="fas fa-folder-open me-2"></i> Archivos Adjuntos
                            </a>
                        </li>

                        @role('admin')
                        <li class="list-group-item">
                            <a href="#submenuProveedores" class="text-decoration-none" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="submenuProveedores">
                                <i class="fas fa-building me-2"></i> Gestión de Proveedores
                            </a>
                            <div class="collapse ps-4" id="submenuProveedores">
                                <ul class="list-unstyled">
                                    <li class="my-2">
                                        <a href="{{ route('proveedores.index') }}" class="text-decoration-none">
                                            <i class="fas fa-list me-2"></i> Ver Proveedores
                                        </a>
                                    </li>
                                    <li class="my-2">
                                        <a href="{{ route('proveedores.create') }}" class="text-decoration-none">
                                            <i class="fas fa-plus me-2"></i> Crear Proveedor
                                        </a>
                                    </li>
                                    <li class="my-2">
                                        <a href="{{ route('compras.index') }}" class="text-decoration-none">
                                            <i class="fa-solid fa-store me-2"></i> Compras
                                        </a>
                                    </li>
                                    <li class="my-2">
                                        <a href="{{ route('compras.create') }}" class="text-decoration-none">
                                            <i class="fa-solid fa-cart-plus me-2"></i> Crear Compra
                                        </a>
                                    </li>
                                    <li class="my-2">
                                        <a href="{{ route('pagos.index') }}" class="text-decoration-none">
                                            <i class="fas fa-money-check-alt me-2"></i> Pagos
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        @endrole

                        @role('admin')
                        <li class="list-group-item">
                            <a class="text-decoration-none" href="{{ route('admin.index') }}">
                                <i class="fas fa-cogs me-2"></i> {{ __('Centro de Gestión') }}
                            </a>
                        </li>
                        @endrole

                        <!-- Historial de Vacaciones -->
                        @role('admin')
                        <li class="list-group-item">
                            <a href="{{ route('historial-vacacion.index') }}" class="text-decoration-none">
                                <i class="fas fa-history me-2"></i> Historial de Vacaciones
                            </a>
                        </li>
                        @endrole



                    @endrole

                    <!-- Gestión de Bultos (disponible también para trabajadores con área asignada) -->
                    @auth
                    @if (
                        auth()->user()->hasRole(['admin', 'jefe']) ||
                        (
                            auth()->user()->trabajador &&
                            auth()->user()->trabajador->area_id &&
                            auth()->user()->trabajador->area_id !== 5
                        )
                    )
                    <!-- Gestión de Bultos -->
                    <li class="list-group-item">
                        <a class="text-decoration-none dropdown-toggle" data-bs-toggle="collapse" href="#gestionBultos" role="button" aria-expanded="false" aria-controls="gestionBultos">
                            <i class="fas fa-boxes me-2"></i> Gestión de Bultos
                        </a>
                        <div class="collapse" id="gestionBultos">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <a href="{{ route('bultos.index') }}" class="text-decoration-none">
                                        <i class="fas fa-search me-2"></i> Buscar Bulto
                                    </a>
                                </li>
                                <li class="list-group-item">
                                    <a class="text-decoration-none dropdown-toggle" data-bs-toggle="collapse" href="#gestionReclamos" role="button" aria-expanded="false" aria-controls="gestionReclamos">
                                        <i class="fas fa-clipboard-list me-2"></i> Reclamos
                                    </a>
                                    <div class="collapse ps-3" id="gestionReclamos">
                                        <ul class="list-group">
                                            <li class="list-group-item">
                                                <a href="{{ route('reclamos.index') }}" class="text-decoration-none">
                                                    <i class="fas fa-tasks me-2"></i> Pendientes
                                                </a>
                                            </li>
                                            <li class="list-group-item">
                                                <a href="{{ route('perfiles.reclamos.area') }}" class="text-decoration-none">
                                                    <i class="fas fa-comments me-2"></i> Conversaciones de Área
                                                </a>
                                            </li>
                                            <li class="list-group-item">
                                                <a href="{{ route('reclamos.mios') }}" class="text-decoration-none">
                                                    <i class="fas fa-user-check me-2"></i> Mis Reclamos
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </li>
                    @endif
                    @endauth


                    @auth
                    @if (
                        auth()->user()->hasRole(['admin', 'jefe']) ||
                        (
                            auth()->user()->trabajador &&
                            auth()->user()->trabajador->area_id
                        )
                    )
                        <!-- Opción: Seguimiento de Productos -->
                        <li class="list-group-item">
                            <a href="{{ url('/tracking-productos') }}" class="text-decoration-none">
                                <i class="fas fa-map-marker-alt me-2"></i> Seguimiento de Productos
                            </a>
                        </li>
                    @endif
                    @endauth




                    
                </ul>
            </div>
        </div>
        @endif
        @endauth


        

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    





    <script>
        function cargarNotificaciones() {
            $.get('{{ url('notificaciones/recientes') }}', function(data) {

                

                
                

                const badge = $('#notificationDropdown .badge');
                const dropdown = $('#notificationDropdown').next('.dropdown-menu');

                // Actualizar contador
                if (data.total > 0) {
                    badge.text(data.total).show();
                } else {
                    badge.hide();
                }

                // Actualizar listado
                if (dropdown.length) {
                    let html = '';
                    const iconos = {
                        'App\\Notifications\\NotificacionAdmin': 'fas fa-edit text-primary',
                        'App\\Notifications\\NotificacionAdminVacaciones': 'fas fa-umbrella-beach text-info',
                        'App\\Notifications\\NuevoReclamoAreaNotification': 'fas fa-box text-warning',
                        'App\\Notifications\\ReclamoRespondidoNotification': 'fas fa-reply text-success',
                        'App\\Notifications\\NuevoComentarioReclamoNotification': 'fas fa-comment text-secondary',
                        'App\\Notifications\\ReclamoCerradoNotification': 'fas fa-lock text-danger',
                        'App\\Notifications\\SolicitudActualizada': 'fas fa-file-signature text-primary',
                        'App\\Notifications\\ReclamoReabiertoNotification': 'fas fa-undo text-info',
                    };

                    if (data.items.length > 0) {
                        data.items.forEach(function(n) {
                            html += `
                                <li class="dropdown-item">
                                    <a href="{{ url('notifications/mark-as-read') }}/${n.id}" class="d-flex align-items-center gap-2">
                                        <i class="${iconos[n.tipo] || 'fas fa-info-circle text-muted'}"></i>
                                        <span>${n.mensaje}</span>
                                    </a>
                                </li>
                            `;
                        });
                        html += `
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('notifications.markAllAsRead') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-center">Marcar todas como leídas</button>
                                </form>
                            </li>
                        `;
                    } else {
                        html += '<li class="dropdown-item text-center text-muted">No tienes notificaciones nuevas.</li>';
                    }

                    dropdown.html(html);
                }
            });
        }

        $(document).ready(function() {
            cargarNotificaciones();
            setInterval(cargarNotificaciones, 9000); // cada 30 segundos
        });
    </script>

    @stack('scripts')
    @livewireScripts

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('✅ Service Worker registrado', reg))
            .catch(err => console.error('❌ Error al registrar Service Worker', err));
        }
    </script>

</body>
</html>
