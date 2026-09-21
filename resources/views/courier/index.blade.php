@extends('layouts.app')

@vite('resources/css/courier.css')

@section('content')
@php
    $n = fn ($v) => number_format((int) $v, 0, ',', '.');
    $hayBultos = $alertas !== null && $alertas['total'] > 0;
    $hayPesajes = $alertas !== null && $alertas['pesajes']['registros'] > 0;
    $ultimaCarga = $importaciones->first();
    $tipoMostrado = $mostradas->first()?->tipo;
@endphp

<div class="co-page">

    @include('courier.partials.header', [
        'titulo' => 'Courier · Pago del mes',
        'subtitulo' => 'Carga la descarga de Geolice, revisa qué no calza con los catálogos y sigue el proceso hasta el pago a cada agente.',
        'volverRuta' => route('cobranzas.general'),
        'volverTexto' => 'Volver al panel de Finanzas',
        'meta' => 'Datos mensuales · Geolice y bodega',
    ])

    @include('courier.partials.nav', ['activo' => 'index'])

    @if($errors->any())
        <div class="co-alert co-alert-danger" role="alert">
            <strong>No se pudo importar.</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ====== PERÍODO ====== --}}
    <section class="co-periodo">
        <form method="GET" action="{{ route('courier.index') }}" class="co-periodo-form">
            <label for="periodo">Período de pago</label>
            <select id="periodo" name="periodo" class="form-select" onchange="this.form.submit()" @disabled($periodos->isEmpty())>
                @forelse($periodos as $p)
                    <option value="{{ $p->codigo }}" @selected($periodo && $p->id === $periodo->id)>{{ $p->nombre }}</option>
                @empty
                    <option value="">Sin períodos todavía</option>
                @endforelse
            </select>
        </form>

        <div class="co-periodo-info">
            @if($periodo)
                <span class="co-badge {{ $periodo->estaCerrado() ? 'co-badge-neutral' : 'co-badge-si' }}">
                    {{ $periodo->estaCerrado() ? 'Cerrado' : 'Abierto' }}
                </span>
                <span class="co-periodo-kpi"><strong>{{ $n($alertas['total']) }}</strong> bultos cargados</span>
                @if($ultimaCarga)
                    <span class="co-periodo-kpi">Última carga <strong>{{ $ultimaCarga->created_at->format('d-m-Y H:i') }}</strong></span>
                @endif
            @else
                <span class="co-periodo-kpi is-muted">Aún no hay períodos: la primera carga crea el suyo.</span>
            @endif
        </div>
    </section>

    {{-- ====== PASOS DEL PROCESO ====== --}}
    <ol class="co-steps">
        <li class="co-step {{ $hayBultos ? 'is-done' : 'is-current' }}">
            <span class="co-step-num">1</span>
            <span class="co-step-body">
                <span class="co-step-title">Importar Geolice</span>
                <span class="co-step-state">{{ $hayBultos ? $n($alertas['total']) . ' bultos' : 'Pendiente' }}</span>
            </span>
        </li>
        <li class="co-step {{ $hayPesajes ? 'is-done' : ($hayBultos ? 'is-current' : 'is-soon') }}">
            <span class="co-step-num">2</span>
            <span class="co-step-body">
                <span class="co-step-title">Importar pesajes de bodega</span>
                <span class="co-step-state">
                    @if($hayPesajes)
                        {{ $n($alertas['pesajes']['dias']) }} {{ $alertas['pesajes']['dias'] === 1 ? 'día' : 'días' }} · {{ $n($alertas['con_pesaje']) }} bultos con peso
                    @elseif($hayBultos)
                        Pendiente
                    @else
                        Después del paso 1
                    @endif
                </span>
            </span>
        </li>
        <li class="co-step is-soon">
            <span class="co-step-num">3</span>
            <span class="co-step-body">
                <span class="co-step-title">Calcular pago</span>
                <span class="co-step-state">Próximo paso</span>
            </span>
        </li>
        <li class="co-step is-soon">
            <span class="co-step-num">4</span>
            <span class="co-step-body">
                <span class="co-step-title">Resumen y banco</span>
                <span class="co-step-state">Próximo paso</span>
            </span>
        </li>
    </ol>

    {{-- ====== RESULTADO DE UNA CARGA (carrusel) ====== --}}
    @if($mostradas->isNotEmpty())
        @if($tipoMostrado === \App\Models\CourierImportacion::TIPO_PESAJES)
            @include('courier.partials.pesajes-slider', ['cargas' => $mostradas, 'periodo' => $periodo])
        @else
            @include('courier.partials.importacion-slider', ['importacion' => $mostradas->first(), 'periodo' => $periodo])
        @endif
    @endif

    {{-- ====== PASO 1: IMPORTAR ====== --}}
    <section class="co-region co-upload" id="importar">
        <div class="co-region-head">
            <h2 class="co-region-title">Paso 1 · Importar descarga de Geolice</h2>
            <span class="co-region-meta">El archivo <code>export-NNNN-packages.xlsx</code>, tal cual lo entrega Geolice</span>
        </div>
        <form method="POST" action="{{ route('courier.importar-geolice') }}" enctype="multipart/form-data" class="co-region-body co-upload-form" data-upload>
            @csrf
            <div class="co-upload-row">
                <div class="co-field co-upload-file">
                    <label for="archivo">Archivo</label>
                    <input type="file" id="archivo" name="archivo" class="form-control" accept=".xlsx" required>
                </div>
                <div class="co-field">
                    <label for="mes">Mes de pago</label>
                    <input type="month" id="mes" name="periodo" class="form-control" value="{{ old('periodo', $mesSugerido) }}" required>
                </div>
                <button type="submit" class="co-btn co-btn-primary" data-submit>Importar</button>
            </div>
            <p class="co-note">
                Se puede cargar la misma descarga o una más nueva del mismo mes: los bultos ya cargados se actualizan, no se duplican.
                Los que ya existen de un mes anterior no se tocan.
            </p>
            <p class="co-upload-progress" data-progress hidden>
                Importando… la descarga trae decenas de miles de filas y puede tardar un par de minutos. No cierres esta pestaña.
            </p>
        </form>
    </section>

    {{-- ====== PASO 2: PESAJES DE BODEGA ====== --}}
    <section class="co-region co-upload" id="pesajes">
        <div class="co-region-head">
            <h2 class="co-region-title">Paso 2 · Importar pesajes de bodega</h2>
            <span class="co-region-meta">Los CSV <code>Proceso del dia dd-mm-aaaa.csv</code>; puedes elegir varios días a la vez</span>
        </div>
        <form method="POST" action="{{ route('courier.importar-pesajes') }}" enctype="multipart/form-data" class="co-region-body co-upload-form" data-upload>
            @csrf
            <div class="co-upload-row">
                <div class="co-field co-upload-file">
                    <label for="archivos">Archivos</label>
                    <input type="file" id="archivos" name="archivos[]" class="form-control" accept=".csv,.txt" multiple required>
                </div>
                <div class="co-field">
                    <label for="mes-pesajes">Mes de pago</label>
                    <input type="month" id="mes-pesajes" name="periodo" class="form-control" value="{{ old('periodo', $mesSugerido) }}" required>
                </div>
                <button type="submit" class="co-btn co-btn-primary" data-submit>Importar</button>
            </div>
            <p class="co-note">
                La fecha del pesaje se toma del nombre del archivo. Cada bulto guarda su peso por día: si el mismo CSV se carga dos veces, se actualiza, no se duplica.
                @if($hayBultos && ! $hayPesajes)
                    Hoy {{ $n($alertas['total']) }} bultos esperan su peso de bodega.
                @endif
            </p>
            <p class="co-upload-progress" data-progress hidden>
                Importando pesajes…
            </p>
        </form>
    </section>

    {{-- ====== ALERTAS VIVAS DEL PERÍODO ====== --}}
    @if($hayBultos)
        @php
            $comunasFuera = $alertas['comunas_fuera_de_catalogo'];
            $sinConfig = $alertas['sin_configuracion'];
            $estadosDesc = $alertas['estados_desconocidos'];
        @endphp

        <div class="co-alertas-head">
            <h2 class="co-region-title">Qué no calza con los catálogos</h2>
            <span class="co-region-meta">Se recalcula cada vez que abres esta página. Cuando el catálogo se corrige, la alerta desaparece sola.</span>
        </div>

        <div class="co-alertas">

            <article class="co-alerta {{ $comunasFuera === [] ? 'is-ok' : 'is-warn' }}">
                <span class="co-alerta-count">{{ $n(count($comunasFuera)) }}</span>
                <h3 class="co-alerta-title">{{ count($comunasFuera) === 1 ? 'Comuna no reconocida' : 'Comunas no reconocidas' }}</h3>
                <p class="co-alerta-text">
                    @if($comunasFuera === [])
                        Todas las comunas de destino existen en el catálogo.
                    @else
                        {{ $n($alertas['comunas_fuera_bultos']) }} bultos vienen con una comuna que no está en el catálogo, así que no se sabe qué agente los reparte.
                    @endif
                </p>
                @if($comunasFuera !== [])
                    <details class="co-alerta-details">
                        <summary>Ver comunas</summary>
                        <ul class="co-alerta-list">
                            @foreach(array_slice($comunasFuera, 0, 30, true) as $comuna => $cant)
                                <li><span>{{ $comuna }}</span><strong>{{ $n($cant) }}</strong></li>
                            @endforeach
                        </ul>
                        @if(count($comunasFuera) > 30)
                            <p class="co-note">… y {{ count($comunasFuera) - 30 }} más.</p>
                        @endif
                    </details>
                @endif
            </article>

            <article class="co-alerta {{ $sinConfig === [] ? 'is-ok' : 'is-warn' }}">
                <span class="co-alerta-count">{{ $n(count($sinConfig)) }}</span>
                <h3 class="co-alerta-title">{{ count($sinConfig) === 1 ? 'Combinación sin configuración de pago' : 'Combinaciones sin configuración de pago' }}</h3>
                <p class="co-alerta-text">
                    @if($sinConfig === [])
                        Todas las combinaciones agente + cliente + servicio tienen configuración.
                    @else
                        {{ $n($alertas['sin_configuracion_bultos']) }} bultos caen en una combinación agente + cliente + servicio que no existe en Configuración de pago. No es tabla 0: nadie ha definido si se pagan.
                    @endif
                </p>
                @if($sinConfig !== [])
                    <details class="co-alerta-details">
                        <summary>Ver combinaciones</summary>
                        <ul class="co-alerta-list">
                            @foreach(array_slice($sinConfig, 0, 30, true) as $combo => $cant)
                                <li><span>{{ $combo }}</span><strong>{{ $n($cant) }}</strong></li>
                            @endforeach
                        </ul>
                        @if(count($sinConfig) > 30)
                            <p class="co-note">… y {{ count($sinConfig) - 30 }} más.</p>
                        @endif
                    </details>
                @endif
            </article>

            <article class="co-alerta {{ $estadosDesc === [] ? 'is-ok' : 'is-danger' }}">
                <span class="co-alerta-count">{{ $n(count($estadosDesc)) }}</span>
                <h3 class="co-alerta-title">{{ count($estadosDesc) === 1 ? 'Estado de entrega fuera del catálogo' : 'Estados de entrega fuera del catálogo' }}</h3>
                <p class="co-alerta-text">
                    @if($estadosDesc === [])
                        Todos los estados que informa Geolice están en el catálogo, con su regla de pagar o descontar.
                    @else
                        Geolice informa estados que el catálogo no conoce; sin regla, no se sabe si esos bultos se pagan o se descuentan.
                    @endif
                </p>
                @if($estadosDesc !== [])
                    <ul class="co-alerta-list">
                        @foreach($estadosDesc as $e)
                            <li><span>{{ $e['estado'] }}</span><strong>{{ $n($e['bultos']) }}</strong></li>
                        @endforeach
                    </ul>
                @endif
            </article>

        </div>

        <div class="co-chips">
            <span class="co-chip is-ok"><strong>{{ $n($alertas['con_pesaje']) }}</strong> con pesaje de bodega</span>
            <span class="co-chip"><strong>{{ $n($alertas['sin_pesaje']) }}</strong> sin pesaje de bodega</span>
            <span class="co-chip"><strong>{{ $n($alertas['sin_peso_declarado']) }}</strong> sin peso declarado</span>
            <span class="co-chip"><strong>{{ $n($alertas['sin_comuna']) }}</strong> sin comuna de destino</span>
            @foreach($alertas['estados'] as $e)
                <span class="co-chip {{ $e['considerar'] === 'DESCONTAR' ? 'is-descontar' : '' }}">
                    <strong>{{ $n($e['bultos']) }}</strong> {{ $e['estado'] }}
                </span>
            @endforeach
        </div>
    @endif

    {{-- ====== HISTORIAL DE CARGAS ====== --}}
    @if($importaciones->isNotEmpty())
        <section class="co-region">
            <div class="co-region-head">
                <h2 class="co-region-title">Cargas de este período</h2>
                <span class="co-region-meta">Quién cargó qué y cuándo</span>
            </div>
            <div class="co-region-body is-flush">
                <div class="table-responsive">
                    <table class="co-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Archivo</th>
                                <th>Usuario</th>
                                <th class="is-num">Filas</th>
                                <th class="is-num">Nuevos</th>
                                <th class="is-num">Actualizados</th>
                                <th class="is-num">Duración</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($importaciones as $carga)
                                <tr class="{{ $mostradas->contains('id', $carga->id) ? 'is-selected' : '' }}">
                                    <td class="is-strong">{{ $carga->created_at->format('d-m-Y H:i') }}</td>
                                    <td>
                                        <span class="co-badge {{ $carga->tipo === \App\Models\CourierImportacion::TIPO_PESAJES ? 'co-badge-neutral' : 'co-badge-accent' }}">
                                            {{ $carga->tipo === \App\Models\CourierImportacion::TIPO_PESAJES ? 'Pesajes' : 'Geolice' }}
                                        </span>
                                    </td>
                                    <td class="is-mono">{{ $carga->archivo }}</td>
                                    <td>{{ $carga->usuario?->name ?? 'Terminal' }}</td>
                                    <td class="is-num">{{ $n($carga->filas) }}</td>
                                    <td class="is-num">{{ $n($carga->nuevos) }}</td>
                                    <td class="is-num">{{ $n($carga->actualizados) }}</td>
                                    <td class="is-num is-muted">{{ $carga->duracion_seg !== null ? $carga->duracion_seg . ' s' : '—' }}</td>
                                    <td class="is-num">
                                        <a href="{{ route('courier.index', ['periodo' => $periodo->codigo, 'importacion' => $carga->id]) }}#resultado" class="co-btn co-btn-muted co-btn-sm">Ver resultado</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

</div>
@endsection

@push('scripts')
<script>
(function () {
    /* Carrusel del resultado de la carga: una tarjeta por dato. */
    document.querySelectorAll('[data-slider]').forEach(function (slider) {
        var track = slider.querySelector('[data-track]');
        var slides = Array.prototype.slice.call(track.children);
        var dots = slider.querySelector('[data-dots]');
        var prev = slider.querySelector('[data-prev]');
        var next = slider.querySelector('[data-next]');
        var current = slider.querySelector('[data-current]');
        var total = slider.querySelector('[data-total]');
        var index = 0;

        if (!slides.length) { return; }

        total.textContent = slides.length;

        slides.forEach(function (_, i) {
            var dot = document.createElement('button');
            dot.type = 'button';
            dot.setAttribute('aria-label', 'Ir a la tarjeta ' + (i + 1));
            dot.addEventListener('click', function () { ir(i); });
            dots.appendChild(dot);
        });

        function pad() {
            return parseFloat(getComputedStyle(track).paddingLeft) || 0;
        }

        function ir(i) {
            i = Math.max(0, Math.min(slides.length - 1, i));
            track.scrollTo({ left: slides[i].offsetLeft - pad(), behavior: 'smooth' });
        }

        function actualizar() {
            var x = track.scrollLeft + pad() + 2;
            var i = 0;
            slides.forEach(function (s, k) { if (s.offsetLeft <= x) { i = k; } });
            if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 2) { i = slides.length - 1; }
            index = i;
            current.textContent = i + 1;
            Array.prototype.forEach.call(dots.children, function (d, k) { d.classList.toggle('is-active', k === i); });
            prev.disabled = i === 0;
            next.disabled = i === slides.length - 1;
        }

        prev.addEventListener('click', function () { ir(index - 1); });
        next.addEventListener('click', function () { ir(index + 1); });
        track.addEventListener('scroll', function () { window.requestAnimationFrame(actualizar); });
        track.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') { e.preventDefault(); ir(index + 1); }
            if (e.key === 'ArrowLeft') { e.preventDefault(); ir(index - 1); }
        });

        actualizar();
    });

    /* Formularios de carga: avisan que están trabajando y evitan el doble envío. */
    document.querySelectorAll('[data-upload]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var boton = form.querySelector('[data-submit]');
            var aviso = form.querySelector('[data-progress]');
            if (boton) { boton.disabled = true; boton.textContent = 'Importando…'; }
            if (aviso) { aviso.hidden = false; }
        });
    });
})();
</script>
@endpush
