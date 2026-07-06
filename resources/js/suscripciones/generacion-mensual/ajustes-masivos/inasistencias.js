/**
 * resources/js/suscripciones/generacion-mensual/ajustes-masivos/inasistencias.js
 *
 * Maneja el modal de carga masiva de inasistencias.
 * No guarda en BD directamente: sólo prepara novedades y las envía
 * al flujo actual de ajustesMensuales[].
 */

import {
    escaparHtml,
    limpiarTexto,
    normalizarCodigo,
} from '../utils';

export function inicializarInasistenciasMasivas(dom, ajustesMensualesApi = {}) {
    const modal = document.getElementById('modal-ajustes-masivos-inasistencia');

    if (!modal) {
        return;
    }

    const buscador = document.getElementById('inasistencia-masiva-buscador');
    const buscarBtn = document.getElementById('btn-inasistencia-masiva-buscar');
    const limpiarBusquedaBtn = document.getElementById('btn-inasistencia-masiva-limpiar-busqueda');

    const rutasBody = document.getElementById('inasistencia-masiva-rutas-body');
    const seleccionadasBody = document.getElementById('inasistencia-masiva-seleccionadas-body');
    const contadorSeleccionadas = document.getElementById('inasistencia-masiva-seleccionadas-contador');
    const errorBox = document.getElementById('inasistencia-masiva-error');
    const limpiarBtn = document.getElementById('btn-inasistencia-masiva-limpiar');
    const confirmarBtn = document.getElementById('btn-confirmar-inasistencias-masivas');

    const seleccionadas = new Map();

    function filasRutas() {
        return Array.from(modal.querySelectorAll('[data-inasistencia-masiva-ruta]'));
    }

    function checkboxesRutas() {
        return Array.from(modal.querySelectorAll('[data-inasistencia-masiva-checkbox]'));
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

    function obtenerDatosFila(row) {
        return {
            suscripcion_asignacion_id: row.dataset.asignacionId || '',
            label: limpiarTexto(row.dataset.label || ''),
            codigo: limpiarTexto(row.dataset.codigo || ''),
            costo: row.dataset.costo || '',
            punto_1: limpiarTexto(row.dataset.punto1 || ''),
            origen_gasto: limpiarTexto(row.dataset.origenGasto || 'Suscripciones'),
            punto_2: limpiarTexto(row.dataset.punto2 || ''),
            servicio: limpiarTexto(row.dataset.servicio || ''),
            grupo_prefactura: limpiarTexto(row.dataset.grupoPrefactura || ''),
            tipo_asignacion: limpiarTexto(row.dataset.tipoAsignacion || ''),
            q_inasistencia: '',
            observacion: '',
        };
    }

    function actualizarContador() {
        if (contadorSeleccionadas) {
            contadorSeleccionadas.textContent = String(seleccionadas.size);
        }
    }

    function sincronizarCheckbox(id, checked) {
        const checkbox = checkboxesRutas().find(function (item) {
            return String(item.value) === String(id);
        });

        if (checkbox) {
            checkbox.checked = checked;
        }
    }

    function guardarValoresEditados() {
        if (!seleccionadasBody) {
            return;
        }

        seleccionadasBody.querySelectorAll('[data-inasistencia-masiva-seleccionada]').forEach(function (row) {
            const id = row.dataset.asignacionId;

            if (!id || !seleccionadas.has(String(id))) {
                return;
            }

            const item = seleccionadas.get(String(id));

            item.q_inasistencia = row.querySelector('[data-inasistencia-masiva-q]')?.value || '';
            item.observacion = limpiarTexto(row.querySelector('[data-inasistencia-masiva-observacion]')?.value || '');

            seleccionadas.set(String(id), item);
        });
    }

    function renderizarSeleccionadas() {
        if (!seleccionadasBody) {
            return;
        }

        seleccionadasBody.innerHTML = '';

        if (seleccionadas.size === 0) {
            seleccionadasBody.innerHTML = `
                <tr data-inasistencia-masiva-empty>
                    <td colspan="4" class="text-muted text-center">
                        No hay rutas seleccionadas.
                    </td>
                </tr>
            `;

            actualizarContador();
            return;
        }

        seleccionadas.forEach(function (item) {
            const row = document.createElement('tr');

            row.dataset.inasistenciaMasivaSeleccionada = '1';
            row.dataset.asignacionId = item.suscripcion_asignacion_id;

            row.innerHTML = `
                <td>
                    <div class="fw-semibold">${escaparHtml(item.codigo || 'Sin código')}</div>
                    <div class="small text-muted">${escaparHtml(item.label || '—')}</div>
                </td>

                <td>
                    <input
                        type="number"
                        class="form-control form-control-sm text-end"
                        min="1"
                        value="${escaparHtml(item.q_inasistencia || '')}"
                        placeholder="Ej: 1"
                        data-inasistencia-masiva-q
                    >
                </td>

                <td>
                    <input
                        type="text"
                        class="form-control form-control-sm"
                        value="${escaparHtml(item.observacion || '')}"
                        placeholder="Observación opcional"
                        data-inasistencia-masiva-observacion
                    >
                </td>

                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        data-inasistencia-masiva-quitar
                        data-asignacion-id="${escaparHtml(item.suscripcion_asignacion_id)}"
                    >
                        ×
                    </button>
                </td>
            `;

            seleccionadasBody.appendChild(row);
        });

        actualizarContador();
    }

    function agregarSeleccion(row) {
        const item = obtenerDatosFila(row);

        if (!item.suscripcion_asignacion_id) {
            return;
        }

        if (normalizarCodigo(item.tipo_asignacion) !== 'RUTA') {
            return;
        }

        const id = String(item.suscripcion_asignacion_id);

        if (!seleccionadas.has(id)) {
            seleccionadas.set(id, item);
        }
    }

    function quitarSeleccion(id) {
        seleccionadas.delete(String(id));
        sincronizarCheckbox(id, false);
        ocultarError();
        renderizarSeleccionadas();
    }

    function filtrarRutas() {
        const texto = normalizarCodigo(buscador?.value || '');

        filasRutas().forEach(function (row) {
            const busqueda = normalizarCodigo(row.dataset.busqueda || '');
            const visible = texto === '' || busqueda.includes(texto);

            row.classList.toggle('d-none', !visible);
        });
    }

    function limpiarBusqueda() {
        if (buscador) {
            buscador.value = '';
        }

        filtrarRutas();
        ocultarError();
    }

    function limpiarSeleccion() {
        seleccionadas.clear();

        checkboxesRutas().forEach(function (checkbox) {
            checkbox.checked = false;
        });

        ocultarError();
        renderizarSeleccionadas();
    }

    function construirAjustesInasistencia() {
        guardarValoresEditados();

        const ajustes = [];

        seleccionadas.forEach(function (item) {
            const qInasistencia = parseInt(item.q_inasistencia || '', 10);

            ajustes.push({
                clave_control: [
                    'INASISTENCIA',
                    'ASIGNACION',
                    item.suscripcion_asignacion_id || '',
                ].join('|'),

                tipo_ajuste: 'INASISTENCIA',

                concepto_pago_variable_id: '',
                concepto_pago_variable_manual: '',
                concepto_pago_variable_label: '',

                suscripcion_asignacion_id: item.suscripcion_asignacion_id || '',
                suscripcion_proveedor_id: '',
                suscripcion_transportista_id: '',

                suscripcion_proveedor_facturacion_id: '',
                suscripcion_transportista_override_id: '',

                punto_1: item.punto_1 || '',
                origen_gasto: item.origen_gasto || 'Suscripciones',
                punto_2: item.punto_2 || '',
                codigo: item.codigo || '',
                servicio: item.servicio || '',
                grupo_prefactura: item.grupo_prefactura || '',

                costo: item.costo || '',
                q_calendario: '',
                q_inasistencia: Number.isNaN(qInasistencia) ? '' : String(qInasistencia),
                cantidad: '',
                total: '',

                tipo_documento: '',
                detalle_documento: '',
                detalle_impuesto: '',
                final: '',

                observacion: item.observacion || '',

                asignacion_label: item.label || '',
                proveedor_label: '',
                proveedor_facturacion_label: '',
                transportista_label: '',
                transportista_override_label: '',

                total_estimado: 0,
            });
        });

        return ajustes;
    }

    function validarAjustes(ajustes) {
        if (ajustes.length === 0) {
            return 'Selecciona al menos una ruta para registrar inasistencias.';
        }

        const incompleto = ajustes.find(function (ajuste) {
            const q = parseInt(ajuste.q_inasistencia || '', 10);

            return Number.isNaN(q) || q <= 0;
        });

        if (incompleto) {
            return 'Todas las rutas seleccionadas deben tener una cantidad de inasistencia mayor a 0.';
        }

        return '';
    }

    function cerrarModal() {
        if (window.jQuery && typeof window.jQuery(modal).modal === 'function') {
            window.jQuery(modal).modal('hide');
            return;
        }

        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';

        document.body.classList.remove('modal-open');

        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
            backdrop.remove();
        });
    }

    function confirmarInasistencias() {
        ocultarError();

        const ajustes = construirAjustesInasistencia();
        const error = validarAjustes(ajustes);

        if (error) {
            mostrarError(error);
            return;
        }

        if (typeof ajustesMensualesApi.agregarAjustesMasivos === 'function') {
            const resultado = ajustesMensualesApi.agregarAjustesMasivos(ajustes);






            if ((resultado?.agregados ?? ajustes.length) > 0) {
                limpiarSeleccion();
                cerrarModal();
                return;
            }

            if ((resultado?.duplicados ?? 0) > 0) {
                mostrarError(`Se omitieron ${resultado.duplicados} inasistencia(s) porque ya estaban agregadas en el resumen.`);
            }

            return;


        }

        document.dispatchEvent(new CustomEvent('suscripciones:ajustes-masivos', {
            detail: {
                tipo: 'INASISTENCIA',
                ajustes: ajustes,
            },
        }));

        limpiarSeleccion();
        cerrarModal();
    }

    function registrarEventos() {
        if (buscador) {
            buscador.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    filtrarRutas();
                }
            });

            buscador.addEventListener('input', filtrarRutas);
        }

        if (buscarBtn) {
            buscarBtn.addEventListener('click', filtrarRutas);
        }

        if (limpiarBusquedaBtn) {
            limpiarBusquedaBtn.addEventListener('click', limpiarBusqueda);
        }

        if (rutasBody) {
            rutasBody.addEventListener('change', function (event) {
                const checkbox = event.target.closest('[data-inasistencia-masiva-checkbox]');

                if (!checkbox) {
                    return;
                }

                guardarValoresEditados();

                const row = checkbox.closest('[data-inasistencia-masiva-ruta]');

                if (!row) {
                    return;
                }

                if (checkbox.checked) {
                    agregarSeleccion(row);
                } else {
                    seleccionadas.delete(String(checkbox.value));
                }

                ocultarError();
                renderizarSeleccionadas();
            });
        }

        if (seleccionadasBody) {
            seleccionadasBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-inasistencia-masiva-quitar]');

                if (!button) {
                    return;
                }

                guardarValoresEditados();
                quitarSeleccion(button.dataset.asignacionId);
            });

            seleccionadasBody.addEventListener('input', guardarValoresEditados);
        }

        if (limpiarBtn) {
            limpiarBtn.addEventListener('click', limpiarSeleccion);
        }

        if (confirmarBtn) {
            confirmarBtn.addEventListener('click', confirmarInasistencias);
        }
    }

    registrarEventos();
    filtrarRutas();
    renderizarSeleccionadas();
}