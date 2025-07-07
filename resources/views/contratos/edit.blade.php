@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Editar contrato</h3>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('contratos.update', $contrato->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('contratos.form', ['contrato' => $contrato])

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Actualizar contrato</button>
            <a href="{{ route('contratos.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
