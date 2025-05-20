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
                <form id="reclamoForm" action="{{ route('reclamos.store') }}" method="POST" enctype="multipart/form-data">
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
                        <label for="casuistica_inicial_id" class="form-label">Motivo del Reclamo (Casuística Inicial)</label>
                        <select class="form-control" name="casuistica_inicial_id" required>
                            <option value="" disabled selected>Seleccione una casuística</option>
                            @foreach ($casuisticas as $casuistica)
                                <option value="{{ $casuistica->id }}">{{ $casuistica->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    

                    <div class="form-group">
                        <label for="importancia" class="form-label">Nivel de Importancia</label>
                        <select class="form-control" name="importancia" required>
                            <option value="baja">Baja</option>
                            <option value="media">Media</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                    


                    <div class="form-group">
                        <label for="descripcion" class="form-label">Descripción del Problema</label>
                        <textarea class="form-control" name="descripcion" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="foto">Foto del problema (opcional)</label>
                        <input type="file" name="foto" class="form-control">
                    </div>

                    <br>



                    <button type="submit" class="btn btn-primary">Enviar Reclamo</button>
                </form>
            </div>
        </div>
    </div>
</div>
