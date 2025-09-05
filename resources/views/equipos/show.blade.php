@extends('layouts.app')

@section('title', 'Detalle del equipo')

@section('content')
<div class="container py-3">

    @php
        function estado_badge($estado) {
            $map = [
                'En funcionamiento' => 'success',
                'Operativo' => 'success',
                'Mantenimiento' => 'warning',
                'Fuera de servicio' => 'danger',
                'Baja' => 'secondary',
            ];
            $variant = isset($map[$estado]) ? $map[$estado] : 'secondary';
            return '<span class="badge badge-'.$variant.'">'.$estado.'</span>';
        }
    @endphp

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Equipo #{{ $equipo->id }}</h1>
        <div>
            <a href="{{ route('equipos.index') }}" class="btn btn-light">Volver</a>
            @can('update', $equipo)
            <a href="{{ route('equipos.edit', $equipo) }}" class="btn btn-primary">Editar</a>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-uppercase text-muted">Identificación</h6>
                    <table class="table table-sm mb-4">
                        <tr><th scope="row" style="width:40%">Tipo</th><td>{{ $equipo->tipo }}</td></tr>
                        <tr><th scope="row">Marca</th><td>{{ $equipo->marca }}</td></tr>
                        <tr><th scope="row">Modelo</th><td>{{ $equipo->modelo }}</td></tr>
                        <tr><th scope="row">Ubicación</th><td>{{ $equipo->ubicacion }}</td></tr>
                        <tr><th scope="row">Responsable</th><td>{{ $equipo->usuario_asignado }}</td></tr>
                        <tr><th scope="row">Estado</th><td>{!! estado_badge($equipo->estado) !!}</td></tr>
                    </table>

                    <h6 class="text-uppercase text-muted">Red y Sistema</h6>
                    <table class="table table-sm mb-4">
                        <tr><th scope="row" style="width:40%">Nombre del equipo</th><td>{{ $equipo->nombre_equipo }}</td></tr>
                        <tr><th scope="row">Dirección IP</th><td>{{ $equipo->direccion_ip }}</td></tr>
                        <tr><th scope="row">Versión de Windows</th><td>{{ $equipo->version_windows }}</td></tr>
                        <tr><th scope="row">Procesador</th><td>{{ $equipo->procesador }}</td></tr>
                        <tr><th scope="row">RAM</th><td>{{ $equipo->ram }}</td></tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <h6 class="text-uppercase text-muted">Operación</h6>
                    <table class="table table-sm mb-4">
                        <tr><th scope="row" style="width:40%">Controlador</th><td>{{ $equipo->controlador }}</td></tr>
                        <tr><th scope="row">Función principal</th><td>{{ $equipo->funcion_principal }}</td></tr>

                        {{-- Campos de impresora (solo si aplica) --}}
                        @if($equipo->tipo === 'Impresora')
                            <tr><th scope="row">Tipo de impresora</th><td>{{ $equipo->tipo_impresora }}</td></tr>
                            <tr><th scope="row">Resolución</th><td>{{ $equipo->resolucion }}</td></tr>
                            <tr><th scope="row">Tamaño de etiqueta</th><td>{{ $equipo->tamano_etiqueta }}</td></tr>
                        @endif

                        <tr><th scope="row">Observación</th><td>{{ $equipo->observacion }}</td></tr>
                    </table>

                    {{-- Sensible: mostrar solo con permiso y enmascarado por defecto --}}
                    <h6 class="text-uppercase text-muted">Credenciales</h6>
                    <table class="table table-sm">
                        <tr>
                            <th scope="row" style="width:40%">Usuario</th>
                            <td>{{ $equipo->usuario }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Contraseña</th>
                            <td>
                                @can('viewSensitive', $equipo)
                                    <span id="pwd" data-real="{{ $equipo->contrasena }}">••••••••</span>
                                    <button type="button" class="btn btn-link btn-sm p-0 ml-2" id="togglePwd">Mostrar</button>
                                @else
                                    —
                                @endcan
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="text-right text-muted small">
                <span class="mr-3">Creado: {{ optional($equipo->created_at)->diffForHumans() }}</span>
                <span>Actualizado: {{ optional($equipo->updated_at)->diffForHumans() }}</span>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
@can('viewSensitive', $equipo)
<script>
  (function(){
    var shown = false;
    $('#togglePwd').on('click', function(){
        var $el = $('#pwd');
        if (!shown) {
            $el.text($el.data('real'));
            $(this).text('Ocultar');
        } else {
            $el.text('••••••••');
            $(this).text('Mostrar');
        }
        shown = !shown;
    });
  })();
</script>
@endcan
@endpush
