@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">📋 Reclamos Pendientes</h2>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif


    <form method="GET" class="form-inline mb-3">
        <label for="filtro_trabajador" class="mr-2">Filtrar por trabajador:</label>
        <select name="trabajador_id" id="filtro_trabajador" class="form-control mr-2">
            <option value="">Todos</option>
            @foreach ($trabajadores as $t)
                <option value="{{ $t->id }}" {{ request('trabajador_id') == $t->id ? 'selected' : '' }}>
                    {{ $t->Nombre }} {{ $t->ApellidoPaterno }}
                </option>
            @endforeach
        </select>
        <br>
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </form>

    


    @if($reclamos->count())
        <div class="table-responsive">
            <table class="table table-bordered table-striped shadow-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>Importancia</th>
                        <th>ID Reclamo</th>
                        <th>ID Bulto</th>
                        <th>Área Derivada</th>
                        <th>Usuario Gestor</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                        <th>Tiempo Abierto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>



                <tbody>
                    @foreach($reclamos as $reclamo)
                        <tr>
                            {{-- Importancia --}}
                            <td>
                                @switch($reclamo->importancia)
                                    @case('urgente')
                                        <span class="badge bg-danger text-white">Urgente</span>
                                        @break
                                    @case('alta')
                                        <span class="badge bg-warning text-dark">Alta</span>
                                        @break
                                    @case('media')
                                        <span class="badge bg-info text-dark">Media</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">Baja</span>
                                @endswitch
                            </td>

                            {{-- ID Reclamo --}}
                            <td>#{{ $reclamo->id }}</td>

                            {{-- ID Bulto --}}
                            <td>
                                @if ($reclamo->bulto)
                                    {{ $reclamo->bulto->codigo_bulto }}
                                @elseif ($reclamo->tipo_solicitud === 'consulta')
                                    <span class="text-info">Consulta general</span>
                                @else
                                    <span class="text-muted">Sin bulto</span>
                                @endif
                            </td>

                            {{-- Área --}}
                            <td>{{ $reclamo->area->nombre ?? '—' }}</td>

                            {{-- Usuario Gestor --}}
                            <td>
                                {{ $reclamo->trabajador->Nombre ?? '' }}
                                {{ $reclamo->trabajador->ApellidoPaterno ?? '' }}
                            </td>

                            {{-- Descripción --}}
                            <td>{{ $reclamo->descripcion }}</td>

                            {{-- Fecha --}}
                            <td>{{ $reclamo->created_at->format('d-m-Y H:i') }}</td>

                            {{-- Tiempo Abierto --}}
                            <td>{{ $reclamo->created_at->diffForHumans() }}</td>

                            {{-- Acciones --}}
                            <td>
                                @php
                                    $correoInterno = resolvePerfilEmail(Auth::user()->email);
                                    $trabajadorActual = \App\Models\Trabajador::whereHas('user', function ($q) use ($correoInterno) {
                                        $q->where('email', $correoInterno);
                                    })->first();

                                    $tieneArea = $trabajadorActual && $trabajadorActual->area_id !== null;
                                    $puedeCerrar = $reclamo->estado !== 'cerrado' && $tieneArea;
                                @endphp

                                @if ($puedeCerrar)
                                    <button class="btn btn-sm btn-danger btn-abrir-modal"
                                            data-reclamo-id="{{ $reclamo->id }}">
                                        Cerrar
                                    </button>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>










            </table>
        </div>
    @else
        <div class="alert alert-info mt-4">
            No hay reclamos pendientes.
        </div>
    @endif

    <!-- Modal de Cierre de Reclamo -->
    <div class="modal fade" id="casuisticaModal" tabindex="-1" role="dialog" aria-labelledby="casuisticaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('reclamos.cerrar', 0) }}" id="cerrarReclamoForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="casuisticaModalLabel">Cerrar Reclamo</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="reclamo_id" id="reclamo_id">

                        <div class="form-group">
                            <label for="tipo_solicitud">Tipo de Solicitud</label>
                            <select name="tipo_solicitud" id="tipo_solicitud" class="form-control" required>
                                <option value="" disabled selected>Seleccione tipo...</option>
                                <option value="reclamo">Reclamo</option>
                                <option value="instruccion">Instrucción</option>
                                <option value="consulta">Consulta</option>
                            </select>
                        </div>

                        <div id="camposReclamo" style="display: none;">
                            <div class="form-group">
                                <label for="area_id">Área Responsable</label>


                                <select name="area_id" class="form-control" required>
                                    <option value="" disabled selected>Seleccione Área...</option>
                                    @foreach($areas as $area)
                                        <option value="{{ $area->id }}">{{ $area->nombre }}</option>
                                    @endforeach
                                </select>




                            </div>

                            <div class="form-group">
                                <label for="casuistica_id">Casuística</label>
                                <select name="casuistica_id" id="casuistica_id" class="form-control" required>
                                    <option value="" disabled selected>Seleccione Casuística...</option>
                                    @foreach($casuisticas as $casuistica)
                                        <option value="{{ $casuistica->id }}">{{ $casuistica->nombre }}</option>
                                    @endforeach
                                    <option value="otro">Otro (especificar)</option>
                                </select>
                            </div>

                            <div class="form-group mt-2" id="otra_casuistica_wrapper_cierre" style="display: none;">
                                <label for="otra_casuistica">Escriba la nueva casuística</label>
                                <input type="text" name="otra_casuistica" class="form-control" placeholder="Ingrese motivo personalizado">
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Cerrar Reclamo</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- Scripts -->
<script>
$(document).ready(function () {
    $('.btn-abrir-modal').on('click', function () {
        const reclamoId = $(this).data('reclamo-id');
        $('#reclamo_id').val(reclamoId);
        const formAction = "{{ route('reclamos.cerrar', '__id__') }}".replace('__id__', reclamoId);
        $('#cerrarReclamoForm').attr('action', formAction);
        $('#casuisticaModal').modal('show');

        // Reiniciar selección y validaciones previas
        $('#tipo_solicitud').val('');
        $('#camposReclamo').hide();
        $('select[name="area_id"]').removeAttr('required');
        $('select[name="casuistica_id"]').removeAttr('required');
    });

    $('#tipo_solicitud').on('change', function () {
        const tipo = $(this).val();

        $('select[name="area_id"]').val('');
        $('select[name="casuistica_id"]').val('');


        $('#camposReclamo').slideDown();

        if (tipo === 'reclamo') {
            $('select[name="area_id"]').closest('.form-group').show();
            $('select[name="casuistica_id"]').closest('.form-group').show();
            $('select[name="area_id"]').attr('required', true);
            $('select[name="casuistica_id"]').attr('required', true);
        } else if (tipo === 'instruccion' || tipo === 'consulta') {
            $('select[name="area_id"]').closest('.form-group').hide();
            $('select[name="casuistica_id"]').closest('.form-group').show();
            $('select[name="area_id"]').removeAttr('required');
            $('select[name="casuistica_id"]').attr('required', true);
        } else {
            $('#camposReclamo').slideUp();
            $('select[name="area_id"]').removeAttr('required');
            $('select[name="casuistica_id"]').removeAttr('required');
        }
    });


    
});
</script>


<script>
    $(document).on('change', '#casuistica_id', function () {
        if ($(this).val() === 'otro') {
            $('#otra_casuistica_wrapper_cierre').slideDown();
            $('input[name="otra_casuistica"]').attr('required', true);
        } else {
            $('#otra_casuistica_wrapper_cierre').slideUp();
            $('input[name="otra_casuistica"]').val('').removeAttr('required');
        }
    });
</script>




@unless(auth()->user()->hasAnyRole(['admin', 'jefe']))
    <div class="mb-3">
        <a href="{{ route('empleados.perfil') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Perfil
        </a>
    </div>
@endunless
@endsection
