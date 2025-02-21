<!-- Modal -->
<div class="modal fade" id="employeeModal{{ $empleado->id }}" tabindex="-1" role="dialog" aria-labelledby="employeeModalLabel{{ $empleado->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"> <!-- modal-lg para un modal más grande -->
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="employeeModalLabel{{ $empleado->id }}">Información Detallada de {{ $empleado->Nombre }} {{ $empleado->ApellidoPaterno }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Contenido Detallado del Empleado -->
          <div class="row">
              <div class="col-md-4">
                  @if($empleado->Foto)
                      <img src="{{ url('storage/' . $empleado->Foto) }}" class="img-fluid" alt="Foto de {{ $empleado->Nombre }}">
                  @else
                      <img src="{{ url('images/default-avatar.png') }}" class="img-fluid" alt="Imagen predeterminada">
                  @endif
              </div>
              <div class="col-md-8">
                  <table class="table table-bordered">
                      <tbody>
                          <tr>
                              <th>Rut</th>
                              <td>{{ $empleado->Rut }}</td>
                          </tr>
                          <tr>
                              <th>Nombre Completo</th>
                              <td>{{ $empleado->Nombre }} {{ $empleado->SegundoNombre }} {{ $empleado->TercerNombre }} {{ $empleado->ApellidoPaterno }} {{ $empleado->ApellidoMaterno }}</td>
                          </tr>
                          <tr>
                              <th>Fecha Nacimiento</th>
                              <td>{{ $empleado->FechaNacimiento->format('d/m/Y') }}</td>
                          </tr>
                          <tr>
                              <th>Edad</th>
                              <td>{{ $empleado->edad }} años</td>
                          </tr>
                          <tr>
                              <th>Número Celular</th>
                              <td>{{ $empleado->numero_celular }}</td>
                          </tr>
                          <tr>
                              <th>Contacto Emergencia</th>
                              <td>{{ $empleado->nombre_emergencia }} | Número: {{ $empleado->contacto_emergencia }}</td>
                          </tr>
                          <tr>
                              <th>Dirección</th>
                              <td>{{ $empleado->calle }} {{ $empleado->comuna->Nombre }}</td>
                          </tr>

                          <tr>
                              <th>Salario Bruto</th>
                              <td>{{ '$' . number_format($empleado->salario_bruto, 0, ',', '.') }}</td>
                          </tr>
                            
                          <tr>
                              <th>Correo Personal</th>
                              <td>{{ $empleado->CorreoPersonal }}</td>
                          </tr>



                          <tr>
                            <th>Banco</th>
                            <td>{{ $empleado->banco }}</td>
                          </tr>

                          <tr>
                            <th>Número de Cuenta</th>
                            <td>{{ $empleado->numero_cuenta }}</td>
                          </tr>

                          <tr>
                            <th>Tipo de Cuenta</th>
                            <td>{{ $empleado->tipo_cuenta }}</td>
                          </tr>







                          <tr>
                            <th>Correo Corporativo</th>
                            <td>{{ $empleado->user->email }}</td>
                          </tr>

                          <tr>
                              <th>Empresa</th>
                              <td>{{ $empleado->empresa->Nombre }}</td>
                          </tr>

                          <tr>
                            <th>Jefe Área</th>
                            <td>{{ $empleado->jefe->nombre }}</td>
                          </tr>

                          <tr>
                            <th>Rut Empresa</th>
                            <td>{{ $empleado->Rut_Empresa }}</td>
                          </tr>


                          <tr>
                            <th>Fecha Contratación</th>
                            <td>{{ \Carbon\Carbon::parse($empleado->fecha_inicio_trabajo)->format('d/m/Y') }}</td>

                          </tr>



                          <tr>
                              <th>Cargo</th>
                              <td>{{ $empleado->cargo->Nombre }}</td>
                          </tr>
                          <tr>
                              <th>AFP</th>
                              <td>{{ $empleado->afp->Nombre }}</td>
                          </tr>
                          <tr>
                              <th>Salud</th>
                              <td>{{ $empleado->salud->Nombre }}</td>
                          </tr>
                          <tr>
                              <th>Casino</th>
                              <td>{{ $empleado->Casino }}</td>
                          </tr>
                          <tr>
                              <th>Estado Civil</th>
                              <td>{{ $empleado->estadoCivil->Nombre }}</td>
                          </tr>


                          <tr>
                              <th>Contrato Firmado</th>
                              <td>{{ $empleado->ContratoFirmado }}</td>
                          </tr>

                          <tr>
                            <th>Fecha Inicio Contrato</th>
                            <td>{{ \Carbon\Carbon::parse($empleado->fecha_inicio_contrato)->format('d/m/Y') }}</td>

                          </tr>


                          <tr>
                              <th>Anexo Contrato</th>
                              <td>{{ $empleado->AnexoContrato }}</td>
                          </tr>
                          <tr>
                              <th>Situación</th>
                              <td>{{ $empleado->situacion->Nombre }}</td>
                          </tr>

                          <tr>
                            <th>Turno</th>
                            <td>{{ $empleado->turno ? $empleado->turno->nombre : 'No asignado' }}</td>
                          </tr>

                          <tr>
                            <th>Sistema de Trabajo</th>
                            <td>{{ $empleado->sistemaTrabajo ? $empleado->sistemaTrabajo->nombre : 'No asignado' }}</td>
                          </tr>
                        
                        



                      </tbody>
                  </table>
              </div>
          </div>
        </div>
        <div class="modal-footer">
            <img src="{{ asset('images/logo.png') }}" alt="Logo de la Empresa" class="img-fluid" style="max-height: 40px; margin-right: auto;">

            <!-- Botón para Descargar PDF -->
            <a href="{{ route('empleados.exportCotizacion', $empleado->id) }}" class="btn btn-primary">
                <i class="fa-solid fa-file-pdf"></i> Descargar Cotización en PDF
            </a>


          <!-- Botón para Cerrar el Modal -->
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
  