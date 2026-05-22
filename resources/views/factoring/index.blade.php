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

    {{-- FILTROS --}}
    <x-finanzas.top-section>
        <x-slot:filters>
            <x-finanzas.filters-card>

                <form method="GET" action="{{ route('factoring.index') }}">
                    <div class="row g-3 align-items-end">

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

                        <div class="col-md-2">
                            <label class="form-label small text-muted">
                                Empresa
                            </label>

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
                            <label class="form-label small text-muted">
                                Entidad Factoring / Banco
                            </label>

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

                        <div class="col-md-2">
                            <label class="form-label small text-muted">
                                Fecha operación desde
                            </label>

                            <input type="date"
                                   name="fecha_inicio"
                                   class="form-control form-control-sm"
                                   value="{{ request('fecha_inicio') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small text-muted">
                                Fecha operación hasta
                            </label>

                            <input type="date"
                                   name="fecha_fin"
                                   class="form-control form-control-sm"
                                   value="{{ request('fecha_fin') }}">
                        </div>

                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('factoring.index') }}"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>

                        <button type="submit"
                                class="btn btn-primary btn-sm">
                            <i class="bi bi-search"></i> Buscar
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

    {{-- TABLA --}}
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-light">
            <div>
                <span class="fw-bold">Factoring registrados</span>
                <small class="text-muted d-block">
                    Información almacenada para documentos financieros de Cuentas por Cobrar.
                </small>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th class="text-nowrap">Empresa</th>
                            <th class="text-nowrap">N° Cesión</th>
                            <th class="text-nowrap">Folio</th>
                            <th class="text-nowrap">Tipo Documento</th>
                            <th class="text-nowrap">Cliente</th>
                            <th class="text-nowrap">RUT Cliente</th>
                            <th class="text-nowrap">Fecha operación</th>
                            <th class="text-nowrap">Entidad Factoring / Banco</th>
                            <th class="text-nowrap">RUT Factoring registrado</th>
                            <th class="text-end text-nowrap">Monto registrado</th>
                            <th class="text-end text-nowrap">Saldo líquido registrado</th>
                            <th class="text-end text-nowrap">Diferencia registrada</th>
                            <th class="text-nowrap">Estado manual</th>
                            <th class="text-nowrap">Estado original</th>
                            <th class="text-nowrap">Usuario</th>
                            <th class="text-center text-nowrap">Detalle</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($factories as $factory)
                            @php
                                $documento = $factory->documentoFinanciero;
                            @endphp

                            <tr>
                                <td class="text-nowrap">
                                    {{ $documento?->empresa?->Nombre ?? 'Sin empresa' }}
                                </td>

                                <td class="text-nowrap fw-semibold">
                                    {{ $factory->cesion ?? '—' }}
                                </td>

                                <td class="text-nowrap">
                                    {{ $documento?->folio ?? '—' }}
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

                                <td class="text-nowrap">
                                    {{ $factory->fecha_factory
                                        ? $factory->fecha_factory->format('d-m-Y')
                                        : '—' }}
                                </td>

                                <td class="text-nowrap">
                                    {{ $factory->banco?->nombre ?? '—' }}
                                </td>

                                <td class="text-nowrap">
                                    {{ $factory->rut_factory ?? '—' }}
                                </td>

                                <td class="text-end text-nowrap">
                                    ${{ number_format((int) ($factory->monto ?? 0), 0, ',', '.') }}
                                </td>

                                <td class="text-end text-nowrap">
                                    ${{ number_format((int) ($factory->saldo_liquido ?? 0), 0, ',', '.') }}
                                </td>

                                <td class="text-end text-nowrap">
                                    ${{ number_format((int) ($factory->diferencia ?? 0), 0, ',', '.') }}
                                </td>

                                <td class="text-nowrap">
                                    {{ $documento?->status ?? '—' }}
                                </td>

                                <td class="text-nowrap">
                                    {{ $documento?->status_original ?? '—' }}
                                </td>

                                <td class="text-nowrap">
                                    {{ $factory->usuario?->name ?? '—' }}
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
                        @empty
                            <tr>
                                <td colspan="16"
                                    class="text-center text-muted py-4">
                                    No existen registros de Factoring para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- PAGINACIÓN --}}
    <div class="mt-3 d-flex justify-content-center">
        {{ $factories->links('pagination::bootstrap-4') }}
    </div>

</div>

@endsection