

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Lista de Proveedores</h1>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <!-- Exportar Dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle shadow-sm" type="button" id="exportDropdown" data-toggle="dropdown" aria-expanded="false">
                <i class="fa-regular fa-file-excel me-2"></i> Exportar
            </button>
            <div class="dropdown-menu shadow-sm fade" aria-labelledby="exportDropdown">
                <a class="dropdown-item d-flex align-items-center" href="<?php echo e(route('proveedores.export')); ?>">
                    <i class="fa-solid fa-file-export me-2 text-success"></i> Exportar a Excel
                </a>
            </div>
        </div>

        <!-- Barra de búsqueda -->
        <div class="d-flex align-items-center">
            <input type="text" class="form-control shadow-sm me-2" placeholder="Buscar proveedor..." id="search">
            <button class="btn btn-outline-primary shadow-sm">
                <i class="fa-solid fa-search"></i>
            </button>
        </div>

        <!-- Botón Agregar -->
        <div class="d-flex align-items-center">
            <a href="<?php echo e(route('proveedores.create')); ?>" 
               class="btn btn-outline-primary shadow-sm" 
               data-bs-toggle="tooltip" 
               title="Agregar Proveedor">
                <i class="fa-solid fa-user-plus fa-lg"></i>
            </a>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-regular fa-circle-check me-2"></i><?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tabla de Proveedores -->
    <div class="table-responsive shadow-sm rounded">
        <table class="table table-hover align-middle">
            <thead class="bg-secondary text-white">
                <tr>
                    <th>#</th>
                    <th>Razón Social</th>
                    <th>RUT Razón Social</th>
                    <th>Teléfono Empresa</th>
                    <th>Banco</th>
                    <th>Representante Legal</th>
                    <th>Teléfono Representante</th>
                    <th class="text-center"></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $proveedores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $proveedor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <!-- Fila principal -->
                    <tr class="accordion-toggle" data-toggle="collapse" data-target="#details-<?php echo e($proveedor->id); ?>" aria-expanded="false">
                        <td><?php echo e($loop->iteration); ?></td>
                        <td><?php echo e($proveedor->razon_social); ?></td>
                        <td><?php echo e($proveedor->rut); ?></td>
                        <td><?php echo e($proveedor->telefono_empresa); ?></td>
                        <td><?php echo e($proveedor->banco); ?></td>
                        <td><?php echo e($proveedor->Nombre_RepresentanteLegal); ?></td>
                        <td><?php echo e($proveedor->Telefono_RepresentanteLegal); ?></td>
                        <td class="text-center">
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" title="Ver Detalles">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <!-- Detalles del proveedor -->
                    <tr class="collapse" id="details-<?php echo e($proveedor->id); ?>">
                        <td colspan="8" class="bg-light">
                            <div class="p-4 rounded shadow-sm border">
                                <div class="row">
                                    <!-- Columna 1: Direcciones -->
                                    <div class="col-md-6">
                                        <h4 class="mb-3">Direcciones</h4>
                                        <p><strong>Dirección Facturación:</strong> <?php echo e($proveedor->direccion_facturacion); ?></p>
                                        <p><strong>Dirección Despacho:</strong> <?php echo e($proveedor->direccion_despacho); ?></p>
                                        <p><strong>Comuna Empresa:</strong> <?php echo e($proveedor->comuna_empresa); ?></p>

                                        <!-- Datos Bancarios -->
                                        <h4 class="mt-4 mb-3">Datos Bancarios</h4>
                                        <p><strong>Banco:</strong> <?php echo e($proveedor->banco); ?></p>
                                        <p><strong>Tipo de Cuenta:</strong> <?php echo e($proveedor->tipo_cuenta); ?></p>
                                        <p><strong>Número de Cuenta:</strong> <?php echo e($proveedor->nro_cuenta); ?></p>
                                        <p><strong>Correo Bancario:</strong> <?php echo e($proveedor->correo_banco); ?></p>
                                        <p><strong>Razón Social Asociada a la Cuenta:</strong> <?php echo e($proveedor->nombre_razon_social_banco); ?></p>
                                        <p><strong>Método de Pago:</strong> <?php echo e($proveedor->tipo_pago); ?></p>




                                    </div>
                    
                                    <!-- Columna 2: Contactos y Bancarios -->
                                    <div class="col-md-6">
                                        <!-- Representante Legal -->
                                        <h4 class="mb-3">Representante Legal</h4>
                                        <p><strong>Correo Electrónico:</strong> <?php echo e($proveedor->Correo_RepresentanteLegal); ?></p>
                    
                                        <!-- Contactos Adicionales -->
                                        <h4 class="mt-4 mb-3">Contactos Adicionales</h4>
                                        <p><strong>Contacto 1:</strong></p>
                                        <ul>
                                            <li><strong>Nombre:</strong> <?php echo e($proveedor->contacto_nombre); ?></li>
                                            <li><strong>Teléfono:</strong> <?php echo e($proveedor->contacto_telefono); ?></li>
                                            <li><strong>Correo:</strong> <?php echo e($proveedor->contacto_correo); ?></li>
                                            <li><strong>Cargo:</strong> <?php echo e($proveedor->cargo_contacto1); ?></li>
                                        </ul>
                                        <p><strong>Contacto 2:</strong></p>
                                        <ul>
                                            <li><strong>Nombre:</strong> <?php echo e($proveedor->nombre_contacto2); ?></li>
                                            <li><strong>Teléfono:</strong> <?php echo e($proveedor->telefono_contacto2); ?></li>
                                            <li><strong>Correo:</strong> <?php echo e($proveedor->correo_contacto2); ?></li>
                                            <li><strong>Cargo:</strong> <?php echo e($proveedor->cargo_contacto2); ?></li>
                                        </ul>
                    

                                    </div>
                                </div>
                    
                                <!-- Acciones -->
                                <div class="d-flex justify-content-end mt-3">
                                    <a href="<?php echo e(route('proveedores.edit', $proveedor->id)); ?>" class="btn btn-warning btn-sm me-2 shadow-sm" data-bs-toggle="tooltip" title="Editar">
                                        <i class="fa-regular fa-pen-to-square"></i> Editar
                                    </a>
                                    <form action="<?php echo e(route('proveedores.destroy', $proveedor->id)); ?>" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este proveedor?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-danger btn-sm shadow-sm" data-bs-toggle="tooltip" title="Eliminar">
                                            <i class="fa-solid fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>



                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No hay proveedores registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Activación de Tooltips -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/proveedores/index.blade.php ENDPATH**/ ?>