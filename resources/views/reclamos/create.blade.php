@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Hacer Reclamo - Bulto {{ $bulto->codigo_bulto }}</h2>

        <form action="{{ route('reclamos.store') }}" method="POST">
            @csrf
            <input type="hidden" name="id_bulto" value="{{ $bulto->id }}">

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción del Reclamo</label>
                <textarea name="descripcion" class="form-control" rows="4" required></textarea>
            </div>

            <button type="submit" class="btn btn-danger">Enviar Reclamo</button>
            <a href="{{ route('bultos.index') }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
@endsection
