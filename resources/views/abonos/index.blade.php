@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Historial de Abonos - {{ $documento->razon_social }}</h3>
    <p><strong>Folio:</strong> {{ $documento->folio }}</p>
    <p><strong>Monto Total Documento:</strong> ${{ number_format($documento->monto_total, 0, ',', '.') }}</p>
    <p><strong>Total Abonado:</strong> ${{ number_format($totalAbonado, 0, ',', '.') }}</p>
    <p><strong>Saldo Pendiente:</strong> ${{ number_format($saldoPendiente, 0, ',', '.') }}</p>

    <hr>

    @if($abonos->isNotEmpty())
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Fecha Abono</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($abonos as $abono)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($abono->fecha_abono)->format('d-m-Y') }}</td>
                        <td class="text-right text-success fw-bold">
                            ${{ number_format($abono->monto, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No hay abonos registrados aún.</p>
    @endif

    <a href="{{ route('cobranzas.documentos') }}" class="btn btn-secondary">Volver</a>
</div>
@endsection
