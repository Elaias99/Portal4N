@extends('layouts.app')

@section('content')
<style>
    /* Estilos CSS integrados */
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f7f9fc;
        color: #333;
    }

    .modern-title {
        font-size: 2rem;
        margin-bottom: 1.5rem;
        color: #444;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }

    .modern-card {
        border-radius: 16px;
        padding: 1.5rem;
        background-color: #fff;
    }

    .modern-card-title {
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .modern-icon {
        font-size: 1.5rem;
        color: #555;
        margin-right: 0.75rem;
    }

    .modern-button {
        background-color: #007bff;
        color: white;
        border-radius: 8px;
        padding: 0.75rem;
        transition: background-color 0.3s ease;
    }

    .modern-button:hover {
        background-color: #0056b3;
    }
</style>

<div class="container">
    <h1 class="text-center modern-title">Lista de Tallas</h1>

    <div class="row">
        @foreach($tallas->groupBy('trabajador.id') as $trabajadorId => $tallasPorTrabajador)
            @php $trabajador = $tallasPorTrabajador->first()->trabajador; @endphp

            <div class="col-md-4 mb-4">
                <div class="card h-100 modern-card shadow-lg">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title modern-card-title">{{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}</h5>
                        
                        <div class="mb-3">
                            <strong>Tallas Principales:</strong>
                            <div class="d-flex align-items-center mt-2">
                                @foreach($tallasPorTrabajador as $talla)
                                    @if($talla->tipoVestimenta->Nombre == 'Polera')
                                        <i class="fas fa-tshirt modern-icon"></i>
                                    @elseif($talla->tipoVestimenta->Nombre == 'Pantalón')
                                        <i class="fa-solid fa-vest-patches modern-icon"></i>
                                    @elseif($talla->tipoVestimenta->Nombre == 'Calzado')
                                        <i class="fa-solid fa-shoe-prints modern-icon"></i>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-auto">
                            <button type="button" class="btn modern-button btn-block" data-toggle="modal" data-target="#detailsModal{{ $trabajador->id }}">
                                Ver Detalles
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modales -->
@foreach($tallas->groupBy('trabajador.id') as $trabajadorId => $tallasPorTrabajador)
    @php $trabajador = $tallasPorTrabajador->first()->trabajador; @endphp
    <div class="modal fade" id="detailsModal{{ $trabajador->id }}" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel{{ $trabajador->id }}" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel{{ $trabajador->id }}">Detalles de Tallas de {{ $trabajador->Nombre }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <strong><h5 class="modal-title">Tallas de {{ $trabajador->Nombre }}</h5></strong><br><br>
                    @foreach($tallasPorTrabajador as $talla)
                        {{ $talla->tipoVestimenta->Nombre }}: {{ $talla->talla }}<br>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
@endsection
