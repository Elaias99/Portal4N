@extends('layouts.app')

@section('content')


<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
                <div class="card-header text-center" style="padding: 20px 0;">
                    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">
                        Editar a {{ $empleado->Nombre }} {{ $empleado->ApellidoPaterno }} {{ $empleado->ApellidoMaterno }}
                    </h1>
                </div>
            
        </div>
    </div>
    
    <form action="{{ url('/empleados/'.$empleado->id) }}" method="post" enctype="multipart/form-data">
        @csrf
        {{ method_field('PATCH') }}
        @include('empleados.form', ['modo' => 'Actualizar'])
    </form>
</div>


@endsection
