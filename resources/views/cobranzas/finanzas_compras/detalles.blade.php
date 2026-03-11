@extends('layouts.app')

@section('content')
<div class="container mt-4" style="max-width: 1100px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary mb-0">
            Detalles del Documento de Compra
        </h2>


        @if($documento->tipo_documento_id != 61 && Auth::id() != 375)
            <button type="button"
                    class="btn btn-outline-secondary btn-sm mt-1 px-2 py-0"
                    data-bs-toggle="modal"
                    data-bs-target="#modalEstadoCompra-{{ $documento->id }}">
                Editar
            </button>
            @include('cobranzas.finanzas_compras.modal_estado', ['doc' => $documento])
        @endif
    </div>




    {{-- Información general --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Información general</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Empresa:</strong> {{ $documento->empresa?->Nombre ?? 'Sin empresa' }}</p>
                    <p><strong>Proveedor:</strong> {{ $documento->razon_social }}</p>
                    <p><strong>RUT Proveedor:</strong> {{ $documento->rut_proveedor }}</p>
                    <p><strong>Tipo Documento:</strong> {{ $documento->tipoDocumento?->nombre ?? '-' }}</p>
                    <p><strong>Folio:</strong> {{ $documento->folio }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Monto Total:</strong> ${{ number_format($documento->monto_total, 0, ',', '.') }}</p>
                    <p><strong>Saldo Pendiente:</strong> ${{ number_format($documento->saldo_pendiente, 0, ',', '.') }}</p>
                    <p><strong>Estado Actual:</strong> {{ $documento->estado ?? $documento->status_original }}</p>
                    <p><strong>Fecha Documento:</strong> {{ $documento->fecha_docto ? \Carbon\Carbon::parse($documento->fecha_docto)->format('d-m-Y') : '-' }}</p>
                    <p><strong>Fecha Vencimiento:</strong> {{ $documento->fecha_vencimiento ? \Carbon\Carbon::parse($documento->fecha_vencimiento)->format('d-m-Y') : '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Resumen del cálculo del saldo pendiente --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Resumen del cálculo del saldo pendiente</div>
        <div class="card-body">
            <p class="mb-1"><strong> Monto total inicial:</strong> ${{ number_format($documento->monto_total, 0, ',', '.') }}</p>

            {{-- Pago registrado --}}
            @if($documento->pagos()->exists())
                @php $pago = $documento->pagos()->latest('fecha_pago')->first(); @endphp
                <div class="card mb-4 shadow-sm border-success">
                    <div class="card-header bg-light fw-bold text-success">
                        Documento marcado como Pago
                    </div>
                    <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                        <p class="mb-2 mb-md-0">
                            Este documento fue marcado como <strong>Pagado</strong>
                            {{ $pago->fecha_pago ? 'el ' . \Carbon\Carbon::parse($pago->fecha_pago)->format('d-m-Y') : '' }}.
                        </p>

                        <form action="{{ route('pagos.destroy', $pago->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este Pago y restaurar el estado original del documento?')">
                            @csrf
                            @method('DELETE')
                            @if (Auth::id() != 375)
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-x-circle"></i> Eliminar Pago
                                </button>
                            @endif
                        </form>
                    </div>
                </div>
            @endif

            {{-- Pronto Pago --}}
            @if($documento->prontoPagos()->exists())
                @php $pp = $documento->prontoPagos()->latest('fecha_pronto_pago')->first(); @endphp
                <div class="card mb-4 shadow-sm border-warning">
                    <div class="card-header bg-light fw-bold text-warning">
                        Documento marcado como Pronto Pago
                    </div>
                    <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                        <p class="mb-2 mb-md-0">
                            Este documento fue marcado como <strong>Pronto Pago</strong>
                            {{ $pp->fecha_pronto_pago ? 'el ' . \Carbon\Carbon::parse($pp->fecha_pronto_pago)->format('d-m-Y') : '' }}.
                        </p>

                        <form action="{{ route('prontopagos.destroy', $pp->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar el registro de Pronto Pago y restaurar el estado original del documento?')">
                            @csrf
                            @method('DELETE')
                            @if (Auth::id() != 375)
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-x-circle"></i> Eliminar Pronto Pago
                                </button>
                            @endif
                        </form>
                    </div>
                </div>
            @endif

            {{-- Abonos --}}
            @if($documento->abonos->isNotEmpty())
                @foreach ($documento->abonos as $abono)
                    <p class="mb-1">
                        <strong> Abono registrado el {{ \Carbon\Carbon::parse($abono->fecha_abono)->format('d-m-Y') }}:</strong>
                        - ${{ number_format($abono->monto, 0, ',', '.') }}
                    </p>
                @endforeach
            @endif

            {{-- Cruces --}}
            @if($documento->cruces->isNotEmpty())
                @foreach ($documento->cruces as $cruce)
                    <p class="mb-1">
                        <strong> Cruce registrado el {{ \Carbon\Carbon::parse($cruce->fecha_cruce)->format('d-m-Y') }}:</strong>
                        - ${{ number_format($cruce->monto, 0, ',', '.') }}
                    </p>
                @endforeach
            @endif

            <hr>
            <p class="fw-bold text-success mb-0">
                <strong>Saldo pendiente actual:</strong> ${{ number_format($documento->saldo_pendiente, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Sección de abonos --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Abonos registrados</div>
        <div class="card-body">
            @if($documento->abonos->isEmpty())
                <p class="text-muted">Sin abonos registrados.</p>
            @else
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Fecha Abono</th>
                            <th>Monto</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documento->abonos as $abono)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($abono->fecha_abono)->format('d-m-Y') }}</td>
                                <td>${{ number_format($abono->monto, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <form action="{{ route('abonos.destroy', $abono->id) }}" 
                                        method="POST" 
                                        onsubmit="return confirm('¿Seguro que deseas eliminar este abono?')">
                                        @csrf
                                        @method('DELETE')
                                        @if (Auth::id() != 375)
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                Eliminar
                                            </button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Sección de cruces --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Cruces registrados</div>
        <div class="card-body">
            @if($documento->cruces->isEmpty())
                <p class="text-muted">Sin cruces registrados.</p>
            @else
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Fecha Cruce</th>
                            <th>Monto</th>
                            <th>Cliente</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documento->cruces as $cruce)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($cruce->fecha_cruce)->format('d-m-Y') }}</td>
                                <td>${{ number_format($cruce->monto, 0, ',', '.') }}</td>
                                <td>
                                    {{ $cruce->cobranzaCompra->razon_social ?? '—' }}
                                    @if($cruce->cobranzaCompra)
                                        <br>
                                        <small class="text-muted">
                                            RUT: {{ $cruce->cobranzaCompra->rut_cliente }}
                                        </small>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <form action="{{ route('cruces.destroy', $cruce->id) }}" 
                                          method="POST"
                                          onsubmit="return confirm('¿Seguro que deseas eliminar este cruce?')">
                                        @csrf
                                        @method('DELETE')
                                        @if (Auth::id() != 375)
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                Eliminar
                                            </button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>



    {{-- ========================================= --}}
{{-- Sección de Referencias (igual a ventas) --}}
{{-- ========================================= --}}

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Referencias del Documento</div>
        <div class="card-body">

            {{-- Si este documento referencia a otro --}}
            @if($documento->referencia)
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <p class="mb-0">
                        <strong>Referencia a:</strong><br>
                        {{ $documento->referencia->tipoDocumento->nombre ?? 'Documento' }}
                        Folio <strong>{{ $documento->referencia->folio }}</strong> —
                        Monto: ${{ number_format($documento->referencia->monto_total, 0, ',', '.') }}
                    </p>

                    @if (Auth::id() != 375)
                        <form action="{{ route('finanzas_compras.quitar_referencia', $documento->id) }}"
                            method="POST"
                            onsubmit="return confirm('¿Seguro que deseas quitar la referencia actual de este documento?')">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                Quitar referencia
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            {{-- Si otros documentos referencian a este --}}
            @if($documento->referenciados->isNotEmpty())
                <p><strong>Referenciado por:</strong></p>
                <ul class="list-unstyled">
                    @foreach($documento->referenciados as $ref)
                        <li class="mb-2 border rounded p-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                {{ $ref->tipoDocumento->nombre ?? 'Documento' }}
                                Folio <strong>{{ $ref->folio }}</strong> —
                                Monto: ${{ number_format($ref->monto_total, 0, ',', '.') }}
                            </div>

                            @if (Auth::id() != 375)
                                <form action="{{ route('finanzas_compras.quitar_referencia', $ref->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('¿Seguro que deseas quitar esta referencia?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Quitar referencia
                                    </button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Si no tiene ninguna referencia (igual que ventas) --}}
            @if(!$documento->referencia && $documento->referenciados->isEmpty())
                <p class="text-muted">Sin referencias asociadas.</p>
            @endif


            {{-- ========================================= --}}
            {{-- Asignar nueva referencia (solo para NC) --}}
            {{-- ========================================= --}}

            @if($documento->tipo_documento_id == 61 && Auth::id() != 375)

                <hr>

                <h6 class="fw-bold text-primary">
                    Asignar nueva referencia
                </h6>

                @if($candidatosReferencia->isEmpty())
                    <p class="text-muted mb-0">
                        No existen facturas disponibles para este proveedor.
                    </p>
                @else

                    <form action="{{ route('finanzas_compras.asignar_referencia', $documento->id) }}"
                        method="POST"
                        class="row g-2 mt-1">

                        @csrf

                        <div class="col-md-9">
                            <select name="factura_id" class="form-select" required>
                                <option value="">Seleccione una factura</option>

                                @foreach($candidatosReferencia as $factura)
                                    <option value="{{ $factura->id }}">
                                        Folio {{ $factura->folio }}
                                        — ${{ number_format($factura->monto_total,0,',','.') }}
                                        — {{ \Carbon\Carbon::parse($factura->fecha_docto)->format('d-m-Y') }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                Guardar referencia
                            </button>
                        </div>

                    </form>

                @endif

            @endif




        </div>
    </div>


    {{-- Botón volver --}}
    <div class="text-center mt-4">
        <a href="{{ session('return_to_listado', route('finanzas_compras.index')) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>

</div>
@endsection
