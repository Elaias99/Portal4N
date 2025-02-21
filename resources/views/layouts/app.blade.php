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

    <!-- Fonts & Icons -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    @vite([
        'resources/css/app.css'
    ])

    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #ffffff; /* Fondo blanco para el body */
            color: #333333;
            overflow-x: hidden;
        }

        /* Barra Superior con estilo futurista (manteniendo los cambios anteriores) */
        .navbar {
            background: linear-gradient(145deg, #231F21, #5CBABC, #0a9396);
            background-size: 300% 300%;
            animation: gradientAnimation 15s ease infinite;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            padding: 10px 20px;
        }

        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Barra lateral (sidebar) restaurada al estilo original) */
        .offcanvas {
            background-color: #f8f9fa; /* Fondo claro */
            color: #333333; /* Color de texto oscuro */
        }

        .offcanvas-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .offcanvas-title {
            font-weight: 700;
        }

        .offcanvas-body .list-group-item {
            border: none;
            padding: 15px;
            transition: background-color 0.3s ease;
        }

        .offcanvas-body .list-group-item:hover {
            background-color: rgba(0, 123, 255, 0.1);
            border-radius: 5px;
        }

        /* Logo del sidebar */
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa; /* Fondo unificado con el sidebar */
            border-radius: 15px;
        }

        .logo-guirnalda {
            max-width: 80%;
            height: auto;
        }

        /* Botón de cierre del sidebar */
        .btn-close {
            color: #333333;
            opacity: 0.8;
        }

        .btn-close:hover {
            opacity: 1;
        }

        /* Estilos de los enlaces en el sidebar */
        .nav-link {
            color: #333333; /* Enlaces oscuros */
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #007bff; /* Color azul para el hover */
            background-color: rgba(0, 123, 255, 0.1);
            border-radius: 5px;
        }

        /* Botón de "Menú" del sidebar */
        .btn-outline-secondary {
            color: #333333;
            border: none;
        }

        .btn-outline-secondary:hover {
            color: #007bff;
            background-color: rgba(0, 123, 255, 0.1);
        }

        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
        }

        .sidebar-footer i {
            color: #007bff; /* Color azul para el icono */
        }

        .sidebar-footer a:hover i {
            color: #0056b3; /* Un azul más oscuro en hover */
        }

        .sidebar-footer p {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
            color: #333;
        }

    </style>

    





    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark">
            <div class="container">

                <a class="navbar-brand" href="{{ url('/') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="4Nortes" style="height: 40px;">
                </a>
                



                <!-- Off-canvas Sidebar Menu Button (only for roles admin and jefe) -->
                @role('admin|jefe')
                <button class="btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuSidebar" aria-controls="menuSidebar">
                    <i class="fas fa-bars"></i> Menú
                </button>
                @endrole

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

        <!-- Offcanvas Sidebar (only for roles admin and jefe) -->
        @role('admin|jefe')
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


                    <!-- Archivos Adjuntos -->
                    @role('admin')
                    <li class="list-group-item">
                        <a href="{{ route('admin.archivos-respaldo') }}" class="text-decoration-none">
                            <i class="fas fa-folder-open me-2"></i> Archivos Adjuntos
                        </a>
                    </li>

                    <li class="list-group-item">
                        <a href="#" class="text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-building me-2"></i> Gestión de Proveedores
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{{ route('proveedores.index') }}" class="dropdown-item">
                                    <i class="fas fa-list me-2"></i> Ver Proveedores
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('proveedores.create') }}" class="dropdown-item">
                                    <i class="fas fa-plus me-2"></i> Crear Proveedor
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('compras.index') }}" class="dropdown-item">
                                    <i class="fa-solid fa-store"></i> Compras
                                </a>

                            </li>

                            <a href="{{ route('compras.create') }}" class="dropdown-item">
                                <i class="fa-solid fa-cart-plus"></i></i> Crear Compra
                            </a>

                        </ul>
                    </li>
                    

                    <li class="list-group-item">
                        <a class="text-decoration-none" href="{{ route('admin.index') }}"><i class="fas fa-cogs me-2"></i> {{ __('Centro de Gestión') }}</a>
                    </li>
                    @endrole

                    <!-- Historial de Vacaciones -->
                    <li class="list-group-item">
                        <a href="{{ route('historial-vacacion.index') }}" class="text-decoration-none">
                            <i class="fas fa-history me-2"></i> Historial de Vacaciones
                        </a>
                    </li>

                    <div class="sidebar-footer text-center mt-4">
                        <a href="{{ route('tutorial') }}" class="d-block text-decoration-none text-dark">
                            <i class="fas fa-info-circle fa-2x"></i> <!-- Ícono de información -->
                            <p class="m-0">Ver Tutorial</p>
                        </a>
                    </div>



                </ul>
            </div>
        </div>
        @endrole

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
