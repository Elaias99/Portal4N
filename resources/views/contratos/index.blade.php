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
            <table class="table align-middle table-bordered table-hover text-center">
                <thead class="thead-dark d-none d-md-table-header-group">
                    <tr>
                        <th>Trabajador</th>
                        <th>Inicio Real del Trabajo</th>
                        <th>Contratos Registrados</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($trabajadores as $trabajador)
                        <tr class="d-none d-md-table-row">
                            <td><strong>{{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }}</strong></td>
                            <td>
                                {{ $trabajador->fecha_inicio_trabajo
                                    ? \Carbon\Carbon::parse($trabajador->fecha_inicio_trabajo)->format('d/m/Y')
                                    : '-' }}
                            </td>
                            <td>
                                @if ($trabajador->contratos->isEmpty())
                                    <span class="text-muted">Sin contratos</span>
                                @else
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($trabajador->contratos as $contrato)
                                            <div class="border rounded p-2 text-start bg-light shadow-sm">
                                                <div><strong>📄 {{ $contrato->tipo }}</strong></div>
                                                <div><small class="text-muted">Firmado el:</small> 
                                                    {{ optional($contrato->fecha_inicio_contrato)->format('d/m/Y') ?? 'Sin fecha' }}
                                                </div>
                                                <div><small class="text-muted">Estado:</small>
                                                    <span class="badge 
                                                        {{ $contrato->estado === 'Firmado' ? 'bg-success text-white' : 
                                                           ($contrato->estado === 'Pendiente' ? 'bg-warning text-dark' : 'bg-danger text-white') }}">
                                                        {{ $contrato->estado }}
                                                    </span>
                                                </div>
                                                @if ($contrato->archivo)
                                                    <div class="mt-1">
                                                        <a href="{{ route('contratos.download', $contrato->id) }}"
                                                           target="_blank" class="text-decoration-none">
                                                            📥 Descargar PDF
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('contratos.create', $trabajador->id) }}" class="btn btn-sm btn-outline-primary mb-1">
                                    Registrar
                                </a>

                                @if ($trabajador->contratos->isNotEmpty())
                                    <a href="{{ route('contratos.edit', $trabajador->contratos->last()->id) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        ✏️
                                    </a>
                                @endif
                            </td>
                        </tr>

                        {{-- Versión móvil --}}
                        <tr class="d-md-none">
                            <td colspan="4">
                                <div class="card shadow-sm mb-2">
                                    <div class="card-body p-3">
                                        <h5 class="mb-2">{{ $trabajador->Nombre }} {{ $trabajador->ApellidoPaterno }}</h5>
                                        <p class="mb-1"><strong>Inicio trabajo:</strong>
                                            {{ optional($trabajador->fecha_inicio_trabajo)->format('d/m/Y') ?? '-' }}
                                        </p>

                                        @if ($trabajador->contratos->isEmpty())
                                            <p class="text-muted">Sin contratos</p>
                                        @else
                                            @foreach ($trabajador->contratos as $contrato)
                                                <div class="border rounded p-2 mb-2 bg-light">
                                                    <div><strong>{{ $contrato->tipo }}</strong></div>
                                                    <div><small class="text-muted">Firmado el:</small>
                                                        {{ optional($contrato->fecha_inicio_contrato)->format('d/m/Y') ?? 'Sin fecha' }}
                                                    </div>
                                                    <div><small class="text-muted">Estado:</small>
                                                        <span class="badge 
                                                            {{ $contrato->estado === 'Firmado' ? 'bg-success text-white' : 
                                                               ($contrato->estado === 'Pendiente' ? 'bg-warning text-dark' : 'bg-danger text-white') }}">
                                                            {{ $contrato->estado }}
                                                        </span>
                                                    </div>
                                                    @if ($contrato->archivo)
                                                        <div>
                                                            <a href="{{ route('contratos.download', $contrato->id) }}"
                                                               class="text-decoration-none" target="_blank">📥 Descargar</a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif

                                        <div class="mt-2 d-flex gap-2">
                                            <a href="{{ route('contratos.create', $trabajador->id) }}"
                                               class="btn btn-sm btn-outline-primary flex-fill">Registrar</a>
                                            @if ($trabajador->contratos->isNotEmpty())
                                                <a href="{{ route('contratos.edit', $trabajador->contratos->last()->id) }}"
                                                   class="btn btn-sm btn-outline-secondary flex-fill">✏️ Editar</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
