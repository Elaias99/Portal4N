{{--
    Resultado de una carga de Geolice, tarjeta por tarjeta, deslizando
    hacia la derecha. $importacion (CourierImportacion), $periodo.
--}}
@php
    $n = fn ($v) => number_format((int) $v, 0, ',', '.');
    $pct = fn ($parte, $total) => $total > 0 ? (int) round($parte * 100 / $total) : 0;

    $c = $importacion->conteos();
    $estados = $importacion->estados();
    $estadosDesconocidos = $importacion->estadosDesconocidos();
    $comunasFuera = $importacion->comunasFueraDeCatalogo();
    $sinConfig = $importacion->sinConfiguracion();

    $filas = (int) ($c['filas'] ?? 0);
    $nuevos = (int) ($c['nuevos'] ?? 0);
    $actualizados = (int) ($c['actualizados'] ?? 0);
    $sinCambio = (int) ($c['sin_cambio'] ?? 0);
    $anteriores = (int) ($c['de_periodo_anterior'] ?? 0);
    $conPeso = (int) ($c['con_peso_declarado'] ?? 0);
    $sinPeso = (int) ($c['sin_peso_declarado'] ?? 0);
    $sinComuna = (int) ($c['sin_comuna'] ?? 0);
    $comunasFueraBultos = (int) ($c['comuna_fuera_de_catalogo'] ?? 0);
    $sinConfigBultos = (int) ($c['sin_configuracion'] ?? 0);
    $fechasMal = (int) ($c['fechas_no_reconocidas'] ?? 0);

    $maxEstado = $estados === [] ? 1 : max(array_values($estados));
@endphp

<section class="co-slider" id="resultado" aria-label="Resultado de la carga" data-slider>

    <div class="co-slider-head">
        <div>
            <h2 class="co-region-title">Resultado de la carga</h2>
            <p class="co-slider-sub">
                <code>{{ $importacion->archivo }}</code>
                · {{ $periodo->nombre }}
                · {{ $importacion->created_at->format('d-m-Y H:i') }}
                · {{ $importacion->usuario?->name ?? 'Terminal' }}
            </p>
        </div>
        <div class="co-slider-nav">
            <button type="button" class="co-btn co-btn-muted co-btn-sm" data-prev>Anterior</button>
            <span class="co-slider-count"><span data-current>1</span> / <span data-total>1</span></span>
            <button type="button" class="co-btn co-btn-muted co-btn-sm" data-next>Siguiente</button>
        </div>
    </div>

    <div class="co-slider-track" tabindex="0" data-track>

        {{-- 1 · Portada --}}
        <article class="co-slide co-slide-hero">
            <span class="co-slide-eyebrow">Carga completada</span>
            <span class="co-slide-value">{{ $n($filas) }}</span>
            <h3 class="co-slide-title">bultos leídos de la descarga</h3>
            <p class="co-slide-text">
                Cada fila del archivo quedó guardada tal como la entrega Geolice.
                Desliza hacia la derecha para ver qué encontró el sistema al compararla con los catálogos.
            </p>
            @if($importacion->duracion_seg !== null)
                <span class="co-slide-foot">Tardó {{ $importacion->duracion_seg }} segundos</span>
            @endif
        </article>

        {{-- 2 · Qué pasó con cada bulto --}}
        <article class="co-slide is-accent">
            <span class="co-slide-eyebrow">Qué pasó con cada bulto</span>
            <div class="co-slide-tiles">
                <div class="co-tile is-accent">
                    <span class="co-tile-value">{{ $n($nuevos) }}</span>
                    <span class="co-tile-label">Nuevos</span>
                    <span class="co-tile-text">No existían en el sistema; se agregaron a {{ $periodo->nombre }}.</span>
                </div>
                <div class="co-tile">
                    <span class="co-tile-value">{{ $n($actualizados) }}</span>
                    <span class="co-tile-label">Actualizados</span>
                    <span class="co-tile-text">Ya estaban en este mes y la descarga traía algo distinto (por ejemplo, el estado de entrega).</span>
                </div>
                <div class="co-tile">
                    <span class="co-tile-value">{{ $n($sinCambio) }}</span>
                    <span class="co-tile-label">Sin cambio</span>
                    <span class="co-tile-text">Ya estaban en este mes, idénticos.</span>
                </div>
                <div class="co-tile {{ $anteriores > 0 ? 'is-warn' : '' }}">
                    <span class="co-tile-value">{{ $n($anteriores) }}</span>
                    <span class="co-tile-label">De un mes anterior</span>
                    <span class="co-tile-text">Ya se cargaron en otro período; no se tocaron. Es el control para no pagar dos veces.</span>
                </div>
            </div>
        </article>

        {{-- 3 · Peso declarado --}}
        <article class="co-slide is-neutral">
            <span class="co-slide-eyebrow">Peso declarado por el cliente</span>
            <span class="co-slide-value">{{ $pct($conPeso, $filas) }}<small>%</small></span>
            <h3 class="co-slide-title">de los bultos traen peso declarado</h3>
            <div class="co-bar" role="img" aria-label="{{ $n($conPeso) }} con peso, {{ $n($sinPeso) }} sin peso">
                <div class="co-bar-fill" style="width: {{ $pct($conPeso, $filas) }}%"></div>
            </div>
            <div class="co-bar-legend">
                <span><strong>{{ $n($conPeso) }}</strong> con peso</span>
                <span><strong>{{ $n($sinPeso) }}</strong> sin peso</span>
            </div>
            <p class="co-slide-text">
                Venir sin peso no es un error: el kilo con que se paga sale primero de la balanza de bodega (paso 2).
                El peso declarado solo se usa cuando no hay pesaje.
            </p>
        </article>

        {{-- 4 · Estados de entrega --}}
        <article class="co-slide {{ $estadosDesconocidos === [] ? 'is-ok' : 'is-danger' }}">
            <span class="co-slide-eyebrow">Estados de entrega</span>
            <span class="co-slide-value">{{ $n(count($estados)) }}</span>
            <h3 class="co-slide-title">{{ count($estados) === 1 ? 'estado distinto' : 'estados distintos' }} en la descarga</h3>
            <ul class="co-slide-list">
                @foreach($estados as $estado => $cant)
                    <li>
                        <span class="co-slide-list-name">
                            {{ $estado }}
                            @if(isset($estadosDesconocidos[$estado]))
                                <em class="co-slide-tag is-danger">no está en el catálogo</em>
                            @endif
                        </span>
                        <span class="co-slide-list-bar"><span style="width: {{ $pct($cant, $maxEstado) }}%"></span></span>
                        <strong class="co-slide-list-num">{{ $n($cant) }}</strong>
                    </li>
                @endforeach
            </ul>
            <p class="co-slide-text">
                @if($estadosDesconocidos === [])
                    Todos existen en el catálogo, que dice para cada uno si el bulto se paga o se descuenta.
                @else
                    Los marcados no existen en el catálogo: sin regla, el cálculo no sabrá si pagarlos o descontarlos.
                @endif
            </p>
        </article>

        {{-- 5 · Comunas no reconocidas --}}
        <article class="co-slide {{ $comunasFuera === [] ? 'is-ok' : 'is-warn' }}">
            <span class="co-slide-eyebrow">Comunas no reconocidas</span>
            <span class="co-slide-value">{{ $n(count($comunasFuera)) }}</span>
            <h3 class="co-slide-title">
                @if($comunasFuera === [])
                    todas las comunas existen en el catálogo
                @else
                    {{ count($comunasFuera) === 1 ? 'comuna que el catálogo no conoce' : 'comunas que el catálogo no conoce' }}
                    · {{ $n($comunasFueraBultos) }} bultos
                @endif
            </h3>
            @if($comunasFuera !== [])
                <ul class="co-slide-list is-plain">
                    @foreach(array_slice($comunasFuera, 0, 8, true) as $comuna => $cant)
                        <li><span class="co-slide-list-name">{{ $comuna }}</span><strong class="co-slide-list-num">{{ $n($cant) }}</strong></li>
                    @endforeach
                </ul>
                @if(count($comunasFuera) > 8)
                    <span class="co-slide-more">… y {{ count($comunasFuera) - 8 }} más en las alertas de abajo.</span>
                @endif
            @endif
            <p class="co-slide-text">
                Sin comuna reconocida no se sabe qué agente reparte el bulto. Suelen ser tildes, mayúsculas o abreviaturas:
                hay que confirmarlas y agregarlas al catálogo de comunas.
            </p>
        </article>

        {{-- 6 · Sin configuración de pago --}}
        <article class="co-slide {{ $sinConfig === [] ? 'is-ok' : 'is-warn' }}">
            <span class="co-slide-eyebrow">Sin configuración de pago</span>
            <span class="co-slide-value">{{ $n($sinConfigBultos) }}</span>
            <h3 class="co-slide-title">
                @if($sinConfig === [])
                    todas las combinaciones tienen configuración
                @else
                    bultos en {{ $n(count($sinConfig)) }} {{ count($sinConfig) === 1 ? 'combinación' : 'combinaciones' }} agente + cliente + servicio sin definir
                @endif
            </h3>
            @if($sinConfig !== [])
                <ul class="co-slide-list is-plain">
                    @foreach(array_slice($sinConfig, 0, 6, true) as $combo => $cant)
                        <li><span class="co-slide-list-name">{{ $combo }}</span><strong class="co-slide-list-num">{{ $n($cant) }}</strong></li>
                    @endforeach
                </ul>
                @if(count($sinConfig) > 6)
                    <span class="co-slide-more">… y {{ count($sinConfig) - 6 }} más en las alertas de abajo.</span>
                @endif
            @endif
            <p class="co-slide-text">
                No es tabla 0. Tabla 0 es una decisión explícita de no pagar; esto es que nadie ha decidido.
                Son las combinaciones que hay que llevarle a Operaciones.
            </p>
        </article>

        {{-- 7 · Otros avisos --}}
        <article class="co-slide is-neutral">
            <span class="co-slide-eyebrow">Otros avisos</span>
            <div class="co-slide-tiles">
                <div class="co-tile">
                    <span class="co-tile-value">{{ $n($sinComuna) }}</span>
                    <span class="co-tile-label">Sin comuna de destino</span>
                    <span class="co-tile-text">Geolice no informó comuna; sin ella no hay agente que asignar.</span>
                </div>
                <div class="co-tile {{ $fechasMal > 0 ? 'is-warn' : '' }}">
                    <span class="co-tile-value">{{ $n($fechasMal) }}</span>
                    <span class="co-tile-label">Fechas no reconocidas</span>
                    <span class="co-tile-text">Fechas que no se pudieron leer y quedaron vacías.</span>
                </div>
            </div>
            <p class="co-slide-text">
                Siguiente paso: importar los pesajes de bodega, para que cada bulto tenga su kilo real antes de calcular.
            </p>
        </article>

    </div>

    <div class="co-slider-dots" data-dots></div>
</section>
