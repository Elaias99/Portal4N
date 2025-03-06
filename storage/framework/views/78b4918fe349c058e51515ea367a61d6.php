<!-- Modal -->
<div class="modal fade" id="employeeModal<?php echo e($empleado->id); ?>" tabindex="-1" role="dialog" aria-labelledby="employeeModalLabel<?php echo e($empleado->id); ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"> <!-- modal-lg para un modal más grande -->
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="employeeModalLabel<?php echo e($empleado->id); ?>">Información Detallada de <?php echo e($empleado->Nombre); ?> <?php echo e($empleado->ApellidoPaterno); ?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Contenido Detallado del Empleado -->
          <div class="row">
              <div class="col-md-4">
                  <?php if($empleado->Foto): ?>
                      <img src="<?php echo e(url('storage/' . $empleado->Foto)); ?>" class="img-fluid" alt="Foto de <?php echo e($empleado->Nombre); ?>">
                  <?php else: ?>
                      <img src="<?php echo e(url('images/default-avatar.png')); ?>" class="img-fluid" alt="Imagen predeterminada">
                  <?php endif; ?>
              </div>
              <div class="col-md-8">
                  <table class="table table-bordered">
                      <tbody>
                          <tr>
                              <th>Rut</th>
                              <td><?php echo e($empleado->Rut); ?></td>
                          </tr>
                          <tr>
                              <th>Nombre Completo</th>
                              <td><?php echo e($empleado->Nombre); ?> <?php echo e($empleado->SegundoNombre); ?> <?php echo e($empleado->TercerNombre); ?> <?php echo e($empleado->ApellidoPaterno); ?> <?php echo e($empleado->ApellidoMaterno); ?></td>
                          </tr>
                          <tr>
                              <th>Fecha Nacimiento</th>
                              <td><?php echo e($empleado->FechaNacimiento->format('d/m/Y')); ?></td>
                          </tr>
                          <tr>
                              <th>Edad</th>
                              <td><?php echo e($empleado->edad); ?> años</td>
                          </tr>
                          <tr>
                              <th>Número Celular</th>
                              <td><?php echo e($empleado->numero_celular); ?></td>
                          </tr>
                          <tr>
                              <th>Contacto Emergencia</th>
                              <td><?php echo e($empleado->nombre_emergencia); ?> | Número: <?php echo e($empleado->contacto_emergencia); ?></td>
                          </tr>
                          <tr>
                              <th>Dirección</th>
                              <td><?php echo e($empleado->calle); ?> <?php echo e($empleado->comuna->Nombre); ?></td>
                          </tr>

                          <tr>
                              <th>Salario Bruto</th>
                              <td><?php echo e('$' . number_format($empleado->salario_bruto, 0, ',', '.')); ?></td>
                          </tr>
                            
                          <tr>
                              <th>Correo Personal</th>
                              <td><?php echo e($empleado->CorreoPersonal); ?></td>
                          </tr>



                          <tr>
                            <th>Banco</th>
                            <td><?php echo e($empleado->banco); ?></td>
                          </tr>

                          <tr>
                            <th>Número de Cuenta</th>
                            <td><?php echo e($empleado->numero_cuenta); ?></td>
                          </tr>

                          <tr>
                            <th>Tipo de Cuenta</th>
                            <td><?php echo e($empleado->tipo_cuenta); ?></td>
                          </tr>







                          <tr>
                            <th>Correo Corporativo</th>
                            <td><?php echo e($empleado->user->email); ?></td>
                          </tr>

                          <tr>
                              <th>Empresa</th>
                              <td><?php echo e($empleado->empresa->Nombre); ?></td>
                          </tr>

                          <tr>
                            <th>Jefe Área</th>
                            <td><?php echo e($empleado->jefe->nombre); ?></td>
                          </tr>

                          <tr>
                            <th>Rut Empresa</th>
                            <td><?php echo e($empleado->Rut_Empresa); ?></td>
                          </tr>


                          <tr>
                            <th>Fecha Contratación</th>
                            <td><?php echo e(\Carbon\Carbon::parse($empleado->fecha_inicio_trabajo)->format('d/m/Y')); ?></td>

                          </tr>



                          <tr>
                              <th>Cargo</th>
                              <td><?php echo e($empleado->cargo->Nombre); ?></td>
                          </tr>
                          <tr>
                              <th>AFP</th>
                              <td><?php echo e($empleado->afp->Nombre); ?></td>
                          </tr>
                          <tr>
                              <th>Salud</th>
                              <td><?php echo e($empleado->salud->Nombre); ?></td>
                          </tr>
                          <tr>
                              <th>Casino</th>
                              <td><?php echo e($empleado->Casino); ?></td>
                          </tr>
                          <tr>
                              <th>Estado Civil</th>
                              <td><?php echo e($empleado->estadoCivil->Nombre); ?></td>
                          </tr>


                          <tr>
                              <th>Contrato Firmado</th>
                              <td><?php echo e($empleado->ContratoFirmado); ?></td>
                          </tr>

                          <tr>
                            <th>Fecha Inicio Contrato</th>
                            <td><?php echo e(\Carbon\Carbon::parse($empleado->fecha_inicio_contrato)->format('d/m/Y')); ?></td>

                          </tr>


                          <tr>
                              <th>Anexo Contrato</th>
                              <td><?php echo e($empleado->AnexoContrato); ?></td>
                          </tr>
                          <tr>
                              <th>Situación</th>
                              <td><?php echo e($empleado->situacion->Nombre); ?></td>
                          </tr>

                          <tr>
                            <th>Turno</th>
                            <td><?php echo e($empleado->turno ? $empleado->turno->nombre : 'No asignado'); ?></td>
                          </tr>

                          <tr>
                            <th>Sistema de Trabajo</th>
                            <td><?php echo e($empleado->sistemaTrabajo ? $empleado->sistemaTrabajo->nombre : 'No asignado'); ?></td>
                          </tr>
                        
                        



                      </tbody>
                  </table>
              </div>
          </div>
        </div>
        <div class="modal-footer">
            <img src="<?php echo e(asset('images/logo.png')); ?>" alt="Logo de la Empresa" class="img-fluid" style="max-height: 40px; margin-right: auto;">

            <!-- Botón para Descargar PDF -->
            <a href="<?php echo e(route('empleados.exportCotizacion', $empleado->id)); ?>" class="btn btn-primary">
                <i class="fa-solid fa-file-pdf"></i> Descargar Cotización en PDF
            </a>


          <!-- Botón para Cerrar el Modal -->
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
  <?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/partials/employee_modal.blade.php ENDPATH**/ ?>