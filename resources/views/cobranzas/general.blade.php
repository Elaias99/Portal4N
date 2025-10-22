@extends('layouts.app')

@vite('resources/css/panel-finanzas.css')

@section('content')

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
