@extends('layouts.app')

@vite('resources/css/cuentas-cobrar.css')

@section('content')

<div class="container-fluid cc" style="max-width: 100%;">

    {{-- ENCABEZADO --}}
    <x-finanzas.header
        :back-route="route('cobranzas.documentos')"
        title="Registros de Factoring"
    />

    {{-- MENSAJES --}}
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

    {{-- FILTROS Y ACCIONES --}}
    <x-finanzas.top-section>
        <x-slot:filters>
            <x-finanzas.filters-card>

                <form method="GET" action="{{ route('factoring.index') }}">
                    <div class="row g-3 align-items-end">

                        {{-- Mes operación --}}
                        <div class="col-md-2">
                            <label class="form-label small text-muted">
                                Mes de operación
                            </label>

                            <input type="month"
                                   name="mes_operacion"
                                   class="form-control form-control-sm"
                                   value="{{ request('mes_operacion') }}">
                        </div>

                        {{-- Cesión --}}
                        <div class="col-md-2">
                            <label class="form-label small text-muted">
                                N° Cesión
                            </label>

                            <input type="text"
                                   name="cesion"
                                   class="form-control form-control-sm"
                                   value="{{ request('cesion') }}"
                                   placeholder="Buscar cesión">
                        </div>

                        {{-- Folio --}}
                        <div class="col-md-1">
                            <label class="form-label small text-muted">
                                Folio
                            </label>

                            <input type="text"
                                   name="folio"
                                   class="form-control form-control-sm"
                                   value="{{ request('folio') }}"
                                   placeholder="N°">
                        </div>

                        {{-- Razón Social --}}
                        <div class="col-md-2">
                            <label class="form-label small text-muted">
                                Razón Social
                            </label>

                            <input type="text"
                                   name="razon_social"
                                   class="form-control form-control-sm"
                                   value="{{ request('razon_social') }}"
                                   placeholder="Buscar cliente">
                        </div>

                        {{-- RUT Cliente --}}
                        <div class="col-md-2">
                            <label class="form-label small text-muted">
                                RUT Cliente
                            </label>

                            <input type="text"
                                   name="rut_cliente"
                                   class="form-control form-control-sm"
                                   value="{{ request('rut_cliente') }}"
                                   placeholder="Buscar RUT">
                        </div>

                        {{-- Empresa --}}
                        <div class="col-md-3">
                            <label class="form-label small text-muted">
                                Empresa
                            </label>

                            <select name="empresa_id"
                                    class="form-select form-select-sm">
                                <option value="">Todas</option>

                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}"
                                        {{ (string) request('empresa_id') === (string) $empresa->id ? 'selected' : '' }}>
                                        {{ $empresa->Nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Entidad Factoring / Banco --}}
                        <div class="col-md-3">
                            <label class="form-label small text-muted">
                                Entidad Factoring / Banco
                            </label>

                            <select name="banco_id"
                                    class="form-select form-select-sm">
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

                        <button type="submit"
                                class="btn btn-primary btn-sm">
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

    {{-- LISTADO DE OPERACIONES FACTORING --}}
    <div class="card border-0 shadow-sm mt-3">

        <div class="card-header bg-light">
            <div>
                <span class="fw-bold">
                    Factoring registrados
                </span>

                <small class="text-muted d-block">
                    Resumen de operaciones registradas para documentos financieros de Cuentas por Cobrar.
                </small>
            </div>
        </div>

        <div class="card-body p-0">

            @forelse($operacionesPorMes as $grupoMes)

                {{-- SEPARADOR DEL MES --}}
                <div class="px-3 py-2 bg-light border-bottom border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar3 text-muted"></i>

                        <span class="fw-bold text-uppercase small">
                            {{ $grupoMes['mes_etiqueta'] }}
                        </span>
                    </div>

                    <span class="small text-muted">
                        {{ $grupoMes['operaciones']->count() }}
                        {{ $grupoMes['operaciones']->count() === 1 ? 'operación registrada' : 'operaciones registradas' }}
                    </span>
                </div>

                {{-- OPERACIONES DEL MES --}}
                @foreach($grupoMes['operaciones'] as $operacion)

                    @php
                        $tieneDetalleNuevo = $operacion['documentos']->every(function ($factory) {
                            return $factory->monto_no_anticipado !== null
                                && $factory->diferencia_precio !== null;
                        });

                        $tieneResumenNuevo = $tieneDetalleNuevo
                            && $operacion['comision_total'] !== null
                            && $operacion['monto_a_recibir'] !== null;
                    @endphp

                    <div class="{{ !$loop->last ? 'border-bottom' : '' }}">

                        {{-- IDENTIFICACIÓN DE LA OPERACIÓN --}}
                        <div class="px-3 pt-3 pb-2 d-flex flex-wrap justify-content-between align-items-start gap-3">

                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold">
                                        Cesión N° {{ $operacion['cesion'] ?? '—' }}
                                    </span>

                                    <span class="badge bg-light text-dark border">
                                        {{ $operacion['cantidad_documentos'] }}
                                        {{ $operacion['cantidad_documentos'] === 1 ? 'documento' : 'documentos' }}
                                    </span>
                                </div>

                                <small class="text-muted d-block mt-1">
                                    Operación de Factoring registrada en Cuentas por Cobrar.
                                </small>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-3 small text-muted">

                                <span class="d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-calendar-event"></i>
                                    {{ $operacion['fecha_factory']
                                        ? $operacion['fecha_factory']->format('d-m-Y')
                                        : 'Sin fecha' }}
                                </span>

                                <span class="d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-bank"></i>
                                    {{ $operacion['banco']?->nombre ?? 'Sin entidad' }}
                                </span>

                                <span class="d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-person"></i>
                                    {{ $operacion['usuario']?->name ?? 'Sin usuario' }}
                                </span>
                            </div>
                        </div>

                        {{-- ALERTAS DE TRAZABILIDAD --}}
                        @if(!$operacion['valores_globales_consistentes'])
                            <div class="px-3">
                                <div class="alert alert-warning py-2 px-3 small mb-2">
                                    Esta operación contiene valores distintos de Comisión Total o
                                    Monto a Recibir entre sus documentos registrados. Revisa su trazabilidad.
                                </div>
                            </div>
                        @endif

                        @unless($tieneResumenNuevo)
                            <div class="px-3">
                                <div class="alert alert-secondary py-2 px-3 small mb-2">
                                    Este registro corresponde a una estructura anterior de Factoring y
                                    no contiene el nuevo desglose completo de la operación.
                                </div>
                            </div>
                        @endunless

                        {{-- RESUMEN FINANCIERO SIEMPRE VISIBLE --}}
                        <div class="px-3 pb-2">
                            <div class="small fw-bold text-muted mb-2">
                                Resumen de la operación
                            </div>

                            <div class="table-responsive border rounded">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="small text-muted">
                                            <th class="text-center text-nowrap">
                                                Cant. Docto.
                                            </th>

                                            <th class="text-end text-nowrap">
                                                Monto Docto.
                                            </th>

                                            <th class="text-end text-nowrap">
                                                Monto Anticipado
                                            </th>

                                            <th class="text-end text-nowrap">
                                                Diferencia de Precio
                                            </th>

                                            <th class="text-end text-nowrap">
                                                Monto Líquido
                                            </th>

                                            <th class="text-end text-nowrap">
                                                Precio de Compra
                                            </th>

                                            <th class="text-end text-nowrap">
                                                Comisión Total (1)
                                            </th>

                                            <th class="text-end text-nowrap">
                                                Monto a Recibir
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr>
                                            <td class="text-center fw-semibold">
                                                {{ $operacion['cantidad_documentos'] }}
                                            </td>

                                            <td class="text-end text-nowrap fw-semibold">
                                                ${{ number_format((int) $operacion['monto_documentos'], 0, ',', '.') }}
                                            </td>

                                            <td class="text-end text-nowrap fw-semibold">
                                                ${{ number_format((int) $operacion['monto_anticipado'], 0, ',', '.') }}
                                            </td>

                                            <td class="text-end text-nowrap fw-semibold">
                                                @if($tieneDetalleNuevo)
                                                    ${{ number_format((int) $operacion['diferencia_precio'], 0, ',', '.') }}
                                                @else
                                                    —
                                                @endif
                                            </td>

                                            <td class="text-end text-nowrap fw-semibold">
                                                @if($tieneDetalleNuevo)
                                                    ${{ number_format((int) $operacion['monto_liquido'], 0, ',', '.') }}
                                                @else
                                                    —
                                                @endif
                                            </td>

                                            <td class="text-end text-nowrap fw-semibold">
                                                @if($tieneDetalleNuevo)
                                                    ${{ number_format((int) $operacion['precio_compra'], 0, ',', '.') }}
                                                @else
                                                    —
                                                @endif
                                            </td>

                                            <td class="text-end text-nowrap fw-semibold">
                                                @if($operacion['comision_total'] !== null)
                                                    ${{ number_format((int) $operacion['comision_total'], 0, ',', '.') }}
                                                @else
                                                    —
                                                @endif
                                            </td>

                                            <td class="text-end text-nowrap fw-bold text-success">
                                                @if($operacion['monto_a_recibir'] !== null)
                                                    ${{ number_format((int) $operacion['monto_a_recibir'], 0, ',', '.') }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- DOCUMENTOS ASOCIADOS OCULTOS INICIALMENTE --}}
                        <details class="px-3 pb-3">

                            <summary class="d-inline-flex align-items-center gap-2 small text-primary"
                                     style="cursor: pointer; list-style: none;">
                                <i class="bi bi-chevron-down"></i>
                                <span>Ver documentos asociados</span>
                                <span class="badge bg-light text-primary border">
                                    {{ $operacion['cantidad_documentos'] }}
                                </span>
                            </summary>

                            <div class="mt-3 border rounded overflow-hidden">

                                <div class="px-3 py-2 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <span class="small fw-bold">
                                        Documentos asociados a la cesión N° {{ $operacion['cesion'] ?? '—' }}
                                    </span>

                                    <span class="small text-muted">
                                        {{ $operacion['cantidad_documentos'] }}
                                        {{ $operacion['cantidad_documentos'] === 1 ? 'registro' : 'registros' }}
                                    </span>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr class="small text-muted">
                                                <th class="text-nowrap">
                                                    Folio
                                                </th>

                                                <th class="text-nowrap">
                                                    Empresa
                                                </th>

                                                <th class="text-nowrap">
                                                    Tipo Documento
                                                </th>

                                                <th class="text-nowrap">
                                                    Cliente
                                                </th>

                                                <th class="text-nowrap">
                                                    RUT Cliente
                                                </th>

                                                <th class="text-end text-nowrap">
                                                    Monto
                                                </th>

                                                <th class="text-end text-nowrap">
                                                    Monto No Anticipado
                                                </th>

                                                <th class="text-end text-nowrap">
                                                    Monto Líquido
                                                </th>

                                                <th class="text-end text-nowrap">
                                                    Diferencia de Precio
                                                </th>

                                                <th class="text-nowrap">
                                                    Estado manual
                                                </th>

                                                <th class="text-nowrap">
                                                    Estado original
                                                </th>

                                                <th class="text-center text-nowrap">
                                                    Detalle
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach($operacion['documentos'] as $factory)
                                                @php
                                                    $documento = $factory->documentoFinanciero;
                                                @endphp

                                                <tr>
                                                    <td class="text-nowrap fw-semibold">
                                                        {{ $documento?->folio ?? '—' }}
                                                    </td>

                                                    <td class="text-nowrap">
                                                        {{ $documento?->empresa?->Nombre ?? 'Sin empresa' }}
                                                    </td>

                                                    <td class="text-nowrap">
                                                        {{ $documento?->tipoDocumento?->nombre ?? '—' }}
                                                    </td>

                                                    <td class="text-nowrap">
                                                        {{ $documento?->razon_social ?? '—' }}
                                                    </td>

                                                    <td class="text-nowrap">
                                                        {{ $documento?->rut_cliente ?? '—' }}
                                                    </td>

                                                    <td class="text-end text-nowrap">
                                                        ${{ number_format((int) ($factory->monto ?? 0), 0, ',', '.') }}
                                                    </td>

                                                    <td class="text-end text-nowrap">
                                                        @if($factory->monto_no_anticipado !== null)
                                                            ${{ number_format((int) $factory->monto_no_anticipado, 0, ',', '.') }}
                                                        @else
                                                            —
                                                        @endif
                                                    </td>

                                                    <td class="text-end text-nowrap">
                                                        @if($factory->saldo_liquido !== null)
                                                            ${{ number_format((int) $factory->saldo_liquido, 0, ',', '.') }}
                                                        @else
                                                            —
                                                        @endif
                                                    </td>

                                                    <td class="text-end text-nowrap">
                                                        @if($factory->diferencia_precio !== null)
                                                            ${{ number_format((int) $factory->diferencia_precio, 0, ',', '.') }}
                                                        @else
                                                            —
                                                        @endif
                                                    </td>

                                                    <td class="text-nowrap">
                                                        {{ $documento?->status ?? '—' }}
                                                    </td>

                                                    <td class="text-nowrap">
                                                        {{ $documento?->status_original ?? '—' }}
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
                            </div>
                        </details>

                    </div>

                @endforeach

            @empty

                <div class="text-center text-muted py-5">
                    No existen operaciones de Factoring para los filtros seleccionados.
                </div>

            @endforelse

        </div>
    </div>

    {{-- PAGINACIÓN POR OPERACIÓN COMPLETA --}}
    @if($paginadorOperaciones->hasPages())
        <div class="mt-3 d-flex justify-content-center">
            {{ $paginadorOperaciones->links('pagination::bootstrap-4') }}
        </div>
    @endif

</div>

@endsection