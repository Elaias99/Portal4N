@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Panel de Seguimiento de Bultos</h2>

    {{-- Filtros --}}
    <div class="mb-4 p-3 border rounded bg-light">
        <h5 class="mb-3">Filtros</h5>
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="filtro-chofer">Chofer</label>
                <select class="form-select" id="filtro-chofer">
                    <option value="">Todos</option>
                    @foreach($choferes as $c)
                        <option value="{{ $c->id }}">{{ $c->Nombre }} {{ $c->ApellidoPaterno }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="filtro-cliente">Empresa / Cliente</label>
                <select class="form-select" id="filtro-cliente">
                    <option value="">Todos</option>
                    @foreach($razonesSociales as $razon)
                        <option value="{{ $razon }}">{{ $razon }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="filtro-codigo">Código Bulto</label>
                <input type="text" class="form-control" id="filtro-codigo" placeholder="Ej: 0039">
            </div>

            <div class="col-md-2">
                <label for="filtro-desde">Desde</label>
                <input type="date" class="form-control" id="filtro-desde">
            </div>

            <div class="col-md-2">
                <label for="filtro-hasta">Hasta</label>
                <input type="date" class="form-control" id="filtro-hasta">
            </div>

            <div class="col-md-12 mt-3">
                <button class="btn btn-secondary" id="limpiar-filtros">Limpiar filtros</button>
            </div>
        </div>
    </div>

    {{-- Tarjetas de estados --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3 estado-tab" data-estado="Retiro" style="cursor:pointer;">
                <div class="card-header">En Retiro</div>
                <div class="card-body">
                    <h5 class="card-title">{{ $countRetiro }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3 estado-tab" data-estado="Recepcionado" style="cursor:pointer;">
                <div class="card-header">Recepcionados</div>
                <div class="card-body">
                    <h5 class="card-title">{{ $countRecepcionado }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3 estado-tab" data-estado="En Ruta" style="cursor:pointer;">
                <div class="card-header">En Ruta</div>
                <div class="card-body">
                    <h5 class="card-title">{{ $countEnRuta }}</h5>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de resumen --}}
    <div id="tabla-contenedor" style="display: none;">
        <h4 class="mb-3">Resumen por Código Bulto</h4>
        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr>
                    <th>Código Bulto</th>
                    <th>Fecha Retiro</th>
                    <th>Fecha Recepción</th>
                    <th>Fecha En Ruta</th>
                </tr>
            </thead>
            <tbody id="tabla-codigos">
                @foreach($historial as $item)
                    <tr
                        data-estado="{{ $item['estado_final'] }}"
                        data-chofer-id="{{ $item['chofer_id'] ?? '' }}"
                        data-codigo="{{ $item['codigo'] }}"
                        data-cliente="{{ $item['razon_social'] ?? '' }}"
                        data-fecha="{{ $item['en_ruta'] ?? $item['recepcionado'] ?? $item['retiro'] ?? '' }}"
                    >
                        <td>{{ $item['codigo'] }}</td>
                        <td>{{ $item['retiro'] ?? '—' }}</td>
                        <td>{{ $item['recepcionado'] ?? '—' }}</td>
                        <td>{{ $item['en_ruta'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filas = document.querySelectorAll('#tabla-codigos tr');

    const filtros = {
        chofer: document.getElementById('filtro-chofer'),
        cliente: document.getElementById('filtro-cliente'),
        codigo: document.getElementById('filtro-codigo'),
        desde: document.getElementById('filtro-desde'),
        hasta: document.getElementById('filtro-hasta'),
        limpiar: document.getElementById('limpiar-filtros'),
    };

    function aplicarFiltros() {
        const idChofer = filtros.chofer.value;
        const cliente = filtros.cliente.value.toLowerCase();
        const codigo = filtros.codigo.value.toLowerCase();
        const desde = filtros.desde.value;
        const hasta = filtros.hasta.value;

        filas.forEach(fila => {
            const choferFila = fila.dataset.choferId;
            const clienteFila = (fila.dataset.cliente || '').toLowerCase();
            const codigoFila = (fila.dataset.codigo || '').toLowerCase();
            const fechaFila = fila.dataset.fecha || '';

            let visible = true;

            if (idChofer && choferFila !== idChofer) visible = false;
            if (cliente && !clienteFila.includes(cliente)) visible = false;
            if (codigo && !codigoFila.includes(codigo)) visible = false;
            if (desde && fechaFila < desde) visible = false;
            if (hasta && fechaFila > hasta) visible = false;

            fila.style.display = visible ? '' : 'none';
        });
    }

    // Aplicar al cambiar cualquier filtro
    Object.values(filtros).forEach(input => {
        if (input && input.tagName !== 'BUTTON') {
            input.addEventListener('input', aplicarFiltros);
        }
    });

    filtros.limpiar.addEventListener('click', () => {
        Object.values(filtros).forEach(input => {
            if (input.tagName === 'SELECT' || input.tagName === 'INPUT') input.value = '';
        });
        aplicarFiltros();
    });

    // Mostrar tabla y aplicar filtro por estado
    document.querySelectorAll('.estado-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const estado = tab.dataset.estado;
            document.getElementById('tabla-contenedor').style.display = 'block';

            filas.forEach(fila => {
                fila.style.display = fila.dataset.estado === estado ? '' : 'none';
            });
        });
    });
});
</script>
@endpush
