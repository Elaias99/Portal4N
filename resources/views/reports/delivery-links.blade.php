@extends('layouts.app')

@section('content')
<div class="container-fluid">

    {{-- TÍTULO --}}
    <div class="mb-4">
        <h1 class="h3">Envíos con prueba de entrega</h1>
        <p class="text-muted mb-0">
            Consulta masiva de tracking y exportación de Pruebas de Entrega (POD).
        </p>
    </div>

    {{-- BUSCADOR MASIVO --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>Búsqueda masiva de tracking</strong>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">
                        Números de tracking (uno por línea)
                    </label>

                    <textarea
                        id="trackingBatchInput"
                        class="form-control"
                        rows="10"
                        placeholder="Ej:
4N202601123267
4N202601148262
4N202601141651"
                    ></textarea>

                    <small class="text-muted">
                        Puedes pegar múltiples trackings desde Excel, correo o texto plano.
                    </small>
                </div>

                <div class="col-md-3 d-flex flex-column justify-content-end gap-2">
                    <button
                        id="batchBtn"
                        class="btn btn-warning"
                        onclick="buscarTrackingMasivo()"
                    >
                        Buscar masivo
                    </button>

                    <button
                        id="batchExportBtn"
                        class="btn btn-success"
                        onclick="exportarExcelMasivo()"
                    >
                        Exportar Excel masivo
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- RESULTADO --}}
    <div id="searchResult"></div>

</div>

<script>
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

    result.innerHTML = `
        <div class="card">
            <div class="card-body">
                ⏳ Procesando tracking(s) en modo masivo...
            </div>
        </div>
    `;

    fetch('/reports/tracking/search-batch', {
        method: 'POST',
        credentials: 'same-origin', // 👈 CLAVE
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ trackings: raw })
    })

    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            result.innerHTML = `
                <div class="alert alert-danger">
                    ❌ Error en la búsqueda masiva
                </div>
            `;
            return;
        }

        const rows = res.results || [];

        let html = `
            <div class="card">
                <div class="card-header">
                    Resultados (${rows.length})
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Tracking</th>
                                    <th>Estado</th>
                                    <th>Fotos</th>
                                    <th>Links POD</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

        rows.forEach(item => {
            const rawState = item.delivery_state || 'unknown';
            const state = estadosES[rawState] || rawState;
            const badge = rawState === 'delivered' ? 'success' : 'warning';
            const photos = item.photos || [];

            let links = '—';

            if (photos.length) {
                links = photos.map((url, i) =>
                    `<a href="${url}" target="_blank">Foto ${i + 1}</a>`
                ).join(' | ');
            }

            html += `
                <tr>
                    <td>${item.tracking}</td>
                    <td><span class="badge bg-${badge}">${state}</span></td>
                    <td>${photos.length}</td>
                    <td style="white-space:nowrap;">${links}</td>
                </tr>
            `;
        });

        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;

        result.innerHTML = html;
    })
    .catch(() => {
        result.innerHTML = `
            <div class="alert alert-danger">
                ❌ Error al consultar el servicio
            </div>
        `;
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Buscar masivo';
    });
}

/* ================= EXPORTAR EXCEL MASIVO ================= */
function exportarExcelMasivo() {
    const raw = getBatchRawInput();
    const btn = document.getElementById('batchExportBtn');

    if (!raw.trim()) {
        alert('Pega al menos un tracking antes de exportar.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '⏳ Exportando...';

    fetch('/reports/tracking/export-batch', {
        method: 'POST',
        credentials: 'same-origin', // 👈 CLAVE
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ trackings: raw })
    })

    .then(res => {
        if (!res.ok) throw new Error();
        return res.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'tracking_batch_pod.xlsx';
        a.click();
        window.URL.revokeObjectURL(url);
    })
    .catch(() => {
        alert('Error al exportar Excel masivo');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Exportar Excel masivo';
    });
}
</script>
@endsection
