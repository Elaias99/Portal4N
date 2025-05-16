<script>
    $(document).ready(function () {
        $('#reclamoModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var bultoId = button.data('bulto-id');
            var bultoCodigo = button.data('bulto-codigo');
            var esReapertura = button.data('reabrir') === true || button.data('reabrir') === "true";
            var reclamoId = button.data('reclamo-id');

            // Setear valores básicos
            $('#modal-bulto-id').val(bultoId);
            $('#modal-bulto-codigo').val(bultoCodigo);

            // Cambiar acción del formulario si es reapertura
            var form = $('#reclamoForm');
            if (esReapertura && reclamoId) {
                form.attr('action', '/reclamos/' + reclamoId + '/reabrir');
            } else {
                form.attr('action', '{{ route('reclamos.store') }}');
            }
        });
    });
</script>
