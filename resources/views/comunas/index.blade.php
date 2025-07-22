@extends('layouts.app')

@section('content')
<div class="container">

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-center">Lista de Comunas por Región</h1>

        
        <a href="{{ route('comunas.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Crear Comuna
        </a>

   
    </div>

    <div class="row">
        {{-- Filtro con layout reutilizable --}}
        <div class="col-lg-2">
            {{-- Tarjeta superior con botones de gestión --}}
            <div class="card shadow-sm p-3 mb-4">
                <h5 class="fw-bold mb-3">Gestión de Comunas</h5>

                <div class="d-grid gap-2">
                    <a href="{{ route('clasificacion-operativa.index') }}" class="btn btn-primary">
                        Operadores
                    </a>

                    <a href="{{ route('comunas.export') }}" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Exportar Comunas a Excel
                    </a>
                </div>
            </div>

            {{-- Filtros usando el layout como componente --}}
            @component('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('comunas.index')
            ])
                @slot('campos')
                    <input type="text" name="search" id="comunaSearch" class="form-control" placeholder="Buscar comuna..." value="{{ request('search') }}">
                @endslot
            @endcomponent
        </div>


        {{-- Listado agrupado y filtrable --}}
        <div class="col-lg-10">
            <div class="accordion" id="accordionRegions">
                @foreach($regions as $region)
                    <div class="accordion-item region-item">
                        <h2 class="accordion-header" id="heading{{ $region->id }}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $region->id }}" aria-expanded="false" aria-controls="collapse{{ $region->id }}">
                                {{ $region->Abreviatura ?? '' }} - {{ $region->Nombre }} ({{ $region->NumeroRomano }})
                            </button>
                        </h2>
                        <div id="collapse{{ $region->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $region->id }}" data-bs-parent="#accordionRegions">
                            <div class="accordion-body">
                                <table class="table table-light table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nombre Comuna</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($region->comunas as $comuna)
                                            <tr class="comuna-item">
                                                <td class="comuna-nombre">{{ $comuna->Nombre }}</td>
                                                <td>
                                                    <a class="btn btn-warning" href="{{ route('comunas.edit', $comuna->id) }}">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </a>
                                                    <form action="{{ route('comunas.destroy', $comuna->id) }}" method="POST" style="display:inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-danger" type="submit" onclick="return confirm('¿Seguro que deseas eliminar esta Comuna?')">
                                                            <i class="fas fa-trash-alt"></i> Eliminar
                                                        </button>
                                                    </form>
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
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('comunaSearch');
        if (!searchInput) return;

        searchInput.addEventListener('keyup', function () {
            const searchValue = this.value.toLowerCase();
            const regions = document.querySelectorAll('.region-item');

            regions.forEach(function (region) {
                let matchesInRegion = false;
                const comunaRows = region.querySelectorAll('.comuna-item');

                comunaRows.forEach(function (row) {
                    const nombre = row.querySelector('.comuna-nombre').textContent.toLowerCase();
                    const match = nombre.includes(searchValue);
                    row.style.display = match ? '' : 'none';
                    if (match) matchesInRegion = true;
                });

                region.style.display = matchesInRegion ? '' : 'none';

                const collapse = region.querySelector('.accordion-collapse');
                if (matchesInRegion && !collapse.classList.contains('show')) {
                    region.querySelector('.accordion-button').click();
                }
            });
        });
    });
</script>
@endpush
