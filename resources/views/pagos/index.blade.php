@extends('layouts.app')

@section('content')

@if(session('mensaje'))
    <div class="alert alert-success shadow-sm">
        <strong>✅ {{ session('mensaje') }}</strong>
    </div>
@endif


<div class="container py-4">
    <div class="row">
        {{-- Columna izquierda: Filtros --}}
        <div class="col-lg-2 mb-4">
            @component('layouts.columna_izquierda', [
                'tituloTarjeta' => 'Gestión Masiva de Pagos',
                'tituloFiltros' => 'Filtrar Por',
                'action' => route('pagos.index')
            ])
                @slot('acciones')
                    {{-- Exportar --}}
                    <form id="formExportarPagos" action="{{ route('pagos.exportar') }}" method="POST">
                        @csrf
                        <input type="hidden" name="ids" id="exportar-ids">
                        <button type="button"
                            class="btn btn-outline-success btn-block py-2 d-flex align-items-center justify-content-center"
                            id="btnExportarPagos">
                            <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
                        </button>
                    </form>
                @endslot

                @slot('filtros')
                    <div class="mb-3">
                        <label for="empresa_id" class="form-label fw-semibold">Empresa</label>
                        <select name="empresa_id" id="empresa_id" class="form-select">
                            <option value="">Todas</option>
                            @foreach ($empresas as $empresa)
                                <option value="{{ $empresa->id }}" {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                    {{ $empresa->Nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="proveedor_id" class="form-label fw-semibold">Razón Social</label>
                        <select name="proveedor_id" id="proveedor_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}" {{ request('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                                    {{ $proveedor->razon_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="fecha_documento" class="form-label fw-semibold">Fecha del Documento</label>
                        <input type="date" name="fecha_documento" id="fecha_documento" class="form-control" value="{{ request('fecha_documento') }}">
                    </div>
                @endslot
            @endcomponent
        </div>

        {{-- Columna central: Tabla --}}
        <div class="col-lg-7">
            <div class="table-responsive shadow-sm rounded">
                <table class="table table-hover align-middle">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-uppercase font-weight-bold">Razón Social</th>
                            <th class="text-uppercase font-weight-bold">Empresa</th>
                            <th class="text-uppercase font-weight-bold">Fecha Vencimiento</th>
                            <th class="text-end text-uppercase font-weight-bold">Monto Total</th>
                            <th class="text-center align-middle text-uppercase font-weight-bold" style="width: 80px;">
                                <label for="selectAll" class="form-check d-flex justify-content-center align-items-center gap-2 mb-0">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                    <span class="small">Todos</span>
                                </label>
                            </th>
                            <th class="text-end text-uppercase font-weight-bold">TOTAL</th>
                        </tr>
                    </thead>

                    <tbody id="tabla-pagos">
                    @foreach($compras as $compra)
                        <tr class="{{ $compra->importante ? 'table-warning' : '' }}">
                            {{-- Columna Razón Social con estrella --}}
                            <td class="align-middle">
                                <div class="d-inline-flex align-items-center">
                                    <button 
                                        type="button"
                                        class="btn-toggle-importante bg-transparent border-0 p-0 m-0 text-warning mr-2"
                                        data-id="{{ $compra->id }}"
                                        style="cursor:pointer;"
                                        title="Marcar como importante">
                                        <i class="fa{{ $compra->importante ? 's' : 'r' }} fa-star"></i>
                                    </button>

                                    <span>{{ $compra->proveedor->razon_social }}</span>
                                </div>
                            </td>


                            {{-- Empresa --}}
                            <td class="align-middle">
                                {{ $compra->empresa->Nombre ?? '—' }}
                            </td>

                            {{-- Fecha Vencimiento --}}
                            <td class="align-middle">
                                {{ \Carbon\Carbon::parse($compra->fecha_vencimiento)->format('d-m-Y') }}
                            </td>

                            {{-- Monto Total --}}
                            <td class="text-end monto font-weight-bold text-dark align-middle">
                                ${{ number_format($compra->pago_total, 0, ',', '.') }}
                            </td>

                            {{-- Checkbox selección --}}
                            <td class="text-center align-middle">
                                <input type="checkbox" 
                                    class="seleccion-pago" 
                                    data-id="{{ $compra->id }}" 
                                    data-monto="{{ $compra->pago_total }}">
                            </td>

                            {{-- Acumulado --}}
                            <td class="text-end acumulado-cell align-middle">—</td>
                        </tr>
                    @endforeach
                </tbody>




                </table>
            </div>

            {{-- Totales --}}
            <div class="mt-5">
                <div class="card border-0 shadow-sm px-4 py-3">
                    <div class="row align-items-center">
                        {{-- Total seleccionado --}}
                        <div class="col-md-6">
                            <h4 class="fw-bold m-0">
                                Total a pagar: 
                                <span class="text-dark">$<span id="total-pagar">0</span></span>
                            </h4>
                        </div>

                        {{-- Total pendiente --}}
                        <div class="col-md-6 text-md-end mt-2 mt-md-0">
                            <small class="text-muted">
                                Pendiente esta semana: 
                                $<span id="resto-pagos">{{ number_format($totalGeneral, 0, ',', '.') }}</span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>


            <div class="mt-4 d-flex justify-content-center">
                {{ $compras->links('pagination::bootstrap-4') }}
            </div>
        </div>

        {{-- Columna derecha: Resumen próximos pagos --}}
        <div class="col-lg-3 mb-4">


            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Próximos Pagos</h5>

                    <ul class="list-unstyled mb-0">
                        @forelse($proximosPagos as $pago)
                            @php
                                $fechaPago = \Carbon\Carbon::parse($pago['fecha']);
                            @endphp
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <button 
                                    class="btn btn-sm p-0 small text-left ver-detalle-pagos"
                                    data-toggle="modal" 
                                    data-target="#modalProximosPagos"
                                    data-detalle='@json($pago["detalles"] ?? [])'>
                                    <strong>{{ $fechaPago->format('d-m') }}</strong> 
                                    ({{ $pago['cantidad'] }} proveedores)
                                </button>
                                <span class="fw-semibold small text-end">
                                    ${{ number_format($pago['total'], 0, ',', '.') }}
                                </span>
                            </li>


                        @empty
                            <li class="text-muted text-center py-2">No hay pagos próximos</li>
                        @endforelse
                    </ul>

                    {{-- Total general solo una vez --}}
                    @if($proximosPagos->count() > 0)
                        <div class="mt-3 text-end fw-bold border-top pt-2">
                            Total general: ${{ number_format(collect($proximosPagos)->sum('total'), 0, ',', '.') }}
                        </div>
                    @endif
                </div>
            </div>




        </div>





    </div>
</div>

@include('pagos.modal_detalle')


@endsection

{{-- JavaScript --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.seleccion-pago');
        const totalSpan = document.getElementById('total-pagar');
        const restoSpan = document.getElementById('resto-pagos');
        const selectAll = document.getElementById('selectAll');
        const exportBtn = document.getElementById('btnExportarPagos');
        const exportInput = document.getElementById('exportar-ids');
        const exportForm = document.getElementById('formExportarPagos');
        const totalGeneral = {{ $totalGeneral }};

        function actualizarTotales() {
            let total = 0;
            checkboxes.forEach(cb => {
                const monto = parseInt(cb.getAttribute('data-monto'));
                const celda = cb.closest('tr').querySelector('.acumulado-cell');
                if (cb.checked) {
                    total += monto;
                    celda.textContent = '$' + total.toLocaleString('es-CL');
                } else {
                    celda.textContent = '—';
                }
            });
            totalSpan.textContent = total.toLocaleString('es-CL');
            restoSpan.textContent = (totalGeneral - total).toLocaleString('es-CL');
        }

        checkboxes.forEach(cb => cb.addEventListener('change', actualizarTotales));
        selectAll?.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            actualizarTotales();
        });

        exportBtn.addEventListener('click', () => {
            const seleccionados = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.getAttribute('data-id'));
            if (seleccionados.length === 0) {
                alert('Debes seleccionar al menos un pago para exportar.');
                return;
            }
            exportInput.value = JSON.stringify(seleccionados);
            exportForm.submit(); // ⬅️ Solo enviamos el form
        });

        // Si venimos con export_ready, dispara la descarga
        @if(session('export_ready'))
            window.location = "{{ route('pagos.descargar') }}";
        @endif
    });
</script>


<script>
    if (window.location.search.includes('exportado=ok')) {
        const url = new URL(window.location.href);
        url.searchParams.delete('exportado');
        window.history.replaceState({}, document.title, url.toString());
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-toggle-importante').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.dataset.id;
                const icon = this.querySelector('i');
                const row = this.closest('tr');

                fetch(`/pagos/${id}/importante`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.importante) {
                            // marcar como importante
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            row.classList.add('table-warning');
                        } else {
                            // desmarcar como importante
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            row.classList.remove('table-warning');
                        }
                    }
                })
                .catch(error => {
                    alert('Error al marcar como importante');
                    console.error(error);
                });
            });
        });
    });
</script>






