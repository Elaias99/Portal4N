{{--
    Calendario operativo de zonas.

    Cada casilla representa:

    zona + fecha → hubo despacho

    Los registros se enviarán dentro del formulario principal
    mediante zonas_operativas[].
--}}

@php
    $calendarioZonasModal = collect(
        $calendarioZonas ?? []
    );

    $fechasFinSemanaModal = collect(
        $fechasFinSemana ?? []
    );

    /*
     * Cuando Laravel devuelve el formulario por un error,
     * priorizamos los valores enviados por el usuario.
     */
    $zonasOperativasAnteriores = collect(
        old('zonas_operativas', [])
    )
        ->filter(function ($registro) {
            return is_array($registro)
                && isset($registro['suscripcion_zona_id'])
                && isset($registro['fecha']);
        })
        ->keyBy(function ($registro) {
            return
                (int) $registro['suscripcion_zona_id']
                . '|'
                . (string) $registro['fecha'];
        });

    /*
     * Agrupar las fechas por fin de semana para mostrar
     * sábado y domingo bajo un mismo encabezado.
     */
    $gruposFinSemana = $fechasFinSemanaModal
        ->groupBy(function ($fecha) {
            return \Carbon\CarbonImmutable::parse($fecha)
                ->startOfWeek()
                ->toDateString();
        })
        ->values();

    $nombreMesCalendario =
        $meses[(int) $mes]
        ?? ('Mes ' . $mes);

    $totalCombinacionesCalendario =
        $calendarioZonasModal->count()
        * $fechasFinSemanaModal->count();

    $indiceZonaOperativa = 0;
@endphp

<div
    class="modal fade"
    id="modal-zonas-distribucion"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-zonas-distribucion-title"
    aria-hidden="true"
>
    <div
        class="modal-dialog modal-xl modal-dialog-scrollable"
        role="document"
    >
        <div class="modal-content">

            {{-- ENCABEZADO --}}
            <div class="modal-header bg-light">
                <div>
                    <h5
                        class="modal-title mb-1"
                        id="modal-zonas-distribucion-title"
                    >
                        Zonas de distribución
                    </h5>

                    <div class="small text-muted">
                        {{ $nombreMesCalendario }}
                        {{ $anio }}
                        ·
                        {{ $calendarioZonasModal->count() }} zonas
                        ·
                        {{ $fechasFinSemanaModal->count() }} fechas
                    </div>
                </div>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="Cerrar"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {{-- CONTENIDO --}}
            <div class="modal-body">

                <div class="alert alert-info small mb-3">
                    <strong>Casilla marcada:</strong>
                    la zona tuvo despacho durante esa fecha.

                    <br>

                    <strong>Casilla desmarcada:</strong>
                    la zona completa no tuvo despacho durante esa fecha.

                    <br>

                    Las inasistencias de una ruta o proveedor individual
                    deben registrarse por separado en
                    <strong>Novedades mensuales</strong>.
                </div>

                @if($calendarioZonasConfigurado ?? false)
                    <div class="alert alert-success small mb-3">
                        El calendario de este período ya fue configurado
                        completamente. Puedes revisar o modificar las casillas.
                    </div>
                @elseif($calendarioZonasIncompleto ?? false)
                    <div class="alert alert-warning small mb-3">
                        Existen registros parciales para este período.
                        Las combinaciones que todavía no estaban guardadas
                        aparecen marcadas por defecto.
                    </div>
                @else
                    <div class="alert alert-light border small mb-3">
                        Este período todavía no tiene calendario zonal
                        guardado. Todas las fechas aparecen marcadas por
                        defecto.
                    </div>
                @endif

                @error('zonas_operativas')
                    <div class="alert alert-danger small mb-3">
                        {{ $message }}
                    </div>
                @enderror

                @if(
                    $calendarioZonasModal->isEmpty()
                    || $fechasFinSemanaModal->isEmpty()
                )
                    <div class="alert alert-warning mb-0">
                        No fue posible construir el calendario zonal para
                        el período seleccionado.
                    </div>
                @else
                    <div class="table-responsive">
                        <table
                            class="table table-sm table-bordered align-middle mb-0"
                            id="tabla-zonas-distribucion"
                        >
                            <thead class="thead-light">
                                <tr>
                                    <th
                                        rowspan="2"
                                        class="align-middle text-center text-nowrap"
                                        style="min-width: 90px;"
                                    >
                                        Zona
                                    </th>

                                    <th
                                        rowspan="2"
                                        class="align-middle text-nowrap"
                                        style="min-width: 170px;"
                                    >
                                        Despacho
                                    </th>

                                    @foreach($gruposFinSemana as $indiceFinSemana => $grupoFechas)
                                        <th
                                            colspan="{{ $grupoFechas->count() }}"
                                            class="text-center text-nowrap"
                                        >
                                            Fin de semana
                                            {{ $indiceFinSemana + 1 }}
                                        </th>
                                    @endforeach
                                </tr>

                                <tr>
                                    @foreach($gruposFinSemana as $grupoFechas)
                                        @foreach($grupoFechas as $fecha)
                                            @php
                                                $fechaCarbon =
                                                    \Carbon\CarbonImmutable::parse(
                                                        $fecha
                                                    );

                                                $nombreDia = $fechaCarbon->isSaturday()
                                                    ? 'Sáb'
                                                    : 'Dom';
                                            @endphp

                                            <th
                                                class="text-center text-nowrap"
                                                style="min-width: 75px;"
                                            >
                                                <div>
                                                    {{ $nombreDia }}
                                                </div>

                                                <div class="small text-muted">
                                                    {{ $fechaCarbon->format('d/m') }}
                                                </div>
                                            </th>
                                        @endforeach
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($calendarioZonasModal as $zona)
                                    <tr
                                        data-zona-distribucion-row
                                        data-zona-id="{{ $zona['id'] }}"
                                    >
                                        <td class="text-center">
                                            <strong>
                                                {{ $zona['numero_zona'] }}
                                            </strong>
                                        </td>

                                        <td>
                                            {{ $zona['despacho'] }}
                                        </td>

                                        @foreach(collect($zona['dias'] ?? []) as $dia)
                                            @php
                                                $claveAnterior =
                                                    (int) $zona['id']
                                                    . '|'
                                                    . $dia['fecha'];

                                                $registroAnterior =
                                                    $zonasOperativasAnteriores
                                                        ->get($claveAnterior);

                                                if ($registroAnterior !== null) {
                                                    $valorAnterior = (string) (
                                                        $registroAnterior[
                                                            'hubo_despacho'
                                                        ] ?? '0'
                                                    );

                                                    $huboDespacho = in_array(
                                                        $valorAnterior,
                                                        [
                                                            '1',
                                                            'true',
                                                            'on',
                                                        ],
                                                        true
                                                    );

                                                    $observacion =
                                                        $registroAnterior[
                                                            'observacion'
                                                        ] ?? null;
                                                } else {
                                                    $huboDespacho =
                                                        (bool) (
                                                            $dia[
                                                                'hubo_despacho'
                                                            ] ?? true
                                                        );

                                                    $observacion =
                                                        $dia['observacion']
                                                        ?? null;
                                                }

                                                $fechaDia =
                                                    \Carbon\CarbonImmutable::parse(
                                                        $dia['fecha']
                                                    );
                                            @endphp

                                            <td class="text-center align-middle">
                                                <input
                                                    type="hidden"
                                                    name="zonas_operativas[{{ $indiceZonaOperativa }}][suscripcion_zona_id]"
                                                    value="{{ $zona['id'] }}"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="zonas_operativas[{{ $indiceZonaOperativa }}][fecha]"
                                                    value="{{ $dia['fecha'] }}"
                                                >

                                                {{--
                                                    Si el checkbox está desmarcado,
                                                    este input envía 0.
                                                --}}
                                                <input
                                                    type="hidden"
                                                    name="zonas_operativas[{{ $indiceZonaOperativa }}][hubo_despacho]"
                                                    value="0"
                                                >

                                                {{--
                                                    Si está marcado, el navegador
                                                    enviará posteriormente el valor 1.
                                                --}}
                                                <input
                                                    type="checkbox"
                                                    name="zonas_operativas[{{ $indiceZonaOperativa }}][hubo_despacho]"
                                                    value="1"
                                                    class="form-check-input position-static"
                                                    data-zona-operativa-checkbox
                                                    data-zona-id="{{ $zona['id'] }}"
                                                    data-zona-numero="{{ $zona['numero_zona'] }}"
                                                    data-fecha="{{ $dia['fecha'] }}"
                                                    title="Zona {{ $zona['numero_zona'] }} · {{ $zona['despacho'] }} · {{ $fechaDia->format('d/m/Y') }}"
                                                    @checked($huboDespacho)
                                                >

                                                <input
                                                    type="hidden"
                                                    name="zonas_operativas[{{ $indiceZonaOperativa }}][observacion]"
                                                    value="{{ $observacion }}"
                                                >
                                            </td>

                                            @php
                                                $indiceZonaOperativa++;
                                            @endphp
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="small text-muted mt-3">
                        Se enviarán
                        <strong>
                            {{ $totalCombinacionesCalendario }}
                        </strong>
                        combinaciones de zona y fecha.
                    </div>
                @endif
            </div>

            {{-- PIE --}}
            <div class="modal-footer">
                <div class="small text-muted mr-auto">
                    La selección se guardará en la base de datos al presionar
                    <strong>Guardar datos y generar mes completo</strong>.
                </div>

                <button
                    type="button"
                    class="btn btn-primary"
                    data-dismiss="modal"
                >
                    Aplicar selección
                </button>
            </div>
        </div>
    </div>
</div>