<script>
    $(document).ready(function() {
        $('#reclamoModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var bultoId = button.data('bulto-id');
            var bultoCodigo = button.data('bulto-codigo');

            $('#modal-bulto-id').val(bultoId);
            $('#modal-bulto-codigo').val(bultoCodigo);
        });
    });
</script>
