@extends('layouts.app')





@section('content')
    <div class="container">
        <h2>Listado de Bultos Importados</h2>

        <!-- Sección de Importación -->
        <!-- Contenedor opcional para alinear a la derecha -->
        <div class="d-flex justify-content-end mb-4">
            <div class="dropdown">
                <!-- Botón que despliega el menú -->
                <button class="btn btn-outline-secondary dropdown-toggle shadow-sm" 
                        type="button" 
                        id="importDropdown" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false">
                    <i class="fa-regular fa-file-excel me-2"></i> Importar
                </button>

                <!-- Menú desplegable con el formulario dentro -->
                <div class="dropdown-menu shadow-sm fade" aria-labelledby="importDropdown">
                    <!-- Clase p-3 para un padding cómodo dentro del dropdown -->
                    <div class="p-3">
                        <form action="{{ route('bultos.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="file" class="form-label">Subir archivo Excel:</label>
                                <input type="file" name="file" id="file" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                Importar Bultos
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Búsqueda -->
        <div class="d-flex justify-content-center align-items-center my-4">
            <div class="text-center">
                <h3>Buscar Bulto</h3>
                <form action="{{ route('bultos.index') }}" method="GET">
                    <div class="form-group">
                        <input type="text" name="codigo_bulto" id="codigo_bulto" class="form-control text-center" 
                            placeholder="Ingrese el código a buscar" style="width: 400px; margin: auto;">
                    </div>
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </form>
            </div>
        </div>


        

        <!-- Resultados de la Búsqueda -->
        @if(request()->has('codigo_bulto'))
            <div class="d-flex justify-content-center">
                <div class="w-100">
                    <h4 class="text-center mt-2 mb-3">Resultado de la Búsqueda</h4>

                    @if ($bultos && count($bultos) > 0)
                        <div class="table-responsive mx-auto" style="max-height: 450px; overflow-y: auto; width: 90%;">

                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Código Bulto</th>
                                        <th>ID Envío</th>
                                        <th>Atención</th>
                                        <th>Número Destino</th>
                                        <th>Depto Destino</th>
                                        <th>Dirección</th>
                                        <th>Comuna</th>
                                        <th>Razón Social</th>
                                        <th>Fecha Entrega</th>
                                        <th>Ubicación</th>
                                        <th>Región</th>
                                        <th>Nombre Campaña</th>
                                        <th>Descripción Bulto</th>
                                        <th>Observación</th>
                                        <th>Referencia</th>
                                        <th>Peso</th>
                                        <th>Teléfono</th>
                                        <th>Mail</th>
                                        <th>Unidad</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bultos as $bulto)
                                        <tr>
                                            <td>{{ $bulto->id }}</td>
                                            <td>{{ $bulto->codigo_bulto }}</td>
                                            <td>{{ $bulto->id_envio }}</td>
                                            <td>{{ $bulto->atencion }}</td>
                                            <td>{{ $bulto->numero_destino }}</td>
                                            <td>{{ $bulto->depto_destino }}</td>
                                            <td>{{ $bulto->direccion }}</td>
                                            <td>{{ $bulto->comuna->Nombre ?? '—' }}</td>
                                            <td>{{ $bulto->razon_social }}</td>
                                            <td>{{ $bulto->fecha_entrega }}</td>
                                            <td>{{ $bulto->ubicacion }}</td>
                                            <td>{{ $bulto->comuna->region->Nombre ?? '—' }}</td>
                                            <td>{{ $bulto->nombre_campana }}</td>
                                            <td>{{ $bulto->descripcion_bulto }}</td>
                                            <td>{{ $bulto->observacion }}</td>
                                            <td>{{ $bulto->referencia }}</td>
                                            <td>{{ $bulto->peso }}</td>
                                            <td>{{ $bulto->telefono }}</td>
                                            <td>{{ $bulto->mail }}</td>
                                            <td>{{ $bulto->unidad }}</td>
                                            <td>{{ ucfirst($bulto->estado) }}</td>
                                            <td>
                                                <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#reclamoModal"
                                                        data-bulto-id="{{ $bulto->id }}"
                                                        data-bulto-codigo="{{ $bulto->codigo_bulto }}">
                                                    Reportar Reclamo
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @include('reclamos._modal')
                                    @include('reclamos._modal-script')

                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning text-center mt-3">
                            No se encontraron registros de bultos con ese código.
                        </div>
                    @endif
                </div>
            </div>
        @endif


        <!-- Modal para Reportar Reclamo -->

    </div>
@endsection


<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

