@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Reclamos Pendientes</h2>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if($reclamos->count())
        <ul class="list-group mt-4">
            @foreach($reclamos as $reclamo)
                <li class="list-group-item">
                    <strong>Bulto:</strong> {{ $reclamo->bulto->codigo_bulto ?? '—' }} <br>
                    <strong>Descripción:</strong> {{ $reclamo->descripcion }} <br>
                    <strong>Fecha Creación:</strong> {{ $reclamo->created_at->format('d-m-Y') }}
                </li>
            @endforeach
        </ul>
    @else
        <div class="alert alert-info mt-4">
            No hay reclamos pendientes.
        </div>
    @endif
</div>
@endsection
