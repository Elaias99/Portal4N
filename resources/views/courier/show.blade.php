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
                    <div class="table-responsive">
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
            <section class="co-region">
                <div class="co-region-head">
                    <h3 class="co-region-title">Titular del pago</h3>
                    <span class="co-region-meta">Hoja Operador, col. NombreProveedor</span>
                </div>
                <div class="co-region-body">
                    @forelse($agente->proveedores as $prov)
                        <div class="co-titular">
                            <span>{{ $prov->nombre_proveedor }}</span>
                            @if($agente->proveedores->count() > 1 && $prov->principal)
                                <span class="co-badge co-badge-neutral">principal</span>
                            @endif
                        </div>
                    @empty
                        <span class="is-muted">Sin titular registrado.</span>
                    @endforelse

                    @if($agente->proveedores->count() > 1)
                        <p class="co-note" style="margin-top:0.75rem">
                            Más de un titular según la comuna. El proveedor que recibe el pago se resuelve en
                            <a href="{{ route('courier.proveedores', ['q' => $agente->nombre]) }}">Proveedores</a>
                            por agente + repartidor.
                        </p>
                    @endif
                </div>
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

</div>
@endsection
