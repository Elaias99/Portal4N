@php
    $pendientes = session('sin_cobranza')
                ?? session('sin_cobranza_pendientes')
                ?? session('sin_compra_pendientes')
                ?? [];

    $tipoFlujo = session()->has('sin_compra_pendientes') ? 'compra' : 'cobranza';
@endphp

<div
    class="modal fade"
    id="modalCrearCobranza"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalCrearCobranzaLabel"
    aria-hidden="true"
    data-pendientes='@json($pendientes)'
    data-tipo-flujo="{{ $tipoFlujo }}"
>
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalCrearCobranzaLabel">Crear Nueva Cobranza</h5>
                <button type="button" class="close text-white js-close-modal-cobranza" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form
                    id="formCrearCobranzaVentas"
                    class="js-form-crear-cobranza"
                    method="POST"
                    action="{{ route('cobranzas.store') }}"
                    data-tipo="cobranza"
                    data-no-loader
                    style="display:none;"
                >
                    @include('cobranzas.form', [
                        'btnText' => 'Guardar',
                        'isModalFlow' => true,
                        'formIdPrefix' => 'venta_modal'
                    ])
                </form>

                <form
                    id="formCrearCobranzaCompras"
                    class="js-form-crear-cobranza"
                    method="POST"
                    action="{{ route('cobranzas-compras.store') }}"
                    data-tipo="compra"
                    data-no-loader
                    style="display:none;"
                >
                    @include('cobranzas_compras.form', [
                        'btnText' => 'Guardar',
                        'isModalFlow' => true,
                        'formIdPrefix' => 'compra_modal'
                    ])
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function initModalCrearCobranza() {
        if (window.__modalCrearCobranzaInicializado) {
            return;
        }
        window.__modalCrearCobranzaInicializado = true;

        if (typeof window.jQuery === 'undefined') {
            return;
        }

        const $ = window.jQuery;
        const $modal = $('#modalCrearCobranza');

        if (!$modal.length) {
            return;
        }

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

        const state = {
            tipo: 'cobranza',
            pendientes: [],
            index: 0,
            guided: false,
            pendingActionAfterHide: null,
        };

        let bsModalInstance = null;

        function hasBootstrapModalClass() {
            return typeof window.bootstrap !== 'undefined'
                && typeof window.bootstrap.Modal === 'function';
        }

        function hasJQueryBootstrapModal() {
            return typeof $.fn.modal === 'function';
        }

        function getBootstrapModalInstance() {
            if (!hasBootstrapModalClass()) {
                return null;
            }

            try {
                const ModalClass = window.bootstrap.Modal;
                const options = {
                    backdrop: 'static',
                    keyboard: false
                };

                if (typeof ModalClass.getOrCreateInstance === 'function') {
                    return ModalClass.getOrCreateInstance($modal[0], options);
                }

                if (typeof ModalClass.getInstance === 'function') {
                    return ModalClass.getInstance($modal[0]) || new ModalClass($modal[0], options);
                }

                return new ModalClass($modal[0], options);
            } catch (error) {
                return null;
            }
        }

        function showModal() {
            const instance = getBootstrapModalInstance();

            if (instance && typeof instance.show === 'function') {
                bsModalInstance = instance;
                instance.show();
                return;
            }

            if (hasJQueryBootstrapModal()) {
                $modal.modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                });
                return;
            }

            $modal
                .addClass('show')
                .css({
                    display: 'block',
                    paddingRight: '17px'
                })
                .attr('aria-hidden', 'false');

            $('body').addClass('modal-open');

            if (!$('.modal-backdrop').length) {
                $('<div class="modal-backdrop fade show"></div>').appendTo(document.body);
            }
        }

        function hideModal() {
            if (bsModalInstance && typeof bsModalInstance.hide === 'function') {
                bsModalInstance.hide();
                return;
            }

            if (hasJQueryBootstrapModal()) {
                $modal.modal('hide');
                return;
            }

            $modal
                .removeClass('show')
                .css({
                    display: 'none',
                    paddingRight: ''
                })
                .attr('aria-hidden', 'true');

            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        }

        function getActiveFormSelector() {
            return state.tipo === 'compra'
                ? FORM_SELECTORS.compra
                : FORM_SELECTORS.cobranza;
        }

        function getInactiveFormSelector() {
            return state.tipo === 'compra'
                ? FORM_SELECTORS.cobranza
                : FORM_SELECTORS.compra;
        }

        function getActiveForm() {
            return $(getActiveFormSelector());
        }

        function getInactiveForm() {
            return $(getInactiveFormSelector());
        }

        function setFormEnabled($form, enabled) {
            $form.find('input, select, textarea, button').prop('disabled', !enabled);
        }

        function clearAjaxErrors($form) {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.js-ajax-error').remove();
        }

        function resetForm($form) {
            if ($form.length && $form[0]) {
                $form[0].reset();
            }

            clearAjaxErrors($form);

            $form.find('input, select, textarea').prop('disabled', false);
            $form.find('button[type="submit"], input[type="submit"], button[type="button"]').prop('disabled', false);

            $form.find('input[type="hidden"]').each(function () {
                const name = $(this).attr('name');
                if (name !== '_token' && name !== '_method') {
                    $(this).val('');
                }
            });
        }

        function showForm(tipo) {
            state.tipo = tipo;

            const $active = getActiveForm();
            const $inactive = getInactiveForm();

            $inactive.hide();
            setFormEnabled($inactive, false);

            $active.show();
            setFormEnabled($active, true);
        }

        function updateTitle() {
            let titulo = state.tipo === 'compra'
                ? 'Crear Nuevo Proveedor'
                : 'Crear Nuevo Cliente';

            if (state.guided && state.pendientes.length > 0) {
                titulo += ' (' + (state.index + 1) + '/' + state.pendientes.length + ')';
            }

            $('#modalCrearCobranzaLabel').text(titulo);
        }

        function fillCurrentPending() {
            const item = state.pendientes[state.index] || {};
            const $form = getActiveForm();

            resetForm($form);

            $form.find('[name="rut_cliente"]').val(item.rut_cliente || item.rut_proveedor || '');
            $form.find('[name="razon_social"]').val(item.razon_social || '');

            updateTitle();
        }

        function openManual(tipo, rut, razon) {
            state.guided = false;
            state.pendientes = [];
            state.index = 0;
            state.pendingActionAfterHide = null;

            showForm(tipo);

            const $form = getActiveForm();
            resetForm($form);

            $form.find('[name="rut_cliente"]').val(rut || '');
            $form.find('[name="razon_social"]').val(razon || '');

            updateTitle();
            showModal();
        }

        function startGuided(tipo, pendientes) {
            state.guided = true;
            state.tipo = tipo;
            state.pendientes = Array.isArray(pendientes) ? pendientes : [];
            state.index = 0;
            state.pendingActionAfterHide = null;

            if (!state.pendientes.length) {
                return;
            }

            showForm(tipo);
            fillCurrentPending();

            setTimeout(function () {
                showModal();
            }, 150);
        }

        function renderValidationErrors($form, errors) {
            clearAjaxErrors($form);

            Object.entries(errors).forEach(function ([field, messages]) {
                const $field = $form.find('[name="' + field + '"]').first();

                if (!$field.length) {
                    return;
                }

                $field.addClass('is-invalid');

                const html = '<div class="invalid-feedback js-ajax-error">' + messages[0] + '</div>';

                if ($field.next('.invalid-feedback').length) {
                    $field.next('.invalid-feedback').replaceWith(html);
                } else {
                    $field.after(html);
                }
            });
        }

        function finalizarFlujoGuiado() {
            const url = state.tipo === 'compra'
                ? "{{ route('cobranzas-compras.reprocesar-pendientes-compras') }}"
                : "{{ route('cobranzas.reprocesar-pendientes') }}";

            $.post(url, {
                _token: '{{ csrf_token() }}'
            })
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

        function goToNextPending() {
            state.index++;

            if (state.index >= state.pendientes.length) {
                state.pendingActionAfterHide = 'finalize';
                hideModal();
                return;
            }

            state.pendingActionAfterHide = 'next';
            hideModal();
        }

        function cancelarFlujoGuiado() {
            const url = state.tipo === 'compra'
                ? "{{ route('cobranzas-compras.cancelar-pendientes-compras') }}"
                : "{{ route('cobranzas.cancelar-pendientes') }}";

            $.post(url, {
                _token: '{{ csrf_token() }}'
            })
            .done(function () {
                if (window.pageLoader) {
                    window.pageLoader.forceHide();
                }

                state.guided = false;
                state.pendientes = [];
                state.index = 0;
                state.pendingActionAfterHide = null;

                hideModal();

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

        $(document).on('click', '.crear-cobranza-link, .crear-compra-link', function (e) {
            e.preventDefault();

            const rut = $(this).data('rut') || '';
            const razon = $(this).data('razon') || '';
            const tipo = $(this).hasClass('crear-compra-link') ? 'compra' : 'cobranza';

            openManual(tipo, rut, razon);
        });

        $(document).on('submit', '.js-form-crear-cobranza', function (e) {
            e.preventDefault();

            const $form = $(this);
            const formData = new FormData(this);

            clearAjaxErrors($form);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (data) {
                    if (window.pageLoader) {
                        window.pageLoader.forceHide();
                    }

                    if (!data.success) {
                        alert('No fue posible guardar el registro.');
                        return;
                    }

                    if (state.guided) {
                        goToNextPending();
                        return;
                    }

                    hideModal();
                    window.location.reload();
                },
                error: function (xhr) {
                    if (window.pageLoader) {
                        window.pageLoader.forceHide();
                    }

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        renderValidationErrors($form, xhr.responseJSON.errors);
                        return;
                    }

                    alert('Ocurrió un error al guardar.');
                }
            });
        });

        $(document).on('click', '.js-cancel-cobranza-modal, .js-close-modal-cobranza', function (e) {
            e.preventDefault();

            if (state.guided) {
                cancelarFlujoGuiado();
                return;
            }

            hideModal();
        });

        $modal.on('hidden.bs.modal', function () {
            resetForm($(FORM_SELECTORS.cobranza));
            resetForm($(FORM_SELECTORS.compra));

            if (state.pendingActionAfterHide === 'next') {
                state.pendingActionAfterHide = null;

                fillCurrentPending();

                setTimeout(function () {
                    showModal();
                }, 150);

                return;
            }

            if (state.pendingActionAfterHide === 'finalize') {
                state.pendingActionAfterHide = null;
                finalizarFlujoGuiado();
            }
        });

        if (pendientesSesion && pendientesSesion.length > 0) {
            startGuided(tipoFlujoSesion, pendientesSesion);
        }
    }

    window.addEventListener('load', function () {
        initModalCrearCobranza();
    });
})();
</script>