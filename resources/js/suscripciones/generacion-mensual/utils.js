/**
 * resources/js/suscripciones/generacion-mensual/utils.js
 *
 * Utilidades compartidas por los módulos de generación mensual.
 */

export function formatearCLP(valor) {
    return '$' + new Intl.NumberFormat('es-CL').format(valor || 0);
}

export function limpiarTexto(valor) {
    return String(valor || '').replace(/\s+/g, ' ').trim();
}

export function escaparHtml(valor) {
    return String(valor || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

export function selectedOption(select) {
    return select?.options[select.selectedIndex] || null;
}

export function optionLabel(select) {
    const option = selectedOption(select);

    if (!option) {
        return '—';
    }

    return limpiarTexto(option.dataset.label || option.text || '—');
}

export function labelPorValor(select, valor) {
    const option = Array.from(select?.options || []).find(function (item) {
        return String(item.value) === String(valor);
    });

    if (!option) {
        return '—';
    }

    return limpiarTexto(option.dataset.label || option.text || '—');
}

export function normalizarTipo(valor) {
    return limpiarTexto(valor).toUpperCase().replaceAll(' ', '_').replaceAll('-', '_');
}

export function normalizarCodigo(valor) {
    return limpiarTexto(valor).toUpperCase();
}

export function slugCodigo(valor) {
    const texto = limpiarTexto(valor)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .replace(/[^A-Z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');

    return texto || 'PAGO_VARIABLE';
}

export function agregarHidden(nombre, valor, container) {
    if (!container) {
        return;
    }

    const input = document.createElement('input');

    input.type = 'hidden';
    input.name = nombre;
    input.value = valor ?? '';

    container.appendChild(input);
}