@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Listado de Bultos</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Código</th>
                    <th>Dirección</th>
                    <th>Comuna</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bultos as $bulto)
                    <tr>
                        <td>{{ $bulto->id }}</td>
                        <td>{{ $bulto->codigo_bulto }}</td>
                        <td>{{ $bulto->direccion }}</td>
                        <td>{{ $bulto->comuna }}</td>
                        <td>{{ ucfirst($bulto->estado) }}</td>
                        <td>
                            

                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
