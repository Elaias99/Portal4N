

<?php $__env->startSection('content'); ?>
<div class="container">
    <h1 class="text-center">Crear Nueva Factura</h1>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger">
            <ul>
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?php echo e(route('facturas.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label for="proveedor_id">Proveedor</label>
            <select name="proveedor_id" id="proveedor_id" class="form-control" required>
                <option value="">Seleccione un proveedor</option>
                <?php $__currentLoopData = $proveedores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $proveedor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($proveedor->id); ?>" <?php echo e(old('proveedor_id', $proveedorSeleccionado) == $proveedor->id ? 'selected' : ''); ?>>
                        <?php echo e($proveedor->razon_social); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div class="form-group">
            <label for="centro_costo">Centro de Costo</label>
            <input type="text" name="centro_costo" id="centro_costo" class="form-control" value="<?php echo e(old('centro_costo')); ?>" required>
        </div>

        <div class="form-group">
            <label for="empresa_id">Empresa</label>
            <select name="empresa_id" id="empresa_id" class="form-control" required>
                <option value="">Seleccione una empresa</option>
                <?php $__currentLoopData = $empresas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $empresa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($empresa->id); ?>" <?php echo e(old('empresa_id') == $empresa->id ? 'selected' : ''); ?>>
                        <?php echo e($empresa->Nombre); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        

        <div class="form-group">
            <label for="glosa">Glosa</label>
            <textarea name="glosa" id="glosa" class="form-control"><?php echo e(old('glosa')); ?></textarea>
        </div>

        <div class="form-group">
            <label for="comentario">Comentario</label>
            <textarea name="comentario" id="comentario" class="form-control"><?php echo e(old('comentario')); ?></textarea>
        </div>

        <div class="form-group">
            <label for="pagador">Pagador</label>
            <input type="text" name="pagador" id="pagador" class="form-control" value="<?php echo e(old('pagador')); ?>" required>
        </div>

        <div class="form-group">
            <label for="tipo_documento">Tipo de Documento</label>
            <select name="tipo_documento" id="tipo_documento" class="form-control" required>
                <?php $__currentLoopData = ['Factura', 'Boleta', 'Boleta Honorarios', 'Boleta de Tercero', 'Documento', 'Factura Exenta', 'Factura Pendiente']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tipo_documento): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($tipo_documento); ?>" <?php echo e(old('tipo_documento', $factura->tipo_documento ?? '') == $tipo_documento ? 'selected' : ''); ?>>
                        <?php echo e($tipo_documento); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>


        <div class="form-group">
            <label for="status">Estado</label>
            <select name="status" id="status" class="form-control" required>
                <option value="Pendiente" <?php echo e(old('status') == 'Pendiente' ? 'selected' : ''); ?>>Pendiente</option>
                <option value="Pagado" <?php echo e(old('status') == 'Pagado' ? 'selected' : ''); ?>>Pagado</option>
                <option value="Abonado" <?php echo e(old('status') == 'Abonado' ? 'selected' : ''); ?>>Abonado</option>
                <option value="No Pagar" <?php echo e(old('status') == 'No Pagar' ? 'selected' : ''); ?>>No Pagar</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success mt-3">Guardar Factura</button>
        <a href="<?php echo e(route('facturas.index')); ?>" class="btn btn-secondary mt-3">Cancelar</a>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/facturas/create.blade.php ENDPATH**/ ?>