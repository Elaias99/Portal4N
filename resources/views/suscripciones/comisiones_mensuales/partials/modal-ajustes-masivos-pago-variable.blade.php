{{-- 
    resources/views/suscripciones/comisiones_mensuales/partials/modal-ajustes-masivos-pago-variable.blade.php

    Modal para registrar pagos variables masivos.
    No guarda en BD directamente.
    El JS debe construir objetos tipo PAGO_VARIABLE y agregarlos a ajustesMensuales[].
--}}

<div
    class="modal fade"
    id="modal-ajustes-masivos-pago-variable"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-ajustes-masivos-pago-variable-title"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="modal-ajustes-masivos-pago-variable-title">
                        Pagos variables masivos
                    </h5>

                    <div class="small text-muted">
                        Busca proveedores, selecciónalos y completa el concepto variable, tarifa y observación para cada uno.
                    </div>
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
                <select id="pago-variable-masivo-transportista-template" class="d-none">
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

                <select id="pago-variable-masivo-concepto-template" class="d-none">
                    <option value="">Seleccionar concepto...</option>

                    @foreach ($conceptosPagoVariable ?? [] as $concepto)
                        <option
                            value="{{ $concepto->id }}"
                            data-codigo="{{ $concepto->codigo }}"
                            data-nombre="{{ $concepto->nombre }}"
                            data-descripcion="{{ $concepto->descripcion }}"
                        >
                            {{ $concepto->nombre }}
                        </option>
                    @endforeach

                    <option
                        value="__OTRO__"
                        data-codigo="OTRO"
                        data-nombre="Otro"
                    >
                        Otro concepto
                    </option>
                </select>

                <div class="alert alert-warning small mb-3">
                    <strong>Importante:</strong>
                    usa esta opción para agregar pagos puntuales del mes asociados a un concepto operativo.
                    Por ejemplo: compaginado, primera vuelta, segunda vuelta, reposición o apoyo tarifado.
                    Cada pago se sumará al resumen del periodo y se guardará al generar el mes completo.
                </div>

                {{-- SECCIÓN 1: BUSCAR Y SELECCIONAR PROVEEDORES --}}
                <div class="border rounded p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">1. Buscar y seleccionar proveedores</h6>
                            <div class="small text-muted">
                                Puedes buscar por razón social, RUT o tipo de documento.
                            </div>
                        </div>

                        <div class="small text-muted">
                            Seleccionados:
                            <strong id="pago-variable-masivo-seleccionados-contador">0</strong>
                        </div>
                    </div>

                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-8">
                            <label for="pago-variable-masivo-buscador" class="form-label">
                                Buscar proveedor
                            </label>

                            <input
                                type="text"
                                id="pago-variable-masivo-buscador"
                                class="form-control"
                                placeholder="Ej: TRANSPORTES SKY, 76.123.456-7, FACTURA..."
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-md-4 d-flex gap-2">
                            <button
                                type="button"
                                id="btn-pago-variable-masivo-buscar"
                                class="btn btn-outline-primary flex-fill"
                            >
                                Buscar
                            </button>

                            <button
                                type="button"
                                id="btn-pago-variable-masivo-limpiar-busqueda"
                                class="btn btn-outline-secondary flex-fill"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 44px;" class="text-center">Sel.</th>
                                    <th>Proveedor / RUT</th>
                                    <th>Tipo documento</th>
                                    <th>Detalle</th>
                                    <th>Final</th>
                                </tr>
                            </thead>

                            <tbody id="pago-variable-masivo-proveedores-body">
                                @forelse ($proveedores ?? [] as $proveedor)
                                    @php
                                        $cobranzaCompra = $proveedor->cobranzaCompra;

                                        $razonSocial = $cobranzaCompra?->razon_social ?? 'Proveedor sin razón social';
                                        $rutCliente = $cobranzaCompra?->rut_cliente ?? 'Sin RUT';
                                        $tipoDocumento = $proveedor->tipo ?? 'Sin tipo';
                                        $detalleDocumento = $proveedor->detalle_documento ?? '';
                                        $detalleImpuesto = $proveedor->detalle_impuesto ?? '';
                                        $final = $proveedor->final ?? '';

                                        $labelProveedor = trim(
                                            $razonSocial
                                            . ' | '
                                            . $rutCliente
                                            . ' | '
                                            . $tipoDocumento
                                        );

                                        $detalleLabel = trim(
                                            ($detalleDocumento ?: 'Sin detalle documento')
                                            . ' | '
                                            . ($detalleImpuesto ?: 'Sin detalle impuesto')
                                        );

                                        $textoBusqueda = mb_strtoupper(trim(
                                            $razonSocial
                                            . ' '
                                            . $rutCliente
                                            . ' '
                                            . $tipoDocumento
                                            . ' '
                                            . $detalleDocumento
                                            . ' '
                                            . $detalleImpuesto
                                            . ' '
                                            . $final
                                        ));
                                    @endphp

                                    <tr
                                        data-pago-variable-masivo-proveedor
                                        data-busqueda="{{ $textoBusqueda }}"
                                        data-proveedor-id="{{ $proveedor->id }}"
                                        data-label="{{ $labelProveedor }}"
                                        data-razon-social="{{ $razonSocial }}"
                                        data-rut="{{ $rutCliente }}"
                                        data-tipo="{{ $proveedor->tipo }}"
                                        data-detalle-documento="{{ $proveedor->detalle_documento }}"
                                        data-detalle-impuesto="{{ $proveedor->detalle_impuesto }}"
                                        data-final="{{ $proveedor->final }}"
                                    >
                                        <td class="text-center">
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                data-pago-variable-masivo-checkbox
                                                value="{{ $proveedor->id }}"
                                                aria-label="Seleccionar {{ $labelProveedor }}"
                                            >
                                        </td>

                                        <td>
                                            <div class="fw-semibold">
                                                {{ $razonSocial }}
                                            </div>

                                            <div class="small text-muted">
                                                {{ $rutCliente }}
                                            </div>
                                        </td>

                                        <td>
                                            {{ $tipoDocumento }}
                                        </td>

                                        <td>
                                            {{ $detalleLabel }}
                                        </td>

                                        <td>
                                            {{ $final ?: '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted text-center">
                                            No hay proveedores disponibles para carga masiva de pagos variables.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SECCIÓN 2: COMPLETAR DATOS POR PROVEEDOR SELECCIONADO --}}
                <div class="border rounded p-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">2. Completar pagos variables</h6>
                            <div class="small text-muted">
                                Los proveedores marcados arriba aparecerán aquí para definir concepto, tarifa y observación.
                            </div>
                        </div>

                        <button
                            type="button"
                            id="btn-pago-variable-masivo-limpiar"
                            class="btn btn-outline-secondary btn-sm"
                        >
                            Limpiar selección
                        </button>
                    </div>

                    <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                        <table class="table table-sm table-bordered align-middle mb-2">
                            <thead class="table-light">
                                <tr>
                                    <th>Proveedor</th>
                                    <th style="width: 190px;">Transportista</th>
                                    <th style="width: 220px;">Concepto</th>
                                    <th style="width: 180px;">Concepto manual</th>
                                    <th style="width: 140px;" class="text-end">Tarifa</th>
                                    <th>Observación</th>
                                    <th style="width: 80px;" class="text-center">Quitar</th>
                                </tr>
                            </thead>

                            <tbody id="pago-variable-masivo-seleccionados-body">
                                <tr data-pago-variable-masivo-empty>
                                    <td colspan="7" class="text-muted text-center">
                                        No hay proveedores seleccionados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="pago-variable-masivo-error" class="alert alert-danger small d-none mb-0"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    data-dismiss="modal"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    id="btn-confirmar-pagos-variables-masivos"
                    class="btn btn-primary"
                >
                    Agregar pagos variables al resumen
                </button>
            </div>
        </div>
    </div>
</div>