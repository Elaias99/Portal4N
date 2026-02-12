@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Honorarios con Fecha de Pago Corporativa</h2>


    <form method="GET" class="card p-3 mb-4">

        <div class="row">

            {{-- Año --}}
            <div class="col-md-3">
                <label>Año</label>
                <select name="anio" class="form-control">
                    <option value="">Todos</option>
                    @foreach($anios as $anio)
                        <option value="{{ $anio }}"
                            {{ request('anio') == $anio ? 'selected' : '' }}>
                            {{ $anio }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Mes --}}
            <div class="col-md-3">
                <label>Mes</label>
                <select name="mes" class="form-control">
                    <option value="">Todos</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}"
                            {{ request('mes') == $m ? 'selected' : '' }}>
                            {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
                        </option>
                    @endfor
                </select>
            </div>

            {{-- Servicio --}}
            <div class="col-md-3">
                <label>Servicio</label>
                <select name="servicio" class="form-control">
                    <option value="">Todos</option>
                    @foreach($servicios as $servicio)
                        <option value="{{ $servicio }}"
                            {{ request('servicio') == $servicio ? 'selected' : '' }}>
                            {{ $servicio }}
                        </option>
                    @endforeach
                </select>
            </div>


            {{-- Botones --}}
            <div class="form-group col-md-0 text-right">
                <button type="submit" class="btn btn-sm btn-outline-primary mr-2">
                    Filtrar
                </button>
                

                <a href="{{ route('honorarios.mensual.calendario') }}"
                class="btn btn-sm btn-outline-secondary">
                    Limpiar
                </a>

                <a href="{{ route('honorarios.mensual.index') }}"
                class="btn btn-sm btn-outline-secondary">
                    Volver
                </a>


            </div>

        </div>

    </form>


    <div class="card border-0 shadow-sm mb-3" style="background-color:#f8f9fa;">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table mb-0 table-hover" style="background-color:#f8f9fa;">

                    <thead>
                        <tr style="background-color:#e9ecef;">
                            <th class="border-0">Folio</th>
                            <th class="border-0">Emisor</th>
                            <th class="border-0">Servicio</th>
                            <th class="border-0">Fecha Emisión</th>
                            <th class="border-0">Fecha Pago Corporativa</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($honorarios as $honorario)
                            <tr style="border-bottom:1px solid #dee2e6;">
                                <td class="align-middle">
                                    {{ $honorario->folio }}
                                </td>

                                <td class="align-middle">
                                    <div class="font-weight-600">
                                        {{ $honorario->razon_social_emisor }}
                                    </div>
                                    <div class="small text-muted">
                                        RUT: {{ $honorario->rut_emisor }}
                                    </div>
                                </td>

                                <td class="align-middle">
                                    <div>
                                        {{ $honorario->cobranzaCompra->servicio ?? '-' }}
                                    </div>

                                    @if($honorario->cobranzaCompra?->creditos)
                                        <span class="badge" style="background:#e9ecef; color:#495057;">
                                            {{ $honorario->cobranzaCompra->creditos }} días
                                        </span>
                                    @endif
                                </td>

                                <td class="align-middle">
                                    {{ optional($honorario->fecha_emision)->format('d-m-Y') }}
                                </td>

                                <td class="align-middle">
                                    @if($honorario->fecha_pago_corporativa)
                                        <span class="badge" style="background:#e9ecef; color:#495057;">
                                            {{ $honorario->fecha_pago_corporativa->format('d-m-Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted">
                                            No definido
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No existen honorarios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>
    </div>



    {{-- Paginación --}}
    <div class="py-3 d-flex justify-content-center">
        {{ $honorarios->links('pagination::bootstrap-4') }}
    </div>

</div>
@endsection
