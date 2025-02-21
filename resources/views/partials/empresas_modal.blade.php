    {{-- <!-- Modal -->
    <div class="modal fade" id="empresasModal" tabindex="-1" role="dialog" aria-labelledby="empresasModalLabel" aria-hidden="true">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="empresasModalLabel">Agregar Empresa</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('empresas.store') }}" method="POST">
                    @csrf
                    @include('empresas.form')
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div> --}}


<!-- Modal -->
<!-- Modal -->
<div class="modal fade" id="empresasModal" tabindex="-1" role="dialog" aria-labelledby="empresasModalLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="empresasModalLabel">Agregar Empresa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('empresas.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('empresas.form')
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Abrir el modal si hay errores de validación -->
@if ($errors->any())
    <script>
        $(document).ready(function() {
            $('#empresasModal').modal('show');
        });
    </script>
@endif


