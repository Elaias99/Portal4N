<!-- Modal: Consulta General (sin bulto) -->
<div class="modal fade" id="consultaModal" tabindex="-1" role="dialog" aria-labelledby="consultaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header">
                <h5 class="modal-title" id="consultaModalLabel">
                    <i class="fa-solid fa-circle-question text-info me-1"></i> Nueva Consulta General
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {{-- Body --}}
            <div class="modal-body">
                <form id="consultaForm" action="{{ route('reclamos.consulta.store') }}" method="POST" enctype="multipart/form-data">

                    @csrf

                    {{-- Área Responsable --}}
                    <div class="form-group">
                        <label for="area_id">Área Responsable</label>
                        <select class="form-control" name="area_id" required>
                            <option value="" disabled selected>Seleccione un área</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Casuística --}}
                    <div class="form-group">
                        <label for="casuistica_inicial_id">Casuística</label>
                        <select class="form-control" name="casuistica_inicial_id" required>
                            <option value="" disabled selected>Seleccione un motivo</option>
                            @foreach ($casuisticas as $casuistica)
                                <option value="{{ $casuistica->id }}">{{ $casuistica->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Importancia --}}
                    <div class="form-group">
                        <label for="importancia">Importancia</label>
                        <select class="form-control" name="importancia" required>
                            <option value="baja">Baja</option>
                            <option value="media" selected>Media</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>

                    {{-- Descripción --}}
                    <div class="form-group">
                        <label for="descripcion">Descripción de la Consulta</label>
                        <textarea class="form-control" name="descripcion" rows="4" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="foto">Adjuntar Imagen (opcional)</label>
                        <input type="file" name="foto" class="form-control">
                    </div>


                    {{-- Submit --}}
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane me-1"></i> Enviar Consulta
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Script para asegurarse de limpiar el formulario al abrir --}}
<script>
    $(document).ready(function () {
        $('#consultaModal').on('show.bs.modal', function () {
            // Resetear campos al abrir
            $('#consultaForm')[0].reset();
        });
    });
</script>
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> -->