(function () {
    function initProveedorHonorariosModal() {
        if (window.__proveedorHonorariosModalInicializado) return;
        window.__proveedorHonorariosModalInicializado = true;

        if (typeof window.jQuery === 'undefined') return;

        const $ = window.jQuery;

        const $modal = $('#modalCrearProveedorHonorarios');
        const $form = $('#formCrearProveedorHonorarios');

        if (!$modal.length || !$form.length) return;

        const config = window.honorariosProveedorConfig || {};
        const csrf = config.csrf || '';
        const routes = config.routes || {};

        const pendientesRaw = $modal.attr('data-pendientes') || '[]';

        let pendientes = [];

        try {
            pendientes = JSON.parse(pendientesRaw);
        } catch (e) {
            pendientes = [];
        }

        if (!Array.isArray(pendientes) || pendientes.length === 0) {
            return;
        }

        let indexActual = 0;
        let finalizando = false;

        // ============================================================
        // HELPERS BASE
        // ============================================================

        function mostrarModal() {
            $modal.modal('show');
        }

        function ocultarModal() {
            $modal.modal('hide');
        }

        function actualizarTitulo() {
            const total = pendientes.length;
            const actual = indexActual + 1;

            $('#modalCrearProveedorHonorariosLabel').text(
                `Crear proveedor de honorarios (${actual}/${total})`
            );
        }

        function limpiarErrores() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback.js-error').remove();
            $form.find('.invalid-feedback.d-block.js-error').remove();
        }

        function resetForm() {
            if ($form[0]) {
                $form[0].reset();
            }

            limpiarErrores();
            resetDynamicFields($form);
            resetFkOtherFields($form);
        }

        function setFieldValue(name, value) {
            const $field = $form.find(`[name="${name}"]`).first();

            if (!$field.length) return;

            $field.val(value || '');
        }

        function fillCurrentPending() {
            const item = pendientes[indexActual] || {};

            resetForm();

            setFieldValue('rut_cliente', item.rut_cliente || item.rut_proveedor || '');
            setFieldValue('razon_social', item.razon_social || '');

            resetDynamicFields($form);
            resetFkOtherFields($form);

            actualizarTitulo();
        }

        // ============================================================
        // CAMPOS DINÁMICOS DEL FORMULARIO COBRANZA_COMPRAS
        // ============================================================

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

        function resetDynamicFields($context) {
            $context.find('.js-provider-dynamic-select').each(function () {
                syncDynamicSelect($(this));
            });
        }

        function setDynamicFieldValue(name, value) {
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

        // ============================================================
        // CAMPOS FK CON OPCIÓN OTRO: BANCO / TIPO CUENTA
        // ============================================================

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

        function resetFkOtherFields($context) {
            $context.find('.js-fk-other-select').each(function () {
                syncFkOtherSelect($(this));
            });
        }

        function setFkFieldValue(name, value) {
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

            $form.find(`select[name="${name}"]`).each(function () {
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

        function fillProveedorSinRegistro($button) {
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

                const wasDynamic = setDynamicFieldValue(name, value);

                if (!wasDynamic) {
                    const wasFkOther = setFkFieldValue(name, value);

                    if (!wasFkOther) {
                        $field.val(value);
                    }
                }
            });
        }

        // ============================================================
        // AJAX
        // ============================================================

        function postJson(url, formData = null) {
            const options = {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            };

            if (formData) {
                options.body = formData;
            }

            return fetch(url, options).then(async function (response) {
                const contentType = response.headers.get('content-type') || '';

                if (!contentType.includes('application/json')) {
                    throw new Error('La respuesta del servidor no es JSON.');
                }

                const data = await response.json();

                if (!response.ok) {
                    const error = new Error(data.message || 'Error en la solicitud.');
                    error.response = data;
                    error.status = response.status;
                    throw error;
                }

                return data;
            });
        }

        function renderErrores(errors) {
            limpiarErrores();

            Object.entries(errors || {}).forEach(function ([field, messages]) {
                const $field = $form.find(`[name="${field}"]`).first();

                if (!$field.length) return;

                $field.addClass('is-invalid');

                const message = Array.isArray(messages) ? messages[0] : messages;

                $field.after(`
                    <div class="invalid-feedback js-error">
                        ${message}
                    </div>
                `);
            });
        }

        function guardarProveedorActual() {
            resetDynamicFields($form);
            resetFkOtherFields($form);
            limpiarErrores();

            const formData = new FormData($form[0]);

            return postJson(routes.storeProveedor, formData);
        }

        function reprocesarHonorariosPendientes() {
            return postJson(routes.reprocesarHonorarios);
        }

        function cancelarPendientes() {
            return postJson(routes.cancelarHonorarios);
        }

        // ============================================================
        // FLUJO GUIADO
        // ============================================================

        function pasarAlSiguiente() {
            indexActual++;

            if (indexActual < pendientes.length) {
                fillCurrentPending();
                return;
            }

            finalizando = true;

            reprocesarHonorariosPendientes()
                .then(function (data) {
                    alert(data.message || 'Honorarios importados correctamente.');
                    ocultarModal();
                    window.location.reload();
                })
                .catch(function (error) {
                    console.error('[B.H.] Error reprocesando honorarios:', error);
                    alert(error.message || 'No fue posible reprocesar los honorarios.');
                    finalizando = false;
                });
        }

        // ============================================================
        // EVENTOS
        // ============================================================

        $(document).on('change', '#formCrearProveedorHonorarios .js-provider-dynamic-select', function () {
            syncDynamicSelect($(this));
        });

        $(document).on('input', '#formCrearProveedorHonorarios .js-provider-dynamic-other', function () {
            syncDynamicOther($(this));
        });

        $(document).on('change', '#formCrearProveedorHonorarios .js-fk-other-select', function () {
            syncFkOtherSelect($(this));
        });

        $(document).on('click', '#formCrearProveedorHonorarios .js-fill-proveedor-sin-registro', function (e) {
            e.preventDefault();

            fillProveedorSinRegistro($(this));
        });

        $form.on('submit', function (e) {
            e.preventDefault();

            const $submit = $form.find('[type="submit"]').last();
            const textoOriginal = $submit.text();

            $submit.prop('disabled', true).text('Guardando...');

            guardarProveedorActual()
                .then(function (data) {
                    if (!data.success) {
                        alert(data.message || 'No fue posible guardar el proveedor.');
                        return;
                    }

                    agregarOpcionesFkDesdeRespuesta(data);

                    pasarAlSiguiente();
                })
                .catch(function (error) {
                    console.error('[B.H.] Error guardando proveedor:', error);

                    if (error.status === 422 && error.response?.errors) {
                        renderErrores(error.response.errors);
                        return;
                    }

                    alert(error.message || 'Ocurrió un error al guardar el proveedor.');
                })
                .finally(function () {
                    $submit.prop('disabled', false).text(textoOriginal);
                });
        });

        $(document).on('click', '.js-cancel-modal-proveedor-honorarios, #formCrearProveedorHonorarios .js-cancel-cobranza-modal', function (e) {
            e.preventDefault();

            if (finalizando) return;

            const confirmar = confirm(
                '¿Deseas cancelar la regularización de proveedores? La importación de honorarios quedará cancelada.'
            );

            if (!confirmar) return;

            cancelarPendientes()
                .then(function () {
                    ocultarModal();
                    window.location.reload();
                })
                .catch(function (error) {
                    console.error('[B.H.] Error cancelando pendientes:', error);
                    alert(error.message || 'No fue posible cancelar la importación pendiente.');
                });
        });

        $modal.on('hidden.bs.modal', function () {
            if (finalizando) return;
        });

        // ============================================================
        // INIT
        // ============================================================

        fillCurrentPending();

        setTimeout(function () {
            mostrarModal();
        }, 300);
    }

    window.addEventListener('load', initProveedorHonorariosModal);
})();