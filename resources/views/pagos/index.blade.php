@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Gestión de Pagos</h3>

    <div class="dropdown me-2">
        <button class="btn btn-outline-secondary dropdown-toggle shadow-sm" type="button" id="accionesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-gear me-2"></i> Acciones
        </button>
        <div class="dropdown-menu shadow-sm fade" aria-labelledby="accionesDropdown">
    
            <!-- Exportar -->
            <form id="formExportarPagos" action="{{ route('pagos.exportar') }}" method="POST">
                @csrf
                <input type="hidden" name="ids" id="exportar-ids">
                <button type="button" class="dropdown-item" id="btnExportarPagos">
                    <i class="fa-solid fa-file-export me-2"></i> Exportar seleccionados
                </button>
            </form>
            

        </div>
    </div>

    <br>

    <!-- Filtros -->
    <form method="GET" action="{{ route('pagos.index') }}" class="mb-4">
        <div class="row g-3">
            <!-- Filtro Empresa -->
            <div class="col-md-4">
                <label for="empresa_id" class="form-label">Empresa</label>
                <select name="empresa_id" id="empresa_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}" {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>
                            {{ $empresa->Nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro Proveedor (Razón Social) -->
            <div class="col-md-4">
                <label for="proveedor_id" class="form-label">Razón Social</label>
                <select name="proveedor_id" id="proveedor_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" {{ request('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                            {{ $proveedor->razon_social }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro Fecha Documento -->
            <div class="col-md-4">
                <label for="fecha_documento" class="form-label">Fecha del Documento</label>
                <input type="date" name="fecha_documento" id="fecha_documento" class="form-control" value="{{ request('fecha_documento') }}">
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('pagos.index') }}" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>


    <table class="table table-bordered table-hover" id="tabla-pagos">
        <thead class="table-light">
            <tr>
                <th>Razón Social</th>
                <th>Empresa</th>
                <th>Fecha Vencimiento</th>
                <th>Monto Total</th>
                <th>Select</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>


            @foreach($compras as $index => $compra)
                <tr>
                    <td>{{ $compra->proveedor->razon_social ?? '—' }}</td>
                    <td>{{ $compra->empresa->Nombre ?? '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($compra->fecha_vencimiento)->format('d-m-Y') }}</td>
                    <td class="text-end monto">${{ number_format($compra->pago_total, 0, ',', '.') }}</td>

                    <td class="text-center">
                        <input type="checkbox"
                            class="seleccion-pago"
                            data-id="{{ $compra->id }}"
                            data-monto="{{ $compra->pago_total }}">
                    </td>
                    <td class="text-end acumulado-cell">—</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4 text-end">
        <h5><strong>Total a pagar:</strong> $<span id="total-pagar">0</span></h5>

        <h5><strong>Resto:</strong> $<span id="resto-pagos">{{ number_format($totalGeneral, 0, ',', '.') }}</span></h5>

    </div>
</div>
@endsection


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.seleccion-pago');
        const totalSpan = document.getElementById('total-pagar');
        const restoSpan = document.getElementById('resto-pagos');
        const filas = document.querySelectorAll('#tabla-pagos tbody tr');

        const exportBtn = document.getElementById('btnExportarPagos');
        const exportInput = document.getElementById('exportar-ids');
        const exportForm = document.getElementById('formExportarPagos');

        exportBtn.addEventListener('click', () => {
            const seleccionados = [];
            document.querySelectorAll('.seleccion-pago').forEach((cb, index) => {
                if (cb.checked) {
                    const row = cb.closest('tr');
                    const compraId = cb.getAttribute('data-id');
                    seleccionados.push(compraId);

                }
            });

            if (seleccionados.length === 0) {
                alert('Debes seleccionar al menos un pago para exportar.');
                return;
            }

            exportInput.value = JSON.stringify(seleccionados);
            exportForm.submit();
        });

        const totalGeneral = {{ $totalGeneral }};

        function actualizarTotales() {
            let total = 0;

            filas.forEach((fila) => {
                const checkbox = fila.querySelector('.seleccion-pago');
                const monto = parseInt(checkbox.getAttribute('data-monto'));
                const celdaTotal = fila.querySelector('.acumulado-cell');

                if (checkbox.checked) {
                    total += monto;
                    celdaTotal.textContent = '$' + total.toLocaleString('es-CL');
                } else {
                    celdaTotal.textContent = '—';
                }
            });

            totalSpan.textContent = total.toLocaleString('es-CL');
            restoSpan.textContent = (totalGeneral - total).toLocaleString('es-CL');
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', actualizarTotales);
        });
    });
</script>



