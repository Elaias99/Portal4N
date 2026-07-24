@extends('layouts.app')

@section('content')

<div class="container">

    <h1 class="mb-3">
        Agregar producto
    </h1>

    <div class="card shadow-sm">
        <div class="card-body">

            <form
                action="{{ route('almacenamiento.store') }}"
                method="POST"
            >
                @include('almacenamiento.form', [
                    'btnText' => 'Guardar'
                ])
            </form>

        </div>
    </div>

</div>

@endsection