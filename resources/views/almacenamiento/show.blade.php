@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1>Detalle del producto</h1>

        <a
            href="{{ route('almacenamiento.index') }}"
            class="btn btn-secondary"
        >
            Volver
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="mb-3">
                <strong>ID:</strong>
                {{ $almacenamientoBodega->id }}
            </div>

            <div class="mb-3">
                <strong>Nombre:</strong>
                {{ $almacenamientoBodega->Nombre }}
            </div>

            <div class="mb-3">
                <strong>Precio:</strong>
                {{ $almacenamientoBodega->precio }}
            </div>

            <div class="mb-3">
                <strong>Cantidad:</strong>
                {{ $almacenamientoBodega->cantidad }}
            </div>

            <div class="mb-3">
                <strong>Descripción:</strong>
                {{ $almacenamientoBodega->descripcion }}
            </div>

            <a
                href="{{ route('almacenamiento.edit', $almacenamientoBodega) }}"
                class="btn btn-warning"
            >
                Editar
            </a>

        </div>
    </div>

</div>

@endsection