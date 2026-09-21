@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Ficha del agente',
        'volverRuta' => route('courier.agentes.index'),
        'volverTexto' => 'Volver a agentes',
    ])

    @include('courier.partials.nav', ['activo' => 'agentes'])

    {{-- ====== CABECERA DEL AGENTE ====== --}}
    <div class="co-ficha-head">
        <div>
            <h2 class="co-ficha-name">{{ $agente->nombre }}</h2>
            <div class="co-ficha-tags">
                @forelse($zonas as $zona)
                    <span class="co-badge {{ $zona === 'RM' ? 'co-badge-zona-rm' : 'co-badge-zona-regiones' }}">Zona {{ $zona }}</span>
                @empty
                    <span class="co-badge co-badge-zona-none">Sin zona registrada</span>
                @endforelse
                @if(! $agente->activo)
                    <span class="co-badge co-badge-no">inactivo</span>
                @endif
            </div>
        </div>

        <div class="co-ficha-kpis">
            <div>
                <div class="co-ficha-kpi-value">{{ number_format($cobertura->count(), 0, ',', '.') }}</div>
                <div class="co-ficha-kpi-label">comunas</div>
            </div>
            <div>
                <div class="co-ficha-kpi-value">{{ number_format($configuraciones->count(), 0, ',', '.') }}</div>
                <div class="co-ficha-kpi-label">config. de pago</div>
            </div>
            <div>
                <div class="co-ficha-kpi-value">{{ number_format($resumenPago['si'], 0, ',', '.') }}</div>
                <div class="co-ficha-kpi-label">se pagan</div>
            </div>
        </div>
    </div>

    <div class="co-ficha-grid">

        {{-- ====== COLUMNA IZQUIERDA: CONFIGURACIÓN DE PAGO ====== --}}
        <section class="co-region">
            <div class="co-region-head">
                <h3 class="co-region-title">Configuración de pago</h3>
                <span class="co-region-meta">
                    <span class="co-badge co-badge-si">SI {{ $resumenPago['si'] }}</span>
                    <span class="co-badge co-badge-no">NO {{ $resumenPago['no'] }}</span>
                    @if($resumenPago['revisar'] > 0)
                        <span class="co-badge co-badge-revisar">REVISAR {{ $resumenPago['revisar'] }}</span>
                    @endif
                </span>
            </div>
            <div class="co-region-body is-flush">
                @if($configuraciones->isEmpty())
                    <div class="co-empty">Este agente no tiene configuraciones de pago.</div>
                @else
                    <div class="table-responsive co-ficha-scroll">
                        <table class="co-table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Servicio</th>
                                    <th>¿Se paga?</th>
                                    <th>Tarifa</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($configuraciones as $cfg)
                                    <tr>
                                        <td>{{ $cfg->comerciante }}</td>
                                        <td class="is-muted">{{ $cfg->servicio }}</td>
                                        <td>
                                            @if($cfg->pagar === 'SI')
                                                <span class="co-badge co-badge-si">SI</span>
                                            @elseif($cfg->pagar === 'NO')
                                                <span class="co-badge co-badge-no">NO</span>
                                            @else
                                                <span class="co-badge co-badge-revisar">REVISAR</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($cfg->tabla === null)
                                                <span class="is-muted">—</span>
                                            @else
                                                <span class="co-badge {{ $cfg->tabla === 0 ? 'co-badge-neutral' : 'co-badge-accent' }}"
                                                      title="{{ $cfg->tarifa_nombre }}">
                                                    Tabla {{ $cfg->tabla }}
                                                </span>
                                                @if($cfg->tabla !== 0 && $cfg->tarifa_nombre)
                                                    <span class="is-muted" style="font-size:0.72rem"> {{ $cfg->tarifa_nombre }}</span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        {{-- ====== COLUMNA DERECHA: TITULARES Y COMUNAS ====== --}}
        <div>



            {{-- Titular del pago: una sola fila, sin cuerpo. Título a la
                 izquierda, nombre(s) a la derecha. --}}
            <section class="co-region">
                <div class="co-region-head">
                    <h3 class="co-region-title">Titular del pago</h3>
                    <span class="co-titulares">
                        @forelse($agente->proveedores as $prov)
                            <span class="co-titular">
                                {{ $prov->nombre_proveedor }}
                                @if($agente->proveedores->count() > 1 && $prov->principal)
                                    <span class="co-badge co-badge-neutral">principal</span>
                                @endif
                            </span>
                        @empty
                            <span class="is-muted">Sin titular registrado.</span>
                        @endforelse
                    </span>
                </div>

                @if($agente->proveedores->count() > 1)
                    <div class="co-region-body" style="padding-top:0.6rem; padding-bottom:0.6rem">
                        <p class="co-note" style="margin:0">
                            Más de un titular según la comuna. El proveedor que recibe el pago se resuelve en
                            <a href="{{ route('courier.proveedores', ['q' => $agente->nombre]) }}">Proveedores</a>
                            por agente + repartidor.
                        </p>
                    </div>
                @endif
            </section>









            <section class="co-region">
                <div class="co-region-head">
                    <h3 class="co-region-title">Comunas que cubre</h3>
                    <span class="co-region-meta">{{ $cobertura->where('pagar_retorno', true)->count() }} pagan retorno</span>
                </div>
                <div class="co-region-body is-flush">
                    @if($cobertura->isEmpty())
                        <div class="co-empty">Este agente no tiene comunas asignadas.</div>
                    @else
                        <div class="table-responsive" style="max-height: 520px; overflow-y: auto">
                            <table class="co-table">
                                <thead>
                                    <tr>
                                        <th>Comuna</th>
                                        <th>Zona</th>
                                        <th class="is-num">Retorno</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cobertura as $fila)
                                        <tr>
                                            <td>{{ $fila->localidad }}</td>
                                            <td>
                                                @if($fila->zona)
                                                    <span class="co-badge {{ $fila->zona === 'RM' ? 'co-badge-zona-rm' : 'co-badge-zona-regiones' }}">{{ $fila->zona }}</span>
                                                @else
                                                    <span class="co-badge co-badge-zona-none">—</span>
                                                @endif
                                            </td>
                                            <td class="is-num">
                                                @if($fila->pagar_retorno)
                                                    ${{ number_format($fila->valor_retorno ?? 0, 0, ',', '.') }}
                                                @else
                                                    <span class="is-muted">no</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>

    </div>

    {{-- ====== TARIFAS QUE USA ESTE AGENTE ====== --}}
    @isset($tarifasAgente)
    <section class="co-region co-tarifas" id="tarifas">
        <div class="co-region-head">
            <div>
                <h3 class="co-region-title">Tarifas de este agente</h3>
                <p class="co-tarifas-sub">
                    Valor por bulto según kilos enteros. Desde 21 kg: valor de 20 kg + kilo adicional por cada kilo extra.
                </p>
            </div>
            <span class="co-region-meta">
                @foreach($tarifasAgente as $tarifa)
                    <span class="co-badge co-badge-accent">Tabla {{ $tarifa->numero }}</span>
                @endforeach
            </span>
        </div>

        @if($tarifasAgente->isEmpty())
            <div class="co-empty">Este agente no tiene tablas de tarifa asignadas.</div>
        @else
            {{-- Calculadora acotada a las tablas del agente --}}
            <form method="GET" action="{{ route('courier.agentes.show', $agente) }}#tarifas" class="co-filters co-tarifas-calc">
                <div class="co-field co-span-4">
                    <label for="tabla">¿Cuánto vale un bulto? · Tabla</label>
                    <select id="tabla" name="tabla" class="form-select">
                        @foreach($tarifasAgente as $t)
                            <option value="{{ $t->numero }}" @selected(($tablaCalc ?? $tarifasAgente->first()->numero) === $t->numero)>
                                Tabla {{ $t->numero }} · {{ $t->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="co-field co-span-2">
                    <label for="peso">Kilos (entero)</label>
                    <input type="number" id="peso" name="peso" class="form-control" min="1" max="5000"
                           value="{{ $pesoCalc ?? 3 }}">
                </div>
                <div class="co-span-2">
                    <button type="submit" class="co-btn co-btn-primary w-100">
                         Calcular
                    </button>
                </div>
                <div class="co-span-4">
                    @if($valorCalc !== null)
                        <div class="co-calc-result">
                            ${{ number_format($valorCalc, 0, ',', '.') }}
                            <small>tabla {{ $tablaCalc }} · {{ $pesoCalc }} kg{{ $pesoCalc > 20 ? ' · incluye kilo adicional' : '' }}</small>
                        </div>
                    @elseif($tablaCalc !== null)
                        <div class="co-calc-result">Sin valor para esa combinación</div>
                    @endif
                </div>
            </form>

            {{-- Tabla comprimida por tramos --}}
            <div class="co-region-body is-flush">
                <div class="table-responsive">
                    <table class="co-table co-tarifas-table">
                        <thead>
                            <tr>
                                <th class="co-tarifas-kilos">Kilos</th>
                                @foreach($tarifasAgente as $tarifa)
                                    <th class="is-num {{ ($tablaCalc ?? null) === $tarifa->numero ? 'is-hit' : '' }}">
                                        <span class="co-tarifas-num">Tabla {{ $tarifa->numero }}</span>
                                        <span class="co-tarifas-nombre">{{ $tarifa->nombre }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Una fila por rango: se abre una nueva donde alguna tabla cambia de valor. --}}
                            @foreach($tarifasTramos as $fila)
                                @php
                                    $filaHit = $pesoCalc !== null && $pesoCalc >= $fila['desde'] && $pesoCalc <= $fila['hasta'];
                                @endphp
                                <tr class="{{ $filaHit ? 'is-hit' : '' }}">
                                    <td class="co-tarifas-kilos">
                                        @if($fila['desde'] === $fila['hasta'])
                                            <strong>{{ $fila['desde'] }}</strong> kg
                                        @else
                                            <strong>{{ $fila['desde'] }} – {{ $fila['hasta'] }}</strong> kg
                                        @endif
                                    </td>
                                    @foreach($tarifasAgente as $tarifa)
                                        <td class="is-num co-tarifas-valor {{ $filaHit && ($tablaCalc ?? null) === $tarifa->numero ? 'is-hit' : '' }}">
                                            ${{ number_format($fila['valores'][$tarifa->numero] ?? 0, 0, ',', '.') }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr class="co-tarifas-adicional {{ $pesoCalc !== null && $pesoCalc > 20 ? 'is-hit' : '' }}">
                                <td class="co-tarifas-kilos">
                                    <strong>+ 21 kg</strong> <span class="is-muted">por kilo adicional</span>
                                </td>
                                @foreach($tarifasAgente as $tarifa)
                                    <td class="is-num co-tarifas-valor">
                                        @if($tarifa->esPlana())
                                            <span class="co-badge co-badge-neutral">plana</span>
                                        @else
                                            + ${{ number_format($tarifa->kilo_adicional, 0, ',', '.') }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="co-note co-tarifas-foot">
                    Cada columna es una tabla de la hoja <code>Pesos</code>; las filas juntan los kilos que valen lo mismo.
                    <a href="{{ route('courier.tarifas') }}">Ver las 17 tablas completas</a>.
                </p>
            </div>
        @endif
    </section>
    @endisset

</div>
@endsection
