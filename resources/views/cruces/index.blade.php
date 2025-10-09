@extends('layouts.app')

@section('content')
<div class="container">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Historial de Cruces - {{ $documento->razon_social }}</h3>
        <a href="{{ route('cobranzas.documentos') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            {{-- INFORMACIÓN GENERAL --}}
            <p><strong>Folio:</strong> {{ $documento->folio }}</p>
            <p><strong>Monto Total Documento:</strong> ${{ number_format($documento->monto_total, 0, ',', '.') }}</p>
            <p><strong>Total Cruzado:</strong> ${{ number_format($totalCruzado, 0, ',', '.') }}</p>
            <p><strong>Saldo Pendiente:</strong> ${{ number_format($saldoPendiente, 0, ',', '.') }}</p>
            <hr>

            {{-- TABLA DE CRUCES --}}
            @if($cruces->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha Cruce</th>
                                <th class="text-end">Monto</th>
                                <th style="width: 180px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cruces as $cruce)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($cruce->fecha_cruce)->format('d-m-Y') }}</td>
                                    <td class="text-end text-success fw-bold">
                                        ${{ number_format($cruce->monto, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <a href="{{ route('cruces.edit', $cruce->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </a>

                                        <form action="{{ route('cruces.destroy', $cruce->id) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('¿Seguro que deseas eliminar este cruce?');">
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
                    No hay cruces registrados aún.
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
