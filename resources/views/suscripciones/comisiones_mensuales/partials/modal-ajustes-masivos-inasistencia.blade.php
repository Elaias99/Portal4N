{{-- resources/views/suscripciones/comisiones_mensuales/partials/modal-ajustes-masivos-inasistencia.blade.php --}}

<div
    class="modal fade"
    id="modal-ajustes-masivos-inasistencia"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-ajustes-masivos-inasistencia-title"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="modal-ajustes-masivos-inasistencia-title">
                        Inasistencias masivas
                    </h5>

                    <div class="small text-muted">
                        Busca rutas normales, selecciónalas y define los días de inasistencia para cada una.
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
                    usa esta opción para registrar días no realizados en rutas habituales del mes.
                    Las rutas seleccionadas se descontarán del cálculo del periodo.
                </div>


                {{-- SECCIÓN 1: BUSCAR Y SELECCIONAR RUTAS --}}
                <div class="border rounded p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">1. Buscar y seleccionar rutas</h6>
                            <div class="small text-muted">
                                Puedes buscar por código, proveedor, RUT, transportista, punto o servicio.
                            </div>
                        </div>

                        <div class="small text-muted">
                            Seleccionadas:
                            <strong id="inasistencia-masiva-seleccionadas-contador">0</strong>
                        </div>
                    </div>

                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-8">
                            <label for="inasistencia-masiva-buscador" class="form-label">
                                Buscar proveedor, ruta, transportista o punto
                            </label>

                            <input
                                type="text"
                                id="inasistencia-masiva-buscador"
                                class="form-control"
                                placeholder="Ej: TRANSPORTES SKY, FE.SD.03, Las Condes..."
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-md-4 d-flex gap-2">
                            <button
                                type="button"
                                id="btn-inasistencia-masiva-buscar"
                                class="btn btn-outline-primary flex-fill"
                            >
                                Buscar
                            </button>

                            <button
                                type="button"
                                id="btn-inasistencia-masiva-limpiar-busqueda"
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
                                    <th>Código / proveedor / transportista</th>
                                    <th>Punto</th>
                                    <th>Punto 2</th>
                                    <th class="text-end">Costo</th>
                                </tr>
                            </thead>

                            <tbody id="inasistencia-masiva-rutas-body">
                                @php
                                    $rutasInasistenciaMasiva = collect($asignacionesAjustesMensuales ?? [])
                                        ->filter(fn ($asignacion) => mb_strtoupper(trim((string) $asignacion->tipo_asignacion)) === 'RUTA')
                                        ->values();
                                @endphp

                                @forelse($rutasInasistenciaMasiva as $asignacion)
                                    @php
                                        $cobranza = $asignacion->suscripcionProveedor?->cobranzaCompra;
                                        $transportista = $asignacion->transportista;

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
                                        ));
                                    @endphp

                                    <tr
                                        data-inasistencia-masiva-ruta
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
                                                data-inasistencia-masiva-checkbox
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
                                        <td colspan="5" class="text-muted text-center">
                                            No hay rutas normales disponibles para carga masiva de inasistencias.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SECCIÓN 2: COMPLETAR DATOS POR RUTA SELECCIONADA --}}
                <div class="border rounded p-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">2. Completar inasistencias</h6>
                            <div class="small text-muted">
                                Las rutas marcadas arriba aparecerán aquí para definir Q inasistencia y observación.
                            </div>
                        </div>

                        <button
                            type="button"
                            id="btn-inasistencia-masiva-limpiar"
                            class="btn btn-outline-secondary btn-sm"
                        >
                            Limpiar selección
                        </button>
                    </div>

                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-sm table-bordered align-middle mb-2">
                            <thead class="table-light">
                                <tr>
                                    <th>Ruta / proveedor / transportista</th>
                                    <th style="width: 180px;" class="text-end">Q inasistencia</th>
                                    <th>Observación</th>
                                    <th style="width: 80px;" class="text-center">Quitar</th>
                                </tr>
                            </thead>

                            <tbody id="inasistencia-masiva-seleccionadas-body">
                                <tr data-inasistencia-masiva-empty>
                                    <td colspan="4" class="text-muted text-center">
                                        No hay rutas seleccionadas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="inasistencia-masiva-error" class="alert alert-danger small d-none mb-0"></div>
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
                    id="btn-confirmar-inasistencias-masivas"
                    class="btn btn-primary"
                >
                    Agregar inasistencias al resumen
                </button>
            </div>
        </div>
    </div>
</div>