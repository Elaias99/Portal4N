@php
    $pendientes = session('sin_cobranza')
                ?? session('sin_cobranza_pendientes')
                ?? session('sin_compra_pendientes')
                ?? [];

    $tipoFlujo = session()->has('sin_compra_pendientes') ? 'compra' : 'cobranza';
@endphp

<script>
    window.cobranzaConfig = {
        csrf: '{{ csrf_token() }}',
        routes: {
            storeCobranza: "{{ route('cobranzas.store') }}",
            storeCompra: "{{ route('cobranzas-compras.store') }}",
            reprocesarCobranza: "{{ route('cobranzas.reprocesar-pendientes') }}",
            reprocesarCompra: "{{ route('cobranzas-compras.reprocesar-pendientes-compras') }}",
            cancelarCobranza: "{{ route('cobranzas.cancelar-pendientes') }}",
            cancelarCompra: "{{ route('cobranzas-compras.cancelar-pendientes-compras') }}"
        }
    };
</script>

<div
    class="modal fade"
    id="modalCrearCobranza"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalCrearCobranzaLabel"
    aria-hidden="true"
    data-pendientes='@json($pendientes)'
    data-tipo-flujo="{{ $tipoFlujo }}"
>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-proveedor-dialog" role="document">
        <div class="modal-content modal-proveedor-content">


            

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalCrearCobranzaLabel">Crear Nueva Cobranza</h5>
                <button type="button" class="close text-white js-close-modal-cobranza" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>



            

            <div class="modal-body">
                <form
                    id="formCrearCobranzaVentas"
                    class="js-form-crear-cobranza"
                    method="POST"
                    action="{{ route('cobranzas.store') }}"
                    data-tipo="cobranza"
                    data-no-loader
                    style="display:none;"
                >
                    @include('cobranzas.form', [
                        'btnText' => 'Guardar',
                        'isModalFlow' => true,
                        'formIdPrefix' => 'venta_modal'
                    ])
                </form>

                <form
                    id="formCrearCobranzaCompras"
                    class="js-form-crear-cobranza"
                    method="POST"
                    action="{{ route('cobranzas-compras.store') }}"
                    data-tipo="compra"
                    data-no-loader
                    style="display:none;"
                >
                    @include('cobranzas_compras.form', [
                        'btnText' => 'Guardar',
                        'isModalFlow' => true,
                        'formIdPrefix' => 'compra_modal'
                    ])
                </form>
            </div>
        </div>
    </div>
</div>

@vite('resources/js/modules/cobranza/modalCrearCobranza.js')