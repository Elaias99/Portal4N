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

@vite('resources/js/reports/delivery-links.js')

@endsection
