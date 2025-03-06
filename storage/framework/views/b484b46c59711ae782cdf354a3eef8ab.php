

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Lista de Compras</h1>

    <!-- Botón Agregar -->
    <div class="d-flex align-items-center mb-3">
        <a href="<?php echo e(route('compras.create')); ?>" 
           class="btn btn-outline-primary shadow-sm" 
           data-bs-toggle="tooltip" 
           title="Agregar Compra">
            <i class="fa-solid fa-plus fa-lg"></i>
        </a>
    </div>

    <!-- Filtros -->
    <form method="GET" action="<?php echo e(route('compras.index')); ?>" class="mb-4">
        <div class="row g-3">
            <!-- Filtro Año -->
            <div class="col-md-3">
                <label for="year" class="form-label">Año</label>
                <select name="year" id="year" class="form-select">
                    <option value="">Todos</option>
                    <option value="2025" <?php echo e(request('year') == '2025' ? 'selected' : ''); ?>>2025</option>
                    <option value="2024" <?php echo e(request('year') == '2024' ? 'selected' : ''); ?>>2024</option>
                    <option value="2023" <?php echo e(request('year') == '2023' ? 'selected' : ''); ?>>2023</option>
                </select>
            </div>

            <!-- Filtro Mes -->
            <div class="col-md-3">
                <label for="month" class="form-label">Mes</label>
                <select name="month" id="month" class="form-select">
                    <option value="">Todos</option>
                    <option value="Enero" <?php echo e(request('month') == 'Enero' ? 'selected' : ''); ?>>Enero</option>
                    <option value="Febrero" <?php echo e(request('month') == 'Febrero' ? 'selected' : ''); ?>>Febrero</option>
                    <option value="Marzo" <?php echo e(request('month') == 'Marzo' ? 'selected' : ''); ?>>Marzo</option>
                    <option value="Abril" <?php echo e(request('month') == 'Abril' ? 'selected' : ''); ?>>Abril</option>
                </select>
            </div>

            <!-- Filtro Proveedor -->
            <div class="col-md-3">
                <label for="provider" class="form-label">Proveedor</label>
                <select name="provider" id="provider" class="form-select">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = $proveedores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $proveedor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($proveedor->razon_social); ?>" <?php echo e(request('provider') == $proveedor->razon_social ? 'selected' : ''); ?>>
                            <?php echo e($proveedor->razon_social); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <!-- Filtro Estado -->
            <div class="col-md-3">
                <label for="status" class="form-label">Estado</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="Pendiente" <?php echo e(request('status') == 'Pendiente' ? 'selected' : ''); ?>>Pendiente</option>
                    <option value="Pagado" <?php echo e(request('status') == 'Pagado' ? 'selected' : ''); ?>>Pagado</option>
                    <option value="Abonado" <?php echo e(request('status') == 'Abonado' ? 'selected' : ''); ?>>Abonado</option>
                    <option value="No Pagar" <?php echo e(request('status') == 'No Pagar' ? 'selected' : ''); ?>>No Pagar</option>
                </select>
            </div>
        </div>

        <!-- Botones -->
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?php echo e(route('compras.index')); ?>" class="btn btn-secondary">Limpiar Filtros</a>
        </div>
    </form>

    <!-- Mensaje de éxito -->
    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-regular fa-circle-check me-2"></i><?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tabla Scrollable -->
    <div class="table-responsive shadow-sm rounded" style="overflow-x: auto;">
        <table class="table table-hover align-middle table-striped">
            <thead class="bg-secondary text-white">
                <tr>
                    <th>#</th>
                    <th>Usuario</th>
                    <th>Centro de Costo</th>
                    <th>Glosa</th>
                    <th>Observacion</th>
                    <th>Tipo Pago</th>
                    <th>Empresa Facturadora</th>
                    <th>Año</th>
                    <th>Mes de servicio</th>
                    <th>Razón Social</th>
                    <th>Rut Razón Social</th>
                    <th>Tipo de Documento</th>
                    <th>Fecha del Documento</th>
                    <th>Número del Documento</th>
                    <th>Orden de Compra (O.C)</th>
                    <th>Pago Total</th>
                    <th>Fecha Vencimiento</th>
                    <th>Forma de Pago</th>
                    <th>Archivo O.C</th>
                    <th>Archivo Documento</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $compras; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $compra): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($loop->iteration); ?></td>
                        <td><?php echo e($compra->user->name ?? 'No especificado'); ?></td>
                        <td><?php echo e($compra->centro_costo); ?></td>
                        <td><?php echo e($compra->glosa); ?></td>
                        <td><?php echo e($compra->observacion); ?></td>
                        <td><?php echo e($compra->tipo_pago); ?></td>
                        <td><?php echo e($compra->empresa->Nombre); ?></td>
                        <td><?php echo e($compra->año); ?></td>
                        <td><?php echo e($compra->mes); ?></td>
                        <td><?php echo e($compra->proveedor->razon_social); ?></td>
                        <td><?php echo e($compra->proveedor->rut); ?></td>
                        <td><?php echo e($compra->tipo_documento); ?></td>
                        <td><?php echo e($compra->fecha_documento); ?></td>
                        <td><?php echo e($compra->numero_documento); ?></td>
                        <td><?php echo e($compra->oc); ?></td>
                        <td><?php echo e(number_format($compra->pago_total, 2)); ?></td>
                        <td><?php echo e($compra->fecha_vencimiento); ?></td>
                        <td><?php echo e($compra->forma_pago); ?></td>
                        <td>
                            <?php if($compra->archivo_oc): ?>
                                <a href="<?php echo e(route('compras.descargarArchivoOC', $compra->id)); ?>" target="_blank">Ver O.C</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($compra->archivo_documento): ?>
                                <a href="<?php echo e(route('compras.descargarArchivoDocumento', $compra->id)); ?>" target="_blank">Ver Documento</a>
                            <?php endif; ?>
                        </td>


                        <td>
                            <form action="<?php echo e(route('compras.updateStatus', $compra->id)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PATCH'); ?>
                        
                                <select name="status" class="form-select" style="min-width: 120px;" onchange="this.form.submit()">
                                    <option value="Pendiente" <?php echo e($compra->status == 'Pendiente' ? 'selected' : ''); ?>>Pendiente</option>
                                    <option value="Pagado" <?php echo e($compra->status == 'Pagado' ? 'selected' : ''); ?>>Pagado</option>
                                    <option value="Abonado" <?php echo e($compra->status == 'Abonado' ? 'selected' : ''); ?>>Abonado</option>
                                    <option value="No Pagar" <?php echo e($compra->status == 'No Pagar' ? 'selected' : ''); ?>>No Pagar</option>
                                </select>
                            </form>
                        </td>
                        
                        
                        
                        
                        


                        <td class="text-center">
                            <a href="<?php echo e(route('compras.edit', $compra->id)); ?>" class="btn btn-warning btn-sm me-2 shadow-sm" data-bs-toggle="tooltip" title="Editar">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <form action="<?php echo e(route('compras.destroy', $compra->id)); ?>" method="POST" class="d-inline"
                                onsubmit="return confirm('¿Está seguro de que desea eliminar esta compra? Esta acción no se puede deshacer.');">
                              <?php echo csrf_field(); ?>
                              <?php echo method_field('DELETE'); ?>
                              <button type="submit" class="btn btn-danger btn-sm shadow-sm" data-bs-toggle="tooltip" title="Eliminar">
                                  <i class="fa-solid fa-trash"></i>
                              </button>
                          </form>
                          
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="22" class="text-center text-muted">No hay compras registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/compras/index.blade.php ENDPATH**/ ?>