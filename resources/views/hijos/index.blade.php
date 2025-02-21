@extends('layouts.app')

@section('content')

<div class="container">
    @if(Session::has('Mensaje'))
        <div class="alert alert-success" role="alert">
            {{ Session::get('Mensaje') }}
        </div>
    @endif

    <h1 class="text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);">Lista de Hijos</h1>

    <div class="row">
        @foreach($trabajadores as $trabajador)
            @if($trabajador->hijos->isNotEmpty()) <!-- Mostrar solo si el trabajador tiene hijos -->
                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">{{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }} {{ $trabajador->ApellidoMaterno }}</h5>
                            <p class="card-text">
                                {{ $trabajador->hijos->count() }} hijo(s)
                                <br>
                                @foreach($trabajador->hijos as $hijo)
                                    <small>{{ $hijo->nombre }} ({{ $hijo->edad }} años)</small><br>
                                @endforeach
                            </p>
                            <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#detailsModal{{ $trabajador->id }}">Ver Detalles</a>
                        </div>
                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade" id="detailsModal{{ $trabajador->id }}" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel{{ $trabajador->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="detailsModalLabel{{ $trabajador->id }}">Hijos de {{ $trabajador->Nombre }}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Género</th>
                                            <th>Parentesco</th>
                                            <th>Fecha de Nacimiento</th>
                                            <th>Edad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($trabajador->hijos as $hijo)
                                            <tr>
                                                <td>{{ $hijo->nombre }}</td>
                                                <td>{{ $hijo->genero }}</td>
                                                <td>{{ $hijo->parentesco }}</td>
                                                <td>{{ $hijo->fecha_nacimiento }}</td>
                                                <td>{{ $hijo->edad }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>

@endsection
