@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $n = fn ($v) => number_format((int) ($v ?? 0), 0, ',', '.');
@endphp

<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Apoyo Alza',
        'subtitulo' => 'A qué proveedor se le da apoyo, sobre qué y de cuánto. No tiene mes: cada período lo calcula solo con estas reglas.',
    ])

    @include('courier.partials.nav', ['activo' => 'apoyo-alza'])

    @if(session('apoyoCargado'))
        @php $c = session('apoyoCargado'); @endphp
        <div class="co-alert co-alert-ok" role="status">
            <strong>{{ $n($c['reglas']) }} reglas cargadas de {{ $c['archivo'] }}.</strong>
            Reemplazan la lista anterior. Los meses abiertos las toman al volver a calcular.
        </div>
    @endif

    @if($errors->has('archivo'))
        <div class="co-alert co-alert-danger" role="alert">{{ $errors->first('archivo') }}</div>
    @endif

    <section class="co-region">
        <form method="GET" action="{{ route('courier.apoyo-alza') }}" class="co-filters" role="search">
            <div class="co-field co-span-6">
                <label for="q">Proveedor, RUT o agencia</label>
                <input type="search" id="q" name="q" class="form-control"
                       value="{{ $buscar }}" autocomplete="off">
            </div>
            <div class="co-span-2">
                <button type="submit" class="co-btn co-btn-primary w-100">Buscar</button>
            </div>
            @if($buscar !== '')
                <div class="co-span-2">
                    <a href="{{ route('courier.apoyo-alza') }}" class="co-btn co-btn-muted w-100">Limpiar</a>
                </div>
            @endif
        </form>

        <div class="co-region-head">
            <h2 class="co-region-title">{{ $n($reglasApoyo->total()) }} reglas</h2>
        </div>

        <div class="co-region-body is-flush">
            @if($reglasApoyo->isEmpty())
                <div class="co-empty">
                    {{ $buscar !== '' ? 'Ninguna regla coincide con la búsqueda.' : 'Todavía no hay reglas. Carga la plantilla más abajo.' }}
                </div>
            @else
                <div class="table-responsive">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Proveedor</th>
                                <th>RUT</th>
                                <th>Sobre qué</th>
                                <th>Servicio del acuerdo</th>
                                <th class="is-num">Cuánto</th>
                                <th>Agencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reglasApoyo as $r)
                                <tr>
                                    <td class="is-strong">{{ $r->proveedor ?? '—' }}</td>
                                    <td class="is-mono">{{ $r->rut_proveedor ?? '—' }}</td>
                                    <td>{{ $r->proceso_base }}</td>
                                    <td>{{ $r->servicio_acuerdo ?? '—' }}</td>
                                    <td class="is-num">
                                        @if($r->factor === '%')
                                            {{ rtrim(rtrim(number_format((float) $r->porcentaje * 100, 4, ',', '.'), '0'), ',') }} %
                                        @else
                                            ${{ $n($r->monto_dia) }} por día
                                        @endif
                                    </td>
                                    <td>{{ $r->agencia ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="co-pagination">
                    <span>
                        Mostrando {{ $reglasApoyo->firstItem() }}–{{ $reglasApoyo->lastItem() }} de {{ $n($reglasApoyo->total()) }}
                    </span>
                    {{ $reglasApoyo->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </section>

    <section class="co-region">
        <div class="co-region-head">
            <h2 class="co-region-title">Cargar la lista</h2>
        </div>
        <div class="co-region-body">
            <p class="co-note">
                Plantilla de Apoyo Alza, en la primera hoja:
                <strong>Razón Social Cliente · RUT Cliente · Proceso · Servicio de Acuerdo · Factor · Porcentaje · Monto · Empresa Mandante · Agencia</strong>
                (y Zona, si se quiere). Las dos primeras columnas son el proveedor.
                La carga <strong>reemplaza la lista completa</strong>.
            </p>

            <form method="POST" action="{{ route('courier.apoyo-alza.cargar') }}" enctype="multipart/form-data" data-subir>
                @csrf
                <div class="co-field">
                    <label for="archivo-apoyo">Plantilla de Apoyo Alza</label>
                    <input type="file" id="archivo-apoyo" name="archivo" class="form-control" accept=".xlsx" required>
                </div>
                <button type="submit" class="co-btn co-btn-primary" data-loading-text="Cargando…" style="margin-top:.75rem">Cargar lista</button>
            </form>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('[data-subir]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var boton = form.querySelector('button[type="submit"]');

            if (boton) {
                boton.disabled = true;
                boton.textContent = boton.getAttribute('data-loading-text') || 'Procesando…';
            }
        });
    });
})();
</script>
@endpush
