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

        function syncDynamicSelect($select) {
            const selectedValue = $select.val();
            const hiddenSelector = $select.data('hidden-input');
            const otherWrapperSelector = $select.data('other-wrapper');
            const otherInputSelector = $select.data('other-input');

            const $hidden = $(hiddenSelector);
            const $otherWrapper = $(otherWrapperSelector);
            const $otherInput = $(otherInputSelector);

            if (selectedValue === '__otro__') {
                $otherWrapper.show();

                const otherValue = String($otherInput.val() ?? '').trim();
                $hidden.val(otherValue);

                return;
            }

            $otherWrapper.hide();
            $otherInput.val('');
            $hidden.val(selectedValue || '');
        }

        function syncDynamicOther($input) {
            const hiddenSelector = $input.data('hidden-input');
            const $hidden = $(hiddenSelector);

            $hidden.val($input.val() || '');
        }



        function syncFkOtherSelect($select) {
            const selectedValue = $select.val();
            const otherWrapperSelector = $select.data('other-wrapper');
            const otherInputSelector = $select.data('other-input');

            const $otherWrapper = $(otherWrapperSelector);
            const $otherInput = $(otherInputSelector);

            if (selectedValue === '__otro__') {
                $otherWrapper.show();
                $otherInput.prop('required', true);
                return;
            }

            $otherWrapper.hide();
            $otherInput.prop('required', false);
            $otherInput.val('');
        }

        function resetFkOtherFields($form) {
            $form.find('.js-fk-other-select').each(function () {
                syncFkOtherSelect($(this));
            });
        }

        function setFkFieldValue($form, name, value) {
            const $field = $form.find(`[name="${name}"]`).first();

            if (!$field.length) {
                return false;
            }

            $field.val(value || '');
            syncFkOtherSelect($field);

            return true;
        }



        function agregarOpcionFkATodosLosSelects(name, registro) {
            if (!registro || !registro.id || !registro.nombre) {
                return;
            }

            const value = String(registro.id);
            const label = String(registro.nombre);

            $(`select[name="${name}"]`).each(function () {
                const $select = $(this);

                const yaExiste = $select.find(`option[value="${value}"]`).length > 0;

                if (yaExiste) {
                    return;
                }

                const $option = $('<option>', {
                    value: value,
                    text: label
                });

                const $opcionOtro = $select.find('option[value="__otro__"]').first();

                if ($opcionOtro.length) {
                    $option.insertBefore($opcionOtro);
                } else {
                    $select.append($option);
                }
            });
        }

        function agregarOpcionesFkDesdeRespuesta(data) {
            agregarOpcionFkATodosLosSelects('banco_id', data.banco);
            agregarOpcionFkATodosLosSelects('tipo_cuenta_id', data.tipo_cuenta);
        }



        function resetDynamicFields($form) {
            $form.find('.js-provider-dynamic-select').each(function () {
                syncDynamicSelect($(this));
            });
        }

        function setDynamicFieldValue($form, name, value) {
            const $field = $form.find(`[name="${name}"]`).first();

            if (!$field.length) {
                return false;
            }

            const fieldId = $field.attr('id');

            if (!fieldId) {
                return false;
            }

            const $select = $form
                .find(`.js-provider-dynamic-select[data-hidden-input="#${fieldId}"]`)
                .first();

            if (!$select.length) {
                return false;
            }

            const normalizedValue = String(value ?? '').trim().toLowerCase();
            let foundOption = false;

            $select.find('option').each(function () {
                const $option = $(this);

                if (String($option.val() ?? '').trim().toLowerCase() === normalizedValue) {
                    $select.val($option.val());
                    foundOption = true;
                    return false;
                }

                return true;
            });

            if (foundOption) {
                syncDynamicSelect($select);
                return true;
            }

            $select.val('__otro__');
            syncDynamicSelect($select);

            const $otherInput = $($select.data('other-input'));

            $otherInput.val(value);
            syncDynamicOther($otherInput);

            return true;
        }

        function fillCurrentPending() {
            const item = state.pendientes[state.index] || {};
            const $form = getActiveForm();

            formService.reset($form);

            formService.fill($form, {
                rut_cliente: item.rut_cliente || item.rut_proveedor || '',
                razon_social: item.razon_social || ''
            });

            resetDynamicFields($form);
            resetFkOtherFields($form);

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

                if (currentValue !== '') return;




                const wasDynamic = setDynamicFieldValue($form, name, value);

                if (!wasDynamic) {
                    const wasFkOther = setFkFieldValue($form, name, value);

                    if (!wasFkOther) {
                        $field.val(value);
                    }
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
            resetDynamicFields(getActiveForm());
            resetFkOtherFields(getActiveForm());
        });



        $(document).on('change', '.js-provider-dynamic-select', function () {
            syncDynamicSelect($(this));
        });


        $(document).on('change', '.js-fk-other-select', function () {
            syncFkOtherSelect($(this));
        });


        $(document).on('input', '.js-provider-dynamic-other', function () {
            syncDynamicOther($(this));
        });

        $(document).on('click', '.js-fill-proveedor-sin-registro', function (e) {

            e.preventDefault();

            fillProveedorSinRegistro($(this));
        });

        $(document).on('submit', '.js-form-crear-cobranza', function (e) {

            e.preventDefault();

            const $form = $(this);

            resetDynamicFields($form);
            resetFkOtherFields($form);
            formService.clearErrors($form);

            ajaxService.submitForm($form)



                .done(function (data) {

                    if (!data.success) {
                        alert('No fue posible guardar el registro.');
                        return;
                    }

                    agregarOpcionesFkDesdeRespuesta(data);

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

            resetDynamicFields($(FORM_SELECTORS.compra));
            resetFkOtherFields($(FORM_SELECTORS.compra));

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

        resetDynamicFields($(FORM_SELECTORS.compra));
        resetFkOtherFields($(FORM_SELECTORS.compra));

        if (pendientesSesion.length > 0) {
            flujo.startGuided(tipoFlujoSesion, pendientesSesion);
        }
    }

    window.addEventListener('load', initModalCrearCobranza);

})();