@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold mb-0">Historial General de Abonos</h3>
        <a href="{{ route('cobranzas.general') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver al Panel
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <p class="mb-2">
                <strong>Total de Abonos Registrados:</strong> {{ $cantidadAbonos }}
            </p>
            <p class="mb-3">
                <strong>Total Abonado Global:</strong> 
                <span class="text-success fw-bold">${{ number_format($totalAbonado, 0, ',', '.') }}</span>
            </p>
            <hr>

            @if($abonos->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha Abono</th>
                                <th>Documento</th>
                                <th>Cliente</th>
                                <th>RUT</th>
                                <th class="text-end">Monto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($abonos as $abono)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($abono->fecha_abono)->format('d-m-Y') }}</td>
                                    <td>{{ $abono->documento->folio ?? '—' }}</td>
                                    <td>{{ $abono->documento->razon_social ?? '—' }}</td>
                                    <td>{{ $abono->documento->rut_cliente ?? '—' }}</td>
                                    <td class="text-end text-success fw-bold">
                                        ${{ number_format($abono->monto, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <a href="{{ route('abonos.edit', $abono->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </a>
                                        <form action="{{ route('abonos.destroy', $abono->id) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('¿Seguro que deseas eliminar este abono?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-warning text-center mb-0">
                    No hay abonos registrados aún en el sistema.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
