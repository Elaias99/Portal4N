/**
 * Inventario de bodega
 *
 * Comportamientos exclusivos de:
 * resources/views/almacenamiento/index.blade.php
 */

document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('[data-almacenamiento-index]');

    // Evita ejecutar este código fuera del listado de almacenamiento.
    if (!page) {
        return;
    }

    initializeProductSearch(page);
    initializeDeleteConfirmation(page);
});

/**
 * Normaliza un texto para realizar búsquedas más flexibles.
 *
 * Ejemplo:
 * "PAPÉL Carta" se transforma en "papel carta".
 *
 * @param {string} value
 * @returns {string}
 */
function normalizeText(value) {
    return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

/**
 * Inicializa el buscador instantáneo del listado.
 *
 * @param {HTMLElement} page
 */
function initializeProductSearch(page) {
    const searchInput = page.querySelector('#buscarProducto');
    const clearButton = page.querySelector('#limpiarBusqueda');
    const productRows = Array.from(
        page.querySelectorAll('[data-product-row]')
    );
    const noResults = page.querySelector('#sinResultados');
    const tableContainer = page.querySelector('.table-responsive');

    if (
        !searchInput ||
        !clearButton ||
        productRows.length === 0 ||
        !noResults
    ) {
        return;
    }

    /**
     * Filtra las filas según el contenido escrito.
     */
    const filterProducts = () => {
        const searchTerm = normalizeText(searchInput.value);
        let visibleProducts = 0;

        productRows.forEach((row) => {
            const searchableContent = normalizeText(
                row.dataset.search
            );

            const matches = searchableContent.includes(searchTerm);

            row.classList.toggle('d-none', !matches);

            if (matches) {
                visibleProducts += 1;
            }
        });

        const hasResults = visibleProducts > 0;

        noResults.classList.toggle('d-none', hasResults);

        if (tableContainer) {
            tableContainer.classList.toggle('d-none', !hasResults);
        }
    };

    /**
     * Restablece el listado completo.
     */
    const clearSearch = () => {
        searchInput.value = '';
        filterProducts();
        searchInput.focus();
    };

    searchInput.addEventListener('input', filterProducts);
    clearButton.addEventListener('click', clearSearch);

    // Permite limpiar rápidamente la búsqueda presionando Escape.
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && searchInput.value !== '') {
            clearSearch();
        }
    });
}

/**
 * Solicita confirmación antes de eliminar un registro.
 *
 * @param {HTMLElement} page
 */
function initializeDeleteConfirmation(page) {
    const deleteForms = page.querySelectorAll(
        '.almacenamiento-delete-form'
    );

    deleteForms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            const productName =
                form.dataset.productName || 'este producto';

            const confirmed = window.confirm(
                `¿Seguro que deseas eliminar "${productName}"?\n\n` +
                'Esta acción eliminará el registro del inventario.'
            );

            if (!confirmed) {
                event.preventDefault();
            }
        });
    });
}