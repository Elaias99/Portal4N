@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
<style>
    .co-regla-detalle summary {
        cursor: pointer;
        font-weight: 600;
    }

    .co-regla-detalle dl {
        display: grid;
        grid-template-columns: auto auto;
        gap: .15rem .75rem;
        margin: .4rem 0 0;
        font-size: .75rem;
    }

    .co-regla-detalle dt {
        font-weight: 500;
        color: #64748b;
    }

    .co-regla-detalle dd {
        margin: 0;
    }
</style>

<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Reglas de pago',
        'subtitulo' => 'Qué agente cobra por qué cliente y servicio, si se paga y con qué tabla. El cálculo del mes usa estas reglas.',
    ])

    @include('courier.partials.nav', ['activo' => 'reglas'])

    <section class="co-region">
        <form method="GET" action="{{ route('courier.reglas') }}" class="co-filters" role="search">
            <div class="co-field co-span-6">
                <label for="q">Courier o cliente</label>
                <input type="search" id="q" name="q" class="form-control"
                       value="{{ $buscar }}" placeholder="4N RM, Disalcar…" autocomplete="off">
            </div>
            <div class="co-span-2">
                <button type="submit" class="co-btn co-btn-primary w-100">Buscar</button>
            </div>
            @if($buscar !== '')
                <div class="co-span-2">
                    <a href="{{ route('courier.reglas') }}" class="co-btn co-btn-muted w-100">Limpiar</a>
                </div>
            @endif
        </form>

        <div class="co-region-head">
            <h2 class="co-region-title">
                {{ number_format($reglas->total(), 0, ',', '.') }} reglas
            </h2>
        </div>

        <div class="co-region-body is-flush">
            @if($reglas->isEmpty())
                <div class="co-empty">Ninguna regla coincide con la búsqueda.</div>
            @else
                <div class="table-responsive">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Courier</th>
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th>¿Se paga?</th>
                                <th>Tabla</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reglas as $r)
                                <tr>
                                    <td>
                                        <details class="co-regla-detalle">
                                            <summary>{{ $r->agente }}</summary>
                                            <dl>
                                                <dt>RUT courier</dt>
                                                <dd class="is-mono">{{ $r->rut_proveedor ?? '—' }}</dd>
                                                <dt>RUT cliente</dt>
                                                <dd class="is-mono">{{ $r->rut_cliente ?? '—' }}</dd>
                                                <dt>Código servicio</dt>
                                                <dd class="is-mono">{{ $r->codigo_servicio ?? '—' }}</dd>
                                            </dl>
                                        </details>
                                    </td>
                                    <td>{{ $r->comerciante }}</td>
                                    <td>{{ $r->servicio }}</td>
                                    <td>
                                        @if($r->pagar === 'SI')
                                            <span class="co-badge co-badge-si">SÍ</span>
                                        @elseif($r->pagar === 'NO')
                                            <span class="co-badge co-badge-no">NO</span>
                                        @else
                                            <span class="co-badge co-badge-revisar">{{ $r->pagar }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($r->pagar === 'SI' && $r->tabla !== null)
                                            {{ $r->tabla }}@if($r->tabla_nombre) · {{ $r->tabla_nombre }}@endif
                                        @else
                                            <span class="is-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="co-pagination">
                    <span>
                        Mostrando {{ $reglas->firstItem() }}–{{ $reglas->lastItem() }} de {{ number_format($reglas->total(), 0, ',', '.') }}
                    </span>
                    {{ $reglas->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
        <div class="co-region-body" style="border-top:1px solid var(--co-line-soft)">
            <p class="co-note">
                Haz clic en el nombre del courier para ver los RUT y el código del servicio.
            </p>
        </div>
    </section>

</div>
@endsection
