@extends('layouts.app')

@section('content')




<style>

    /* //////////////////////////////////////////////////////////////////////////////////// */
    /* SECCCION DE LAS TARJETA PARA EAACEDER A CUENTAS POR COBRAR - EL HISTORIAL Y EXPORTAR */
    /* //////////////////////////////////////////////////////////////////////////////////// */
    /* ====== AJUSTE VISUAL KUCOIN STYLE ====== */
    :root {
        --bg: #F8FAFC;          /* fondo principal */
        --surface-1: #F6F8FC;   /* fondo suave para las tarjetas */
        --border: #E6EDF5;      /* borde muy sutil */
        --hover-surface: #F2F5FA;
        --input-bg: #F1F5F9;    /* fondo gris azulado para inputs */
        --input-hover: #E9EEF4;
        --primary: #2563EB;
        --primary-hover: #1D4ED8;
        --text-muted: #64748B;
    }

    body {
        background-color: var(--bg);
    }

    .card-hover {
        background-color: var(--surface-1); /* antes #fff */
        border-radius: 16px;
        border: 1px solid var(--border);    /* línea muy ligera */
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        transition: all 0.2s ease-in-out;
    }

    .card-hover:hover {
        transform: translateY(-4px);
        background-color: #F9FBFD;          /* al pasar el mouse, se aclara */
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
    }

    .icon-wrapper {
        background-color: #f5f7fa;
        border-radius: 12px;
        padding: 12px 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .text-primary {
        color: #2563eb !important; /* Azul moderno tipo KuCoin */
    }

    .card {
        border-radius: 16px;
    }

    /* //////////////////////////////////////////////////////////////////////////////////// */
    /* SECCCION DE LAS TARJETA PARA EAACEDER A CUENTAS POR COBRAR - EL HISTORIAL Y EXPORTAR */
    /* //////////////////////////////////////////////////////////////////////////////////// */




    /* //////////////////////////////////////////////////////////////////////////////////// */
    /* SECCCION PARA EL BLOQUE DE FILTROS */
    /* //////////////////////////////////////////////////////////////////////////////////// */
    .filter-card {
        background-color: var(--surface-1);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        transition: all 0.25s ease-in-out;
    }

    .filter-card:hover {
        background-color: var(--hover-surface);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
        transform: translateY(-2px);
    }

    .filter-card .card-body {
        padding: 1.5rem 1.75rem;
    }

    /* === INPUTS === */
    .filter-card .form-control {
        background-color: var(--input-bg);
        border: 1px solid var(--border);
        border-radius: 10px;
        font-size: 0.875rem;
        color: #1E293B;
        transition: border-color 0.2s ease-in-out, background-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .filter-card .form-control::placeholder {
        color: var(--text-muted);
    }

    .filter-card .form-control:hover {
        background-color: var(--input-hover);
    }

    .filter-card .form-control:focus {
        background-color: var(--surface-1);
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    /* === BOTONES === */
    .filter-card .btn-primary {
        background-color: var(--primary);
        border-color: var(--primary);
        transition: all 0.2s ease-in-out;
    }

    .filter-card .btn-primary:hover {
        background-color: var(--primary-hover);
    }

    .filter-card .btn-outline-secondary {
        border-color: var(--border);
        color: #475569;
        background-color: var(--surface-1);
        transition: all 0.2s ease-in-out;
    }

    .filter-card .btn-outline-secondary:hover {
        background-color: var(--input-hover);
    }

    /* === DROPDOWN === */
    .filter-card .dropdown-menu {
        border-radius: 12px;
        border: 1px solid var(--border);
        background-color: var(--surface-1);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
    }

    /* === LABELS === */
    .filter-card label {
        font-weight: 500;
        color: var(--text-muted);
    }



    /* ////////////////////////////////////////////////////////////////////// */
    /* ///////////////////////    TIPOGRAFÍA  //////////////////////////////////////////// */
    /* === Aplicación Global === */
    #modulo-finanzas {
        font-family: 'Source Sans 3', 'Segoe UI', Roboto, sans-serif;
        color: var(--text-primary);
        font-size: 0.9375rem; /* 15px */
        line-height: 1.55;
    }

    /* Jerarquías dentro del módulo */
    #modulo-finanzas h1, 
    #modulo-finanzas h2, 
    #modulo-finanzas h3, 
    #modulo-finanzas h4, 
    #modulo-finanzas h5 {
        font-weight: 600;
        letter-spacing: -0.01em;
        color: var(--text-primary);
    }

    #modulo-finanzas label,
    #modulo-finanzas .form-label {
        font-weight: 500;
        font-size: 0.8125rem;
        color: var(--text-muted);
    }

    #modulo-finanzas ::placeholder {
        color: var(--text-placeholder);
        opacity: 1;
    }

    /* Inputs y botones */
    #modulo-finanzas input, 
    #modulo-finanzas select, 
    #modulo-finanzas button {
        font-family: 'Source Sans 3', 'Segoe UI', sans-serif;
        font-size: 0.875rem;
    }

    #modulo-finanzas .btn {
        font-weight: 500;
        letter-spacing: -0.005em;
    }


    /* ////////////////////////////////////////////////////////////// */
    /* //////////////////// TABLA DE RESULTADOS ////////////////////// */
    /* ////////////////////////////////////////////////////////////// */

    .table-finanzas {
        background-color: var(--surface-1);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        font-family: 'Source Sans 3', 'Segoe UI', sans-serif;
        font-size: 0.875rem;
    }

    .table-finanzas thead {
        background-color: var(--surface-1); /* Fondo coherente con las tarjetas */
        color: var(--text-muted);           /* Gris azulado suave */
        font-weight: 500;                   /* Más liviano */
        text-transform: none;
        border-bottom: 1px solid var(--border);
    }

    .table-finanzas thead th {
        padding: 0.75rem 1rem;
        border: none;
        font-size: 0.8125rem;               /* Tamaño más contenido */
        letter-spacing: -0.01em;            /* Leve ajuste tipográfico */
        text-align: center;
    }


    .table-finanzas tbody tr {
        transition: background-color 0.2s ease-in-out;
        border-bottom: 1px solid var(--border);
    }

    .table-finanzas tbody tr:hover {
        background-color: var(--hover-surface);
    }

    .table-finanzas td {
        padding: 0.65rem 1rem;
        color: #334155;
        vertical-align: middle;
        border: none;
    }

    .table-finanzas td.text-end {
        text-align: right;
    }

    /* Estado refinado — coherente con el estilo KuCoin */
    .table-finanzas .badge {
        font-weight: 500;
        font-size: 0.75rem;
        border-radius: 6px;
        padding: 0.25rem 0.55rem;
        letter-spacing: -0.01em;
        border: 1px solid transparent;
    }

    /* Versiones de estado */
    .table-finanzas .badge-success {
        background-color: rgba(16, 185, 129, 0.12); /* verde suave */
        color: #047857;                             /* verde oscuro */
        border-color: rgba(16, 185, 129, 0.25);
    }

    .table-finanzas .badge-danger {
        background-color: rgba(239, 68, 68, 0.12);  /* rojo tenue */
        color: #B91C1C;
        border-color: rgba(239, 68, 68, 0.25);
    }

    .table-finanzas .badge-warning {
        background-color: rgba(251, 191, 36, 0.15); /* amarillo suave */
        color: #92400E;
        border-color: rgba(251, 191, 36, 0.3);
    }


    /* Botón Ver refinado */
    .table-finanzas .btn-outline-primary {
        border: 1px solid var(--primary);
        color: var(--primary);
        font-weight: 500;
        font-size: 0.8125rem;
        padding: 0.25rem 0.6rem;
        border-radius: 8px;
        transition: all 0.2s ease-in-out;
    }

    .table-finanzas .btn-outline-primary:hover {
        background-color: var(--primary);
        color: #fff;
    }

    /* ////////////////////////////////////////////////////////////// */
    /* ////////////////// DETALLE DEL DOCUMENTO ////////////////////// */
    /* ////////////////////////////////////////////////////////////// */

    .detalle-finanzas h6 {
        font-weight: 600;
        font-size: 1rem;
        color: #1E293B;
        margin-bottom: 1rem;
    }

    /* === Tabla principal (detalle del documento) === */
    .detalle-finanzas table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 1.5rem;
        font-size: 0.875rem;
    }

    .detalle-finanzas th {
        background-color: var(--surface-1);
        color: var(--text-muted);
        font-weight: 500;
        border: none;
        width: 20%;
        padding: 0.65rem 0.75rem;
        text-align: left;
        border-right: 1px solid var(--border);
    }

    .detalle-finanzas td {
        background-color: var(--hover-surface);
        color: var(--text-primary);
        border: none;
        padding: 0.65rem 0.75rem;
        border-top: 1px solid var(--border);
    }

    /* Borde visual suave entre filas */
    .detalle-finanzas tr:not(:last-child) td {
        border-bottom: 1px solid var(--border);
    }

    /* Hover refinado */
    .detalle-finanzas tr:hover td {
        background-color: var(--input-hover);
        transition: background-color 0.2s ease-in-out;
    }

    /* === Movimientos Anteriores === */
    .detalle-finanzas .table-striped thead {
        background-color: var(--surface-1);
        color: var(--text-muted);
        font-weight: 500;
        border-bottom: 1px solid var(--border);
    }

    .detalle-finanzas .table-striped tbody tr:nth-child(odd) {
        background-color: var(--surface-1);
    }

    .detalle-finanzas .table-striped tbody tr:nth-child(even) {
        background-color: var(--hover-surface);
    }

    .detalle-finanzas .table-striped td {
        color: var(--text-primary);
        border: none;
        padding: 0.6rem 0.75rem;
    }

    .detalle-finanzas .table-striped th {
        border: none;
        padding: 0.6rem 0.75rem;
        font-weight: 500;
        color: var(--text-muted);
    }






    
</style>




<div class="container-fluid mt-4" id="modulo-finanzas">

    {{-- ====== CABECERA ====== --}}
    <div class="text-center mb-4">
        <h2 class="fw-bold">Módulo Finanzas</h2>
        <p class="text-muted mb-0">Panel central de gestión — Documentos, abonos, cruces y movimientos</p>
    </div>


    {{-- ====== ACCESOS DIRECTOS ====== --}}
    <div class="row justify-content-center text-center g-4 mb-4">

        {{-- === Cuentas por Cobrar === --}}
        <div class="col-md-3">
            <a href="{{ route('cobranzas.documentos') }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                        <h6 class="fw-semibold mb-1">Cuentas por Cobrar</h6>
                        <p class="text-muted small mb-0">Gestión de facturas y notas de crédito</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- === Exportar Documentos === --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                    <h6 class="fw-semibold mb-2">Exportar documentos</h6>
                    <p class="text-muted small mb-3">Importa nuevos o exporta los existentes.</p>
                    <a href="{{ route('cobranzas.export') }}" class="btn btn-sm btn-outline-primary px-4">Exportar</a>
                </div>
            </div>
        </div>

        {{-- === Historial de Movimientos === --}}
        <div class="col-md-3">
            <a href="{{ route('panelfinanza.show') }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                        <h6 class="fw-semibold mb-1">Historial de Movimientos</h6>
                        <p class="text-muted small mb-0">Ver abonos y cruces en un solo listado</p>
                    </div>
                </div>
            </a>
        </div>

    </div>


    {{-- ====== SECCIÓN PRINCIPAL ====== --}}
    <div class="row">
        <div class="row justify-content-center">
            {{-- === COLUMNA CENTRAL (BUSCADOR + TABLA) === --}}
            <div class="col-md-10 col-lg-8">





            <div class="card filter-card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('cobranzas.general') }}">
                        <div class="row align-items-end">
                            <div class="col-md-4 mb-3">
                                <label class="small text-muted">Buscar por Folio o Cliente</label>
                                <input type="text" name="q" class="form-control form-control-sm"
                                    placeholder="Ej: 10256 o Transportes Sur Ltda" value="{{ request('q') }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="small text-muted">RUT Cliente</label>
                                <input type="text" name="rut" class="form-control form-control-sm"
                                    placeholder="Ej: 76.543.210-9" value="{{ request('rut') }}">
                            </div>

                            {{-- 🔽 Filtro: Fecha de Documento --}}
                            <div class="col-md-2 dropdown-fechas mb-3">
                                <label class="form-label small text-muted">Fecha Origen</label>
                                <div class="dropdown w-100">
                                    <button class="btn dropdown-toggle btn-sm w-100 text-start" type="button"
                                            id="dropdownFechas" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-calendar3"></i> Fecha Dcto.
                                    </button>

                                    <div class="dropdown-menu p-3">
                                        <label class="form-label small text-muted">Desde</label>
                                        <input type="date" name="fecha_inicio" class="form-control form-control-sm mb-2"
                                            value="{{ request('fecha_inicio') }}">

                                        <label class="form-label small text-muted">Hasta</label>
                                        <input type="date" name="fecha_fin" class="form-control form-control-sm mb-2"
                                            value="{{ request('fecha_fin') }}">
                                    </div>
                                </div>
                            </div>

                            {{-- 🔽 Filtro: Fecha de Vencimiento --}}
                            <div class="col-md-2 dropdown-fechas mb-3">
                                <label class="form-label small text-muted">Fecha Vencimiento</label>
                                <div class="dropdown w-100">
                                    <button class="btn dropdown-toggle btn-sm w-100 text-start" type="button"
                                            id="dropdownVencimiento" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-calendar-event"></i> Fecha Venc.
                                    </button>

                                    <div class="dropdown-menu p-3">
                                        <label class="form-label small text-muted">Desde</label>
                                        <input type="date" name="vencimiento_inicio" class="form-control form-control-sm mb-2"
                                            value="{{ request('vencimiento_inicio') }}">

                                        <label class="form-label small text-muted">Hasta</label>
                                        <input type="date" name="vencimiento_fin" class="form-control form-control-sm mb-2"
                                            value="{{ request('vencimiento_fin') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-1 text-end mb-3 d-flex gap-2 justify-content-end">
                                {{-- Botón Buscar --}}
                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                    <i class="fa fa-search"></i>
                                </button>

                                {{-- Botón Limpiar --}}
                                <a href="{{ route('cobranzas.general') }}" class="btn btn-outline-secondary btn-sm px-3">
                                    <i class="fa fa-eraser"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

                {{-- ====== RESULTADOS ====== --}}
                @if(isset($documentos) && $documentos->count())



                    <div class="table-responsive">
                        <table class="table table-finanzas table-hover table-sm align-middle text-center">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Cliente</th>
                                    <th>RUT</th>
                                    <th>Fecha Docto</th>
                                    <th>Fecha Venc.</th>
                                    <th>Estado</th>
                                    <th class="text-end">Saldo Pendiente</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($documentos as $doc)
                                    <tr>
                                        <td>{{ $doc->folio }}</td>
                                        <td>{{ $doc->razon_social }}</td>
                                        <td>{{ $doc->rut_cliente }}</td>
                                        <td>{{ $doc->fecha_docto }}</td>
                                        <td>{{ $doc->fecha_vencimiento }}</td>

                                        <td>
                                            <span class="badge badge-{{ 
                                                $doc->status == 'Vencido' ? 'danger' : 
                                                ($doc->status == 'Por vencer' ? 'warning' : 'success') 
                                            }}">
                                                {{ $doc->status }}
                                            </span>
                                        </td>

                                        <td class="text-end">
                                            ${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            <a href="{{ route('cobranzas.general', array_merge(request()->only(['q','rut','status']), ['documento_id' => $doc->id])) }}"
                                            class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>







                @elseif(request()->has('q') || request()->has('rut'))
                    <div class="alert alert-warning text-center">
                        <i class="fa fa-info-circle"></i> No se encontraron documentos con los criterios ingresados.
                    </div>
                @endif

                {{-- ====== DETALLE DE DOCUMENTO ====== --}}
                <div class="detalle-finanzas">
                    @isset($documentoSeleccionado)
                        <hr class="my-4">
                        <h6 class="fw-bold">Detalle del Documento — Folio {{ $documentoSeleccionado->folio }}</h6>
                        <table class="table table-bordered table-sm mb-4">
                            <tbody>
                                <tr>
                                    <th>Empresa</th><td>{{ $documentoSeleccionado->empresa?->Nombre ?? '-' }}</td>
                                    <th>Tipo Documento</th><td>{{ $documentoSeleccionado->tipoDocumento?->nombre ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Cliente</th><td>{{ $documentoSeleccionado->razon_social }}</td>
                                    <th>RUT</th><td>{{ $documentoSeleccionado->rut_cliente }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha Documento</th><td>{{ $documentoSeleccionado->fecha_docto }}</td>
                                    <th>Fecha Vencimiento</th><td>{{ $documentoSeleccionado->fecha_vencimiento }}</td>
                                </tr>
                                <tr>
                                    <th>Monto Total</th><td>${{ number_format($documentoSeleccionado->monto_total,0,',','.') }}</td>
                                    <th>Saldo Pendiente</th><td>${{ number_format($documentoSeleccionado->saldo_pendiente,0,',','.') }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <h6 class="fw-bold mb-3">Movimientos Anteriores</h6>
                        <table class="table table-striped table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Tipo Movimiento</th>
                                    <th>Usuario</th>
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documentoSeleccionado->movimientos as $mov)
                                    <tr>
                                        <td>{{ $mov->id }}</td>
                                        <td>{{ $mov->tipo_movimiento }}</td>
                                        <td>{{ $mov->user->name ?? '— Sistema —' }}</td>

                                        <td>{{ $mov->created_at->format('d-m-Y H:i') }}</td>
                                        <td>{{ $mov->descripcion }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">Sin movimientos registrados</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endisset
                </div>


            </div>
        </div>

    </div>

    <footer class="text-center mt-5">
        <small class="text-muted">© 4NLogística — Área de Finanzas</small>
    </footer>
</div>
@endsection
