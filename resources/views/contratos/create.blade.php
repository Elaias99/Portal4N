@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Registrar nuevo contrato o anexo</h3>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('contratos.store', $trabajador->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        @include('contratos.form')

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Guardar contrato</button>
            <a href="{{ route('contratos.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
