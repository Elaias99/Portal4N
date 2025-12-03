@extends('layouts.app')

@section('content')

<div class="container">

    <h1 class="mb-4">Panel Administrativo</h1>

    <p class="mb-4">
        Desde este panel puedes acceder a las herramientas exclusivas de administración del sistema.
    </p>

    <div class="row">

        <!-- Gestión de Roles -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Gestión de Roles</h5>
                    <p class="card-text">
                        Administra los roles asignados a cada usuario dentro del sistema.
                    </p>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-primary">
                        <i class="fas fa-user-shield me-2"></i> Administrar Roles
                    </a>
                </div>
            </div>
        </div>

        <!-- Respaldo de la Base de Datos -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Respaldo de Base de Datos</h5>
                    <p class="card-text">
                        Genera un archivo con una copia completa de la base de datos actual.
                    </p>
                    <a href="{{ route('admin.backup.index') }}" class="btn btn-success">
                        <i class="fas fa-database me-2"></i> Generar Respaldo
                    </a>
                </div>
            </div>
        </div>

        <!-- Correos Automáticos -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Correos Automáticos</h5>
                    <p class="card-text">
                        Configura los correos automáticos y sus horarios de envío.
                    </p>
                    <a href="{{ route('admin.automatic_emails.index') }}" class="btn btn-warning">
                        <i class="fas fa-envelope me-2"></i> Administrar Correos
                    </a>
                </div>
            </div>
        </div>


    </div>

</div>

@endsection
