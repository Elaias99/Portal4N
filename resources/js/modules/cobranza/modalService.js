export function createModalService($modal) {
    let bsModalInstance = null;

    function hasBootstrapModalClass() {
        return typeof window.bootstrap !== 'undefined'
            && typeof window.bootstrap.Modal === 'function';
    }

    function hasJQueryBootstrapModal($) {
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

    function show($) {
        const instance = getBootstrapModalInstance();

        if (instance && typeof instance.show === 'function') {
            bsModalInstance = instance;
            instance.show();
            return;
        }

        if (hasJQueryBootstrapModal($)) {
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

    function hide($) {
        if (bsModalInstance && typeof bsModalInstance.hide === 'function') {
            bsModalInstance.hide();
            return;
        }

        if (hasJQueryBootstrapModal($)) {
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

    return {
        show,
        hide
    };
}