@extends('layouts.app')

@section('content')
<div class="container">
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mb-4" style="background-color: #f8f9fa; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);">
                <div class="card-header text-center" style="padding: 20px 0;">
                    <h1 class="text-center" style="text-transform: uppercase; font-size: 2.5rem; letter-spacing: 2px;">Crear Empleado</h1>
                </div>
            </div>
        </div>
    </div>
    
    <form action="{{ url('/empleados') }}" method="post" enctype="multipart/form-data">
        {{ csrf_field() }}
        @include('empleados.form', ['modo' => 'Agregar'])
    </form>
</div>


@endsection
