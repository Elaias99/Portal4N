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

    // 🟢 Abrir modal manualmente desde enlace
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

                        // 🧭 Pasar al siguiente cliente pendiente
                        if (typeof abrirSiguienteCobranza === 'function') {
                            console.log('➡️ Pasando al siguiente cliente...');
                            setTimeout(() => abrirSiguienteCobranza(), 600);
                        }
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

    // =====================================================
    // 🧩 NUEVO: Flujo guiado automático (si hay varias pendientes)
    // =====================================================
    @if(session('sin_cobranza'))
        @php
            // 🔥 Guardamos los pendientes y limpiamos la sesión de inmediato
            $pendientes = session('sin_cobranza');
            session()->forget('sin_cobranza');
        @endphp

        let pendientes = @json($pendientes);
        console.log('🧠 Pendientes cargados:', pendientes);

        let indiceActual = 0;

        window.abrirSiguienteCobranza = function () {
            indiceActual++;
            if (indiceActual >= pendientes.length) {
                alert("✅ Todas las cobranzas pendientes han sido creadas.");
                return;
            }

            const actual = pendientes[indiceActual];
            $('#modalCrearCobranza #rut_cliente').val(actual.rut_cliente);
            $('#modalCrearCobranza #razon_social').val(actual.razon_social);
            $('#modalCrearCobranza').modal('show');
        };

        // 🟢 Iniciar el flujo automáticamente al cargar
        const primera = pendientes[indiceActual];
        $('#modalCrearCobranza #rut_cliente').val(primera.rut_cliente);
        $('#modalCrearCobranza #razon_social').val(primera.razon_social);
        $('#modalCrearCobranza').modal('show');
    @endif

});
</script>
@endpush
