function toggleFechaEstado(select, id) {
    const inputFecha = document.getElementById('fecha-input-' + id);
    const hiddenFecha = document.getElementById('fecha-hidden-' + id);

    if (['Abono', 'Pago', 'Pronto pago', 'Cobranza judicial'].includes(select.value)) {
        if (inputFecha) inputFecha.style.display = 'block';
    } else {
        if (inputFecha) {
            inputFecha.style.display = 'none';
            inputFecha.value = '';
        }
        if (hiddenFecha) hiddenFecha.value = '';
    }
}