@extends('layouts.app')

@section('content')

<div class="container">

    <h1 class="mb-3">
        Editar producto
    </h1>

    <div class="card shadow-sm">
        <div class="card-body">

            <form
                action="{{ route('almacenamiento.update', $almacenamientoBodega) }}"
                method="POST"
            >
                @method('PUT')

                @include('almacenamiento.form', [
                    'btnText' => 'Actualizar'
                ])
            </form>

        </div>
    </div>

</div>

@endsection