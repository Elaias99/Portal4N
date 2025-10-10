@extends('layouts.app')

@section('content')
<div class="container mt-4" style="max-width: 1100px;">
    <h2 class="mb-4 text-center fw-bold text-primary">
        Detalles del Documento Financiero
    </h2>

    {{-- 🔹 Información general del documento --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Información general</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Empresa:</strong> {{ $documento->empresa?->Nombre ?? 'Sin empresa' }}</p>
                    <p><strong>Razón Social:</strong> {{ $documento->razon_social }}</p>
                    <p><strong>RUT Cliente:</strong> {{ $documento->rut_cliente }}</p>
                    <p><strong>Tipo Documento:</strong> {{ $documento->tipoDocumento?->nombre ?? '-' }}</p>
                    <p><strong>Folio:</strong> {{ $documento->folio }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Monto Total:</strong> ${{ number_format($documento->monto_total, 0, ',', '.') }}</p>
                    <p><strong>Saldo Pendiente:</strong> ${{ number_format($documento->saldo_pendiente, 0, ',', '.') }}</p>
                    <p><strong>Estado Actual:</strong> {{ $documento->status ?? $documento->status_original }}</p>
                    <p><strong>Fecha Documento:</strong> {{ $documento->fecha_docto ? \Carbon\Carbon::parse($documento->fecha_docto)->format('d-m-Y') : '-' }}</p>
                    <p><strong>Fecha Vencimiento:</strong> {{ $documento->fecha_vencimiento ? \Carbon\Carbon::parse($documento->fecha_vencimiento)->format('d-m-Y') : '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- 🔹 Nuevo: Resumen del cálculo del saldo pendiente --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Resumen del cálculo del saldo pendiente</div>
        <div class="card-body">

            {{-- Paso 1: Monto base --}}
            <p class="mb-1"><strong>1️⃣ Monto total inicial:</strong> ${{ number_format($documento->monto_total, 0, ',', '.') }}</p>

            {{-- Paso 2: Descuento por nota de crédito --}}
            @if($referencias['referenciadoPor']->isNotEmpty())
                @foreach ($referencias['referenciadoPor'] as $ref)
                    <p class="mb-1">
                        <strong>2️⃣ Descuento por Nota de Crédito folio {{ $ref->folio }}:</strong> 
                        - ${{ number_format($ref->monto_total, 0, ',', '.') }}
                    </p>
                @endforeach
            @endif

            {{-- Paso 3: Aplicación de abonos --}}
            @if($documento->abonos->isNotEmpty())
                @foreach ($documento->abonos as $abono)
                    <p class="mb-1">
                        <strong>3️⃣ Abono registrado el {{ \Carbon\Carbon::parse($abono->fecha_abono)->format('d-m-Y') }}:</strong>
                        - ${{ number_format($abono->monto, 0, ',', '.') }}
                    </p>
                @endforeach
            @endif

            {{-- Paso 4: Aplicación de cruces --}}
            @if($documento->cruces->isNotEmpty())
                @foreach ($documento->cruces as $cruce)
                    <p class="mb-1">
                        <strong>4️⃣ Cruce registrado el {{ \Carbon\Carbon::parse($cruce->fecha_cruce)->format('d-m-Y') }}:</strong>
                        - ${{ number_format($cruce->monto, 0, ',', '.') }}
                    </p>
                @endforeach
            @endif

            {{-- Resultado final --}}
            <hr>
            <p class="fw-bold text-success mb-0">
                ✅ <strong>Saldo pendiente actual:</strong> ${{ number_format($documento->saldo_pendiente, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- 🔹 Sección de abonos --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Abonos registrados</div>
        <div class="card-body">
            @if($documento->abonos->isEmpty())
                <p class="text-muted">Sin abonos registrados.</p>
            @else
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Fecha Abono</th>
                            <th>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documento->abonos as $abono)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($abono->fecha_abono)->format('d-m-Y') }}</td>
                                <td>${{ number_format($abono->monto, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- 🔹 Sección de cruces --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Cruces registrados</div>
        <div class="card-body">
            @if($documento->cruces->isEmpty())
                <p class="text-muted">Sin cruces registrados.</p>
            @else
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Fecha Cruce</th>
                            <th>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documento->cruces as $cruce)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($cruce->fecha_cruce)->format('d-m-Y') }}</td>
                                <td>${{ number_format($cruce->monto, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- 🔹 Referencias (Notas de crédito u otros documentos) --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Referencias del documento</div>
        <div class="card-body">
            @if($referencias['referencia'])
                <p><strong>Este documento referencia a:</strong> 
                    Folio {{ $referencias['referencia']->folio }} 
                    ({{ $referencias['referencia']->tipoDocumento?->nombre ?? 'Sin tipo' }}) 
                    por ${{ number_format($referencias['referencia']->monto_total, 0, ',', '.') }}
                </p>
            @endif

            @if($referencias['referenciadoPor']->isNotEmpty())
                <p><strong>Este documento es referenciado por:</strong></p>
                <ul>
                    @foreach ($referencias['referenciadoPor'] as $ref)
                        <li>
                            Nota de crédito folio {{ $ref->folio }} 
                            por ${{ number_format($ref->monto_total, 0, ',', '.') }}
                        </li>
                    @endforeach
                </ul>
            @endif

            @if(!$referencias['referencia'] && $referencias['referenciadoPor']->isEmpty())
                <p class="text-muted">Sin referencias asociadas.</p>
            @endif
        </div>
    </div>

    {{-- 🔹 Botón para volver --}}
    <div class="text-center mt-4">
        <a href="{{ route('cobranzas.documentos') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>
</div>
@endsection
