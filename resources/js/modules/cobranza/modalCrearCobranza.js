import { createModalService } from './modalService';
import { createFormService } from './formService';
import { createCobranzaState } from './state';
import { createFlujoService } from './flujoService';
import { createAjaxService } from './ajaxService';

(function () {

    function initModalCrearCobranza() {

        if (window.__modalCrearCobranzaInicializado) return;
        window.__modalCrearCobranzaInicializado = true;

        if (typeof window.jQuery === 'undefined') return;

        const $ = window.jQuery;
        const $modal = $('#modalCrearCobranza');

        if (!$modal.length) return;

        const modalService = createModalService($modal);
        const formService = createFormService($);
        const state = createCobranzaState();

        const ajaxService = createAjaxService({
            $,
            csrf: window.cobranzaConfig.csrf
        });

        /**
         * Wrapper para que flujoService pueda usar modal.show()
         * y modal.hide() sin tener que conocer jQuery.
         */
        const modal = {
            show: () => modalService.show($),
            hide: () => modalService.hide($),
        };

        const pendientesRaw = $modal.attr('data-pendientes') || '[]';
        let pendientesSesion = [];

        try {
            pendientesSesion = JSON.parse(pendientesRaw);
        } catch (e) {
            pendientesSesion = [];
        }

        const tipoFlujoSesion = $modal.attr('data-tipo-flujo') || 'cobranza';

        const FORM_SELECTORS = {
            cobranza: '#formCrearCobranzaVentas',
            compra: '#formCrearCobranzaCompras',
        };

        // -----------------------
        // Helpers UI
        // -----------------------

        function getActiveForm() {
            return state.tipo === 'compra'
                ? $(FORM_SELECTORS.compra)
                : $(FORM_SELECTORS.cobranza);
        }

        function getInactiveForm() {
            return state.tipo === 'compra'
                ? $(FORM_SELECTORS.cobranza)
                : $(FORM_SELECTORS.compra);
        }

        function showForm(tipo) {
            state.tipo = tipo;

            const $active = getActiveForm();
            const $inactive = getInactiveForm();

            $inactive.hide();
            formService.setEnabled($inactive, false);

            $active.show();
            formService.setEnabled($active, true);
        }

        function updateTitle() {
            let titulo = state.tipo === 'compra'
                ? 'Crear Nuevo Proveedor'
                : 'Crear Nuevo Cliente';

            if (state.guided && state.pendientes.length > 0) {
                titulo += ` (${state.index + 1}/${state.pendientes.length})`;
            }

            $('#modalCrearCobranzaLabel').text(titulo);
        }

        function fillCurrentPending() {
            const item = state.pendientes[state.index] || {};
            const $form = getActiveForm();

            formService.reset($form);

            formService.fill($form, {
                rut_cliente: item.rut_cliente || item.rut_proveedor || '',
                razon_social: item.razon_social || ''
            });

            updateTitle();
        }

        function fillProveedorSinRegistro($button) {
            const $form = $(FORM_SELECTORS.compra);

            const defaults = {
                servicio: 'Sin registro',
                creditos: 0,
                tipo: 'Sin registro',
                facturacion: 'Sin registro',
                forma_pago: 'Sin registro',
                zona: 'Sin registro',
                importancia: 'Sin registro',
                responsable: 'Sin registro',
                nombre_cuenta: 'Sin registro',
                rut_cuenta: 'Sin registro',
                numero_cuenta: 'Sin registro',
                banco_id: $button.data('banco-sin-registro-id') || '',
                tipo_cuenta_id: $button.data('tipo-cuenta-sin-registro-id') || ''
            };

            Object.entries(defaults).forEach(function ([name, value]) {
                const $field = $form.find(`[name="${name}"]`).first();

                if (!$field.length) return;

                const currentValue = String($field.val() ?? '').trim();

                if (currentValue === '') {
                    $field.val(value);
                }
            });
        }

        // -----------------------
        // Flujo Service
        // -----------------------

        const flujo = createFlujoService({
            state,
            modalService: modal,
            formService,
            ajaxService,
            getActiveForm,
            showForm,
            updateTitle,
            fillCurrentPending
        });

        // -----------------------
        // Eventos
        // -----------------------

        $(document).on('click', '.crear-cobranza-link, .crear-compra-link', function (e) {

            e.preventDefault();

            const rut = $(this).data('rut') || '';
            const razon = $(this).data('razon') || '';
            const tipo = $(this).hasClass('crear-compra-link') ? 'compra' : 'cobranza';

            flujo.openManual(tipo, rut, razon);
        });

        $(document).on('click', '.js-fill-proveedor-sin-registro', function (e) {

            e.preventDefault();

            fillProveedorSinRegistro($(this));
        });

        $(document).on('submit', '.js-form-crear-cobranza', function (e) {

            e.preventDefault();

            const $form = $(this);

            formService.clearErrors($form);

            ajaxService.submitForm($form)
                .done(function (data) {

                    if (!data.success) {
                        alert('No fue posible guardar el registro.');
                        return;
                    }

                    if (state.guided) {
                        flujo.goToNextPending();
                        return;
                    }

                    modal.hide();
                    window.location.reload();
                })
                .fail(function (xhr) {

                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        formService.renderErrors($form, xhr.responseJSON.errors);
                        return;
                    }

                    alert('Ocurrió un error al guardar.');
                });
        });

        $(document).on('click', '.js-cancel-cobranza-modal, .js-close-modal-cobranza', function (e) {

            e.preventDefault();

            if (state.guided) {
                flujo.cancelarFlujoGuiado();
                return;
            }

            modal.hide();
        });

        $modal.on('hidden.bs.modal', function () {

            formService.reset($(FORM_SELECTORS.cobranza));
            formService.reset($(FORM_SELECTORS.compra));

            if (state.pendingActionAfterHide === 'next') {

                state.pendingActionAfterHide = null;

                fillCurrentPending();

                setTimeout(() => modal.show(), 150);
                return;
            }

            if (state.pendingActionAfterHide === 'finalize') {

                state.pendingActionAfterHide = null;
                flujo.finalizarFlujoGuiado();
            }
        });

        // -----------------------
        // Init
        // -----------------------

        if (pendientesSesion.length > 0) {
            flujo.startGuided(tipoFlujoSesion, pendientesSesion);
        }
    }

    window.addEventListener('load', initModalCrearCobranza);

})();