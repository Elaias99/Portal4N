@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Listado de Bultos Importados</h2>

        <!-- Sección de Importación -->
        <div class="mb-4">
            <form action="{{ route('bultos.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="file">Subir archivo Excel:</label>
                    <input type="file" name="file" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success">Importar Bultos</button>
            </form>
        </div>

        <!-- Sección de Búsqueda -->
        <div class="mb-4">
            <h3>Buscar Bulto</h3>
            <form action="{{ route('bultos.index') }}" method="GET">
                <div class="form-group">
                    <label for="codigo_bulto">Código Bulto:</label>
                    <input type="text" name="codigo_bulto" id="codigo_bulto" class="form-control" placeholder="Ingrese el código a buscar">
                </div>
                <button type="submit" class="btn btn-primary">Buscar</button>
            </form>
        </div>

        <!-- Resultados de la Búsqueda -->
        @if(request()->has('codigo_bulto'))
            @if ($bultos && count($bultos) > 0)
                <h3 class="mt-4">Resultado de la búsqueda</h3>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Código Bulto</th>
                                <th>ID Envío</th>
                                <th>Atención</th>
                                <th>Numero Destino</th>
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
                                    <td>{{ $bulto->comuna }}</td>
                                    <td>{{ $bulto->razon_social }}</td>
                                    <td>{{ $bulto->fecha_entrega }}</td>
                                    <td>{{ $bulto->ubicacion }}</td>
                                    <td>{{ $bulto->region }}</td>
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
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-warning mt-3">
                    No se encontraron registros de bultos con ese código.
                </div>
            @endif
        @endif

        <!-- Modal para Reportar Reclamo -->
        <div class="modal fade" id="reclamoModal" tabindex="-1" role="dialog" aria-labelledby="reclamoModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reclamoModalLabel">Reportar Reclamo</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="reclamoForm" action="{{ route('reclamos.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="id_bulto" id="modal-bulto-id">
                            
                            <div class="form-group">
                                <label for="codigo_bulto" class="form-label">Código del Bulto</label>
                                <input type="text" class="form-control" id="modal-bulto-codigo" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label for="id_jefe" class="form-label">Asignar a:</label>
                                <select class="form-control" name="id_jefe" required>
                                    <option value="" disabled selected>Seleccione un responsable</option>
                                    @foreach ($jefes as $jefe)
                                        <option value="{{ $jefe->id }}">{{ $jefe->nombre }} - {{ $jefe->area }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="descripcion" class="form-label">Descripción del Problema</label>
                                <textarea class="form-control" name="descripcion" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Enviar Reclamo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function() {
        $('#reclamoModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var bultoId = button.data('bulto-id');
            var bultoCodigo = button.data('bulto-codigo');

            $('#modal-bulto-id').val(bultoId);
            $('#modal-bulto-codigo').val(bultoCodigo);
        });
    });
</script>
