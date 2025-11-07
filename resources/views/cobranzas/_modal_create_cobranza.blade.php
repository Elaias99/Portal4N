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

    // =====================================================
    // 🟢 Abrir modal manualmente desde enlace
    // =====================================================
    $(document).on('click', '.crear-cobranza-link, .crear-compra-link', function (e) {
        e.preventDefault();

        const rut = $(this).data('rut') || '';
        const razon = $(this).data('razon') || '';
        const tipo = $(this).hasClass('crear-compra-link') ? 'compra' : 'cobranza';

        // Guardamos el tipo actual en el modal
        $('#modalCrearCobranza').data('tipo', tipo);

        $('#modalCrearCobranza #rut_cliente').val(rut);
        $('#modalCrearCobranza #razon_social').val(razon);

        $('#modalCrearCobranza').modal('show');
    });

    // =====================================================
    // 🟢 Enviar formulario por AJAX
    // =====================================================
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
                    alert('Registro creado correctamente ✅');

                    // 🧭 Pasar al siguiente pendiente según tipo
                    if (typeof abrirSiguienteCobranza === 'function') {
                        console.log('➡️ Pasando al siguiente registro pendiente...');
                        setTimeout(() => abrirSiguienteCobranza(), 600);
                    }
                } else if (data.errors) {
                    alert('Errores de validación:\n' + Object.values(data.errors).join('\n'));
                }
            },

            error: function (xhr) {
                console.error(xhr.responseText);
                alert('Ocurrió un error al guardar.');
            }
        });
    });

    // =====================================================
    // 🧩 Flujo guiado automático (ventas o compras)
    // =====================================================
    @php
        $pendientes = session('sin_cobranza') ?? session('sin_cobranza_pendientes') 
                    ?? session('sin_compra_pendientes');
        $tipoFlujo = session('sin_compra_pendientes') ? 'compra' : 'cobranza';
    @endphp

    @if($pendientes ?? false)
        let pendientes = @json($pendientes);
        let tipoFlujo = "{{ $tipoFlujo }}";
        console.log('🧠 Pendientes cargados:', pendientes, 'Tipo:', tipoFlujo);

        let indiceActual = 0;

        window.abrirSiguienteCobranza = function () {
            indiceActual++;
            if (indiceActual >= pendientes.length) {
                alert("✅ Todos los registros pendientes han sido creados. Se procesarán los documentos automáticamente...");

                // Elegir endpoint según tipo
                const url = tipoFlujo === 'compra'
                    ? "{{ route('cobranzas.reprocesar-pendientes-compras') }}"
                    : "{{ route('cobranzas.reprocesar-pendientes') }}";

                $.post(url, {_token: '{{ csrf_token() }}'}, function(resp) {
                    if (resp.success) {
                        alert("✅ Documentos reprocesados correctamente.");
                        location.reload();
                    } else {
                        alert("⚠️ Hubo un problema al reprocesar los documentos.");
                    }
                });

                return;
            }

            const actual = pendientes[indiceActual];
            $('#modalCrearCobranza #rut_cliente').val(actual.rut_cliente || actual.rut_proveedor);
            $('#modalCrearCobranza #razon_social').val(actual.razon_social);
            $('#modalCrearCobranza').modal('show');
        };

        // 🟢 Iniciar el flujo automáticamente al cargar
        const primera = pendientes[indiceActual];
        $('#modalCrearCobranza #rut_cliente').val(primera.rut_cliente || primera.rut_proveedor);
        $('#modalCrearCobranza #razon_social').val(primera.razon_social);
        $('#modalCrearCobranza').modal('show');
    @endif
});
</script>
@endpush

