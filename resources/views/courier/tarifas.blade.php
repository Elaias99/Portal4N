@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Tablas tarifarias',
        'subtitulo' => 'Las 17 tablas de la hoja Pesos. Cada configuración de pago apunta a una de ellas.',
    ])

    @include('courier.partials.nav', ['activo' => 'tarifas'])

    {{-- ====== CALCULADORA ====== --}}
    <section class="co-region">
        <div class="co-region-head">
            <h2 class="co-region-title">¿Cuánto vale un bulto?</h2>
            <span class="co-region-meta">Misma regla que usará el cálculo mensual</span>
        </div>
        <form method="GET" action="{{ route('courier.tarifas') }}" class="co-filters" style="border-bottom:0">
            <div class="co-field co-span-5">
                <label for="tabla">Tabla</label>
                <select id="tabla" name="tabla" class="form-select">
                    @foreach($tarifas as $t)
                        <option value="{{ $t->numero }}" @selected($tablaCalc === $t->numero)>
                            {{ $t->numero }} · {{ $t->nombre }}
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
                <button type="submit" class="co-btn co-btn-primary w-100">Calcular</button>
            </div>
            <div class="co-span-3">
                @if($valorCalc !== null)
                    <div class="co-calc-result">
                        ${{ number_format($valorCalc, 0, ',', '.') }}
                        <small>tabla {{ $tablaCalc }} · {{ $pesoCalc }} kg{{ $pesoCalc > 20 ? ' · proyectado con kilo adicional' : '' }}</small>
                    </div>
                @elseif($tablaCalc !== null)
                    <div class="co-calc-result">Sin valor para esa combinación</div>
                @endif
            </div>
        </form>
    </section>

    {{-- ====== TABLAS ====== --}}
    <section class="co-region">
        <div class="co-region-head">
            <h2 class="co-region-title">{{ $tarifas->count() }} tablas</h2>
            <span class="co-region-meta">Valores en pesos chilenos, por kilo entero</span>
        </div>
        <div class="co-region-body is-flush">
            @if($tarifas->isEmpty())
                <div class="co-empty">No hay tarifas cargadas.</div>
            @else
                <div class="table-responsive">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th style="width:70px">Tabla</th>
                                <th>Nombre</th>
                                <th class="is-num">1 kg</th>
                                <th class="is-num">5 kg</th>
                                <th class="is-num">10 kg</th>
                                <th class="is-num">20 kg</th>
                                <th class="is-num">Kilo adicional</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tarifas as $tarifa)
                                <tr>
                                    <td>
                                        <span class="co-badge {{ $tarifa->numero === 0 ? 'co-badge-neutral' : 'co-badge-accent' }}">{{ $tarifa->numero }}</span>
                                    </td>
                                    <td class="{{ $tarifa->numero === 0 ? 'is-muted' : 'is-strong' }}">{{ $tarifa->nombre }}</td>

                                    @foreach(['precio_1', 'precio_5', 'precio_10', 'precio_20'] as $campo)
                                        <td class="is-num">
                                            @if($tarifa->$campo !== null)
                                                ${{ number_format($tarifa->$campo, 0, ',', '.') }}
                                            @else
                                                <span class="is-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <td class="is-num">
                                        @if($tarifa->esPlana())
                                            <span class="co-badge co-badge-neutral">plana</span>
                                        @else
                                            ${{ number_format($tarifa->kilo_adicional, 0, ',', '.') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        <div class="co-region-body" style="border-top:1px solid var(--co-line-soft)">
            <p class="co-note">
                Hasta 20 kg el valor está escrito en la tabla. Desde 21 kg se calcula como
                <code>valor(20 kg) + (kilos − 20) × kilo adicional</code>, igual que la planilla.
                Las tablas planas cobran lo mismo a cualquier peso. La tabla 0 vale $0 y se usa
                cuando la configuración dice que no se paga.
            </p>
        </div>
    </section>

</div>
@endsection
