@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">

    {{-- ====== CABECERA ====== --}}
    <div class="text-center mb-4">
        <h2 class="fw-bold">Módulo Finanzas</h2>
        <p class="text-muted mb-0">Panel central de gestión — Documentos, abonos, cruces y movimientos</p>
    </div>

    {{-- ====== ACCESOS DIRECTOS ====== --}}
    <div class="row justify-content-center text-center mb-4">
        {{-- Documentos Financieros --}}
        <div class="col-md-3 mb-3">
            <a href="{{ route('cobranzas.documentos') }}" class="text-decoration-none text-dark">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <i class="fa fa-file-invoice-dollar fa-3x text-primary mb-2"></i>
                        <h6 class="fw-bold mb-1">Documentos Financieros</h6>
                        <p class="small mb-0">Gestión de facturas y notas de crédito</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Historial de Abonos --}}
        <div class="col-md-3 mb-3">
            <a href="{{ route('abonos.show') }}" class="text-decoration-none text-dark">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <i class="fa fa-coins fa-3x text-warning mb-2"></i>
                        <h6 class="fw-bold mb-1">Historial de Abonos</h6>
                        <p class="small mb-0">Ver todos los abonos del sistema</p>
                    </div>
                </div>
            </a>

        </div>

        {{-- Historial de Cruces --}}
        {{-- Historial de Cruces --}}
        <div class="col-md-3 mb-3">
            <a href="{{ route('cruces.show') }}" class="text-decoration-none text-dark">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <i class="fa fa-link fa-3x text-info mb-2"></i>
                        <h6 class="fw-bold mb-1">Historial de Cruces</h6>
                        <p class="small mb-0">Ver todos los cruces del sistema</p>
                    </div>
                </div>
            </a>
        </div>

        
    </div>

    {{-- ====== SECCIÓN PRINCIPAL ====== --}}
    <div class="row">
        {{-- === COLUMNA IZQUIERDA === --}}
        <div class="col-md-2 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <h6 class="fw-bold mb-3">Importar / Exportar documentos</h6>
                    <p class="small text-muted">Utiliza esta opción para importar nuevos documentos o exportar los existentes.</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('cobranzas.export') }}" class="btn btn-sm btn-outline-primary">Exportar</a>
                        {{-- <button class="btn btn-sm btn-outline-success" disabled>Importar</button> --}}
                    </div>
                </div>
            </div>
        </div>

        {{-- === COLUMNA CENTRAL (BUSCADOR + TABLA) === --}}
        <div class="col-md-8">
            {{-- ====== BUSCADOR ====== --}}
            <div class="card border-0 shadow-sm mb-4">
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
                            {{-- <div class="col-md-3 mb-3">
                                <label class="small text-muted">Estado</label>



                                <select name="status" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    <option value="Al día" {{ request('status')=='Al día'?'selected':'' }}>Al día</option>
                                    <option value="Vencido" {{ request('status')=='Vencido'?'selected':'' }}>Vencido</option>
                                    <option value="Pagado" {{ request('status')=='Pagado'?'selected':'' }}>Pagado</option>
                                </select>




                            </div> --}}
                            <div class="col-md-5 text-end mb-3">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ====== RESULTADOS ====== --}}
            @if(isset($documentos) && $documentos->count())
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle text-center">
                        <thead class="table-light">
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
                                        <span class="badge bg-{{ $doc->status == 'Vencido' ? 'danger' : 'success' }}">
                                            {{ $doc->status }}
                                        </span>
                                    </td>





                                    <td class="text-end">${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}</td>
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

        {{-- === COLUMNA DERECHA === --}}
        <div class="col-md-2 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <h6 class="fw-bold mb-3">Ayuda al usuario</h6>
                    <p class="small text-muted mb-2">
                        En esta sección puedes buscar facturas, abonos y cruces.  
                        Utiliza el buscador central para encontrar rápidamente por folio o razón social.
                    </p>
                    <p class="small text-muted">
                        Los resultados se mostrarán al centro y podrás revisar los movimientos anteriores.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center mt-5">
        <small class="text-muted">© 4NLogística — Área de Finanzas</small>
    </footer>
</div>
@endsection
