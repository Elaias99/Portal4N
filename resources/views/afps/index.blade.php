@extends('layouts.app')

@section('content')
<div class="container">

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-center">Lista de AFPs</h1>
        <a href="{{ route('afps.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Crear AFP
        </a>
    </div>


    <div class="row">

        <div class="col-lg-2">
            @include('layouts.filtros', [
                'titulo' => 'Filtrar Nombre',
                'action' => route('afps.index'),
                'campos' => '
                    <input type="text" name="search" class="form-control" placeholder="Buscar región..." value="' . request('search') . '">
                '
            ])
        </div>




        <div class="col-lg-10">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Tasa de Cotización (%)</th>
                            <th>Tasa SIS (%)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($afps as $afp)
                        <tr>
                            <td>{{ $afp->Nombre }}</td>
                            <td>{{ number_format($afp->tasaAfp->tasa_cotizacion ?? 0, 2, ',', '.') }}%</td>
                            <td>{{ number_format($afp->tasaAfp->tasa_sis ?? 0, 2, ',', '.') }}%</td>

                            <td class="text-center">
                                <div class="d-flex flex-column gap-1">
                                    <a href="{{ route('afps.edit', $afp->id) }}" class="btn btn-sm btn-warning w-100 text-center d-inline-block">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>

                                    <form action="{{ route('afps.destroy', $afp->id) }}" method="POST" class="w-100">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger w-100 text-center d-inline-block" onclick="return confirm('¿Seguro que deseas eliminar esta AFP?');">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>



                            
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>





    </div>







    <footer class="mt-4 text-center">
        <a href="https://www.previred.com/indicadores-previsionales/" target="_blank" class="text-info">
            <i class="fa-solid fa-info fa-2x"></i>
            <p>Indicadores Previsionales</p>
        </a>
    </footer>

</div>
@endsection
