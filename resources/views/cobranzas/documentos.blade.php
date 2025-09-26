@extends('layouts.app')

@section('content')


    {{-- Mensaje de éxito --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning">
            <strong>{{ session('warning') }}</strong>
            <ul>
                @foreach (session('detalles_errores') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-4">Documentos Financieros</h1>
    </div>



    


<div class="row">

    <div class="col-lg-2">

        @component('layouts.columna_izquierda', [
                'tituloTarjeta' => 'Accesos rápidos',
                'tituloFiltros' => 'Filtrar Por',
                'action' => route('cobranzas.documentos')
        ])
            @slot('acciones')
                <div class="d-grid gap-2 mt-2">
                    
                    {{-- Formulario de importación --}}
                    <form action="{{ route('cobranzas.import') }}" method="POST" enctype="multipart/form-data" class="mb-4">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <input type="file" name="file" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary" type="submit">Importar Excel</button>
                            </div>
                        </div>
                    </form>



                </div>



            @endslot

            @slot('filtros')

                <div class="mb-3">
                    <label class="form-label">Razón Social:</label>
                    <input type="text" name="razon_social" class="form-control" placeholder="Buscar..." value="{{ request('razon_social') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">RUT Cliente:</label>
                    <input type="text" name="rut_cliente" class="form-control" placeholder="Ej: 76170725-6" value="{{ request('rut_cliente') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Folio:</label>
                    <input type="text" name="folio" class="form-control" placeholder="Número de folio..." value="{{ request('folio') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Fecha Documento:</label>
                    <input type="date" name="fecha_docto" class="form-control" value="{{ request('fecha_docto') }}">
                </div>

            @endslot



        @endcomponent

    </div>




    
    <div class="col-lg-10">
        {{-- Tabla de registros --}}
        <div class="table-responsive">
            <table class="table table-hover">


                <thead>
                    <tr>
                        <th>Tipo Doc</th>
                        <th>Rut Cliente</th>
                        <th>Razon Social</th>
                        <th>Folio</th>
                        <th>Fecha Docto</th>
                        <th>Fecha Recepcion</th>
                        <th>Fecha Acuse Recibo</th>
                        <th>Fecha Reclamo</th>
                        <th>Monto Exento</th>
                        <th>Monto Neto</th>
                        <th>Monto IVA</th>
                        <th>Monto Total</th>
                        <th>Tipo Docto. Referencia</th>
                        <th>Folio Docto. Referencia</th>
                    </tr>
                </thead>


                <tbody>
                    @foreach ($documentoFinancieros as $doc)
                        <tr>
                            <td>{{ $doc->tipo_doc }}</td>
                            <td>{{ $doc->rut_cliente }}</td>
                            <td>{{ $doc->razon_social }}</td>
                            <td>{{ $doc->folio }}</td>

                            {{-- Fechas --}}
                            <td>{{ \Carbon\Carbon::parse($doc->fecha_docto)->format('d-m-Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($doc->fecha_recepcion)->format('d-m-Y H:i') }}</td>
                            <td>{{ $doc->fecha_acuse_recibo ? \Carbon\Carbon::parse($doc->fecha_acuse_recibo)->format('d-m-Y H:i') : '-' }}</td>
                            <td>{{ $doc->fecha_reclamo ? \Carbon\Carbon::parse($doc->fecha_reclamo)->format('d-m-Y H:i') : '-' }}</td>

                            {{-- Montos --}}
                            <td>${{ number_format($doc->monto_exento, 0, ',', '.') }}</td>
                            <td>${{ number_format($doc->monto_neto, 0, ',', '.') }}</td>
                            <td>${{ number_format($doc->monto_iva, 0, ',', '.') }}</td>
                            <td>${{ number_format($doc->monto_total, 0, ',', '.') }}</td>

                            <td>{{ $doc->tipo_docto_referencia }}</td>
                            <td>{{ $doc->folio_docto_referencia }}</td>
                        </tr>
                    @endforeach
                </tbody>





            </table>
        </div>

    </div>


</div>
</div>
@endsection

