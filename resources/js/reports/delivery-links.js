

    // Guarda el último resultado del batch
    let lastBatchResults = [];

    /* ================= TRADUCCIÓN DE ESTADOS ================= */
    const estadosES = {
        pending: 'Pendiente',
        in_transit: 'En tránsito',
        out_for_delivery: 'En reparto',
        delivered: 'Entregado',
        cancelled: 'Cancelado',
        returned: 'Devuelto'
    };

    /* ================= UTIL ================= */
    function getBatchRawInput() {
        return document.getElementById('trackingBatchInput').value || '';
    }





    /* ================= BUSCAR MASIVO ================= */
    function buscarTrackingMasivo() {
        const raw = getBatchRawInput();
        const result = document.getElementById('searchResult');
        const btn = document.getElementById('batchBtn');

        if (!raw.trim()) {
            alert('Pega al menos un tracking (uno por línea).');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '⏳ Procesando...';

        fetch('/reports/tracking/search-batch', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ trackings: raw })
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                result.innerHTML = `<div class="alert alert-danger">Error</div>`;
                return;
            }

            lastBatchResults = res.results || [];

            let html = `
            <div class="card">
                <div class="card-header">Resultados</div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Tracking</th>
                                <th>Estado</th>
                                <th>Fotos</th>
                                <th>Vista POD (selecciona imágenes)</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            lastBatchResults.forEach(item => {
                const rawState = item.delivery_state || 'unknown';
                const state = estadosES[rawState] || rawState;
                const badge = rawState === 'delivered' ? 'success' : 'warning';
                const photos = item.photos || [];

                let photosHtml = '—';

                if (photos.length) {
                    photosHtml = `
                        <div style="display:flex; gap:12px; flex-wrap:wrap;">
                            ${photos.map((url, idx) => `
                                <div style="text-align:center;">
                                    <input
                                        type="checkbox"
                                        class="photo-checkbox"
                                        data-tracking="${item.tracking}"
                                        data-state="${rawState}"
                                        data-url="${url}"
                                        style="margin-bottom:6px;"
                                    >
                                    <br>
                                    <a href="${url}" target="_blank">
                                        <img
                                            src="${url}"
                                            style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #ccc;"
                                        >

                                    </a>
                                    <div style="font-size:12px;color:#666;">
                                        Foto ${idx + 1}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }

                html += `
                    <tr>
                        <td>${item.tracking}</td>
                        <td><span class="badge bg-${badge}">${state}</span></td>
                        <td>${photos.length}</td>
                        <td>${photosHtml}</td>
                    </tr>
                `;
            });

            html += `</tbody></table></div></div>`;
            result.innerHTML = html;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = 'Buscar masivo';
        });
    }








    /* ================= EXPORTAR EXCEL MASIVO ================= */
    function exportarExcelMasivo() {
        const btn = document.getElementById('batchExportBtn');

        const checked = Array.from(document.querySelectorAll('.photo-checkbox:checked'));

        if (!checked.length) {
            alert('Selecciona al menos una imagen para exportar.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '⏳ Exportando...';

        const items = checked.map(cb => ({
            tracking: cb.dataset.tracking,
            state: cb.dataset.state,
            url: cb.dataset.url
        }));

        fetch('/reports/tracking/export-batch', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ items })
        })
        .then(res => res.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'tracking_batch_pod.xlsx';
            a.click();
            window.URL.revokeObjectURL(url);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = 'Exportar Excel masivo';
        });
    }

// Exponer funciones al scope global para uso en Blade (onclick)
window.buscarTrackingMasivo = buscarTrackingMasivo;
window.exportarExcelMasivo = exportarExcelMasivo;
