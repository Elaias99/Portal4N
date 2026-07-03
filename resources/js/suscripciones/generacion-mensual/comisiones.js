/**
 * resources/js/suscripciones/generacion-mensual/comisiones.js
 *
 * Maneja la sección de comisiones / pagos adicionales del mes.
 * Mantiene el nombre técnico "comisiones" porque así lo espera el backend.
 */

import {
    agregarHidden,
    escaparHtml,
    formatearCLP,
    labelPorValor,
    limpiarTexto,
    optionLabel,
} from './utils';

export function inicializarComisiones(dom, comisionesIniciales = []) {
    let comisiones = [];

    function actualizarTotalComisionActual() {
        const { costoInput, totalInput } = dom.comision;

        if (!costoInput || !totalInput) {
            return;
        }

        const costo = parseInt(costoInput.value || 0, 10);

        totalInput.value = formatearCLP(costo);
    }

    function limpiarFormularioComision() {
        const c = dom.comision;

        if (c.proveedorSelect) c.proveedorSelect.value = '';
        if (c.transportistaSelect) c.transportistaSelect.value = '';
        if (c.punto1Input) c.punto1Input.value = '';
        if (c.origenGastoInput) c.origenGastoInput.value = 'Suscripciones';
        if (c.punto2Input) c.punto2Input.value = '';
        if (c.servicioInput) c.servicioInput.value = 'Reparto fin de semana';
        if (c.costoInput) c.costoInput.value = '';
        if (c.observacionInput) c.observacionInput.value = '';

        actualizarTotalComisionActual();
    }

    function renderizarComisiones() {
        const c = dom.comision;

        if (!c.hiddenContainer || !c.resumenBody) {
            return;
        }

        c.hiddenContainer.innerHTML = '';
        c.resumenBody.innerHTML = '';

        if (comisiones.length === 0) {
            c.resumenBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-muted text-center">
                        No hay comisiones agregadas para este periodo.
                    </td>
                </tr>
            `;

            if (c.cantidadTexto) c.cantidadTexto.textContent = '0';
            if (c.totalTexto) c.totalTexto.textContent = formatearCLP(0);

            return;
        }

        let total = 0;

        comisiones.forEach(function (comision, index) {
            total += parseInt(comision.costo || 0, 10);

            agregarHidden(`comisiones[${index}][suscripcion_proveedor_id]`, comision.suscripcion_proveedor_id, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][suscripcion_transportista_id]`, comision.suscripcion_transportista_id, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][punto_1]`, comision.punto_1, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][origen_gasto]`, comision.origen_gasto, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][punto_2]`, comision.punto_2, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][servicio]`, comision.servicio, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][costo]`, comision.costo, c.hiddenContainer);
            agregarHidden(`comisiones[${index}][observacion]`, comision.observacion, c.hiddenContainer);

            const row = document.createElement('tr');

            row.innerHTML = `
                <td>${escaparHtml(comision.proveedor_label)}</td>
                <td>${escaparHtml(comision.transportista_label)}</td>
                <td>${escaparHtml(comision.punto_1 || '—')}</td>
                <td>${escaparHtml(comision.servicio || '—')}</td>
                <td class="text-end">${formatearCLP(comision.costo)}</td>
                <td>${escaparHtml(comision.observacion || '—')}</td>
                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        data-index="${index}"
                        data-action="eliminar-comision"
                    >
                        Eliminar
                    </button>
                </td>
            `;

            c.resumenBody.appendChild(row);
        });

        if (c.cantidadTexto) c.cantidadTexto.textContent = String(comisiones.length);
        if (c.totalTexto) c.totalTexto.textContent = formatearCLP(total);
    }

    function agregarComisionDesdeFormulario() {
        const c = dom.comision;

        const proveedorId = c.proveedorSelect?.value || '';
        const transportistaId = c.transportistaSelect?.value || '';
        const costo = parseInt(c.costoInput?.value || '', 10);

        if (!proveedorId) {
            alert('Selecciona un proveedor para la comisión.');
            return;
        }

        if (!transportistaId) {
            alert('Selecciona un transportista para la comisión.');
            return;
        }

        if (Number.isNaN(costo) || costo < 0) {
            alert('Ingresa un costo válido para la comisión.');
            return;
        }

        const existeMismaComision = comisiones.some(function (comision) {
            return String(comision.suscripcion_proveedor_id) === String(proveedorId)
                && String(comision.suscripcion_transportista_id) === String(transportistaId);
        });

        if (existeMismaComision) {
            alert('Ya agregaste una comisión para este proveedor y transportista.');
            return;
        }

        comisiones.push({
            suscripcion_proveedor_id: proveedorId,
            suscripcion_transportista_id: transportistaId,
            proveedor_label: optionLabel(c.proveedorSelect),
            transportista_label: optionLabel(c.transportistaSelect),
            punto_1: limpiarTexto(c.punto1Input?.value),
            origen_gasto: limpiarTexto(c.origenGastoInput?.value) || 'Suscripciones',
            punto_2: limpiarTexto(c.punto2Input?.value),
            servicio: limpiarTexto(c.servicioInput?.value) || 'Comisión mensual',
            costo: costo,
            observacion: limpiarTexto(c.observacionInput?.value),
        });

        limpiarFormularioComision();
        renderizarComisiones();
    }

    function restaurarComisionesIniciales() {
        const c = dom.comision;

        comisionesIniciales.forEach(function (comision) {
            comisiones.push({
                suscripcion_proveedor_id: comision.suscripcion_proveedor_id,
                suscripcion_transportista_id: comision.suscripcion_transportista_id,
                proveedor_label: labelPorValor(c.proveedorSelect, comision.suscripcion_proveedor_id),
                transportista_label: labelPorValor(c.transportistaSelect, comision.suscripcion_transportista_id),
                punto_1: limpiarTexto(comision.punto_1 || ''),
                origen_gasto: limpiarTexto(comision.origen_gasto || 'Suscripciones'),
                punto_2: limpiarTexto(comision.punto_2 || ''),
                servicio: limpiarTexto(comision.servicio || 'Comisión mensual'),
                costo: parseInt(comision.costo || 0, 10),
                observacion: limpiarTexto(comision.observacion || ''),
            });
        });
    }

    function registrarEventosComisiones() {
        const c = dom.comision;

        if (c.costoInput) {
            c.costoInput.addEventListener('input', actualizarTotalComisionActual);
        }

        if (c.agregarBtn) {
            c.agregarBtn.addEventListener('click', agregarComisionDesdeFormulario);
        }

        if (c.resumenBody) {
            c.resumenBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-action="eliminar-comision"]');

                if (!button) {
                    return;
                }

                const index = parseInt(button.dataset.index, 10);

                comisiones.splice(index, 1);
                renderizarComisiones();
            });
        }
    }

    registrarEventosComisiones();
    restaurarComisionesIniciales();
    actualizarTotalComisionActual();
    renderizarComisiones();
}