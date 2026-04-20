<!-- Modal -->
<div class="modal fade" id="employeeModal{{ $empleado->id }}" tabindex="-1" role="dialog" aria-labelledby="employeeModalLabel{{ $empleado->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-xl" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">

            <!-- Header -->
            <!-- Header -->
            <div class="modal-header bg-white border-bottom position-relative" style="padding: 1.25rem 1.5rem;">
                <div>
                    <h5 class="modal-title mb-1" id="employeeModalLabel{{ $empleado->id }}" style="font-size: 1.45rem; font-weight: 600;">
                        Información Detallada de {{ $empleado->Nombre }} {{ $empleado->ApellidoPaterno }}
                    </h5>
                    <small class="text-muted">
                        Ficha del trabajador
                    </small>
                </div>

                <button type="button"
                        class="btn btn-light btn-sm rounded-circle shadow-sm"
                        data-dismiss="modal"
                        aria-label="Cerrar"
                        style="
                            position: absolute;
                            top: 16px;
                            right: 16px;
                            width: 38px;
                            height: 38px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            z-index: 10;
                        ">
                    <span aria-hidden="true" class="text-dark" style="font-size: 1.2rem;">&times;</span>
                </button>
            </div>

            <!-- Body -->
            <div class="modal-body bg-light" style="padding: 1.5rem;">
                <div class="row g-0">

                    <!-- Columna izquierda -->
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="h-100 bg-white border rounded shadow-sm d-flex flex-column align-items-center justify-content-start"
                             style="padding: 1.5rem; min-height: 100%;">
                            
                            <div class="mb-3 text-center">
                                @if($empleado->Foto)
                                    <img src="{{ url($empleado->Foto) }}"
                                         class="img-fluid rounded-circle border shadow-sm"
                                         alt="Foto de {{ $empleado->Nombre }}"
                                         loading="lazy"
                                         style="width: 220px; height: 220px; object-fit: cover;">
                                @else
                                    <img src="{{ asset('images/default-avatar.png') }}"
                                         class="img-fluid rounded-circle border shadow-sm"
                                         alt="Imagen predeterminada"
                                         loading="lazy"
                                         style="width: 220px; height: 220px; object-fit: cover;">
                                @endif
                            </div>

                            <div class="w-100 mt-2">
                                <div class="border rounded p-3 bg-light">
                                    <div class="mb-2">
                                        <small class="text-muted d-block">Nombre completo</small>
                                        <strong>
                                            {{ $empleado->Nombre }} {{ $empleado->SegundoNombre }} {{ $empleado->TercerNombre }} {{ $empleado->ApellidoPaterno }} {{ $empleado->ApellidoMaterno }}
                                        </strong>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted d-block">Cargo</small>
                                        <strong>{{ $empleado->cargo->Nombre ?? 'No disponible' }}</strong>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted d-block">Empresa</small>
                                        <strong>{{ $empleado->empresa->Nombre ?? 'No disponible' }}</strong>
                                    </div>

                                    <div>
                                        <small class="text-muted d-block">Situación</small>
                                        <strong>{{ $empleado->situacion->Nombre ?? 'No disponible' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna derecha -->
                    <div class="col-md-8">
                        <div class="bg-white border rounded shadow-sm h-100" style="padding: 1rem;">
                            <div class="table-responsive" style="max-height: 620px;">
                                <table class="table table-bordered table-sm mb-0 align-middle">
                                    <tbody>
                                        <tr>
                                            <th class="bg-light" style="width: 34%;">Rut</th>
                                            <td class="text-break">{{ $empleado->Rut }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Nombre Completo</th>
                                            <td class="text-break">
                                                {{ $empleado->Nombre }} {{ $empleado->SegundoNombre }} {{ $empleado->TercerNombre }} {{ $empleado->ApellidoPaterno }} {{ $empleado->ApellidoMaterno }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Fecha Nacimiento</th>
                                            <td>{{ $empleado->FechaNacimiento->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Edad</th>
                                            <td>{{ $empleado->edad }} años</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Número Celular</th>
                                            <td class="text-break">{{ $empleado->numero_celular }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Contacto Emergencia</th>
                                            <td class="text-break">
                                                {{ $empleado->nombre_emergencia }} | Número: {{ $empleado->contacto_emergencia }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Dirección</th>
                                            <td class="text-break">{{ $empleado->calle }} {{ $empleado->comuna->Nombre ?? '' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Salario Bruto</th>
                                            <td>{{ '$' . number_format($empleado->salario_bruto, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Correo Personal</th>
                                            <td class="text-break">{{ $empleado->CorreoPersonal }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Banco</th>
                                            <td>{{ $empleado->banco->nombre ?? 'No disponible' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Número de Cuenta</th>
                                            <td class="text-break">{{ $empleado->numero_cuenta }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Tipo de Cuenta</th>
                                            <td>{{ $empleado->tipo_cuenta }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Correo Corporativo</th>
                                            <td class="text-break">{{ $empleado->user->email ?? 'No disponible' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Empresa</th>
                                            <td>{{ $empleado->empresa->Nombre ?? 'No disponible' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Jefe Área</th>
                                            <td>{{ $empleado->jefe?->nombre ?? 'Sin jefe asignado' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Rut Empresa</th>
                                            <td class="text-break">{{ $empleado->empresa->rut ?? '' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Fecha Contratación</th>
                                            <td>{{ \Carbon\Carbon::parse($empleado->fecha_inicio_trabajo)->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Cargo</th>
                                            <td>{{ $empleado->cargo->Nombre ?? 'No disponible' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">AFP</th>
                                            <td>{{ $empleado->afp->Nombre ?? 'No disponible' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Salud</th>
                                            <td>{{ $empleado->salud->Nombre ?? 'No disponible' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Casino</th>
                                            <td>{{ $empleado->Casino }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Estado Civil</th>
                                            <td>{{ $empleado->estadoCivil->Nombre ?? 'No disponible' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Situación</th>
                                            <td>{{ $empleado->situacion->Nombre ?? 'No disponible' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Días Proporcionales Acumulados</th>
                                            <td>{{ $empleado->dias_proporcionales }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Turno</th>
                                            <td>{{ $empleado->turno ? $empleado->turno->nombre : 'No asignado' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light">Sistema de Trabajo</th>
                                            <td>{{ $empleado->sistemaTrabajo ? $empleado->sistemaTrabajo->nombre : 'No asignado' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer bg-white border-top d-flex justify-content-between align-items-center flex-wrap"
                 style="padding: 1rem 1.5rem;">
                
                <div class="mb-2 mb-md-0">
                    <img src="{{ asset('images/logo.png') }}"
                         alt="Logo de la Empresa"
                         class="img-fluid"
                         style="max-height: 42px;"
                         loading="lazy">
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    @role('admin')
                    <form action="{{ route('empleados.desvincular', $empleado->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit"
                                class="btn btn-outline-warning"
                                onclick="return confirm('¿Seguro que deseas desvincular a este empleado?');">
                            <i class="fa-solid fa-user-slash me-1"></i> Desvincular
                        </button>
                    </form>
                    @endrole

                    <a href="{{ route('historial-vacacion.index', ['trabajador_id' => $empleado->id]) }}"
                       class="btn btn-outline-primary">
                        <i class="fa-solid fa-calendar-days me-1"></i> Historial de Días
                    </a>

                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>