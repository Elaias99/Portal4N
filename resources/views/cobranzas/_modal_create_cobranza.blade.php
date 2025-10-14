<!-- Modal Crear Cobranza (Bootstrap 4 compatible) -->
<div class="modal fade" id="modalCrearCobranza" tabindex="-1" role="dialog" aria-labelledby="modalCrearCobranzaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">

      <!-- === HEADER === -->
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalCrearCobranzaLabel">Crear Nueva Cobranza</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- === BODY === -->
      <div class="modal-body">
        <form id="formCrearCobranza" action="{{ route('cobranzas.store') }}" method="POST">
          @include('cobranzas.form', ['btnText' => 'Guardar'])
        </form>
      </div>

    </div>
  </div>
</div>

@push('scripts')
<script>
$(function () {
    // 🟢 Abrir modal con datos precargados
    $(document).on('click', '.crear-cobranza-link', function (e) {
        e.preventDefault();

        const rut = $(this).data('rut') || '';
        const razon = $(this).data('razon') || '';

        $('#modalCrearCobranza #rut_cliente').val(rut);
        $('#modalCrearCobranza #razon_social').val(razon);

        $('#modalCrearCobranza').modal('show');
    });

    // 🟢 Enviar formulario por AJAX
    $('#formCrearCobranza').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (data) {
                if (data.success) {
                    $('#modalCrearCobranza').modal('hide');
                    alert('Cobranza creada correctamente ✅');
                    // location.reload(); // opcional
                } else if (data.errors) {
                    alert('Errores de validación:\n' + Object.values(data.errors).join('\n'));
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                alert('Ocurrió un error al guardar la cobranza.');
            }
        });
    });
});
</script>
@endpush
