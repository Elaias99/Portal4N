<!-- resources/views/reclamos/_modal.blade.php -->
<div class="modal fade" id="reclamoModal" tabindex="-1" role="dialog" aria-labelledby="reclamoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reclamoModalLabel">Reportar Reclamo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="reclamoForm" action="{{ route('reclamos.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id_bulto" id="modal-bulto-id">

                    <div class="form-group">
                        <label for="codigo_bulto" class="form-label">Código del Bulto</label>
                        <input type="text" class="form-control" id="modal-bulto-codigo" readonly>
                    </div>

                    <div class="form-group">
                        <label for="area_id" class="form-label">Asignar a Área:</label>
                        <select class="form-control" name="area_id" required>
                            <option value="" disabled selected>Seleccione un área</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="descripcion" class="form-label">Descripción del Problema</label>
                        <textarea class="form-control" name="descripcion" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Enviar Reclamo</button>
                </form>
            </div>
        </div>
    </div>
</div>
