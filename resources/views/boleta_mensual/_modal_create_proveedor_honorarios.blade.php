@php
    $pendientesHonorarios = session('sin_honorarios_proveedores_pendientes') ?? [];
@endphp

<script>
    window.honorariosProveedorConfig = {
        csrf: '{{ csrf_token() }}',
        routes: {
            storeProveedor: "{{ route('cobranzas-compras.store') }}",
            reprocesarHonorarios: "{{ route('honorarios.mensual.reprocesar-pendientes') }}",
            cancelarHonorarios: "{{ route('honorarios.mensual.cancelar-pendientes') }}"
        }
    };
</script>

<div
    class="modal fade"
    id="modalCrearProveedorHonorarios"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalCrearProveedorHonorariosLabel"
    aria-hidden="true"
    data-pendientes='@json($pendientesHonorarios)'
>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-proveedor-dialog" role="document">
        <div class="modal-content modal-proveedor-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalCrearProveedorHonorariosLabel">
                    Crear proveedor de honorarios
                </h5>

                <button
                    type="button"
                    class="close text-white js-cancel-modal-proveedor-honorarios"
                    aria-label="Cerrar"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                @if(count($pendientesHonorarios) > 0)
                    <div class="alert alert-warning mb-3">
                        <strong>Proveedores no registrados detectados.</strong>
                        Debe crearlos para completar la importación de boletas de honorarios.
                    </div>
                @endif

                <form
                    id="formCrearProveedorHonorarios"
                    class="js-form-crear-proveedor-honorarios"
                    method="POST"
                    action="{{ route('cobranzas-compras.store') }}"
                    data-no-loader
                >
                    @include('cobranzas_compras.form', [
                        'btnText' => 'Guardar proveedor',
                        'isModalFlow' => true,
                        'formIdPrefix' => 'honorario_proveedor_modal',
                        'cobranzaCompra' => null,
                        'bancos' => $bancos,
                        'tipoCuentas' => $tipoCuentas,
                        'opcionesCobranzaCompra' => $opcionesCobranzaCompra,
                    ])
                </form>

            </div>
        </div>
    </div>
</div>

@vite('resources/js/boleta_mensual_proveedores_pendientes.js')