@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">
        📊 Dashboard de Reclamos
    </h3>

    @if ($resumenPorCasuistica->count())
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">📌 Reclamos Cerrados por Casuística</h5>
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Casuística</th>
                            <th class="text-center">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resumenPorCasuistica as $casuistica)
                            <tr>
                                <td>{{ $casuistica->nombre }}</td>
                                <td class="text-center">{{ $casuistica->cantidad_cerrados }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            No hay reclamos cerrados con casuísticas registradas aún.
        </div>
    @endif


    


</div>
@endsection
