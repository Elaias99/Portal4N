@extends('layouts.app')

@section('content')

@if(!empty($mensaje))
    <div class="alert alert-success shadow-sm">
        <strong>✅ {{ $mensaje }}</strong>
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
                    <thead class="bg-secondary text-white">
                        <tr>
                            <th>Razón Social</th>
                            <th>Empresa</th>
                            <th>Fecha Vencimiento</th>
                            <th class="text-end">Monto Total</th>
                            <th class="text-center align-middle">
                                <label for="selectAll" class="form-check d-flex justify-content-center align-items-center gap-2 mb-0">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                    <span class="small">Todos</span>
                                </label>
                            </th>
                            <th class="text-end">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-pagos">
                        @foreach($compras as $compra)
                            <tr class="{{ $compra->importante ? 'table-warning' : '' }}">
                                <td class="d-flex align-items-center gap-2">
                                    <form action="{{ route('pagos.toggleImportante', $compra->id) }}" method="POST" class="m-0 p-0">
                                        @csrf
                                        @method('POST')
                                        <button type="submit" class="bg-transparent border-0 p-0 m-0 text-warning" title="Marcar como importante" style="cursor:pointer;">
                                            <i class="fa{{ $compra->importante ? 's' : 'r' }} fa-star"></i>
                                        </button>
                                    </form>
                                    <span>{{ $compra->proveedor->razon_social }}</span>
                                </td>
                                <td>{{ $compra->empresa->Nombre ?? '—' }}</td>
                                <td>{{ \Carbon\Carbon::parse($compra->fecha_vencimiento)->format('d-m-Y') }}</td>
                                <td class="text-end monto">${{ number_format($compra->pago_total, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <input type="checkbox" class="seleccion-pago" data-id="{{ $compra->id }}" data-monto="{{ $compra->pago_total }}">
                                </td>
                                <td class="text-end acumulado-cell">—</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totales --}}
            <div class="mt-5">
                <div class="card border-0 shadow-sm px-4 py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="fw-bold m-0">Total a pagar: <span class="text">$<span id="total-pagar">0</span></span></h4>
                        </div>
                        <div class="col-md-6 text-md-end mt-2 mt-md-0">
                            <h5 class="m-0">Resto: <span class="text-muted">$<span id="resto-pagos">{{ number_format($totalGeneral, 0, ',', '.') }}</span></span></h5>
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
                                $fechaPago = \Carbon\Carbon::parse($pago->fecha_vencimiento);
                                $diasRestantes = $fechaPago->diffInDays(now(), false);
                                $claseFecha = $diasRestantes <= 2 ? 'text-danger fw-bold' : ($diasRestantes <= 5 ? 'text-warning fw-semibold' : 'text-muted');
                            @endphp
                            <li class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <span class="{{ $claseFecha }} small">
                                    {{ $fechaPago->format('d-m') }}
                                </span>
                                <span class="fw-semibold small text-end" style="min-width: 90px;">
                                    ${{ number_format($pago->pago_total, 0, ',', '.') }}
                                </span>
                            </li>
                        @empty
                            <li class="text-muted text-center py-2">No hay pagos próximos</li>
                        @endforelse
                    </ul>

                    @if($proximosPagos->count() > 0)
                        <div class="mt-3 text-end fw-bold border-top pt-2">
                            Total: ${{ number_format($proximosPagos->sum('pago_total'), 0, ',', '.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>





    </div>
</div>

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
            exportForm.submit();
            setTimeout(() => {
                window.location.href = "{{ route('pagos.index', ['exportado' => 'ok']) }}";
            }, 2000);
        });
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
        document.querySelectorAll('.toggle-importante').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.dataset.id;
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
                        button.classList.toggle('btn-warning', data.importante);
                        button.classList.toggle('btn-outline-warning', !data.importante);
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
