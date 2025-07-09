@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Listado de Trabajadores con Contratos Registrados</h3>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($trabajadores->isEmpty())
        <div class="alert alert-info">No hay trabajadores con contratos registrados.</div>
    @else
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle text-center table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Trabajador</th>
                        <th>Inicio Real del Trabajo</th>
                        <th>Contratos Registrados</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($trabajadores as $trabajador)
                        <tr>
                            <td><strong>{{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }}</strong></td>

                            <td>
                                {{ $trabajador->fecha_inicio_trabajo ? \Carbon\Carbon::parse($trabajador->fecha_inicio_trabajo)->format('d/m/Y') : '-' }}
                            </td>

                            <td>
                                @if ($trabajador->contratos->isEmpty())
                                    <span class="text-muted">Sin contratos</span>
                                @else
                                    <ul class="list-unstyled mb-0">
                                        @foreach ($trabajador->contratos as $contrato)
                                            <li class="mb-1">
                                                📄 <strong>{{ $contrato->tipo }}</strong><br>
                                                Estado:
                                                <span class="badge 
                                                    {{ $contrato->estado === 'Firmado' ? 'badge-success' : ($contrato->estado === 'Pendiente' ? 'badge-warning' : 'badge-danger') }}">
                                                    {{ $contrato->estado }}
                                                </span>
                                                @if ($contrato->archivo)
                                                    <br><a href="{{ route('contratos.download', $contrato->id) }}" title="Descargar archivo">📄 Descargar</a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>

                            <td class="text-nowrap">
                                <a href="{{ route('contratos.create', $trabajador->id) }}" class="btn btn-sm btn-outline-primary mb-1">
                                    Registrar
                                </a>

                                @if ($trabajador->contratos->isNotEmpty())
                                    <a href="{{ route('contratos.edit', $trabajador->contratos->last()->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Editar último contrato">
                                        ✏️
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
