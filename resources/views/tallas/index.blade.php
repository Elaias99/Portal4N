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

    .hover-shadow:hover {
        transform: translateY(-4px);
        transition: 0.3s ease;
        box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
    }

</style>

<div class="container">
    <h1 class="text-center modern-title">Lista de Tallas</h1>

    <div class="row">
        @foreach($tallas->groupBy('trabajador.id') as $trabajadorId => $tallasPorTrabajador)
            @php $trabajador = $tallasPorTrabajador->first()->trabajador; @endphp

            <div class="col-md-4 mb-4">
                <div class="card h-100 modern-card shadow-sm border-0 rounded-lg hover-shadow">
                    <div class="card-body d-flex flex-column text-center">
                        
                        {{-- Nombre del trabajador --}}
                        <h5 class="card-title modern-card-title mb-2">
                            <i class="fas fa-user mr-2 text-primary"></i>
                            {{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}
                        </h5>

                        {{-- Resumen de tallas --}}
                        <p class="text-muted mb-4">
                            {{ $tallasPorTrabajador->count() }} tallas registradas
                        </p>

                        {{-- Botón principal --}}
                        <div class="mt-auto">
                            <button type="button" 
                                    class="btn btn-outline-primary btn-block shadow-sm" 
                                    data-toggle="modal" 
                                    data-target="#detailsModal{{ $trabajador->id }}">
                                <i class="fas fa-eye mr-1"></i> Ver Detalles
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

    <!-- Modal de Tallas -->
    <div class="modal fade" id="detailsModal{{ $trabajador->id }}" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel{{ $trabajador->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content shadow-lg border-0 rounded">
        
        <!-- Header -->
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="detailsModalLabel{{ $trabajador->id }}">
            <i class="fas fa-tshirt mr-2"></i> 
            Detalles de Tallas - {{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }}
            </h5>
            <button type="button"
                            class="btn btn-light btn-sm rounded-circle shadow-sm"
                            data-dismiss="modal"
                            aria-label="Cerrar"
                            style="
                                position: absolute;
                                top: 16px;
                                right: 16px;
                                width: 32px;
                                height: 32px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                z-index: 10;
                            ">
            <span aria-hidden="true" class="text-dark" style="font-size: 1.2rem;">&times;</span>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
            <div class="row">
            <!-- Imagen del trabajador -->
            <div class="col-md-4 text-center mb-3">
                @if($trabajador->Foto)
                <img src="{{ asset('storage/'.$trabajador->Foto) }}" class="rounded-circle img-fluid shadow" style="max-width: 150px;" alt="Foto de {{ $trabajador->Nombre }}">
                @else
                <img src="{{ asset('images/default-avatar.png') }}" class="rounded-circle img-fluid shadow" style="max-width: 150px;" alt="Imagen predeterminada">
                @endif
            </div>

            <!-- Tallas -->
            <div class="col-md-8">
                <h6 class="text-muted text-uppercase mb-3">Tallas Registradas</h6>
                <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="thead-light">
                    <tr>
                        <th>Prenda</th>
                        <th class="text-center">Talla</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($tallasPorTrabajador as $talla)
                        <tr>
                        <td>
                            <i class="fas 
                            @if($talla->tipoVestimenta->Nombre == 'Polera') fa-tshirt
                            @elseif($talla->tipoVestimenta->Nombre == 'Polerón') fa-hoodie
                            @elseif($talla->tipoVestimenta->Nombre == 'Pantalón') fa-user
                            @elseif($talla->tipoVestimenta->Nombre == 'Geólogo') fa-hard-hat
                            @elseif($talla->tipoVestimenta->Nombre == 'Jacketa') fa-vest
                            @elseif($talla->tipoVestimenta->Nombre == 'Calzado') fa-shoe-prints
                            @else fa-tag
                            @endif mr-2"></i>
                            {{ $talla->tipoVestimenta->Nombre }}
                        </td>
                        <td class="text-center">
                            <span class="badge badge-pill badge-primary text-dark px-3 py-2 shadow-sm">
                            {{ $talla->talla }}
                            </span>
                        </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <img src="{{ asset('images/logo.png') }}" alt="Logo de la Empresa" class="img-fluid" style="max-height: 40px; margin-right: auto;">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times mr-1"></i> Cerrar
            </button>
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
