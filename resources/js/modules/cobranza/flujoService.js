export function createFlujoService({
    state,
    modalService,
    formService,
    ajaxService,
    getActiveForm,
    showForm,
    updateTitle,
    fillCurrentPending
}) {

    function resetGuidedState() {
        state.guided = false;
        state.pendientes = [];
        state.index = 0;
        state.pendingActionAfterHide = null;
    }

    function openManual(tipo, rut, razon) {

        resetGuidedState();

        showForm(tipo);

        const $form = getActiveForm();

        formService.reset($form);

        formService.fill($form, {
            rut_cliente: rut || '',
            razon_social: razon || ''
        });

        updateTitle();
        modalService.show();
    }

    function startGuided(tipo, pendientes) {

        state.guided = true;
        state.tipo = tipo;
        state.pendientes = Array.isArray(pendientes) ? pendientes : [];
        state.index = 0;
        state.pendingActionAfterHide = null;

        if (!state.pendientes.length) return;

        showForm(tipo);
        fillCurrentPending();

        setTimeout(() => modalService.show(), 150);
    }

    function goToNextPending() {

        state.index++;

        if (state.index >= state.pendientes.length) {
            state.pendingActionAfterHide = 'finalize';
            modalService.hide();
            return;
        }

        state.pendingActionAfterHide = 'next';
        modalService.hide();
    }

    function finalizarFlujoGuiado() {

        const url = state.tipo === 'compra'
            ? window.cobranzaConfig.routes.reprocesarCompra
            : window.cobranzaConfig.routes.reprocesarCobranza;

        ajaxService.post(url)
            .done(function (resp) {

                if (window.pageLoader) {
                    window.pageLoader.forceHide();
                }

                if (resp.success) {
                    window.location.reload();
                    return;
                }

                alert(resp.message || 'No fue posible reprocesar los documentos pendientes.');
            })
            .fail(function () {
                if (window.pageLoader) {
                    window.pageLoader.forceHide();
                }

                alert('Ocurrió un error al reprocesar los documentos pendientes.');
            });
    }

    function cancelarFlujoGuiado() {

        const url = state.tipo === 'compra'
            ? window.cobranzaConfig.routes.cancelarCompra
            : window.cobranzaConfig.routes.cancelarCobranza;

        ajaxService.post(url)
            .done(function () {
                if (window.pageLoader) {
                    window.pageLoader.forceHide();
                }

                resetGuidedState();

                modalService.hide();

                setTimeout(function () {
                    window.location.reload();
                }, 250);
            })
            .fail(function () {
                if (window.pageLoader) {
                    window.pageLoader.forceHide();
                }

                alert('No fue posible cancelar el flujo guiado.');
            });
    }

    return {
        openManual,
        startGuided,
        goToNextPending,
        finalizarFlujoGuiado,
        cancelarFlujoGuiado
    };
}