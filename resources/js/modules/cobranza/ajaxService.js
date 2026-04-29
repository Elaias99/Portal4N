export function createAjaxService({ $, csrf }) {

    function post(url, data = {}) {
        return $.ajax({
            url,
            method: 'POST',
            data: {
                _token: csrf,
                ...data
            }
        });
    }

    function submitForm($form) {
        const formData = new FormData($form[0]);

        return $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
    }

    return {
        post,
        submitForm
    };
}