<!-- Modal para ver detalles de la solicitud -->
<div class="modal fade" id="solicitudModal{{ $solicitud->id }}" tabindex="-1" role="dialog" aria-labelledby="solicitudModalLabel{{ $solicitud->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="solicitudModalLabel{{ $solicitud->id }}">
                    Detalles de la Solicitud #{{ $solicitud->id }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Contenido Detallado de la Solicitud -->
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th>Tipo de Día Solicitado</th>
                            <td>{{ ucfirst($solicitud->tipo_dia) }}</td> <!-- Mostrar dinámicamente el tipo de día -->
                        </tr>
                        <tr>
                            <th>Cantidad de Días Solicitados</th>
                            <td>{{ $solicitud->vacacion->dias ?? 'No especificado' }}</td> <!-- Mostrar cantidad de días -->
                        </tr>
                        <tr>
                            <th>Comentario del Administrador</th>
                            <td>{{ $solicitud->comentario_admin ?? 'Sin comentarios aún' }}</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td>
                                <span class="@if($solicitud->estado === 'aprobado') text-success
                                             @elseif($solicitud->estado === 'rechazado') text-danger
                                             @else text-warning @endif">
                                    {{ ucfirst($solicitud->estado) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Fecha de Solicitud</th>
                            <td>{{ $solicitud->created_at }}</td>
                        </tr>
                        <tr>
                            <th>Fecha de Respuesta</th>
                            <td>{{ $solicitud->fecha_respuesta ? $solicitud->fecha_respuesta : 'Pendiente' }}</td>
                        </tr>
                        @if($solicitud->vacacion)
                        <tr>
                            <th>Fecha de Inicio</th>
                            <td>{{ $solicitud->vacacion->fecha_inicio->format('Y-m-d') }}</td>
                        </tr>
                        <tr>
                            <th>Fecha de Fin</th>
                            <td>{{ $solicitud->vacacion->fecha_fin->format('Y-m-d') }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
