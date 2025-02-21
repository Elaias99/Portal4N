@extends('layouts.app')

@section('content')

<!-- Estilos específicos para esta vista -->
<style>
    .centro-gestion .card {
        border: 1px solid #ddd;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease-in-out;
        border-radius: 10px;
        text-align: center; /* Centramos el contenido de la tarjeta */
        padding: 20px;
    }

    .centro-gestion .card:hover {
        transform: scale(1.05); /* Efecto hover */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .centro-gestion .card-title {
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
        font-size: 1.2rem;
    }

    .centro-gestion .btn {
        background-color: #007bff;
        border: none;
        transition: background-color 0.3s ease-in-out;
        font-weight: bold;
        width: 100%; /* Ancho completo del botón */
        padding: 10px;
        margin-top: 15px;
    }

    .centro-gestion .btn:hover {
        background-color: #0056b3;
        color: white;
    }

    .centro-gestion h2 {
        font-size: 2rem;
        color: #333;
        text-align: center;
        margin-bottom: 30px;
    }

    .centro-gestion p {
        text-align: center;
        margin-bottom: 30px;
        color: #555;
    }

    /* Ajuste en el tamaño y estilo de los textos */
    .centro-gestion .card-text {
        font-size: 1rem;
        color: #666;
    }

    /* Centrar tarjetas horizontalmente */
    .centro-gestion .row {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
    }

    /* Añadir espacio entre las tarjetas */
    .centro-gestion .col-md-4 {
        margin-bottom: 20px;
    }
</style>


<!-- Contenido de la vista Centro de Gestión -->
<div class="container centro-gestion">
    <h2 class="mb-4">{{ __('Página de Configuración') }}</h2>
    <p class="mb-4">Elige qué entidad deseas gestionar:</p>

    <div class="row">

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Gestionar AFP') }}</h5>
                    <p class="card-text">Administrar AFP.</p>
                    <a href="{{ route('afps.index') }}" class="btn btn-primary">{{ __('Gestionar AFP') }}</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Gestionar Cargos') }}</h5>
                    <p class="card-text">Administrar Cargos.</p>
                    <a href="{{ route('cargos.index') }}" class="btn btn-primary">{{ __('Gestionar Cargos') }}</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Gestionar Regiones') }}</h5>
                    <p class="card-text">Administrar las Regiones.</p>
                    <a href="{{ route('regions.index') }}" class="btn btn-primary">{{ __('Gestionar Regiones') }}</a>
                </div>
            </div>
        </div>


        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Gestionar Comunas') }}</h5>
                    <p class="card-text">Administrar las Comunas.</p>
                    <a href="{{ route('comunas.index') }}" class="btn btn-primary">{{ __('Gestionar Comunas') }}</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Gestionar Empresas') }}</h5>
                    <p class="card-text">Administrar Empresas.</p>
                    <a href="{{ route('empresas.index') }}" class="btn btn-primary">{{ __('Gestionar Empresas') }}</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Gestionar Estado Civil') }}</h5>
                    <p class="card-text">Administrar Estado Civil.</p>
                    <a href="{{ route('estado_civil.index') }}" class="btn btn-primary">{{ __('Gestionar Estado Civil') }}</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Gestionar Salud') }}</h5>
                    <p class="card-text">Administrar Sistema de Salud.</p>
                    <a href="{{ route('saluds.index') }}" class="btn btn-primary">{{ __('Gestionar Salud') }}</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Gestionar Estados Laborales') }}</h5>
                    <p class="card-text">Administrar Estado Laboral.</p>
                    <a href="{{ route('situacions.index') }}" class="btn btn-primary">{{ __('Gestionar Estados Laborales') }}</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Gestionar Turnos') }}</h5>
                    <p class="card-text">Administrar Turnos.</p>
                    <a href="{{ route('turnos.index') }}" class="btn btn-primary">{{ __('Gestionar Turnos') }}</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Gestionar Sistema de Trabajo') }}</h5>
                    <p class="card-text">Administrar Sistema de Trabajo.</p>
                    <a href="{{ route('sistema_trabajos.index') }}" class="btn btn-primary">{{ __('Gestionar Sistema de Trabajo') }}</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Gestionar Tipo Vestimenta') }}</h5>
                    <p class="card-text">Administrar Tipo Vestimenta.</p>
                    <a href="{{ route('tipo_vestimentas.index') }}" class="btn btn-primary">{{ __('Gestionar Tipo Vestimenta') }}</a>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
