export function createFormService($) {

    function setEnabled($form, enabled) {
        $form.find('input, select, textarea, button')
            .prop('disabled', !enabled);
    }

    function clearErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.js-ajax-error').remove();
    }

    function reset($form) {
        if ($form.length && $form[0]) {
            $form[0].reset();
        }

        clearErrors($form);

        $form.find('input, select, textarea').prop('disabled', false);
        $form.find('button').prop('disabled', false);

        $form.find('input[type="hidden"]').each(function () {
            const name = $(this).attr('name');

            if (name !== '_token' && name !== '_method') {
                $(this).val('');
            }
        });
    }

    function fill($form, data = {}) {
        Object.entries(data).forEach(([name, value]) => {
            $form.find(`[name="${name}"]`).val(value ?? '');
        });
    }

    function renderErrors($form, errors = {}) {
        clearErrors($form);

        Object.entries(errors).forEach(([field, messages]) => {
            const $field = $form.find(`[name="${field}"]`).first();

            if (!$field.length) return;

            $field.addClass('is-invalid');

            const html = `<div class="invalid-feedback js-ajax-error">${messages[0]}</div>`;

            if ($field.next('.invalid-feedback').length) {
                $field.next('.invalid-feedback').replaceWith(html);
            } else {
                $field.after(html);
            }
        });
    }

    return {
        setEnabled,
        clearErrors,
        reset,
        fill,
        renderErrors
    };
}