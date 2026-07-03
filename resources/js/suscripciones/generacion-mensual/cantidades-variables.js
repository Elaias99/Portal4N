/**
 * resources/js/suscripciones/generacion-mensual/cantidades-variables.js
 *
 * Maneja únicamente el cálculo visual de cantidades variables del mes.
 */

import {
    formatearCLP,
    selectedOption,
} from './utils';

export function actualizarTotalVariable(dom) {
    const { asignacionSelect, cantidadInput, totalInput, advertencia } = dom.cantidadVariable;

    if (!asignacionSelect || !cantidadInput || !totalInput) {
        return;
    }

    const option = selectedOption(asignacionSelect);
    const costo = parseInt(option?.dataset?.costo || 0, 10);
    const cantidad = parseInt(cantidadInput.value || 0, 10);
    const total = costo * cantidad;

    totalInput.value = formatearCLP(total);

    if (!advertencia) {
        return;
    }

    const esVariableOperativa = option?.dataset?.tipoAsignacion === 'VARIABLE';

    advertencia.classList.remove('text-danger', 'text-warning', 'text-muted');

    if (option?.value && !esVariableOperativa) {
        advertencia.classList.add('text-warning');
        advertencia.textContent = 'Revisa esta selección: sólo deben cargarse aquí asignaciones configuradas como cantidad variable.';
    } else {
        advertencia.classList.add('text-muted');
        advertencia.textContent = 'Selecciona una asignación variable y escribe la cantidad mensual informada.';
    }
}

export function registrarEventosCantidadesVariables(dom) {
    const cantidad = dom.cantidadVariable;

    if (cantidad.asignacionSelect) {
        cantidad.asignacionSelect.addEventListener('change', function () {
            actualizarTotalVariable(dom);
        });
    }

    if (cantidad.cantidadInput) {
        cantidad.cantidadInput.addEventListener('input', function () {
            actualizarTotalVariable(dom);
        });
    }
}

export function inicializarCantidadesVariables(dom) {
    registrarEventosCantidadesVariables(dom);
    actualizarTotalVariable(dom);
}