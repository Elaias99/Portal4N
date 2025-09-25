@extends('layouts.app')

@section('content')

<div class="container">

    <div class="row">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center">
                            <thead class="thead-light">
                                <tr>

                                    <th>Rut Cliente</th>
                                    <th>Razón Social</th>
                                    <th>Servicio</th>
                                    <th>Creditos</th>

                                </tr>


                            </thead>

                            <tbody>
                                @foreach ($cobranzas as $cobranza)
                                    <tr>

                                        <td>{{ $cobranza->rut_cliente }}</td>
                                        <td>{{ $cobranza->razon_social }}</td>
                                        <td>{{ $cobranza->servicio }}</td>
                                        <td>{{ $cobranza->creditos }}</td>

                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    

</div>



@endsection
