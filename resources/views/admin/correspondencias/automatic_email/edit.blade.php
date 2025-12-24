@extends('layouts.app')

@section('content')
<div class="container">

    <h2 class="mb-4">✏ Editar Correo Automático</h2>

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

    <form method="POST" action="{{ route('admin.automatic_emails.update', $email->id) }}">
        @csrf
        @method('PUT')

        @include('admin.correspondencias.automatic_email.form', [
            'email' => $email
        ])

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
                Guardar cambios
            </button>

            <a href="{{ route('admin.automatic_emails.index') }}"
            class="btn btn-outline-secondary">
                Cancelar y volver al listado
            </a>
        </div>

    </form>

</div>
@endsection
