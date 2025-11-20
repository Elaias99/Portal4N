@extends('layouts.app')

@section('content')

<div class="container">
    <h1 class="mb-4">Respaldo de Base de Datos</h1>

    <p>Esta sección permite generar un respaldo completo de la base de datos.</p>

    {{-- Formulario para generar el respaldo --}}
    <form action="{{ route('admin.backup.database') }}" method="POST">
        @csrf

        <div class="alert alert-warning mt-4">
            <strong>Advertencia:</strong> Al presionar el botón se generará un archivo SQL con toda la base de datos.
            No compartas este archivo con terceros, contiene información sensible.
        </div>

        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-download me-2"></i> Generar Respaldo
        </button>
    </form>

</div>

@endsection
