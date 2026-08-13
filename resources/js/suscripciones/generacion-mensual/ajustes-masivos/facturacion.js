/**
 * resources/js/suscripciones/generacion-mensual/ajustes-masivos/facturacion.js
 *
 * Maneja el modal de carga masiva de cambios de facturación.
 *
 * Cada asignación seleccionada puede tener su propio alcance:
 *
 * 1. Sin fecha efectiva:
 *    -> cambio de facturación para todo el mes.
 *    -> ajustes_mensuales[]
 *    -> tipo_ajuste = FACTURACION
 *
 * 2. Con fecha efectiva:
 *    -> cambio de facturación para una ejecución específica.
 *    -> excepciones_facturacion[]
 *    -> sólo disponible para asignaciones RUTA.
 *
 * No guarda directamente en BD.
 */

import {
    escaparHtml,
    limpiarTexto,
    normalizarCodigo,
} from '../utils';

export function inicializarFacturacionesMasivas(
    dom,
    ajustesMensualesApi = {}
) {
    const modal =
        document.getElementById(
            'modal-ajustes-masivos-facturacion'
        );

    if (!modal) {
        return;
    }

    /*
     * Templates utilizados para construir
     * cada tarjeta de asignación seleccionada.
     */
    const proveedorTemplate =
        document.getElementById(
            'facturacion-masiva-proveedor-template'
        );

    const transportistaTemplate =
        document.getElementById(
            'facturacion-masiva-transportista-template'
        );

    const fechaTemplate =
        document.getElementById(
            'facturacion-masiva-fecha-template'
        );

    /*
     * Buscador.
     */
    const buscador =
        document.getElementById(
            'facturacion-masiva-buscador'
        );

    const buscarBtn =
        document.getElementById(
            'btn-facturacion-masiva-buscar'
        );

    const limpiarBusquedaBtn =
        document.getElementById(
            'btn-facturacion-masiva-limpiar-busqueda'
        );

    /*
     * Listado y selección.
     */
    const asignacionesBody =
        document.getElementById(
            'facturacion-masiva-asignaciones-body'
        );

    const seleccionadasBody =
        document.getElementById(
            'facturacion-masiva-seleccionadas-body'
        );

    const contadorSeleccionadas =
        document.getElementById(
            'facturacion-masiva-seleccionadas-contador'
        );

    const errorBox =
        document.getElementById(
            'facturacion-masiva-error'
        );

    const limpiarBtn =
        document.getElementById(
            'btn-facturacion-masiva-limpiar'
        );

    const confirmarBtn =
        document.getElementById(
            'btn-confirmar-facturaciones-masivas'
        );

    /*
     * Estado local del modal.
     *
     * La clave corresponde a:
     * suscripcion_asignacion_id
     *
     * Cada elemento mantiene su propia fecha.
     */
    const seleccionadas = new Map();

    function filasAsignaciones() {
        return Array.from(
            modal.querySelectorAll(
                '[data-facturacion-masiva-asignacion]'
            )
        );
    }

    function checkboxesAsignaciones() {
        return Array.from(
            modal.querySelectorAll(
                '[data-facturacion-masiva-checkbox]'
            )
        );
    }

    function ocultarError() {
        if (!errorBox) {
            return;
        }

        errorBox.textContent = '';
        errorBox.classList.add('d-none');
    }

    function mostrarError(mensaje) {
        if (!errorBox) {
            alert(mensaje);
            return;
        }

        errorBox.textContent = mensaje;
        errorBox.classList.remove('d-none');
    }

    function esAsignacionRuta(item) {
        return normalizarCodigo(
            item?.tipo_asignacion || ''
        ) === 'RUTA';
    }

    function obtenerDatosFila(row) {
        return {
            suscripcion_asignacion_id:
                row.dataset.asignacionId || '',

            label:
                limpiarTexto(
                    row.dataset.label || ''
                ),

            codigo:
                limpiarTexto(
                    row.dataset.codigo || ''
                ),

            costo_base:
                row.dataset.costo || '',

            punto_1:
                limpiarTexto(
                    row.dataset.punto1 || ''
                ),

            origen_gasto:
                limpiarTexto(
                    row.dataset.origenGasto
                    || 'Suscripciones'
                ),

            punto_2:
                limpiarTexto(
                    row.dataset.punto2 || ''
                ),

            servicio:
                limpiarTexto(
                    row.dataset.servicio || ''
                ),

            grupo_prefactura:
                limpiarTexto(
                    row.dataset.grupoPrefactura || ''
                ),

            tipo_asignacion:
                limpiarTexto(
                    row.dataset.tipoAsignacion || ''
                ),

            /*
             * Alcance individual.
             *
             * Vacío:
             * todo el mes.
             *
             * YYYY-MM-DD:
             * una ejecución específica.
             */
            fecha:
                '',

            /*
             * Información editable.
             */
            suscripcion_proveedor_facturacion_id:
                '',

            proveedor_facturacion_label:
                '',

            suscripcion_transportista_override_id:
                '',

            transportista_override_label:
                '',

            costo:
                '',

            tipo_documento:
                '',

            detalle_documento:
                '',

            detalle_impuesto:
                '',

            final:
                '',

            observacion:
                '',
        };
    }

    function actualizarContador() {
        if (contadorSeleccionadas) {
            contadorSeleccionadas.textContent =
                String(
                    seleccionadas.size
                );
        }
    }

    function sincronizarCheckbox(
        id,
        checked
    ) {
        const checkbox =
            checkboxesAsignaciones()
                .find(function (item) {
                    return String(item.value)
                        === String(id);
                });

        if (checkbox) {
            checkbox.checked = checked;
        }
    }

    function optionLabel(select) {
        const option =
            select?.options[
                select.selectedIndex
            ] || null;

        if (
            !option
            || !option.value
        ) {
            return '';
        }

        return limpiarTexto(
            option.dataset.label
            || option.text
            || ''
        );
    }

    /*
     * Se conserva esta utilidad existente.
     */
    function valorDocumentoDesdeDatos(
        tipo,
        detalleDocumento,
        detalleImpuesto,
        final
    ) {
        return [
            limpiarTexto(
                tipo || ''
            ),

            limpiarTexto(
                detalleDocumento || ''
            ),

            limpiarTexto(
                detalleImpuesto || ''
            ),

            limpiarTexto(
                final || ''
            ),
        ].join('|');
    }

    /*
     * Al seleccionar proveedor se copian
     * sus datos documentales configurados.
     */
    function aplicarDatosProveedor(row) {
        const proveedorSelect =
            row.querySelector(
                '[data-facturacion-masiva-proveedor]'
            );

        const option =
            proveedorSelect?.options[
                proveedorSelect.selectedIndex
            ] || null;

        if (
            !option
            || !option.value
        ) {
            return;
        }

        const tipoDocumentoSelect =
            row.querySelector(
                '[data-facturacion-masiva-tipo-documento]'
            );

        const detalleDocumentoSelect =
            row.querySelector(
                '[data-facturacion-masiva-detalle-documento]'
            );

        const detalleImpuestoSelect =
            row.querySelector(
                '[data-facturacion-masiva-detalle-impuesto]'
            );

        const finalSelect =
            row.querySelector(
                '[data-facturacion-masiva-final]'
            );

        if (
            tipoDocumentoSelect
            && option.dataset.tipo
        ) {
            tipoDocumentoSelect.value =
                limpiarTexto(
                    option.dataset.tipo || ''
                );
        }

        if (
            detalleDocumentoSelect
            && option.dataset.detalleDocumento
        ) {
            detalleDocumentoSelect.value =
                limpiarTexto(
                    option.dataset.detalleDocumento
                    || ''
                );
        }

        if (
            detalleImpuestoSelect
            && option.dataset.detalleImpuesto
        ) {
            detalleImpuestoSelect.value =
                limpiarTexto(
                    option.dataset.detalleImpuesto
                    || ''
                );
        }

        if (
            finalSelect
            && option.dataset.final
        ) {
            finalSelect.value =
                limpiarTexto(
                    option.dataset.final || ''
                );
        }
    }

    /*
     * Guarda los valores visibles de cada tarjeta
     * dentro del Map.
     *
     * Esto incluye ahora la fecha individual.
     */
    function guardarValoresEditados() {
        if (!seleccionadasBody) {
            return;
        }

        seleccionadasBody
            .querySelectorAll(
                '[data-facturacion-masiva-seleccionada]'
            )
            .forEach(function (row) {
                const id =
                    row.dataset.asignacionId;

                if (
                    !id
                    || !seleccionadas.has(
                        String(id)
                    )
                ) {
                    return;
                }

                const item =
                    seleccionadas.get(
                        String(id)
                    );

                const fechaSelect =
                    row.querySelector(
                        '[data-facturacion-masiva-fecha]'
                    );

                const proveedorSelect =
                    row.querySelector(
                        '[data-facturacion-masiva-proveedor]'
                    );

                const transportistaSelect =
                    row.querySelector(
                        '[data-facturacion-masiva-transportista]'
                    );

                item.fecha =
                    limpiarTexto(
                        fechaSelect?.value
                        || ''
                    );

                item.suscripcion_proveedor_facturacion_id =
                    proveedorSelect?.value
                    || '';

                item.proveedor_facturacion_label =
                    optionLabel(
                        proveedorSelect
                    );

                item.suscripcion_transportista_override_id =
                    transportistaSelect?.value
                    || '';

                item.transportista_override_label =
                    optionLabel(
                        transportistaSelect
                    );

                item.costo =
                    row.querySelector(
                        '[data-facturacion-masiva-costo]'
                    )?.value
                    || '';

                item.tipo_documento =
                    limpiarTexto(
                        row.querySelector(
                            '[data-facturacion-masiva-tipo-documento]'
                        )?.value
                        || ''
                    );

                item.detalle_documento =
                    limpiarTexto(
                        row.querySelector(
                            '[data-facturacion-masiva-detalle-documento]'
                        )?.value
                        || ''
                    );

                item.detalle_impuesto =
                    limpiarTexto(
                        row.querySelector(
                            '[data-facturacion-masiva-detalle-impuesto]'
                        )?.value
                        || ''
                    );

                item.final =
                    limpiarTexto(
                        row.querySelector(
                            '[data-facturacion-masiva-final]'
                        )?.value
                        || ''
                    );

                item.observacion =
                    limpiarTexto(
                        row.querySelector(
                            '[data-facturacion-masiva-observacion]'
                        )?.value
                        || ''
                    );

                seleccionadas.set(
                    String(id),
                    item
                );
            });
    }

    /*
     * Renderiza una tarjeta independiente
     * por cada asignación.
     */
    function renderizarSeleccionadas() {
        if (!seleccionadasBody) {
            return;
        }

        seleccionadasBody.innerHTML = '';

        if (seleccionadas.size === 0) {
            seleccionadasBody.innerHTML = `
                <div
                    data-facturacion-masiva-empty
                    class="text-muted text-center border rounded p-3"
                >
                    No hay asignaciones seleccionadas.
                </div>
            `;

            actualizarContador();

            return;
        }

        seleccionadas.forEach(
            function (item) {
                const row =
                    document.createElement(
                        'div'
                    );

                const esRuta =
                    esAsignacionRuta(
                        item
                    );

                row.className =
                    'border rounded p-3 bg-light';

                row.dataset.facturacionMasivaSeleccionada =
                    '1';

                row.dataset.asignacionId =
                    item.suscripcion_asignacion_id;

                row.innerHTML = `
                    <div
                        class="d-flex justify-content-between align-items-start gap-2 mb-3"
                    >
                        <div>
                            <div class="fw-semibold">
                                ${escaparHtml(
                                    item.codigo
                                    || 'Sin código'
                                )}
                            </div>

                            <div class="small text-muted">
                                ${escaparHtml(
                                    item.label
                                    || '—'
                                )}
                            </div>

                            <div class="small text-muted">
                                Tipo asignación:
                                ${escaparHtml(
                                    item.tipo_asignacion
                                    || '—'
                                )}
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            data-facturacion-masiva-quitar
                            data-asignacion-id="${escaparHtml(
                                item.suscripcion_asignacion_id
                            )}"
                        >
                            Quitar
                        </button>
                    </div>

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label small mb-1">
                                Fecha efectiva
                            </label>

                            <select
                                class="form-select form-select-sm"
                                data-facturacion-masiva-fecha
                                ${esRuta ? '' : 'disabled'}
                            >
                                ${
                                    fechaTemplate?.innerHTML
                                    || '<option value="">Todo el mes</option>'
                                }
                            </select>

                            <div class="small text-muted mt-1">
                                ${
                                    esRuta
                                        ? 'Todo el mes mantiene el cambio mensual. Selecciona una fecha para trasladar sólo esa ejecución.'
                                        : 'Este tipo de asignación sólo permite cambio de facturación para todo el mes.'
                                }
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small mb-1">
                                Proveedor facturador efectivo
                            </label>

                            <select
                                class="form-select form-select-sm"
                                data-facturacion-masiva-proveedor
                            >
                                ${
                                    proveedorTemplate?.innerHTML
                                    || '<option value="">Seleccionar proveedor facturador...</option>'
                                }
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small mb-1">
                                Transportista efectivo opcional
                            </label>

                            <select
                                class="form-select form-select-sm"
                                data-facturacion-masiva-transportista
                            >
                                ${
                                    transportistaTemplate?.innerHTML
                                    || '<option value="">Mantener transportista original...</option>'
                                }
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small mb-1">
                                Costo opcional
                            </label>

                            <input
                                type="number"
                                class="form-control form-control-sm text-end"
                                min="0"
                                value="${escaparHtml(
                                    item.costo || ''
                                )}"
                                placeholder="Opcional"
                                data-facturacion-masiva-costo
                            >

                            <div class="small text-muted mt-1">
                                Base:
                                ${escaparHtml(
                                    item.costo_base
                                    || '—'
                                )}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small mb-1">
                                Tipo documento
                            </label>

                            <select
                                class="form-select form-select-sm"
                                data-facturacion-masiva-tipo-documento
                            >
                                <option value="">
                                    Seleccionar...
                                </option>

                                <option value="FACTURA">
                                    FACTURA
                                </option>

                                <option value="BOLETA">
                                    BOLETA
                                </option>

                                <option value="DOCUMENTO">
                                    DOCUMENTO
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small mb-1">
                                Detalle documento
                            </label>

                            <select
                                class="form-select form-select-sm"
                                data-facturacion-masiva-detalle-documento
                            >
                                <option value="">
                                    Seleccionar...
                                </option>

                                <option value="NETO">
                                    NETO
                                </option>

                                <option value="BRUTO">
                                    BRUTO
                                </option>

                                <option value="SIN REGISTRO">
                                    SIN REGISTRO
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small mb-1">
                                Detalle impuesto
                            </label>

                            <select
                                class="form-select form-select-sm"
                                data-facturacion-masiva-detalle-impuesto
                            >
                                <option value="">
                                    Seleccionar...
                                </option>

                                <option value="IMPUESTO">
                                    IMPUESTO
                                </option>

                                <option value="RETENCION">
                                    RETENCION
                                </option>

                                <option value="SIN REGISTRO">
                                    SIN REGISTRO
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small mb-1">
                                Final
                            </label>

                            <select
                                class="form-select form-select-sm"
                                data-facturacion-masiva-final
                            >
                                <option value="">
                                    Seleccionar...
                                </option>

                                <option value="TOTAL">
                                    TOTAL
                                </option>

                                <option value="LIQUIDO A PAGAR">
                                    LIQUIDO A PAGAR
                                </option>
                            </select>
                        </div>

                        <div class="col-md-9">
                            <label class="form-label small mb-1">
                                Observación
                            </label>

                            <input
                                type="text"
                                class="form-control form-control-sm"
                                value="${escaparHtml(
                                    item.observacion
                                    || ''
                                )}"
                                placeholder="Observación opcional"
                                data-facturacion-masiva-observacion
                            >
                        </div>

                    </div>
                `;

                seleccionadasBody
                    .appendChild(row);

                const fechaSelect =
                    row.querySelector(
                        '[data-facturacion-masiva-fecha]'
                    );

                const proveedorSelect =
                    row.querySelector(
                        '[data-facturacion-masiva-proveedor]'
                    );

                const transportistaSelect =
                    row.querySelector(
                        '[data-facturacion-masiva-transportista]'
                    );

                const tipoDocumentoSelect =
                    row.querySelector(
                        '[data-facturacion-masiva-tipo-documento]'
                    );

                const detalleDocumentoSelect =
                    row.querySelector(
                        '[data-facturacion-masiva-detalle-documento]'
                    );

                const detalleImpuestoSelect =
                    row.querySelector(
                        '[data-facturacion-masiva-detalle-impuesto]'
                    );

                const finalSelect =
                    row.querySelector(
                        '[data-facturacion-masiva-final]'
                    );

                /*
                 * Restaurar valores almacenados
                 * en el Map después de cada render.
                 */
                if (fechaSelect) {
                    fechaSelect.value =
                        item.fecha || '';
                }

                if (proveedorSelect) {
                    proveedorSelect.value =
                        item.suscripcion_proveedor_facturacion_id
                        || '';
                }

                if (transportistaSelect) {
                    transportistaSelect.value =
                        item.suscripcion_transportista_override_id
                        || '';
                }

                if (tipoDocumentoSelect) {
                    tipoDocumentoSelect.value =
                        item.tipo_documento
                        || '';
                }

                if (detalleDocumentoSelect) {
                    detalleDocumentoSelect.value =
                        item.detalle_documento
                        || '';
                }

                if (detalleImpuestoSelect) {
                    detalleImpuestoSelect.value =
                        item.detalle_impuesto
                        || '';
                }

                if (finalSelect) {
                    finalSelect.value =
                        item.final
                        || '';
                }
            }
        );

        actualizarContador();
    }

    /*
     * El modal continúa permitiendo seleccionar:
     *
     * RUTA
     * VARIABLE
     * FIJO_MENSUAL
     * OPV
     *
     * Sólo RUTA podrá seleccionar una fecha específica.
     */
    function agregarSeleccion(row) {
        const item =
            obtenerDatosFila(row);

        if (
            !item.suscripcion_asignacion_id
        ) {
            return;
        }

        const tipoAsignacion =
            normalizarCodigo(
                item.tipo_asignacion
            );

        if (
            ![
                'RUTA',
                'VARIABLE',
                'FIJO_MENSUAL',
                'OPV',
            ].includes(
                tipoAsignacion
            )
        ) {
            return;
        }

        const id =
            String(
                item.suscripcion_asignacion_id
            );

        if (
            !seleccionadas.has(id)
        ) {
            seleccionadas.set(
                id,
                item
            );
        }
    }

    function quitarSeleccion(id) {
        seleccionadas.delete(
            String(id)
        );

        sincronizarCheckbox(
            id,
            false
        );

        ocultarError();
        renderizarSeleccionadas();
    }

    function filtrarAsignaciones() {
        const texto =
            normalizarCodigo(
                buscador?.value || ''
            );

        filasAsignaciones()
            .forEach(function (row) {
                const busqueda =
                    normalizarCodigo(
                        row.dataset.busqueda
                        || ''
                    );

                const visible =
                    texto === ''
                    || busqueda.includes(
                        texto
                    );

                row.classList.toggle(
                    'd-none',
                    !visible
                );
            });
    }

    function limpiarBusqueda() {
        if (buscador) {
            buscador.value = '';
        }

        filtrarAsignaciones();
        ocultarError();
    }

    function limpiarSeleccion() {
        seleccionadas.clear();

        checkboxesAsignaciones()
            .forEach(function (checkbox) {
                checkbox.checked = false;
            });

        ocultarError();
        renderizarSeleccionadas();
    }

    /*
     * Construye exclusivamente los cambios
     * cuyo alcance es todo el mes.
     */
    function construirAjustesFacturacion() {
        const ajustes = [];

        seleccionadas.forEach(
            function (item) {
                const fecha =
                    limpiarTexto(
                        item.fecha || ''
                    );

                /*
                 * Si existe fecha, este item pertenece
                 * al flujo de excepciones.
                 */
                if (fecha !== '') {
                    return;
                }

                ajustes.push({
                    clave_control: [
                        'FACTURACION',
                        'ASIGNACION',
                        item.suscripcion_asignacion_id
                        || '',
                    ].join('|'),

                    tipo_ajuste:
                        'FACTURACION',

                    concepto_pago_variable_id:
                        '',

                    concepto_pago_variable_manual:
                        '',

                    concepto_pago_variable_label:
                        '',

                    suscripcion_asignacion_id:
                        item.suscripcion_asignacion_id
                        || '',

                    suscripcion_proveedor_id:
                        '',

                    suscripcion_transportista_id:
                        '',

                    suscripcion_proveedor_facturacion_id:
                        item.suscripcion_proveedor_facturacion_id
                        || '',

                    suscripcion_transportista_override_id:
                        item.suscripcion_transportista_override_id
                        || '',

                    punto_1:
                        item.punto_1 || '',

                    origen_gasto:
                        item.origen_gasto
                        || 'Suscripciones',

                    punto_2:
                        item.punto_2 || '',

                    codigo:
                        item.codigo || '',

                    servicio:
                        item.servicio || '',

                    grupo_prefactura:
                        item.grupo_prefactura
                        || '',

                    costo:
                        item.costo || '',

                    q_calendario:
                        '',

                    q_inasistencia:
                        '',

                    cantidad:
                        '',

                    total:
                        '',

                    tipo_documento:
                        item.tipo_documento
                        || '',

                    detalle_documento:
                        item.detalle_documento
                        || '',

                    detalle_impuesto:
                        item.detalle_impuesto
                        || '',

                    final:
                        item.final
                        || '',

                    observacion:
                        item.observacion
                        || '',

                    asignacion_label:
                        item.label || '',

                    proveedor_label:
                        '',

                    proveedor_facturacion_label:
                        item.proveedor_facturacion_label
                        || '',

                    transportista_label:
                        '',

                    transportista_override_label:
                        item.transportista_override_label
                        || '',

                    total_estimado:
                        0,
                });
            }
        );

        return ajustes;
    }

    /*
     * Construye exclusivamente los elementos
     * que tienen una fecha específica.
     */
    function construirExcepcionesFacturacion() {
        const excepciones = [];

        seleccionadas.forEach(
            function (item) {
                const fecha =
                    limpiarTexto(
                        item.fecha || ''
                    );

                /*
                 * Sin fecha:
                 * pertenece al cambio mensual.
                 */
                if (fecha === '') {
                    return;
                }

                excepciones.push({
                    clave_control: [
                        'EXCEPCION_FACTURACION',
                        'ASIGNACION',
                        item.suscripcion_asignacion_id
                        || '',
                        fecha,
                    ].join('|'),

                    suscripcion_asignacion_id:
                        item.suscripcion_asignacion_id
                        || '',

                    fecha:
                        fecha,

                    suscripcion_proveedor_facturacion_id:
                        item.suscripcion_proveedor_facturacion_id
                        || '',

                    suscripcion_transportista_override_id:
                        item.suscripcion_transportista_override_id
                        || '',

                    /*
                     * Vacío significa utilizar
                     * el costo normal de la ruta.
                     */
                    costo:
                        item.costo || '',

                    tipo_documento:
                        item.tipo_documento
                        || '',

                    detalle_documento:
                        item.detalle_documento
                        || '',

                    detalle_impuesto:
                        item.detalle_impuesto
                        || '',

                    final:
                        item.final
                        || '',

                    observacion:
                        item.observacion
                        || '',

                    /*
                     * Campos visuales.
                     */
                    asignacion_label:
                        item.label || '',

                    proveedor_facturacion_label:
                        item.proveedor_facturacion_label
                        || '',

                    transportista_override_label:
                        item.transportista_override_label
                        || '',

                    codigo:
                        item.codigo || '',

                    tipo_asignacion:
                        item.tipo_asignacion
                        || '',
                });
            }
        );

        return excepciones;
    }

    /*
     * Valida únicamente los cambios mensuales
     * encontrados.
     *
     * Si no existen cambios mensuales, es válido:
     * puede haber sólo excepciones por fecha.
     */
    function validarAjustes(
        ajustes
    ) {
        if (
            ajustes.length === 0
        ) {
            return '';
        }

        const sinProveedor =
            ajustes.find(
                function (ajuste) {
                    return !ajuste
                        .suscripcion_proveedor_facturacion_id;
                }
            );

        if (sinProveedor) {
            const codigo =
                sinProveedor.codigo
                || sinProveedor.asignacion_label
                || 'una asignación';

            return `Debes seleccionar un proveedor facturador efectivo para ${codigo}.`;
        }

        return '';
    }

    /*
     * Valida únicamente las excepciones
     * con fecha específica.
     */
    function validarExcepcionesFacturacion(
        excepciones
    ) {
        if (
            excepciones.length === 0
        ) {
            return '';
        }

        const sinFecha =
            excepciones.find(
                function (excepcion) {
                    return !excepcion.fecha;
                }
            );

        if (sinFecha) {
            return 'Hay una excepción de facturación sin fecha efectiva.';
        }

        const noRuta =
            excepciones.find(
                function (excepcion) {
                    return normalizarCodigo(
                        excepcion.tipo_asignacion
                        || ''
                    ) !== 'RUTA';
                }
            );

        if (noRuta) {
            const codigo =
                noRuta.codigo
                || noRuta.asignacion_label
                || 'la asignación seleccionada';

            return `La excepción por fecha sólo puede aplicarse a asignaciones de tipo RUTA. Revisa ${codigo}.`;
        }

        const sinProveedor =
            excepciones.find(
                function (excepcion) {
                    return !excepcion
                        .suscripcion_proveedor_facturacion_id;
                }
            );

        if (sinProveedor) {
            const codigo =
                sinProveedor.codigo
                || sinProveedor.asignacion_label
                || 'una ruta';

            return `Debes seleccionar un proveedor facturador efectivo para ${codigo}.`;
        }

        return '';
    }

    function cerrarModal() {
        if (
            window.jQuery
            && typeof window.jQuery(modal).modal
                === 'function'
        ) {
            window.jQuery(modal)
                .modal('hide');

            return;
        }

        modal.classList.remove(
            'show'
        );

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

        modal.style.display =
            'none';

        document.body.classList.remove(
            'modal-open'
        );

        document
            .querySelectorAll(
                '.modal-backdrop'
            )
            .forEach(
                function (backdrop) {
                    backdrop.remove();
                }
            );
    }

    /*
     * Confirma toda la selección.
     *
     * Aquí se separan automáticamente:
     *
     * - filas sin fecha
     *      -> ajustes_mensuales[]
     *
     * - filas con fecha
     *      -> excepciones_facturacion[]
     *
     * Una misma confirmación puede contener
     * ambos tipos.
     */
    function confirmarFacturaciones() {
        ocultarError();

        /*
         * Primero sincronizamos las tarjetas visibles
         * con el estado del Map.
         */
        guardarValoresEditados();

        if (
            seleccionadas.size === 0
        ) {
            mostrarError(
                'Selecciona al menos una asignación para registrar cambios de facturación.'
            );

            return;
        }

        const ajustes =
            construirAjustesFacturacion();

        const excepciones =
            construirExcepcionesFacturacion();

        const errorAjustes =
            validarAjustes(
                ajustes
            );

        if (errorAjustes) {
            mostrarError(
                errorAjustes
            );

            return;
        }

        const errorExcepciones =
            validarExcepcionesFacturacion(
                excepciones
            );

        if (errorExcepciones) {
            mostrarError(
                errorExcepciones
            );

            return;
        }

        /*
         * Si existen excepciones, exigimos que
         * el API nuevo esté disponible ANTES de
         * registrar cualquier cambio.
         *
         * Así evitamos un guardado parcial.
         */
        if (
            excepciones.length > 0
            && typeof ajustesMensualesApi
                .agregarExcepcionesFacturacionMasivas
                !== 'function'
        ) {
            mostrarError(
                'No se encuentra disponible el registro de excepciones de facturación por fecha. Actualiza los archivos JavaScript del módulo.'
            );

            return;
        }

        /*
         * El API mensual normalmente siempre existe.
         *
         * Si estamos mezclando ambos tipos y no existe,
         * detenemos todo para evitar un resultado parcial.
         */
        if (
            ajustes.length > 0
            && typeof ajustesMensualesApi
                .agregarAjustesMasivos
                !== 'function'
            && excepciones.length > 0
        ) {
            mostrarError(
                'No se encuentra disponible el registro de cambios mensuales de facturación.'
            );

            return;
        }

        /*
         * Compatibilidad con el antiguo evento:
         * solamente cuando TODA la carga es mensual.
         */
        if (
            ajustes.length > 0
            && excepciones.length === 0
            && typeof ajustesMensualesApi
                .agregarAjustesMasivos
                !== 'function'
        ) {
            document.dispatchEvent(
                new CustomEvent(
                    'suscripciones:ajustes-masivos',
                    {
                        detail: {
                            tipo:
                                'FACTURACION',

                            ajustes:
                                ajustes,
                        },
                    }
                )
            );

            limpiarSeleccion();
            cerrarModal();

            return;
        }

        let agregados =
            0;

        let duplicados =
            0;

        let omitidos =
            0;

        /*
         * Primero registrar cambios mensuales.
         */
        if (
            ajustes.length > 0
        ) {
            const resultadoAjustes =
                ajustesMensualesApi
                    .agregarAjustesMasivos(
                        ajustes
                    );

            agregados +=
                resultadoAjustes?.agregados
                ?? ajustes.length;

            duplicados +=
                resultadoAjustes?.duplicados
                ?? 0;
        }

        /*
         * Luego registrar excepciones por fecha.
         */
        if (
            excepciones.length > 0
        ) {
            const resultadoExcepciones =
                ajustesMensualesApi
                    .agregarExcepcionesFacturacionMasivas(
                        excepciones
                    );

            agregados +=
                resultadoExcepciones?.agregados
                ?? excepciones.length;

            duplicados +=
                resultadoExcepciones?.duplicados
                ?? 0;

            omitidos +=
                resultadoExcepciones?.omitidos
                ?? 0;
        }

        /*
         * Si al menos una entrada fue agregada,
         * la información ya se encuentra en el
         * resumen principal.
         */
        if (
            agregados > 0
        ) {
            limpiarSeleccion();
            cerrarModal();

            return;
        }

        const mensajes =
            [];

        if (
            duplicados > 0
        ) {
            mensajes.push(
                `Se omitieron ${duplicados} cambio(s) porque ya estaban agregados en el resumen.`
            );
        }

        if (
            omitidos > 0
        ) {
            mensajes.push(
                `Se omitieron ${omitidos} cambio(s) por información incompleta.`
            );
        }

        if (
            mensajes.length > 0
        ) {
            mostrarError(
                mensajes.join(' ')
            );

            return;
        }

        mostrarError(
            'No fue posible agregar los cambios de facturación al resumen.'
        );
    }

    function registrarEventos() {
        /*
         * Buscador.
         */
        if (buscador) {
            buscador.addEventListener(
                'keydown',
                function (event) {
                    if (
                        event.key === 'Enter'
                    ) {
                        event.preventDefault();
                        filtrarAsignaciones();
                    }
                }
            );

            buscador.addEventListener(
                'input',
                filtrarAsignaciones
            );
        }

        if (buscarBtn) {
            buscarBtn.addEventListener(
                'click',
                filtrarAsignaciones
            );
        }

        if (limpiarBusquedaBtn) {
            limpiarBusquedaBtn.addEventListener(
                'click',
                limpiarBusqueda
            );
        }

        /*
         * Selección de asignaciones.
         */
        if (asignacionesBody) {
            asignacionesBody.addEventListener(
                'change',
                function (event) {
                    const checkbox =
                        event.target.closest(
                            '[data-facturacion-masiva-checkbox]'
                        );

                    if (!checkbox) {
                        return;
                    }

                    /*
                     * Antes de renderizar nuevamente,
                     * guardar lo que el usuario ya editó
                     * en las otras tarjetas.
                     */
                    guardarValoresEditados();

                    const row =
                        checkbox.closest(
                            '[data-facturacion-masiva-asignacion]'
                        );

                    if (!row) {
                        return;
                    }

                    if (checkbox.checked) {
                        agregarSeleccion(
                            row
                        );
                    } else {
                        seleccionadas.delete(
                            String(
                                checkbox.value
                            )
                        );
                    }

                    ocultarError();
                    renderizarSeleccionadas();
                }
            );
        }

        /*
         * Interacciones dentro de las tarjetas.
         */
        if (seleccionadasBody) {
            seleccionadasBody.addEventListener(
                'click',
                function (event) {
                    const button =
                        event.target.closest(
                            '[data-facturacion-masiva-quitar]'
                        );

                    if (!button) {
                        return;
                    }

                    guardarValoresEditados();

                    quitarSeleccion(
                        button.dataset.asignacionId
                    );
                }
            );

            seleccionadasBody.addEventListener(
                'change',
                function (event) {
                    const proveedorSelect =
                        event.target.closest(
                            '[data-facturacion-masiva-proveedor]'
                        );

                    if (proveedorSelect) {
                        const row =
                            proveedorSelect.closest(
                                '[data-facturacion-masiva-seleccionada]'
                            );

                        if (row) {
                            /*
                             * Primero copiar configuraciones
                             * documentales del proveedor.
                             */
                            aplicarDatosProveedor(
                                row
                            );
                        }
                    }

                    /*
                     * Esto también captura cambios de:
                     *
                     * - fecha
                     * - transportista
                     * - documento
                     * - impuesto
                     * - final
                     */
                    guardarValoresEditados();

                    ocultarError();
                }
            );

            seleccionadasBody.addEventListener(
                'input',
                function () {
                    guardarValoresEditados();
                    ocultarError();
                }
            );
        }

        if (limpiarBtn) {
            limpiarBtn.addEventListener(
                'click',
                limpiarSeleccion
            );
        }

        if (confirmarBtn) {
            confirmarBtn.addEventListener(
                'click',
                confirmarFacturaciones
            );
        }
    }

    registrarEventos();

    /*
     * Estado visual inicial.
     */
    filtrarAsignaciones();
    renderizarSeleccionadas();
}