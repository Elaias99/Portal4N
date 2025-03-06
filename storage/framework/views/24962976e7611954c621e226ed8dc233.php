<!-- Modal para agregar un nuevo cargo -->
<div class="modal fade" id="nuevoCargoModal" tabindex="-1" role="dialog" aria-labelledby="nuevoCargoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nuevoCargoModalLabel">Agregar Nuevo Cargo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="nuevoCargoForm" action="<?php echo e(route('cargos.store')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label for="nombreCargo">Nombre del Cargo</label>
                        <input type="text" class="form-control" id="nombreCargo" name="nombreCargo" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\rr..hh_DESARROLLO\resources\views/cargos/modal.blade.php ENDPATH**/ ?>