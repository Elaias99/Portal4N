@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">📋 Reclamos Pendientes</h2>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if($reclamos->count())
        <div class="table-responsive">
            <table class="table table-bordered table-striped shadow-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>Código Bulto</th>
                        <th>Área</th>
                        <th>Trabajador</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>

                    </tr>
                </thead>
                <tbody>
                    @foreach($reclamos as $reclamo)
                        <tr>
                            <td>{{ $reclamo->bulto->codigo_bulto ?? '—' }}</td>
                            <td>{{ $reclamo->area->nombre ?? '—' }}</td>
                            <td>
                                {{ $reclamo->trabajador->Nombre ?? '' }}
                                {{ $reclamo->trabajador->ApellidoPaterno ?? '' }}
                            </td>
                            <td>{{ $reclamo->descripcion }}</td>
                            <td>{{ $reclamo->created_at->format('d-m-Y H:i') }}</td>


                            <td>
                                <span class="badge badge-warning text-dark text-uppercase">
                                    {{ $reclamo->estado }}
                                </span>
                            </td>

                            <td>
                                @php
                                    // Usamos el correo interno asociado
                                    $correoInterno = resolvePerfilEmail(Auth::user()->email);
                            
                                    // Buscamos el trabajador correspondiente
                                    $trabajadorActual = \App\Models\Trabajador::whereHas('user', function ($q) use ($correoInterno) {
                                        $q->where('email', $correoInterno);
                                    })->first();
                            
                                    $esCreador = $trabajadorActual && $trabajadorActual->id === $reclamo->id_trabajador;
                                @endphp
                            
                                @if ($esCreador && $reclamo->estado !== 'cerrado')
                                    <form action="{{ route('reclamos.cerrar', $reclamo->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de cerrar este reclamo?')">
                                            Cerrar
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            
                            


                            
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="alert alert-info mt-4">
            No hay reclamos pendientes.
        </div>
    @endif
</div>
@endsection
