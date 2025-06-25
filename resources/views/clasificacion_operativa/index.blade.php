@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4 text-dark">Clasificación Operativa por Comuna</h1>


    <div class="row">
    <!-- FILTROS LATERALES -->
    <div class="col-12 col-md-4 col-lg-2 mb-3 mb-lg-0">
        <div class="card shadow-sm p-3 mb-4">
            <h5 class="fw-bold">Filtrar por</h5>

            <form method="GET" action="{{ route('clasificacion-operativa.index') }}">
                <!-- Comuna -->
                <div class="mb-3">
                    <label class="form-label">Comuna:</label>
                    <input type="text" name="comuna" class="form-control" placeholder="Comuna..." value="{{ request('comuna') }}">
                </div>

                <!-- Proveedor -->
                <div class="mb-3">
                    <label class="form-label">Proveedor:</label>
                    <input type="text" name="proveedor" class="form-control" placeholder="Proveedor..." value="{{ request('proveedor') }}">
                </div>

                <!-- Región -->
                <div class="mb-3">
                    <label class="form-label">Región:</label>
                    <select name="region" class="form-select">
                        <option value="">Todas las regiones</option>
                        @foreach($regiones as $region)
                            <option value="{{ $region->id }}" {{ request('region') == $region->id ? 'selected' : '' }}>
                                {{ $region->Nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Zona -->
                <div class="mb-3">
                    <label class="form-label">Zona:</label>
                    <select name="zona" class="form-select">
                        <option value="">Todas las zonas</option>
                        @foreach($zonas as $zona)
                            <option value="{{ $zona->id }}" {{ request('zona') == $zona->id ? 'selected' : '' }}>
                                {{ $zona->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Subzona -->
                <div class="mb-3">
                    <label class="form-label">Subzona:</label>
                    <select name="subzona" class="form-select">
                        <option value="">Todas las subzonas</option>
                        @foreach($subzonas as $subzona)
                            <option value="{{ $subzona->id }}" {{ request('subzona') == $subzona->id ? 'selected' : '' }}>
                                {{ $subzona->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Cobertura -->
                <div class="mb-4">
                    <label class="form-label">Cobertura:</label>
                    <select name="cobertura" class="form-select">
                        <option value="">Todas las coberturas</option>
                        @foreach($coberturas as $cobertura)
                            <option value="{{ $cobertura->id }}" {{ request('cobertura') == $cobertura->id ? 'selected' : '' }}>
                                {{ $cobertura->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- BOTONES -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="{{ route('clasificacion-operativa.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                    <a href="{{ route('clasificacion-operativa.exportar', request()->all()) }}" class="btn btn-outline-success">
                        Exportar a Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-10">
        <div class="accordion" id="accordionRegions">
            @foreach($regiones as $region)
                @php
                    $comunasRegion = $comunas->where('region_id', $region->id);
                @endphp

                <div class="accordion-item shadow-sm mb-3 rounded">
                    <h2 class="accordion-header" id="heading{{ $region->id }}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $region->id }}" aria-expanded="false" aria-controls="collapse{{ $region->id }}">
                            {{ $region->Abreviatura ?? '' }} - {{ $region->Nombre }} ({{ $region->NumeroRomano }})
                            <span class="badge bg-secondary ms-2">{{ $comunasRegion->count() }} comunas</span>
                        </button>
                    </h2>
                    <div id="collapse{{ $region->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $region->id }}" data-bs-parent="#accordionRegions">
                        <div class="accordion-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Frecuencia de días</th>
                                            {{-- <th>Próxima Entrega</th> --}}
                                            <th>Tipo de Zona</th>
                                            <th>Número Región</th>
                                            <th>Comuna</th>

                                            <th>COD IATA</th>
                                            <th>COD IATA2</th>


                                            <th>Comuna Matriz</th>
                                            <th>Nombre Operador</th>
                                            <th>Rut</th>
                                            <th>Zona Madre</th>
                                            <th>Subzona</th>
                                            <th>Zona</th>  
                                            <th>Ruta Geo</th>
                                            <th>Transporte</th>
                                            <th>Origen</th>
                                            <th>Destino Máximo</th>
                                            <th>Nombre de la Ruta</th>
                                            <th>Cobertura</th>
                                            <th>Provincia</th>
                                            <th>Orden Transporte</th>



                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($comunasRegion as $comuna)
                                            <tr>
                                                <td>{{ $comuna->frecuencia_texto ?? '—' }}</td>
                                                {{-- <td>{{ $comuna->proxima_entrega ?? '—' }}</td> --}}
                                                <td>{{ $comuna->clasificacionOperativa->tipoZona->nombre ?? '—' }}</td>
                                                <td>{{ $region->NumeroRomano }}</td>
                                                <td>{{ $comuna->Nombre }}</td>

                                                <td>{{ $comuna->clasificacionOperativa->codigoiata->cod_iata ?? '—' }}</td>
                                                <td>{{ $comuna->clasificacionOperativa->codigoiata->cod_iata2 ?? '—' }}</td>

                                                <td>{{ $comuna->clasificacionOperativa->comuna_matriz ?? '—' }}</td>
                                                <td>
                                                    Operador {{ $comuna->clasificacionOperativa->comuna_matriz ?? '' }}
                                                    {{ $comuna->clasificacionOperativa->proveedor->razon_social ?? '—' }}
                                                </td>
                                                <td>{{ $comuna->clasificacionOperativa->proveedor->rut ?? '—' }}</td>
                                                <td>{{ $comuna->clasificacionOperativa->zona->zonaMadre->nombre ?? '—' }}</td>
                                                <td>{{ $comuna->clasificacionOperativa->subzona->nombre ?? '—' }}</td>
                                                <td>{{ $comuna->clasificacionOperativa->zona->nombre ?? '—' }}</td>
                                                <td>{{ $comuna->clasificacionOperativa->zonaRutaGeografica->nombre ?? '—' }}</td>
                                                <td>{{ $comuna->clasificacionOperativa->zonaRutaGeografica->transporte->nombre ?? 'Sin definir' }}</td>
                                                <td>{{ $comuna->clasificacionOperativa->zonaRutaGeografica->origen->Nombre ?? '—' }}</td>
                                                <td>{{ $comuna->clasificacionOperativa->zonaRutaGeografica->destino->Nombre ?? '—' }}</td>
                                                <td>{{ $comuna->clasificacionOperativa->zonaRutaGeografica->nombre_ruta ?? '—' }}</td>
                                                <td>{{ $comuna->clasificacionOperativa->cobertura->nombre ?? '—' }}</td>
                                                <td>{{ $comuna->clasificacionOperativa->provincia->nombre ?? '—' }}</td>
                                                <td>{{ $comuna->ordenTransporte?->orden ?? '—' }}</td>

                                                <td>
                                                    <a href="{{ route('clasificacion-operativa.edit', $comuna->id) }}" class="btn btn-sm btn-primary">
                                                        Editar
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>



</div>
@endsection
