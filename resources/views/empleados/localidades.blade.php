@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Localidades de los Empleados</h1>
    
    <div class="row">
        @foreach($empleados as $empleado)
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm" style="border-left: 4px solid #007bff;">
                <div class="card-body">
                    <h5 class="card-title text-primary">{{ $empleado->Nombre }} {{ $empleado->ApellidoPaterno }} {{ $empleado->ApellidoMaterno }}</h5>
                    <p class="card-text"><strong>Comuna:</strong> {{ $empleado->comuna->Nombre }}</p>
                    <p class="card-text"><strong>Región:</strong> {{ $empleado->comuna->region->Nombre }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>


</div>
@endsection
