{{-- 
    resources/views/suscripciones/comisiones_mensuales/partials/modal-ajustes-masivos-linea-adicional.blade.php

    Modal para registrar nuevas rutas / líneas adicionales masivas.
    No guarda en BD directamente.
    El JS debe construir objetos tipo LINEA_ADICIONAL y agregarlos a ajustesMensuales[].
--}}

<div
    class="modal fade"
    id="modal-ajustes-masivos-linea-adicional"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-ajustes-masivos-linea-adicional-label"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h5
                        class="modal-title mb-0"
                        id="modal-ajustes-masivos-linea-adicional-label"
                    >
                        Nuevas rutas masivas
                    </h5>

                    <small class="text-muted">
                        Agrega rutas, servicios o líneas que no existen en el maestro y que deben pagarse sólo en este periodo.
                    </small>
                </div>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="Cerrar"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                {{-- Templates de opciones para que el JS clone selects sin campos name --}}
                <select id="linea-adicional-masiva-proveedor-template" class="d-none">
                    <option value="">Seleccionar proveedor...</option>

                    @foreach ($proveedores ?? [] as $proveedor)
                        @php
                            $cobranzaCompra = $proveedor->cobranzaCompra;
                            $razonSocial = $cobranzaCompra?->razon_social ?? 'Proveedor sin razón social';
                            $rutCliente = $cobranzaCompra?->rut_cliente;
                            $labelProveedor = trim($razonSocial . ($rutCliente ? ' - ' . $rutCliente : ''));
                        @endphp

                        <option
                            value="{{ $proveedor->id }}"
                            data-label="{{ $labelProveedor }}"
                            data-tipo="{{ $proveedor->tipo }}"
                            data-detalle-documento="{{ $proveedor->detalle_documento }}"
                            data-detalle-impuesto="{{ $proveedor->detalle_impuesto }}"
                            data-final="{{ $proveedor->final }}"
                        >
                            {{ $labelProveedor }}
                        </option>
                    @endforeach
                </select>

                <select id="linea-adicional-masiva-transportista-template" class="d-none">
                    <option value="">Sin transportista / no aplica</option>

                    @foreach ($transportistas ?? [] as $transportista)
                        <option
                            value="{{ $transportista->id }}"
                            data-label="{{ $transportista->nombre_transportista }}"
                        >
                            {{ $transportista->nombre_transportista }}
                        </option>
                    @endforeach
                </select>

                <div class="alert alert-info small">
                    <strong>Uso recomendado:</strong>
                    usa esta opción para agregar una ruta, cobertura o apoyo que se pagará sólo en este mes.
                    Por ejemplo: una ruta adicional, una entrega puntual, un apoyo especial o una cobertura realizada por otra persona.
                    Esta información se sumará al resumen del periodo sin cambiar la configuración habitual.
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="small text-muted">
                        Líneas preparadas:
                        <strong id="linea-adicional-masiva-contador">0</strong>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm"
                            id="btn-linea-adicional-masiva-agregar"
                        >
                            Agregar nueva ruta
                        </button>

                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            id="btn-linea-adicional-masiva-limpiar"
                        >
                            Limpiar líneas
                        </button>
                    </div>
                </div>

                <div
                    id="linea-adicional-masiva-error"
                    class="alert alert-danger small d-none"
                    role="alert"
                ></div>

                <div
                    id="linea-adicional-masiva-lineas-body"
                    class="d-flex flex-column gap-3"
                >
                    <div
                        data-linea-adicional-masiva-empty
                        class="text-muted text-center border rounded p-3"
                    >
                        No hay nuevas rutas preparadas.
                    </div>
                </div>

                <div class="small text-muted mt-3">
                    Campos mínimos por línea:
                    proveedor, código, servicio, costo y cantidad.
                    Los datos documentales se pueden prellenar desde el proveedor, pero deben quedar editables antes de confirmar.
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    data-dismiss="modal"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    class="btn btn-primary"
                    id="btn-confirmar-lineas-adicionales-masivas"
                >
                    Agregar nuevas rutas al resumen
                </button>
            </div>
        </div>
    </div>
</div>