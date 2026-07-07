{{-- 
    resources/views/suscripciones/comisiones_mensuales/partials/modal-comisiones-masivas.blade.php

    Modal para registrar comisiones / pagos adicionales masivos.
    No guarda en BD directamente.
    El JS debe construir objetos para comisiones[] y agregarlos al flujo actual de comisiones.

    Regla:
    - No usa suscripcion_asignaciones como origen de carga.
    - Un mismo proveedor puede tener más de un pago adicional.
    - Cada clic en "Agregar pago" crea una nueva fila editable.
    - El código interno se maneja como COMISION desde JS/backend, no se pide al usuario.
--}}

<div
    class="modal fade"
    id="modal-comisiones-masivas"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-comisiones-masivas-title"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title mb-1" id="modal-comisiones-masivas-title">
                        Pagos adicionales masivos
                    </h5>

                    <div class="small text-muted">
                        Busca un proveedor y presiona <strong>Agregar pago</strong> una o más veces.
                        Luego completa el monto y ajusta transportista, puntos o servicio si corresponde.
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
                <div class="alert alert-info small mb-3">
                    <strong>Importante:</strong>
                    esta carga masiva crea pagos adicionales del mes en <code>comisiones[]</code>.
                    No usa <code>suscripcion_asignaciones</code> como origen y no registra novedades mensuales.
                    Un mismo proveedor puede tener más de un pago adicional.
                </div>

                {{-- Template para que el JS clone selects sin name --}}
                <select id="comision-masiva-transportista-template" class="d-none">
                    <option value="">Seleccionar transportista...</option>

                    @foreach ($transportistas ?? [] as $transportista)
                        <option
                            value="{{ $transportista->id }}"
                            data-label="{{ $transportista->nombre_transportista }}"
                            data-nombre-normalizado="{{ mb_strtoupper(trim($transportista->nombre_transportista)) }}"
                        >
                            {{ $transportista->nombre_transportista }}
                        </option>
                    @endforeach
                </select>

                {{-- SECCIÓN 1: BUSCAR PROVEEDORES --}}
                <div class="border rounded p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">1. Buscar proveedor</h6>
                            <div class="small text-muted">
                                Puedes buscar por razón social, RUT o tipo de documento.
                                Presiona <strong>Agregar pago</strong> por cada comisión que necesites crear.
                            </div>
                        </div>

                        <div class="small text-muted">
                            Pagos agregados:
                            <strong id="comision-masiva-seleccionados-contador">0</strong>
                        </div>
                    </div>

                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-8">
                            <label for="comision-masiva-buscador" class="form-label">
                                Buscar proveedor
                            </label>

                            <input
                                type="text"
                                id="comision-masiva-buscador"
                                class="form-control"
                                placeholder="Ej: ROMOLO, LORENZA, 12328835-1, FACTURA..."
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-md-4 d-flex gap-2">
                            <button
                                type="button"
                                id="btn-comision-masiva-buscar"
                                class="btn btn-outline-primary flex-fill"
                            >
                                Buscar
                            </button>

                            <button
                                type="button"
                                id="btn-comision-masiva-limpiar-busqueda"
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
                                    <th style="width: 120px;" class="text-center">Acción</th>
                                    <th>Proveedor / RUT</th>
                                    <th style="width: 150px;">Tipo documento</th>
                                    <th style="width: 230px;">Detalle</th>
                                    <th style="width: 150px;">Final</th>
                                </tr>
                            </thead>

                            <tbody id="comision-masiva-proveedores-body">
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

                                        $razonSocialNormalizada = mb_strtoupper(trim($razonSocial));
                                    @endphp

                                    <tr
                                        data-comision-masiva-proveedor
                                        data-busqueda="{{ $textoBusqueda }}"
                                        data-proveedor-id="{{ $proveedor->id }}"
                                        data-label="{{ $labelProveedor }}"
                                        data-razon-social="{{ $razonSocial }}"
                                        data-razon-social-normalizada="{{ $razonSocialNormalizada }}"
                                        data-rut="{{ $rutCliente }}"
                                        data-tipo="{{ $proveedor->tipo }}"
                                        data-detalle-documento="{{ $proveedor->detalle_documento }}"
                                        data-detalle-impuesto="{{ $proveedor->detalle_impuesto }}"
                                        data-final="{{ $proveedor->final }}"
                                    >
                                        <td class="text-center">
                                            <button
                                                type="button"
                                                class="btn btn-outline-primary btn-sm"
                                                data-comision-masiva-agregar-pago
                                            >
                                                Agregar pago
                                            </button>
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
                                            No hay proveedores disponibles para carga masiva de pagos adicionales.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SECCIÓN 2: COMPLETAR COMISIONES --}}
                <div class="border rounded p-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">2. Completar pagos adicionales</h6>
                            <div class="small text-muted">
                                Cada fila representa un pago adicional independiente.
                                El sistema usará código interno <strong>COMISION</strong>.
                                Sólo el monto es obligatorio; puedes corregir transportista, puntos, servicio u observación.
                            </div>
                        </div>

                        <button
                            type="button"
                            id="btn-comision-masiva-limpiar"
                            class="btn btn-outline-secondary btn-sm"
                        >
                            Limpiar pagos agregados
                        </button>
                    </div>

                    <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                        <table class="table table-sm table-bordered align-middle mb-2">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 180px;">Proveedor</th>
                                    <th style="min-width: 200px;">Transportista</th>
                                    <th style="min-width: 150px;">Punto</th>
                                    <th style="min-width: 150px;">Punto 2</th>
                                    <th style="min-width: 190px;">Servicio</th>
                                    <th style="min-width: 130px;" class="text-end">Monto</th>
                                    <th style="min-width: 180px;">Observación</th>
                                    <th style="width: 80px;" class="text-center">Quitar</th>
                                </tr>
                            </thead>

                            <tbody id="comision-masiva-seleccionados-body">
                                <tr data-comision-masiva-empty>
                                    <td colspan="8" class="text-muted text-center">
                                        No hay pagos agregados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        id="comision-masiva-error"
                        class="alert alert-danger small d-none mb-0"
                        role="alert"
                    ></div>
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
                    id="btn-confirmar-comisiones-masivas"
                    class="btn btn-primary"
                >
                    Agregar pagos adicionales al resumen
                </button>
            </div>
        </div>
    </div>
</div>