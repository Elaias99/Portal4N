@extends('layouts.app')

@vite('resources/css/cuentas-cobrar.css')

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | Helpers de presentación
    |--------------------------------------------------------------------------
    */
    $gruposCesiones = collect($cesionesPorMes ?? $operacionesPorMes ?? []);

    $formatoMonto = function ($valor) {
        return '$' . number_format((int) ($valor ?? 0), 0, ',', '.');
    };

    $formatoFecha = function ($fecha) {
        return $fecha ? $fecha->format('d-m-Y') : '—';
    };

    $mostrarEstado = function ($estado) {
        if (!$estado) {
            return '—';
        }

        return $estado === 'Factory'
            ? 'Factoring'
            : $estado;
    };

    $textoPlural = function ($cantidad, $singular, $plural) {
        return ((int) $cantidad === 1) ? $singular : $plural;
    };
@endphp

<div class="container-fluid cc" style="max-width: 100%;">

    <x-finanzas.header
        :back-route="route('cobranzas.documentos')"
        title="Cesiones de Factoring"
    />

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-3">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger shadow-sm mb-3">
            {{ session('error') }}
        </div>
    @endif

    <x-finanzas.top-section>
        <x-slot:filters>
            <x-finanzas.filters-card>
                <form method="GET" action="{{ route('factoring.index') }}">
                    <div class="row g-3 align-items-end">

                        <div class="col-md-2">
                            <label class="form-label small text-muted">Mes de operación</label>
                            <input type="month"
                                   name="mes_operacion"
                                   class="form-control form-control-sm"
                                   value="{{ request('mes_operacion') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small text-muted">N° Cesión</label>
                            <input type="text"
                                   name="cesion"
                                   class="form-control form-control-sm"
                                   value="{{ request('cesion') }}"
                                   placeholder="Buscar cesión">
                        </div>

                        <div class="col-md-1">
                            <label class="form-label small text-muted">Folio</label>
                            <input type="text"
                                   name="folio"
                                   class="form-control form-control-sm"
                                   value="{{ request('folio') }}"
                                   placeholder="N°">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small text-muted">Razón Social</label>
                            <input type="text"
                                   name="razon_social"
                                   class="form-control form-control-sm"
                                   value="{{ request('razon_social') }}"
                                   placeholder="Buscar cliente">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small text-muted">RUT Cliente</label>
                            <input type="text"
                                   name="rut_cliente"
                                   class="form-control form-control-sm"
                                   value="{{ request('rut_cliente') }}"
                                   placeholder="Buscar RUT">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small text-muted">Empresa</label>
                            <select name="empresa_id" class="form-select form-select-sm">
                                <option value="">Todas</option>

                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}"
                                        {{ (string) request('empresa_id') === (string) $empresa->id ? 'selected' : '' }}>
                                        {{ $empresa->Nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small text-muted">Entidad Factoring / Banco</label>
                            <select name="banco_id" class="form-select form-select-sm">
                                <option value="">Todas</option>

                                @foreach($bancos as $banco)
                                    <option value="{{ $banco->id }}"
                                        {{ (string) request('banco_id') === (string) $banco->id ? 'selected' : '' }}>
                                        {{ $banco->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('factoring.index') }}"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle"></i>
                            Limpiar
                        </a>

                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-search"></i>
                            Buscar
                        </button>
                    </div>
                </form>
            </x-finanzas.filters-card>
        </x-slot:filters>

        <x-slot:actions>
            <x-finanzas.mass-actions-card title="Acciones">
                <a href="{{ route('cobranzas.documentos') }}"
                   class="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-arrow-left"></i>
                    <span>Volver a Cuentas por Cobrar</span>
                </a>
            </x-finanzas.mass-actions-card>
        </x-slot:actions>
    </x-finanzas.top-section>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <span class="fw-bold">Listado de Cesiones de Factoring</span>
                <small class="text-muted d-block">
                    Vista resumida con formato tabular para revisión financiera.
                </small>
            </div>
        </div>

        <div class="card-body p-0">

            @forelse($gruposCesiones as $grupoMes)
                @php
                    $cesionesDelMes = collect($grupoMes['cesiones'] ?? []);
                @endphp

                <div class="bg-light border-bottom px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <strong class="text-uppercase">
                        {{ $grupoMes['mes_etiqueta'] ?? 'Sin fecha de operación' }}
                    </strong>

                    <small class="text-muted">
                        {{ $cesionesDelMes->count() }}
                        {{ $textoPlural($cesionesDelMes->count(), 'cesión registrada', 'cesiones registradas') }}
                    </small>
                </div>

                <x-finanzas.plain-table>
                    <thead>
                        <tr>
                            <th>N° Cesión</th>
                            <th>Banco / Factoring</th>
                            <th>Fecha inicial</th>
                            <th>Último mov.</th>
                            <th class="text-center">Docs.</th>
                            <th class="text-end">Monto docto.</th>
                            <th class="text-end">Monto anticipado</th>
                            <th class="text-end">Dif. precio</th>
                            <th class="text-end">Comisión</th>
                            <th class="text-end">Monto a recibir</th>
                            <th>Usuario</th>
                            <th class="text-center">Detalle</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($cesionesDelMes as $cesionItem)
                            @php
                                $collapseId = 'docs-factoring-' . md5($cesionItem['clave_cesion'] ?? (($cesionItem['cesion'] ?? 'sin-cesion') . '-' . ($cesionItem['banco']?->id ?? 'sin-banco')));

                                /*
                                |--------------------------------------------------------------------------
                                | En esta vista se muestra cantidad_documentos, no solo únicos.
                                | Esto evita que una cesión con movimiento posterior sobre un folio
                                | parezca tener menos registros de los que realmente tiene.
                                |--------------------------------------------------------------------------
                                */
                                $cantidadDocumentos = (int) ($cesionItem['cantidad_documentos'] ?? 0);
                            @endphp

                            <tr>
                                <td class="fw-bold text-nowrap">
                                    {{ $cesionItem['cesion'] ?? '—' }}

                                    @if(($cesionItem['cantidad_movimientos'] ?? 0) > 1)
                                        <span class="badge bg-light text-dark border ms-1"
                                              title="Esta cesión tiene movimientos posteriores registrados">
                                            {{ $cesionItem['cantidad_movimientos'] }} mov.
                                        </span>
                                    @endif
                                </td>

                                <td class="text-nowrap">
                                    {{ $cesionItem['banco']?->nombre ?? 'Sin entidad' }}
                                </td>

                                <td class="text-nowrap">
                                    {{ $formatoFecha($cesionItem['fecha_inicio'] ?? null) }}
                                </td>

                                <td class="text-nowrap">
                                    {{ $formatoFecha($cesionItem['fecha_ultimo_movimiento'] ?? null) }}
                                </td>

                                <td class="text-center fw-semibold">
                                    {{ $cantidadDocumentos }}
                                </td>

                                <td class="text-end">
                                    {{ $formatoMonto($cesionItem['monto_documentos'] ?? 0) }}
                                </td>

                                <td class="text-end">
                                    {{ $formatoMonto($cesionItem['monto_anticipado'] ?? 0) }}
                                </td>

                                <td class="text-end">
                                    {{ $formatoMonto($cesionItem['diferencia_precio'] ?? 0) }}
                                </td>

                                <td class="text-end">
                                    {{ $formatoMonto($cesionItem['comision_total'] ?? 0) }}
                                </td>

                                <td class="text-end fw-bold text-success">
                                    {{ $formatoMonto($cesionItem['monto_a_recibir'] ?? 0) }}
                                </td>

                                <td class="text-nowrap">
                                    {{ $cesionItem['usuario']?->name ?? '—' }}
                                </td>

                                <td class="text-center">
                                    <button class="btn btn-outline-primary btn-sm"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#{{ $collapseId }}"
                                            aria-expanded="false"
                                            aria-controls="{{ $collapseId }}">
                                        Ver docs.
                                        <span class="badge bg-light text-primary border ms-1">
                                            {{ $cantidadDocumentos }}
                                        </span>
                                    </button>
                                </td>
                            </tr>

                            <tr class="collapse" id="{{ $collapseId }}">
                                <td colspan="12" class="bg-light p-2">
                                    <div class="border rounded bg-white overflow-hidden">
                                        <div class="px-3 py-2 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <strong class="small">
                                                Documentos asociados a la cesión N° {{ $cesionItem['cesion'] ?? '—' }}
                                            </strong>

                                            <small class="text-muted">
                                                {{ $cantidadDocumentos }}
                                                {{ $textoPlural($cantidadDocumentos, 'registro', 'registros') }}
                                            </small>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0 align-middle">
                                                <thead class="table-light">
                                                    <tr class="small text-muted">
                                                        <th>Folio</th>
                                                        <th>Empresa</th>
                                                        <th>Tipo documento</th>
                                                        <th>Cliente</th>
                                                        <th>RUT Cliente</th>
                                                        <th class="text-end">Monto cedido</th>
                                                        <th class="text-end">Monto anticipado</th>
                                                        <th class="text-end">Dif. precio</th>
                                                        <th>Estado</th>
                                                        <th class="text-center">Detalle</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    @foreach(collect($cesionItem['documentos'] ?? []) as $factory)
                                                        @php($documento = $factory->documentoFinanciero)

                                                        <tr>
                                                            <td class="fw-semibold">
                                                                {{ $documento?->folio ?? '—' }}
                                                            </td>

                                                            <td class="text-nowrap">
                                                                {{ $documento?->empresa?->Nombre ?? '—' }}
                                                            </td>

                                                            <td title="{{ $documento?->tipoDocumento?->nombre }}">
                                                                {{ \Illuminate\Support\Str::limit($documento?->tipoDocumento?->nombre ?? '—', 22) }}
                                                            </td>

                                                            <td class="text-nowrap">
                                                                {{ $documento?->razon_social ?? '—' }}
                                                            </td>

                                                            <td class="text-nowrap">
                                                                {{ $documento?->rut_cliente ?? '—' }}
                                                            </td>

                                                            <td class="text-end">
                                                                {{ $formatoMonto($factory->monto ?? 0) }}
                                                            </td>

                                                            <td class="text-end">
                                                                {{ $formatoMonto($factory->saldo_liquido ?? 0) }}
                                                            </td>

                                                            <td class="text-end fw-semibold">
                                                                {{ $formatoMonto($factory->diferencia_precio ?? 0) }}
                                                            </td>

                                                            <td>
                                                                {{ $mostrarEstado($documento?->status ?? null) }}
                                                            </td>

                                                            <td class="text-center">
                                                                @if($documento)
                                                                    <a href="{{ route('documentos.detalles', $documento->id) }}"
                                                                       class="btn btn-outline-primary btn-sm">
                                                                        Ver
                                                                    </a>
                                                                @else
                                                                    —
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        @if(($cesionItem['cantidad_movimientos'] ?? 0) > 1)
                                            <div class="px-3 py-2 border-top small text-muted">
                                                Esta cesión contiene {{ $cesionItem['cantidad_movimientos'] }} movimientos.
                                                La fecha “Último mov.” corresponde al registro más reciente asociado a la cesión.
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-finanzas.plain-table>

            @empty
                <div class="text-center text-muted py-5">
                    No existen cesiones de Factoring para los filtros seleccionados.
                </div>
            @endforelse

        </div>
    </div>

    @if($paginadorOperaciones->hasPages())
        <div class="mt-3 d-flex justify-content-center">
            {{ $paginadorOperaciones->links('pagination::bootstrap-4') }}
        </div>
    @endif

</div>

@endsection