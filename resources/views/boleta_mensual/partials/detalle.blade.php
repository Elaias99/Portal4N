<div id="detalle-contenido">

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Detalle Honorarios Mensuales</strong>

            <button type="button"
                    class="btn btn-sm btn-outline-secondary"
                    id="btn-volver-tarjetas">
                ← Volver
            </button>
        </div>

        <div class="card-body">

            @if($registros->isEmpty())
                <p class="text-muted">No hay registros para este período.</p>
            @else


                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Fecha Anulación</th>
                            <th>Emisor</th>
                            <th>RUT</th>
                            <th class="text-end">Bruto</th>
                            <th class="text-end">Retenido</th>
                            <th class="text-end">Pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registros as $r)
                            <tr>
                                <td>{{ $r->empresa->Nombre }}</td>
                                <td>{{ $r->folio }}</td>
                                <td>{{ $r->fecha_emision->format('d-m-Y') }}</td>
                                <td>{{ $r->estado }}</td>
                                <td>{{ $r->fecha_anulacion }}</td>
                                <td>{{ $r->razon_social_emisor }}</td>
                                <td>{{ $r->rut_emisor }}</td>
                                <td class="text-end">
                                    {{ number_format($r->monto_bruto, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($r->monto_retenido, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($r->monto_pagado, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        @if(isset($totales))
                            <tr class="fw-bold">
                                <td colspan="7">Totales</td>
                                <td class="text-end">
                                    {{ number_format($totales->monto_bruto, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($totales->monto_retenido, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($totales->monto_pagado, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif




                    </tbody>
                </table>


                
            @endif

        </div>

    </div>

</div>
