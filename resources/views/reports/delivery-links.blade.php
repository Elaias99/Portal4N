@extends('layouts.app')

@section('content')
<div class="container-fluid">

    {{-- TÍTULO --}}
    <div class="mb-4">
        <h1 class="h3">Envíos con prueba de entrega</h1>
        <p class="text-muted mb-0">
            Consulta manual de tracking y exportación de pruebas de entrega (POD).
        </p>
    </div>

    {{-- BUSCADOR --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>Buscar tracking manualmente</strong>
        </div>

        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Número de tracking</label>
                    <input
                        type="text"
                        id="trackingInput"
                        class="form-control"
                        placeholder="Ej: 4N202601081369"
                    >
                </div>

                <div class="col-md-2">
                    <button
                        class="btn btn-primary w-100"
                        onclick="buscarTracking()"
                    >
                        Buscar
                    </button>
                </div>

                <div class="col-md-2">
                    <button
                        id="exportBtn"
                        class="btn btn-outline-success w-100"
                        onclick="exportarExcel()"
                    >
                        Exportar Excel
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

/* ================= BUSCAR TRACKING ================= */
function buscarTracking() {
    const tracking = document.getElementById('trackingInput').value.trim();
    const result = document.getElementById('searchResult');

    if (!tracking) {
        alert('Ingresa un número de tracking');
        return;
    }

    result.innerHTML = `
        <div class="card">
            <div class="card-body">
                ⏳ Consultando tracking...
            </div>
        </div>
    `;

    fetch('/reports/tracking/search', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .content
        },
        body: JSON.stringify({ tracking })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            result.innerHTML = `
                <div class="alert alert-warning">
                    ❌ ${res.message}
                </div>
            `;
            return;
        }

        const rawState = res.data.delivery_state || 'unknown';
        const state = estadosES[rawState] || rawState;
        const photos = res.data.photos || [];

        const estadoBadge = `
            <span class="badge bg-${rawState === 'delivered' ? 'success' : 'warning'}">
                ${state}
            </span>
        `;

        let photosHtml = '';

        if (photos.length) {
            photosHtml += `<ul class="list-group list-group-flush mt-2">`;

            photos.forEach((url, i) => {
                photosHtml += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Foto ${i + 1}</span>
                        <a href="${url}" target="_blank" class="btn btn-sm btn-outline-primary">
                            Ver
                        </a>
                    </li>
                `;
            });

            photosHtml += `</ul>`;
        } else {
            photosHtml = `<p class="text-muted mb-0">No existen fotos de entrega.</p>`;
        }

        result.innerHTML = `
            <div class="card">
                <div class="card-header">
                    Resultado del tracking
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Tracking</strong><br>
                            ${tracking}
                        </div>

                        <div class="col-md-4">
                            <strong>Estado</strong><br>
                            ${estadoBadge}
                        </div>
                    </div>

                    <div>
                        <strong>Pruebas de entrega (POD)</strong>
                        ${photosHtml}
                    </div>
                </div>
            </div>
        `;
    })
    .catch(() => {
        result.innerHTML = `
            <div class="alert alert-danger">
                ❌ Error al consultar el servicio
            </div>
        `;
    });
}

/* ================= EXPORTAR EXCEL ================= */
function exportarExcel() {
    const tracking = document.getElementById('trackingInput').value.trim();
    const btn = document.getElementById('exportBtn');

    if (!tracking) {
        alert('Busca un tracking primero');
        return;
    }

    // Feedback visual
    btn.disabled = true;
    btn.innerHTML = '⏳ Exportando...';

    fetch('/reports/tracking/export', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .content
        },
        body: JSON.stringify({ tracking })
    })
    .then(res => {
        if (!res.ok) throw new Error();
        return res.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `tracking_${tracking}.xlsx`;
        a.click();
        window.URL.revokeObjectURL(url);
    })
    .catch(() => {
        alert('Error al exportar Excel');
    })
    .finally(() => {
        // Restaurar botón
        btn.disabled = false;
        btn.innerHTML = 'Exportar Excel';
    });
}
</script>
@endsection
