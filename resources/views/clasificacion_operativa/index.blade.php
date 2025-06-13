@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4 text-primary">Clasificación Operativa por Comuna</h1>

    <div class="accordion" id="accordionRegions">
        @foreach($regiones as $region)
            @php
                $comunasRegion = $comunas->where('region_id', $region->id);
            @endphp

            <div class="accordion-item">
                <h2 class="accordion-header" id="heading{{ $region->id }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $region->id }}" aria-expanded="false" aria-controls="collapse{{ $region->id }}">
                        {{ $region->Abreviatura ?? '' }} - {{ $region->Nombre }} ({{ $region->NumeroRomano }})
                    </button>
                </h2>
                <div id="collapse{{ $region->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $region->id }}" data-bs-parent="#accordionRegions">
                    <div class="accordion-body">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Frecuencia de días</th>
                                    <th>Próxima Entrega</th>
                                    <th>Tipo de Zona</th>
                                    <th>Número Región</th>
                                    <th>Comuna</th>
                                    <th>Comuna Matriz</th>
                                    <th>Nombre Operador</th>
                                    <th>Rut</th>
                                    <th>Zona Madre</th>
                                    <th>Subzona</th>
                                    <th>Zona</th>  
                                    <th>Ruta Geo</th>
                                    <th>Transporte</th>                                                                                                                                              
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comunasRegion as $comuna)
                                    <tr>
                                        <td>{{ $comuna->frecuencia_texto ?? '—' }}</td>

                                        <td>
                                            {{ $comuna->proxima_entrega ?? '—' }}
                                        </td>

                                        <td>{{ $comuna->clasificacionOperativa->tipoZona->nombre ?? '—' }}</td>

                                        <td>{{ $region->NumeroRomano }}</td>

                                        <td>{{ $comuna->Nombre }}</td>
                                        <td>{{ $comuna->clasificacionOperativa->comuna_matriz ?? '—' }}</td>

                                        <td>{{"Operador"}} {{$comuna->clasificacionOperativa->comuna_matriz ?? ''}} {{ $comuna->clasificacionOperativa->proveedor->razon_social ?? '—' }}</td>

                                        <td>{{ $comuna->clasificacionOperativa->proveedor->rut ?? '—' }}</td>
                                        <td>{{ $comuna->clasificacionOperativa->zona->zonaMadre->nombre ?? '—' }}</td>                                        
                                        <td>{{ $comuna->clasificacionOperativa->subzona->nombre ?? '—' }}</td>
                                        <td>{{ $comuna->clasificacionOperativa->zona->nombre ?? '—' }}</td>

                                        <td>
                                            @if ($comuna->clasificacionOperativa?->zonaRutaGeografica)
                                                {{ $comuna->clasificacionOperativa->zonaRutaGeografica->nombre }}
                                            @else
                                                <span class="text-muted">No asignada</span>
                                            @endif
                                        </td>

                                        <td>{{ $comuna->clasificacionOperativa->zonaRutaGeografica->transporte->nombre ?? 'Sin definir' }}</td>

                                                                                                                                                                                                       
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
        @endforeach
    </div>
</div>
@endsection
