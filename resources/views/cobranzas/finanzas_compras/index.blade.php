@extends('layouts.app')

@section('content')

<div class="container" style="max-width: 1150px;">

    {{-- 🔹 Mensajes de estado --}}
    {{-- 🟢 ÉXITO --}}
    @if(session('success'))
        <div class="alert alert-success custom-alert mx-auto shadow-sm" style="max-width:100%; border-left:5px solid #28a745; border-radius:10px; padding:12px 16px;">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <div><strong>Éxito:</strong> {{ session('success') }}</div>
            </div>
        </div>
    @endif

    {{-- 🟡 ADVERTENCIA --}}
    @if(session('warning'))
        <div class="alert alert-warning custom-alert mx-auto shadow-sm" style="max-width:100%; border-left:5px solid #ffc107; border-radius:10px; padding:12px 16px;">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    <div><strong>Atención:</strong> {{ session('warning') }}</div>
                </div>

                @if(session('detalles_errores'))
                    <button class="btn btn-link btn-sm p-0 text-decoration-none text-warning"
                            type="button"
                            data-toggle="collapse"
                            data-target="#detallesErrores"
                            aria-expanded="false"
                            aria-controls="detallesErrores">
                        <i class="bi bi-caret-down-fill"></i> Ver detalles
                    </button>
                @endif
            </div>

            @if(session('detalles_errores'))
                <div id="detallesErrores" class="collapse mt-2">
                    <div class="error-list border-top pt-2"
                        style="max-height:180px; overflow-y:auto; background:#fffef5; border-radius:8px; padding:8px 10px;">
                        <ul class="small mb-0 ps-3" style="list-style-type:'⚠️ '; line-height:1.4;">
                            @foreach (session('detalles_errores') as $error)
                                <li class="mb-1">Folio duplicado: {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- 🔴 ERROR --}}
    @if(session('error'))
        <div class="alert alert-danger custom-alert mx-auto shadow-sm" style="max-width:100%; border-left:5px solid #dc3545; border-radius:10px; padding:12px 16px;">
            <div class="d-flex align-items-center">
                <i class="bi bi-x-circle-fill text-danger me-2"></i>
                <div><strong>Error:</strong> {{ session('error') }}</div>
            </div>
        </div>
    @endif


    <div class="mb-3">
        <a href="{{ route('cobranzas.general') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left"></i> Volver al Panel Principal
        </a>
    </div>

    <h1 class="text-center mb-4">📊 Registro RCV COMPRAS</h1>

    {{-- 🔹 Sección de Importación --}}
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title mb-3">Importar archivo Excel</h5>

            <form action="{{ route('finanzas_compras.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <input type="file" name="file" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-file-earmark-arrow-up"></i> Importar
                        </button>
                    </div>
                </div>
            </form>
        </div>


        <a href="{{ route('finanzas_compras.export') }}" class="btn btn-outline-success btn-sm w-100">
            <i class="bi bi-file-earmark-arrow-down"></i> Exportar Excel
        </a>
    </div>











    {{-- 🔹 Tabla de registros --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title mb-3">Documentos Importados</h5>

            @if($documentosCompras->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>

                                <th>status</th>
                                <th>Tipo Doc</th>
                                <th>Tipo Compra</th>
                                <th>RUT Proveedor</th>
                                <th>Razón Social</th>
                                <th>Folio</th>
                                <th>Fecha Docto</th>
                                <th>Fecha Vencimiento</th>
                                <th>Monto Neto</th>
                                <th>Monto IVA Rec.</th>
                                <th>Monto Total</th>
                                <th>Saldo Pendiente</th>
                                <th>Empresa</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documentosCompras as $doc)
                                <tr>
                                    <td>
                                        @php
                                            $color = $doc->status_original === 'Vencido' ? 'bg-danger' : 'bg-success';
                                            $estadoMostrar = $doc->estado ?: $doc->status_original;
                                        @endphp

                                        <span class="badge {{ $color }}">{{ $estadoMostrar }}</span>

                                        {{-- Botón para abrir el modal de cambio de estado --}}
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary mt-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEstadoCompra-{{ $doc->id }}">
                                            Editar
                                        </button>

                                        {{-- Modal --}}
                                        @include('cobranzas.finanzas_compras.modal_estado', ['doc' => $doc])
                                    </td>


                                    <td>{{ $doc->tipoDocumento?->nombre ?? '-' }}</td>
                                    <td>{{ $doc->tipo_compra ?? '-' }}</td>
                                    <td>{{ $doc->rut_proveedor }}</td>
                                    <td>{{ $doc->razon_social }}</td>
                                    <td>{{ $doc->folio }}</td>
                                    <td>{{ $doc->fecha_docto ? \Carbon\Carbon::parse($doc->fecha_docto)->format('d-m-Y') : '-' }}</td>
                                    <td>{{ $doc->fecha_vencimiento }}</td>
                                    <td class="text-end">${{ number_format($doc->monto_neto, 0, ',', '.') }}</td>
                                    <td class="text-end">${{ number_format($doc->monto_iva_recuperable, 0, ',', '.') }}</td>



                                    <td class="text-end fw-bold">${{ number_format($doc->monto_total, 0, ',', '.') }}</td>



                                    
                                    <td class="text-end fw-bold {{ $doc->saldo_pendiente == 0 ? 'text-success' : 'text-danger' }}">
                                        ${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}
                                    </td>


                                    <td>{{ $doc->empresa?->Nombre ?? '—' }}</td>

                                    {{-- 🔹 Acciones / Detalles --}}
                                    <td class="text-center">

                                        {{-- 🚫 Si es una Nota de Crédito --}}

                                            {{-- 🔹 Un solo botón para ver todos los detalles --}}
                                            <a href="{{ route('finanzas_compras.show', $doc->id) }}?{{ http_build_query(request()->query()) }}" 
                                            class="btn btn-sm btn-outline-primary w-100">
                                                <i class="bi bi-eye"></i> Ver Detalles
                                            </a>


                                    </td>


                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- 🔹 Paginación --}}
                <div class="mt-3 d-flex justify-content-center">
                    {{ $documentosCompras->links('pagination::bootstrap-4') }}
                </div>
            @else
                <p class="text-muted text-center mb-0">Aún no hay registros importados.</p>
            @endif
        </div>
    </div>

</div>


<script>
    function toggleFechaEstado(select, id) {
        const inputFecha = document.getElementById('fecha-input-' + id);
        const hiddenFecha = document.getElementById('fecha-hidden-' + id);

        // Mostrar el campo de fecha solo para estados manuales
        if (['Abono', 'Pago', 'Pronto pago', 'Cobranza judicial'].includes(select.value)) {
            if (inputFecha) inputFecha.style.display = 'block';
        } else {
            if (inputFecha) {
                inputFecha.style.display = 'none';
                inputFecha.value = '';
            }
            if (hiddenFecha) hiddenFecha.value = '';
        }
    }

    // 👇 Este bloque asegura que Bootstrap Modal esté correctamente inicializado
    document.addEventListener('DOMContentLoaded', function () {
        const modalElements = document.querySelectorAll('.modal');
        modalElements.forEach(function (modalEl) {
            modalEl.addEventListener('show.bs.modal', function () {
                // Reposicionar o limpiar formularios si hace falta
            });
        });
    });
</script>



@endsection
