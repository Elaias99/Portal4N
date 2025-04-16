@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <h2 class="fw-bold m-0">Gestión de Pagos</h2>

        <div class="position-relative">
            <button 
                class="btn btn-light border shadow-sm d-flex align-items-center gap-1 px-1 py-1" 
                type="button" 
                id="accionesDropdown" 
                data-bs-toggle="dropdown" 
                aria-expanded="false"
            >
                <i class="fa-solid fa-gear"></i>
                <span class="fw-semibold">Acciones</span>
                <i class="fa-solid fa-caret-down small"></i>
            </button>

            <div class="dropdown-menu dropdown-menu-end shadow-sm mt-1 rounded" aria-labelledby="accionesDropdown">
                <form id="formExportarPagos" action="{{ route('pagos.exportar') }}" method="POST">
                    @csrf
                    <input type="hidden" name="ids" id="exportar-ids">
                    <button type="button" class="dropdown-item d-flex align-items-center gap-1" id="btnExportarPagos">
                        <i class="fa-solid fa-file-excel text-success"></i> 
                        <span class="text-success">Exportar a Excel</span>
                    </button>
                </form>
           </div>
        </div>
    </div>


    {{-- Filtros --}}
    <form method="GET" action="{{ route('pagos.index') }}" class="card shadow-sm p-4 mb-4 border-0">
        <div class="row g-3">
            <div class="col-md-4">
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

            <div class="col-md-4">
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

            <div class="col-md-4">
                <label for="fecha_documento" class="form-label fw-semibold">Fecha del Documento</label>
                <input type="date" name="fecha_documento" id="fecha_documento" class="form-control" value="{{ request('fecha_documento') }}">
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="submit" class="btn btn-primary px-4">Filtrar</button>
            <a href="{{ route('pagos.index') }}" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </form>

    {{-- Tabla de Pagos --}}
    <div class="table-responsive">
        <table class="table align-middle shadow-sm">
            <thead class="table-light">
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
            <tbody>
                @foreach($compras as $compra)
                    <tr>
                        <td>{{ $compra->proveedor->razon_social ?? '—' }}</td>
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
</div>
@endsection

{{-- JavaScript --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.seleccion-pago');
        const totalSpan = document.getElementById('total-pagar');
        const restoSpan = document.getElementById('resto-pagos');
        const filas = document.querySelectorAll('#tabla-pagos tbody tr');
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
        });
    });
</script>
