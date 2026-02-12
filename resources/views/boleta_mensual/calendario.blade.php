@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Honorarios con Fecha de Pago Corporativa</h2>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Folio</th>
                <th>Emisor</th>
                <th>Servicio</th>
                <th>Fecha Emisión</th>
                <th>Fecha Pago Corporativa</th>
            </tr>
        </thead>
        <tbody>
            @forelse($honorarios as $honorario)
                <tr>
                    <td>{{ $honorario->folio }}</td>
                    <td>{{ $honorario->razon_social_emisor }}</td>
                    <td>
                        {{ $honorario->cobranzaCompra->servicio ?? '-' }}
                        @if($honorario->cobranzaCompra?->creditos)
                            ({{ $honorario->cobranzaCompra->creditos }} días)
                        @endif
                    </td>
                    <td>
                        {{ optional($honorario->fecha_emision)->format('d-m-Y') }}
                    </td>
                    <td>
                        @if($honorario->fecha_pago_corporativa)
                            {{ $honorario->fecha_pago_corporativa->format('d-m-Y') }}
                        @else
                            <span class="text-danger">No definido</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">
                        No existen honorarios registrados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
