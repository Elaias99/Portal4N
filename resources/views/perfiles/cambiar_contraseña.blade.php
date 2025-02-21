@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Cambiar Contraseña</h2>

    {{-- Mostrar mensajes de error o éxito --}}
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Formulario de cambio de contraseña --}}
    <form method="POST" action="{{ route('perfiles.cambiar_contraseña.update') }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="current_password">Contraseña Actual</label>
            <input type="password" class="form-control" id="current_password" name="current_password" required>
        </div>

        <div class="form-group">
            <label for="new_password">Nueva Contraseña</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
        </div>

        <div class="form-group">
            <label for="new_password_confirmation">Confirmar Nueva Contraseña</label>
            <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Cambiar Contraseña</button>
    </form>
</div>
@endsection
