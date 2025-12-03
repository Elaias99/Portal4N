@extends('layouts.app')

@section('content')
<div class="container">

    <h2 class="mb-4">➕ Crear Correo Automático</h2>

    <a href="{{ route('admin.automatic_emails.index') }}" class="btn btn-secondary mb-3">
        ← Volver al listado
    </a>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Errores encontrados:</strong>
            <ul class="mt-2 mb-0">
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.automatic_emails.store') }}">
        @csrf

        @include('admin.correspondencias.automatic_email.form')

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                💾 Guardar Correo Automático
            </button>
        </div>
    </form>

</div>
@endsection
