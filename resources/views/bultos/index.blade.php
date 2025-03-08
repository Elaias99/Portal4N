@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Listado de Bultos</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Código</th>
                    <th>Dirección</th>
                    <th>Comuna</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bultos as $bulto)
                    <tr>
                        <td>{{ $bulto->id }}</td>
                        <td>{{ $bulto->codigo_bulto }}</td>
                        <td>{{ $bulto->direccion }}</td>
                        <td>{{ $bulto->comuna }}</td>
                        <td>{{ ucfirst($bulto->estado) }}</td>


                        <td>
                            <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#reclamoModal"
                                data-bulto-id="{{ $bulto->id }}"
                                data-bulto-codigo="{{ $bulto->codigo_bulto }}">
                                Reportar Reclamo
                            </button>
                        </td>


                        <td>
                            

                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>


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

                            <!-- Código del Bulto -->
                            <div class="form-group">
                                <label for="codigo_bulto" class="form-label">Código del Bulto</label>
                                <input type="text" class="form-control" id="modal-bulto-codigo" disabled>
                            </div>

                            <!-- Seleccionar Responsable (Jefe) -->
                            <div class="form-group">
                                <label for="id_jefe" class="form-label">Asignar a:</label>
                                <select class="form-control" name="id_jefe" required>
                                    <option value="" disabled selected>Seleccione un responsable</option>
                                    @foreach ($jefes as $jefe)
                                        <option value="{{ $jefe->id }}">{{ $jefe->nombre }} - {{ $jefe->area }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Descripción del Problema -->
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



    </div>
@endsection

<!-- Agregar jQuery, Popper.js y Bootstrap.js en la vista -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function() {
        $('#reclamoModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Botón que activó el modal
            var bultoId = button.data('bulto-id');
            var bultoCodigo = button.data('bulto-codigo');

            $('#modal-bulto-id').val(bultoId);
            $('#modal-bulto-codigo').val(bultoCodigo);
        });
    });
</script>


