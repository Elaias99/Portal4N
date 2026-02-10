{{-- 
|--------------------------------------------------------------------------
| NOTA IMPORTANTE – DATOS INYECTADOS VÍA SERVICE PROVIDER
|--------------------------------------------------------------------------
| Este modal es compartido por los módulos de VENTAS y COMPRAS.
|
| El formulario de COMPRAS (cobranzas_compras.form) requiere las
| variables $bancos y $tipoCuentas, las cuales NO provienen de los
| controladores de ventas ni de documentos.
|
| Para evitar acoplar controladores y prevenir errores de tipo
| "Undefined variable", estas variables se inyectan automáticamente
| mediante un View Composer definido en:
|
|   app/Providers/AppServiceProvider.php
|
| Método: boot()
|
| Cualquier cambio en los datos requeridos por este modal debe
| reflejarse también en dicho Provider.
|--------------------------------------------------------------------------
--}}



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
            <form id="formCrearCobranza" method="POST">

                {{-- ========================= --}}
                {{-- FORMULARIO VENTAS --}}
                {{-- ========================= --}}
                <div id="form-ventas">
                    @include('cobranzas.form', ['btnText' => 'Guardar'])
                </div>

                {{-- ========================= --}}
                {{-- FORMULARIO COMPRAS --}}
                {{-- ========================= --}}
                <div id="form-compras" style="display:none;">
                    @include('cobranzas_compras.form', ['btnText' => 'Guardar'])
                </div>

            </form>
        </div>


    </div>
  </div>
</div>


@push('scripts')


<script>
$(function () {

    // =====================================================
    // Habilitar / deshabilitar inputs del formulario
    // =====================================================
    function toggleFormState(container, enabled) {
        $(container).find('input, select, textarea').each(function () {

            if (enabled) {
                // Habilitar input
                $(this).prop('disabled', false);

                // Restaurar required si lo tenía
                if ($(this).data('was-required')) {
                    $(this).attr('required', true);
                }

            } else {
                // Guardar si era required
                if ($(this).attr('required')) {
                    $(this).data('was-required', true);
                }

                // Deshabilitar input
                $(this).removeAttr('required');
                $(this).prop('disabled', true);
            }
        });
    }


    // =====================================================
    // Abrir modal manualmente desde enlace
    // =====================================================
    $(document).on('click', '.crear-cobranza-link, .crear-compra-link', function (e) {
        e.preventDefault();

        const rut   = $(this).data('rut') || '';
        const razon = $(this).data('razon') || '';
        const tipo  = $(this).hasClass('crear-compra-link') ? 'compra' : 'cobranza';

        // Guardar tipo
        $('#modalCrearCobranza').data('tipo', tipo);

        // Mostrar formulario correcto
        if (tipo === 'compra') {
            toggleFormState('#form-ventas', false);
            toggleFormState('#form-compras', true);

            $('#form-ventas').hide();
            $('#form-compras').show();
        } else {
            toggleFormState('#form-compras', false);
            toggleFormState('#form-ventas', true);

            $('#form-compras').hide();
            $('#form-ventas').show();
        }

        // Action correcto
        const formAction = tipo === 'compra'
            ? "{{ route('cobranzas-compras.store') }}"
            : "{{ route('cobranzas.store') }}";

        $('#formCrearCobranza').attr('action', formAction);

        // Precargar datos
        $('#modalCrearCobranza #rut_cliente').val(rut);
        $('#modalCrearCobranza #razon_social').val(razon);

        $('#modalCrearCobranza').modal('show');
    });


    // =====================================================
    // Flujo guiado automático (ventas o compras)
    // =====================================================
    @php
        $pendientes = session('sin_cobranza')
                    ?? session('sin_cobranza_pendientes')
                    ?? session('sin_compra_pendientes');

        $tipoFlujo = session('sin_compra_pendientes') ? 'compra' : 'cobranza';
    @endphp

    @if($pendientes ?? false)

        let pendientes = @json($pendientes);
        let tipoFlujo  = "{{ $tipoFlujo }}";

        $('#modalCrearCobranza').data('tipo', tipoFlujo);

        if (tipoFlujo === 'compra') {
            toggleFormState('#form-ventas', false);
            toggleFormState('#form-compras', true);

            $('#form-ventas').hide();
            $('#form-compras').show();
        } else {
            toggleFormState('#form-compras', false);
            toggleFormState('#form-ventas', true);

            $('#form-compras').hide();
            $('#form-ventas').show();
        }

        let indiceActual = 0;

        window.abrirSiguienteCobranza = function () {
            indiceActual++;

            if (indiceActual >= pendientes.length) {
                alert("✅ Todos los registros pendientes han sido creados. Se procesarán los documentos automáticamente...");

                const url = tipoFlujo === 'compra'
                    ? "{{ route('cobranzas-compras.reprocesar-pendientes-compras') }}"
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

            $('#modalCrearCobranza #rut_cliente')
                .val(actual.rut_cliente || actual.rut_proveedor);

            $('#modalCrearCobranza #razon_social')
                .val(actual.razon_social);

            $('#modalCrearCobranza').modal('show');
        };

        // Iniciar automáticamente
        const primera = pendientes[indiceActual];

        $('#modalCrearCobranza #rut_cliente')
            .val(primera.rut_cliente || primera.rut_proveedor);

        $('#modalCrearCobranza #razon_social')
            .val(primera.razon_social);

        $('#modalCrearCobranza').modal('show');

    @endif


    // =====================================================
    // Enviar formulario por AJAX
    // =====================================================
    $('#formCrearCobranza').on('submit', function (e) {
        e.preventDefault();

        const tipo = $('#modalCrearCobranza').data('tipo') || 'cobranza';

        const formAction = tipo === 'compra'
            ? "{{ route('cobranzas-compras.store') }}"
            : "{{ route('cobranzas.store') }}";

        $(this).attr('action', formAction);

        const formData = new FormData(this);

        $.ajax({
            url: formAction,
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

                    if (typeof abrirSiguienteCobranza === 'function') {
                        setTimeout(() => abrirSiguienteCobranza(), 600);
                    }
                } else if (data.errors) {
                    alert(
                        'Errores de validación:\n' +
                        Object.values(data.errors).join('\n')
                    );
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                alert('Ocurrió un error al guardar.');
            }
        });
    });

});
</script>



@endpush


