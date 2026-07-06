{{-- resources/views/suscripciones/comisiones_mensuales/partials/modal-ajustes-masivos-facturacion.blade.php --}}

<div
    class="modal fade"
    id="modal-ajustes-masivos-facturacion"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-ajustes-masivos-facturacion-title"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="modal-ajustes-masivos-facturacion-title">
                        Cambios de facturación masivos
                    </h5>

                    <div class="small text-muted">
                        Busca asignaciones, selecciónalas y define el proveedor facturador efectivo para este periodo.
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
                <div class="alert alert-warning small mb-3">
                    <strong>Importante:</strong>
                    esta carga masiva sólo registra cambios puntuales del periodo.
                    No modifica el proveedor base de la asignación maestra.
                    Las novedades se agregarán al resumen inferior y se guardarán recién al generar el mes completo.
                </div>

                {{-- Templates para JS --}}
                <select id="facturacion-masiva-proveedor-template" class="d-none" aria-hidden="true">
                    <option value="">Seleccionar proveedor facturador...</option>

                    @foreach($proveedores as $proveedor)
                        @php
                            $cobranza = $proveedor->cobranzaCompra;

                            $proveedorLabel = trim(
                                ($cobranza?->razon_social ?? 'Sin razón social')
                                . ' | '
                                . ($cobranza?->rut_cliente ?? 'Sin RUT')
                                . ' | '
                                . ($proveedor->tipo ?? 'Sin tipo')
                            );
                        @endphp

                        <option
                            value="{{ $proveedor->id }}"
                            data-label="{{ $proveedorLabel }}"
                            data-tipo="{{ $proveedor->tipo }}"
                            data-detalle-documento="{{ $proveedor->detalle_documento }}"
                            data-detalle-impuesto="{{ $proveedor->detalle_impuesto }}"
                            data-final="{{ $proveedor->final }}"
                        >
                            {{ $proveedorLabel }}
                        </option>
                    @endforeach
                </select>

                <select id="facturacion-masiva-transportista-template" class="d-none" aria-hidden="true">
                    <option value="">Mantener transportista original...</option>

                    @foreach($transportistas as $transportista)
                        <option
                            value="{{ $transportista->id }}"
                            data-label="{{ $transportista->nombre_transportista }}"
                        >
                            {{ $transportista->nombre_transportista }}
                        </option>
                    @endforeach
                </select>

                {{-- SECCIÓN 1: BUSCAR Y SELECCIONAR ASIGNACIONES --}}
                <div class="border rounded p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">1. Buscar y seleccionar asignaciones</h6>
                            <div class="small text-muted">
                                Puedes buscar por código, proveedor base, RUT, transportista, punto, servicio o tipo de asignación.
                            </div>
                        </div>

                        <div class="small text-muted">
                            Seleccionadas:
                            <strong id="facturacion-masiva-seleccionadas-contador">0</strong>
                        </div>
                    </div>

                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-8">
                            <label for="facturacion-masiva-buscador" class="form-label">
                                Buscar asignación
                            </label>

                            <input
                                type="text"
                                id="facturacion-masiva-buscador"
                                class="form-control"
                                placeholder="Ej: LOTA, BH.01, proveedor, transportista, punto..."
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-md-4 d-flex gap-2">
                            <button
                                type="button"
                                id="btn-facturacion-masiva-buscar"
                                class="btn btn-outline-primary flex-fill"
                            >
                                Buscar
                            </button>

                            <button
                                type="button"
                                id="btn-facturacion-masiva-limpiar-busqueda"
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
                                    <th>Código / proveedor base / transportista</th>
                                    <th>Tipo</th>
                                    <th>Punto</th>
                                    <th>Punto 2</th>
                                    <th class="text-end">Costo</th>
                                </tr>
                            </thead>

                            <tbody id="facturacion-masiva-asignaciones-body">
                                @php
                                    $asignacionesFacturacionMasiva = collect($asignacionesAjustesMensuales ?? [])
                                        ->filter(function ($asignacion) {
                                            return in_array(
                                                mb_strtoupper(trim((string) $asignacion->tipo_asignacion)),
                                                ['RUTA', 'VARIABLE', 'FIJO_MENSUAL', 'OPV'],
                                                true
                                            );
                                        })
                                        ->values();
                                @endphp

                                @forelse($asignacionesFacturacionMasiva as $asignacion)
                                    @php
                                        $cobranza = $asignacion->suscripcionProveedor?->cobranzaCompra;
                                        $transportista = $asignacion->transportista;
                                        $tipoAsignacion = mb_strtoupper(trim((string) $asignacion->tipo_asignacion));

                                        $asignacionLabel = trim(
                                            ($asignacion->codigo ?? 'Sin código')
                                            . ' | '
                                            . ($cobranza?->razon_social ?? 'Sin proveedor')
                                            . ' | '
                                            . ($transportista?->nombre_transportista ?? 'Sin transportista')
                                            . ' | $'
                                            . number_format((int) $asignacion->costo, 0, ',', '.')
                                        );

                                        $textoBusqueda = mb_strtoupper(trim(
                                            ($asignacion->codigo ?? '')
                                            . ' '
                                            . ($cobranza?->razon_social ?? '')
                                            . ' '
                                            . ($cobranza?->rut_cliente ?? '')
                                            . ' '
                                            . ($transportista?->nombre_transportista ?? '')
                                            . ' '
                                            . ($asignacion->punto_1 ?? '')
                                            . ' '
                                            . ($asignacion->punto_2 ?? '')
                                            . ' '
                                            . ($asignacion->servicio ?? '')
                                            . ' '
                                            . ($asignacion->tipo_asignacion ?? '')
                                        ));
                                    @endphp

                                    <tr
                                        data-facturacion-masiva-asignacion
                                        data-busqueda="{{ $textoBusqueda }}"
                                        data-asignacion-id="{{ $asignacion->id }}"
                                        data-label="{{ $asignacionLabel }}"
                                        data-codigo="{{ $asignacion->codigo }}"
                                        data-costo="{{ (int) $asignacion->costo }}"
                                        data-punto-1="{{ $asignacion->punto_1 }}"
                                        data-origen-gasto="{{ $asignacion->origen_gasto }}"
                                        data-punto-2="{{ $asignacion->punto_2 }}"
                                        data-servicio="{{ $asignacion->servicio }}"
                                        data-grupo-prefactura="{{ $asignacion->grupo_prefactura }}"
                                        data-tipo-asignacion="{{ $asignacion->tipo_asignacion }}"
                                    >
                                        <td class="text-center">
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                data-facturacion-masiva-checkbox
                                                value="{{ $asignacion->id }}"
                                                aria-label="Seleccionar {{ $asignacionLabel }}"
                                            >
                                        </td>

                                        <td>
                                            <div class="fw-semibold">
                                                {{ $asignacion->codigo ?? 'Sin código' }}
                                            </div>

                                            <div class="small text-muted">
                                                {{ $cobranza?->razon_social ?? 'Sin proveedor' }}
                                                <span class="mx-1">|</span>
                                                {{ $transportista?->nombre_transportista ?? 'Sin transportista' }}
                                            </div>
                                        </td>

                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $tipoAsignacion ?: 'SIN TIPO' }}
                                            </span>
                                        </td>

                                        <td>
                                            {{ $asignacion->punto_1 ?? '—' }}
                                        </td>

                                        <td>
                                            {{ $asignacion->punto_2 ?? '—' }}
                                        </td>

                                        <td class="text-end">
                                            ${{ number_format((int) $asignacion->costo, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-muted text-center">
                                            No hay asignaciones disponibles para cambios de facturación masivos.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SECCIÓN 2: COMPLETAR DATOS POR ASIGNACIÓN SELECCIONADA --}}
                <div class="border rounded p-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">2. Completar cambio de facturación</h6>
                            <div class="small text-muted">
                                Las asignaciones marcadas arriba aparecerán aquí para definir proveedor facturador efectivo y datos opcionales.
                            </div>
                        </div>

                        <button
                            type="button"
                            id="btn-facturacion-masiva-limpiar"
                            class="btn btn-outline-secondary btn-sm"
                        >
                            Limpiar selección
                        </button>
                    </div>




                    <div
                        id="facturacion-masiva-seleccionadas-body"
                        class="d-flex flex-column gap-3"
                        style="max-height: 420px; overflow-y: auto;"
                    >
                        <div data-facturacion-masiva-empty class="text-muted text-center border rounded p-3">
                            No hay asignaciones seleccionadas.
                        </div>
                    </div>






                    <div id="facturacion-masiva-error" class="alert alert-danger small d-none mb-0"></div>
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
                    id="btn-confirmar-facturaciones-masivas"
                    class="btn btn-primary"
                >
                    Agregar cambios de facturación al resumen
                </button>
            </div>
        </div>
    </div>
</div>