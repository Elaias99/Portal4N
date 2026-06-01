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

    $normalizarFecha = function ($fecha) {
        if (!$fecha) {
            return null;
        }

        if ($fecha instanceof \Carbon\CarbonInterface) {
            return $fecha;
        }

        try {
            return \Carbon\Carbon::parse($fecha);
        } catch (\Throwable $e) {
            return null;
        }
    };

    $formatoFecha = function ($fecha) use ($normalizarFecha) {
        $fechaNormalizada = $normalizarFecha($fecha);

        return $fechaNormalizada
            ? $fechaNormalizada->format('d-m-Y')
            : '—';
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

    /*
    |--------------------------------------------------------------------------
    | Movimiento base / comprobante de la cesión
    |--------------------------------------------------------------------------
    | Para efectos de presentación financiera, la fila principal se mantiene como
    | resumen del comprobante base de la cesión. Los movimientos posteriores se
    | muestran dentro del detalle del documento correspondiente.
    |--------------------------------------------------------------------------
    */
    $obtenerMovimientoComprobante = function ($cesionItem) {
        $movimientos = collect($cesionItem['movimientos'] ?? []);

        if ($movimientos->isEmpty()) {
            return null;
        }

        return $movimientos
            ->sortBy(function ($movimiento) {
                return $movimiento['fecha_orden'] ?? '0000-00-00';
            })
            ->first();
    };

    /*
    |--------------------------------------------------------------------------
    | Consolidar documentos para compatibilidad
    |--------------------------------------------------------------------------
    | Si el controlador ya envía documentos_detalle, la vista usará esa estructura.
    | Este helper queda como respaldo para estructuras antiguas basadas en
    | documentos/registros Factory.
    |--------------------------------------------------------------------------
    */
    $consolidarDocumentosCesion = function ($documentos) use ($normalizarFecha) {
        return collect($documentos ?? [])
            ->groupBy(function ($factory) {
                return $factory->documento_financiero_id
                    ?: 'factory-sin-documento-' . $factory->id;
            })
            ->map(function ($registrosDocumento) use ($normalizarFecha) {
                $registrosOrdenados = collect($registrosDocumento)
                    ->sortBy(function ($factory) use ($normalizarFecha) {
                        $fecha = $normalizarFecha($factory->fecha_factory ?? null);

                        $fechaOrden = $fecha
                            ? $fecha->format('Y-m-d')
                            : '0000-00-00';

                        return $fechaOrden . '-' . str_pad((string) $factory->id, 12, '0', STR_PAD_LEFT);
                    })
                    ->values();

                $factoryComprobante = $registrosOrdenados->first();
                $factoryUltimo = $registrosOrdenados->last();

                $documento = $factoryUltimo?->documentoFinanciero
                    ?? $factoryComprobante?->documentoFinanciero;

                $movimientosDocumento = $registrosOrdenados
                    ->map(function ($factory, $index) {
                        return [
                            'numero_movimiento' => $index + 1,
                            'factory_id' => $factory->id,
                            'fecha_factory' => $factory->fecha_factory,
                            'monto_cedido' => (int) ($factory->monto ?? 0),
                            'monto_anticipado' => (int) ($factory->saldo_liquido ?? 0),
                            'monto_no_anticipado' => (int) ($factory->monto_no_anticipado ?? 0),
                            'diferencia_precio' => (int) ($factory->diferencia_precio ?? 0),
                            'comision_total' => (int) ($factory->comision_total ?? 0),
                            'monto_a_recibir' => (int) ($factory->monto_a_recibir ?? 0),
                            'registro' => $factory,
                        ];
                    })
                    ->values();

                /*
                |--------------------------------------------------------------------------
                | Valores base del documento
                |--------------------------------------------------------------------------
                | No se suman los movimientos posteriores al monto original. Los
                | movimientos posteriores quedan visibles en el desglose interno.
                |--------------------------------------------------------------------------
                */
                return [
                    'factory' => $factoryUltimo ?? $factoryComprobante,
                    'factory_base' => $factoryComprobante,
                    'factory_ultimo' => $factoryUltimo,
                    'documento' => $documento,
                    'registros' => $registrosOrdenados,

                    'cantidad_movimientos' => $registrosOrdenados->count(),
                    'cantidad_movimientos_documento' => $registrosOrdenados->count(),

                    'monto_documento' => (int) ($factoryComprobante?->monto ?? 0),
                    'monto_original' => (int) ($factoryComprobante?->monto ?? 0),
                    'monto_no_anticipado_base' => (int) ($factoryComprobante?->monto_no_anticipado ?? 0),
                    'monto_no_anticipado' => (int) ($factoryComprobante?->monto_no_anticipado ?? 0),
                    'monto_anticipado_base' => (int) ($factoryComprobante?->saldo_liquido ?? 0),
                    'monto_anticipado' => (int) ($factoryComprobante?->saldo_liquido ?? 0),
                    'diferencia_precio_base' => (int) ($factoryComprobante?->diferencia_precio ?? 0),
                    'diferencia_precio' => (int) ($factoryComprobante?->diferencia_precio ?? 0),

                    'ultimo_monto_cedido' => (int) ($factoryUltimo?->monto ?? 0),
                    'ultimo_monto_anticipado' => (int) ($factoryUltimo?->saldo_liquido ?? 0),
                    'ultimo_monto_no_anticipado' => (int) ($factoryUltimo?->monto_no_anticipado ?? 0),
                    'ultima_diferencia_precio' => (int) ($factoryUltimo?->diferencia_precio ?? 0),

                    'movimientos' => $movimientosDocumento,
                ];
            })
            ->sortBy(function ($item) {
                $folio = (string) ($item['documento']?->folio ?? '');

                return str_pad($folio, 20, '0', STR_PAD_LEFT);
            })
            ->values();
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
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
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

                <div class="factoring-month-header px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
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
                                $collapseId = 'docs-factoring-' . md5(
                                    $cesionItem['clave_cesion']
                                        ?? (($cesionItem['cesion'] ?? 'sin-cesion') . '-' . ($cesionItem['banco']?->id ?? 'sin-banco'))
                                );

                                $movimientosCesion = collect($cesionItem['movimientos'] ?? []);
                                $movimientoComprobante = $obtenerMovimientoComprobante($cesionItem);

                                /*
                                |--------------------------------------------------------------------------
                                | Resumen visible de la cesión
                                |--------------------------------------------------------------------------
                                | Se usa la estructura preparada por el controlador. Si existe
                                | movimiento base, se mantiene como respaldo visual.
                                |--------------------------------------------------------------------------
                                */
                                $montoDocumentosResumen = (int) (
                                    $cesionItem['monto_documentos']
                                        ?? $movimientoComprobante['monto_documentos']
                                        ?? 0
                                );

                                $montoAnticipadoResumen = (int) (
                                    $cesionItem['monto_anticipado']
                                        ?? $movimientoComprobante['monto_anticipado']
                                        ?? 0
                                );

                                $diferenciaPrecioResumen = (int) (
                                    $cesionItem['diferencia_precio']
                                        ?? $movimientoComprobante['diferencia_precio']
                                        ?? 0
                                );

                                $comisionTotalResumen = (int) (
                                    $cesionItem['comision_total']
                                        ?? $movimientoComprobante['comision_total']
                                        ?? 0
                                );

                                $montoARecibirResumen = (int) (
                                    $cesionItem['monto_a_recibir']
                                        ?? $movimientoComprobante['monto_a_recibir']
                                        ?? 0
                                );

                                $fechaInicioResumen = $cesionItem['fecha_inicio']
                                    ?? $movimientoComprobante['fecha_factory']
                                    ?? null;

                                /*
                                |--------------------------------------------------------------------------
                                | Detalle visual por documento único
                                |--------------------------------------------------------------------------
                                | La prioridad es documentos_detalle porque allí vienen los
                                | movimientos reales por documento preparados desde el controller.
                                |--------------------------------------------------------------------------
                                */
                                $documentosDetalle = collect($cesionItem['documentos_detalle'] ?? []);

                                if ($documentosDetalle->isEmpty()) {
                                    $documentosDetalle = $consolidarDocumentosCesion($cesionItem['documentos'] ?? []);
                                }

                                $cantidadDocumentos = (int) (
                                    $cesionItem['cantidad_documentos_unicos']
                                        ?? $documentosDetalle->count()
                                );

                                $cantidadRegistrosFactory = (int) (
                                    $cesionItem['cantidad_documentos']
                                        ?? collect($cesionItem['documentos'] ?? [])->count()
                                );

                                $cantidadMovimientosCesion = (int) (
                                    $cesionItem['cantidad_movimientos']
                                        ?? $movimientosCesion->count()
                                );
                            @endphp

                            <tr class="factoring-row-main">
                                <td class="fw-bold text-nowrap">
                                    {{ $cesionItem['cesion'] ?? '—' }}

                                    @if($cantidadMovimientosCesion > 1)
                                        <span class="badge badge-movimientos ms-1"
                                              title="Esta cesión tiene movimientos posteriores registrados">
                                            {{ $cantidadMovimientosCesion }} mov.
                                        </span>
                                    @endif
                                </td>

                                <td class="text-nowrap">
                                    {{ $cesionItem['banco']?->nombre ?? 'Sin entidad' }}
                                </td>

                                <td class="text-nowrap">
                                    {{ $formatoFecha($fechaInicioResumen) }}
                                </td>

                                <td class="text-nowrap">
                                    {{ $formatoFecha($cesionItem['fecha_ultimo_movimiento'] ?? null) }}
                                </td>

                                <td class="text-center fw-semibold">
                                    {{ $cantidadDocumentos }}
                                </td>

                                <td class="text-end">
                                    {{ $formatoMonto($montoDocumentosResumen) }}
                                </td>

                                <td class="text-end">
                                    {{ $formatoMonto($montoAnticipadoResumen) }}
                                </td>

                                <td class="text-end">
                                    {{ $formatoMonto($diferenciaPrecioResumen) }}
                                </td>

                                <td class="text-end">
                                    {{ $formatoMonto($comisionTotalResumen) }}
                                </td>

                                <td class="text-end fw-bold text-success">
                                    {{ $formatoMonto($montoARecibirResumen) }}
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

                            <tr class="collapse factoring-row-open" id="{{ $collapseId }}">
                                <td colspan="12" class="p-2">
                                    <div class="factoring-detail-box overflow-hidden">
                                        <div class="px-3 py-2 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <strong class="small">
                                                Documentos asociados a la cesión N° {{ $cesionItem['cesion'] ?? '—' }}
                                            </strong>

                                            <small class="text-muted">
                                                {{ $cantidadDocumentos }}
                                                {{ $textoPlural($cantidadDocumentos, 'documento', 'documentos') }}
                                                únicos
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
                                                        <th class="text-end">Monto original</th>
                                                        <th class="text-end">No anticipado</th>
                                                        <th class="text-end">Monto anticipado</th>
                                                        <th class="text-end">Dif. precio</th>
                                                        <th>Estado</th>
                                                        <th class="text-center">Detalle</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    @foreach($documentosDetalle as $detalleDocumento)



                                                        @php
                                                            $factory = $detalleDocumento['factory']
                                                                ?? $detalleDocumento['factory_ultimo']
                                                                ?? $detalleDocumento['factory_base']
                                                                ?? null;

                                                            $documento = $detalleDocumento['documento'] ?? null;

                                                            $movimientosDocumento = collect($detalleDocumento['movimientos'] ?? []);

                                                            $cantidadMovimientosDocumento = (int) (
                                                                $detalleDocumento['cantidad_movimientos_documento']
                                                                    ?? $detalleDocumento['cantidad_movimientos']
                                                                    ?? $movimientosDocumento->count()
                                                            );

                                                            /*
                                                            |--------------------------------------------------------------------------
                                                            | Monto original
                                                            |--------------------------------------------------------------------------
                                                            | El monto original NO se acumula. El documento existe una sola vez.
                                                            |--------------------------------------------------------------------------
                                                            */
                                                            $montoOriginalDocumento = (int) (
                                                                $detalleDocumento['monto_documento']
                                                                    ?? $detalleDocumento['monto_original']
                                                                    ?? 0
                                                            );

                                                            /*
                                                            |--------------------------------------------------------------------------
                                                            | Valores acumulados por movimientos del mismo documento
                                                            |--------------------------------------------------------------------------
                                                            | Estos sí se suman, porque corresponden a movimientos Factoring reales
                                                            | aplicados sobre el mismo folio.
                                                            |--------------------------------------------------------------------------
                                                            */
                                                            $montoNoAnticipadoDocumento = (int) (
                                                                $detalleDocumento['total_monto_no_anticipado_movimientos']
                                                                    ?? (
                                                                        $movimientosDocumento->isNotEmpty()
                                                                            ? $movimientosDocumento->sum(fn ($movimiento) => (int) ($movimiento['monto_no_anticipado'] ?? 0))
                                                                            : ($detalleDocumento['monto_no_anticipado_base'] ?? $detalleDocumento['monto_no_anticipado'] ?? 0)
                                                                    )
                                                            );

                                                            $montoAnticipadoDocumento = (int) (
                                                                $detalleDocumento['total_monto_anticipado_movimientos']
                                                                    ?? (
                                                                        $movimientosDocumento->isNotEmpty()
                                                                            ? $movimientosDocumento->sum(fn ($movimiento) => (int) ($movimiento['monto_anticipado'] ?? 0))
                                                                            : ($detalleDocumento['monto_anticipado_base'] ?? $detalleDocumento['monto_anticipado'] ?? 0)
                                                                    )
                                                            );

                                                            $diferenciaPrecioDocumento = (int) (
                                                                $detalleDocumento['total_diferencia_precio_movimientos']
                                                                    ?? (
                                                                        $movimientosDocumento->isNotEmpty()
                                                                            ? $movimientosDocumento->sum(fn ($movimiento) => (int) ($movimiento['diferencia_precio'] ?? 0))
                                                                            : ($detalleDocumento['diferencia_precio_base'] ?? $detalleDocumento['diferencia_precio'] ?? 0)
                                                                    )
                                                            );
                                                        @endphp



                                                        <tr>
                                                            <td class="fw-semibold">
                                                                {{ $documento?->folio ?? '—' }}

                                                                @if($cantidadMovimientosDocumento > 1)
                                                                    <span class="badge badge-movimientos ms-1"
                                                                          title="Este documento tiene más de un registro Factoring dentro de la cesión. El desglose de movimientos se muestra debajo.">
                                                                        {{ $cantidadMovimientosDocumento }} mov.
                                                                    </span>
                                                                @endif
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
                                                                {{ $formatoMonto($montoOriginalDocumento) }}
                                                            </td>

                                                            <td class="text-end">
                                                                {{ $formatoMonto($montoNoAnticipadoDocumento) }}
                                                            </td>

                                                            <td class="text-end">
                                                                {{ $formatoMonto($montoAnticipadoDocumento) }}
                                                            </td>

                                                            <td class="text-end fw-semibold">
                                                                {{ $formatoMonto($diferenciaPrecioDocumento) }}
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

                                        <div class="px-3 py-2 border-top small text-muted">
                                            Esta cesión contiene
                                            <strong>{{ $cantidadMovimientosCesion }}</strong>
                                            {{ $textoPlural($cantidadMovimientosCesion, 'movimiento general', 'movimientos generales') }}
                                            y
                                            <strong>{{ $cantidadRegistrosFactory }}</strong>
                                            {{ $textoPlural($cantidadRegistrosFactory, 'registro Factoring', 'registros Factoring') }}.
                                            Los documentos se muestran una sola vez; si un documento tiene movimientos posteriores, el detalle de esos movimientos se muestra bajo su fila.
                                            La fecha “Último mov.” corresponde al registro más reciente asociado a la cesión.
                                        </div>
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