{{-- resources/views/cobranzas_compras/salud.blade.php --}}
@extends('layouts.app')

@section('content')
@php
    // Conteos SOLO de la página actual (por paginación)
    $pageItems = collect($cobranzasCompras->items());

    $total = $pageItems->count();
    $criticos = $pageItems->where('nivel', 'critico')->count();
    $advertencias = $pageItems->where('nivel', 'advertencia')->count();
    $saludables = $pageItems->where('nivel', 'saludable')->count();

    $hallazgos = $pageItems
        ->flatMap(fn($e) => collect($e['problemas'] ?? []))
        ->filter()
        ->countBy()
        ->sortDesc();

    $topHallazgos = $hallazgos->take(3);

    $from = $cobranzasCompras->firstItem() ?? 0;
    $to = $cobranzasCompras->lastItem() ?? 0;
    $grandTotal = $cobranzasCompras->total();
@endphp

<div class="container-fluid py-4 provider-health">

    {{-- HERO / ENCABEZADO --}}
    <div class="health-hero mb-4">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <div class="health-kicker">Centro de Control</div>
                <h2 class="health-title mb-1">Salud de Proveedores</h2>
                <div class="health-subtitle">
                    Diagnóstico operativo del maestro de proveedores: detecta incompletos, inconsistentes y riesgosos sin modificar datos.
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('cobranzas-compras.index') }}" class="btn btn-outline-secondary">
                    Volver a Gestión Proveedores
                </a>
                <a href="{{ route('cobranzasCompra.export') }}" class="btn btn-outline-success">
                    Exportar Excel
                </a>

            </div>
        </div>

        {{-- CINTA DE ESTADO --}}
        <div class="health-strip mt-4">
            <button class="strip-item is-active" data-filter-level="all" type="button">
                <span class="strip-dot"></span>
                <span class="strip-label">Todos</span>
                <span class="strip-value">{{ $total }}</span>
            </button>

            <button class="strip-item strip-critical" data-filter-level="critico" type="button">
                <span class="strip-dot"></span>
                <span class="strip-label">Críticos</span>
                <span class="strip-value">{{ $criticos }}</span>
            </button>

            <button class="strip-item strip-warn" data-filter-level="advertencia" type="button">
                <span class="strip-dot"></span>
                <span class="strip-label">En observación</span>
                <span class="strip-value">{{ $advertencias }}</span>
            </button>

            <button class="strip-item strip-ok" data-filter-level="saludable" type="button">
                <span class="strip-dot"></span>
                <span class="strip-label">Saludables</span>
                <span class="strip-value">{{ $saludables }}</span>
            </button>

            <div class="strip-spacer"></div>

            <div class="strip-mini">
                <div class="mini-title">Hallazgos más frecuentes</div>
                @if($topHallazgos->isEmpty())
                    <div class="mini-muted">Sin hallazgos</div>
                @else
                    <div class="mini-tags">
                        @foreach($topHallazgos as $h => $cnt)
                            <span class="mini-tag" title="{{ $h }}">
                                <span class="mini-tag-name">{{ $h }}</span>
                                <span class="mini-tag-count">{{ $cnt }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- CUERPO: SOLO TICKETS --}}
    <div class="health-shell">
        <main class="health-main">
            <div class="main-header">
                <div class="main-title">Tickets de Diagnóstico</div>
                <div class="main-sub" id="results-counter">
                    Mostrando {{ $from }}–{{ $to }} de {{ $grandTotal }} proveedor(es)
                </div>
            </div>

            <div class="ticket-feed" id="ticket-feed">
                @foreach($cobranzasCompras as $e)
                    @php
                        /** @var \App\Models\CobranzaCompra $p */
                        $p = $e['proveedor'];
                        $nivel = $e['nivel'] ?? 'saludable';
                        $problemas = $e['problemas'] ?? [];

                        $hasProblemas = count($problemas) > 0;

                        $bankName = optional($p->banco)->nombre;
                        $tipoCuenta = optional($p->tipoCuenta)->nombre;

                        $stamp = $nivel === 'critico' ? 'REQUIERE ACCIÓN' : ($nivel === 'advertencia' ? 'EN OBSERVACIÓN' : 'OK');
                    @endphp

                    <section class="ticket ticket-{{ $nivel }}" data-level="{{ $nivel }}">
                        {{-- COLUMNA VISUAL (Sello + Banda) --}}
                        <div class="ticket-left">
                            <div class="ticket-stamp">{{ $stamp }}</div>
                            <div class="ticket-band"></div>
                            <div class="ticket-score">
                                <div class="score-label">Hallazgos</div>
                                <div class="score-value">{{ count($problemas) }}</div>
                            </div>
                        </div>

                        {{-- CONTENIDO PRINCIPAL --}}
                        <div class="ticket-body">
                            <div class="ticket-top">
                                <div class="ticket-identity">
                                    <div class="ticket-name" title="{{ $p->razon_social }}">
                                        {{ $p->razon_social ?? '—' }}
                                    </div>
                                    <div class="ticket-meta">
                                        <span class="meta-pill"><span class="meta-key">RUT</span> {{ $p->rut_cliente ?? '—' }}</span>
                                        {{-- <span class="meta-pill"><span class="meta-key">Servicio</span> {{ $p->servicio ?? '—' }}</span>
                                        <span class="meta-pill"><span class="meta-key">Créditos</span> {{ $p->creditos ?? '—' }}</span> --}}
                                    </div>
                                </div>

                                <div class="ticket-actions">
                                    @if (Auth::id() != 375)
                                        <a href="{{ route('cobranzas-compras.edit', $p) }}" class="btn btn-sm btn-warning">
                                            Editar
                                        </a>
                                    @endif

                                    @if($hasProblemas)
                                        <button class="btn btn-sm btn-outline-dark"
                                                type="button"
                                                data-toggle="collapse"
                                                data-target="#detalle-{{ $p->id }}"
                                                aria-expanded="false"
                                                aria-controls="detalle-{{ $p->id }}">
                                            Inspeccionar
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary" type="button" disabled>
                                            Sin hallazgos
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- “Mapa” de completitud (no tabla) --}}
                            <div class="ticket-map">
                                <div class="map-item {{ $p->servicio ? 'ok' : 'bad' }}">
                                    <div class="map-k">Servicio</div>
                                    <div class="map-v">{{ $p->servicio ?: 'Faltante' }}</div>
                                </div>

                                <div class="map-item {{ is_numeric($p->creditos) ? 'ok' : 'bad' }}">
                                    <div class="map-k">Créditos</div>
                                    <div class="map-v">{{ is_numeric($p->creditos) ? $p->creditos.' días' : 'Faltante' }}</div>
                                </div>

                                <div class="map-item {{ ($p->banco_id && $p->tipo_cuenta_id) ? 'ok' : 'bad' }}">
                                    <div class="map-k">Banco / Tipo</div>
                                    <div class="map-v">
                                        {{ ($bankName || $tipoCuenta) ? trim(($bankName ?? '—').' / '.($tipoCuenta ?? '—')) : 'Incompleto' }}
                                    </div>
                                </div>

                                <div class="map-item {{ ($p->rut_cuenta && $p->numero_cuenta) ? 'ok' : 'bad' }}">
                                    <div class="map-k">Cuenta</div>
                                    <div class="map-v">
                                        {{ ($p->rut_cuenta && $p->numero_cuenta) ? ($p->rut_cuenta.' · '.$p->numero_cuenta) : 'Incompleta' }}
                                    </div>
                                </div>

                                <div class="map-item {{ $p->responsable ? 'ok' : 'warn' }}">
                                    <div class="map-k">Responsable</div>
                                    <div class="map-v">{{ $p->responsable ?: 'No asignado' }}</div>
                                </div>

                                <div class="map-item {{ $p->zona ? 'ok' : 'warn' }}">
                                    <div class="map-k">Zona</div>
                                    <div class="map-v">{{ $p->zona ?: 'No definida' }}</div>
                                </div>
                            </div>

                            {{-- DETALLE (colapsable) --}}
                            <div class="collapse ticket-detail" id="detalle-{{ $p->id }}">
                                <div class="detail-inner">
                                    <div class="detail-title">Hallazgos detectados</div>

                                    <div class="detail-list">
                                        @foreach($problemas as $h)
                                            <div class="detail-chip">{{ $h }}</div>
                                        @endforeach
                                    </div>

                                    <div class="detail-foot">
                                        <div class="detail-note">
                                            Este diagnóstico es informativo y no modifica registros. Ideal para priorizar correcciones antes de impactar vencimientos, calendario y gestión operativa.
                                        </div>
                                        <div class="detail-links">
                                            <a href="{{ route('cobranzas-compras.index', ['buscar' => $p->rut_cliente]) }}"
                                               class="btn btn-sm btn-outline-secondary">
                                                Ver en listado
                                            </a>
                                            @if (Auth::id() != 375)
                                                <a href="{{ route('cobranzas-compras.edit', $p) }}"
                                                   class="btn btn-sm btn-dark">
                                                    Corregir ahora
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </section>
                @endforeach
            </div>

            {{-- Paginación --}}
            @if ($cobranzasCompras->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $cobranzasCompras->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </main>
    </div>
</div>

<style>
/* =========================
   SALUD DE PROVEEDORES — UI
   Bootstrap 4 friendly
========================= */
.provider-health { --bg: #0f172a; --ink: #111827; --muted: #6b7280; --card: #ffffff; --line: rgba(17,24,39,.10); }

/* HERO: ahora neutro/clarito (alineado con el resto del sistema) */
.provider-health .health-hero{
    background: #ffffff;
    color: #111827;
    border: 1px solid rgba(17,24,39,.08);
    border-radius: 18px;
    padding: 22px 22px 18px 22px;
    box-shadow: 0 14px 35px rgba(0,0,0,.06);
    position: relative;
    overflow: hidden;
}

/* Se elimina el “glow” azul del hero */
.provider-health .health-hero:after{ content:none; }

.provider-health .health-kicker{
    font-size: .78rem;
    letter-spacing: .20em;
    text-transform: uppercase;
    opacity: .85;
    color: #0f766e;
}
.provider-health .health-title{
    font-weight: 800;
    letter-spacing: -.02em;
}
.provider-health .health-subtitle{
    opacity: .92;
    max-width: 900px;
    color: #6b7280;
}

/* Strip (ajustado a fondo claro) */
.provider-health .health-strip{
    display:flex;
    align-items:stretch;
    gap:10px;
    padding-top: 6px;
    position: relative;
    z-index: 1;
}
.provider-health .strip-item{
    border: 1px solid rgba(17,24,39,.10);
    background: #f9fafb;
    color:#111827;
    padding: 10px 12px;
    border-radius: 14px;
    display:flex;
    align-items:center;
    gap:10px;
    min-width: 150px;
    cursor:pointer;
    transition: transform .12s ease, background .12s ease, border-color .12s ease;
    outline:none;
}
.provider-health .strip-item:hover{ transform: translateY(-1px); background: #f3f4f6; }
.provider-health .strip-item.is-active{
    border-color: rgba(15,118,110,.35);
    background: rgba(15,118,110,.08);
}
.provider-health .strip-dot{
    width:10px; height:10px; border-radius: 999px;
    background: rgba(17,24,39,.35);
}
.provider-health .strip-label{ font-weight: 700; }
.provider-health .strip-value{
    margin-left:auto;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 999px;
    background: rgba(17,24,39,.06);
}
.provider-health .strip-critical .strip-dot{ background: #fb7185; }
.provider-health .strip-warn .strip-dot{ background: #fbbf24; }
.provider-health .strip-ok .strip-dot{ background: #34d399; }
.provider-health .strip-spacer{ flex:1; }

.provider-health .strip-mini{
    min-width: 280px;
    border-left: 1px solid rgba(17,24,39,.10);
    padding-left: 14px;
}
.provider-health .mini-title{ font-size: .82rem; font-weight: 700; opacity: .95; color:#111827; }
.provider-health .mini-muted{ font-size:.85rem; opacity:.85; color:#6b7280; }
.provider-health .mini-tags{ display:flex; flex-wrap:wrap; gap:8px; margin-top: 6px; }
.provider-health .mini-tag{
    display:flex; gap:8px; align-items:center;
    padding: 6px 10px;
    border-radius: 999px;
    background: #f9fafb;
    border: 1px solid rgba(17,24,39,.10);
    font-size: .82rem;
    color:#111827;
}
.provider-health .mini-tag-count{
    font-weight: 800;
    padding: 1px 8px;
    border-radius: 999px;
    background: rgba(17,24,39,.08);
}

/* Shell */
.provider-health .health-shell{
    display:grid;
    grid-template-columns: 1fr;
    gap: 16px;
    align-items:start;
}

/* Main */
.provider-health .health-main{
    background: transparent;
}
.provider-health .main-header{
    display:flex;
    align-items:baseline;
    justify-content: space-between;
    gap: 10px;
    padding: 2px 4px;
}
.provider-health .main-title{ font-weight: 800; font-size: 1.05rem; }
.provider-health .main-sub{ color: var(--muted); font-size: .90rem; }

/* Tickets */
.provider-health .ticket-feed{
    display:flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 10px;
}
.provider-health .ticket{
    display:grid;
    grid-template-columns: 150px 1fr;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 14px 35px rgba(0,0,0,.06);
    position: relative;
}
@media (max-width: 720px){
    .provider-health .ticket{ grid-template-columns: 1fr; }
    .provider-health .ticket-left{ min-height: 120px; }
}
.provider-health .ticket-left{
    background: #0b1220;
    color: #fff;
    position: relative;
    padding: 14px;
    display:flex;
    flex-direction: column;
    justify-content: space-between;
}
.provider-health .ticket-stamp{
    font-weight: 900;
    font-size: .78rem;
    letter-spacing: .12em;
    text-transform: uppercase;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.18);
    padding: 8px 10px;
    border-radius: 12px;
    width: fit-content;
    transform: rotate(-2deg);
}
.provider-health .ticket-band{
    position:absolute;
    left:0; bottom:0;
    height: 8px;
    width: 100%;
    opacity: .95;
}
.provider-health .ticket-score{ margin-top: 10px; }
.provider-health .score-label{ font-size: .78rem; opacity: .85; }
.provider-health .score-value{ font-size: 2.0rem; font-weight: 900; line-height: 1; }

.provider-health .ticket-body{ padding: 14px 14px 12px 14px; }
.provider-health .ticket-top{
    display:flex;
    justify-content: space-between;
    align-items:flex-start;
    gap: 12px;
}
.provider-health .ticket-name{
    font-weight: 900;
    font-size: 1.06rem;
    letter-spacing: -.01em;
    max-width: 800px;
}
.provider-health .ticket-meta{
    display:flex;
    flex-wrap:wrap;
    gap: 8px;
    margin-top: 8px;
}
.provider-health .meta-pill{
    border: 1px solid var(--line);
    background:#f9fafb;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: .85rem;
    color: var(--ink);
}
.provider-health .meta-key{
    color: var(--muted);
    font-weight: 700;
    margin-right: 6px;
}
.provider-health .ticket-actions{
    display:flex;
    gap: 8px;
    align-items:center;
    flex-wrap: wrap;
}

/* Mapa */
.provider-health .ticket-map{
    margin-top: 12px;
    display:grid;
    grid-template-columns: repeat(6, minmax(140px, 1fr));
    gap: 10px;
}
@media (max-width: 1300px){
    .provider-health .ticket-map{ grid-template-columns: repeat(3, minmax(160px, 1fr)); }
}
@media (max-width: 720px){
    .provider-health .ticket-map{ grid-template-columns: 1fr; }
}
.provider-health .map-item{
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 10px 12px;
    background: #fff;
    position: relative;
    overflow:hidden;
}
.provider-health .map-item:before{
    content:"";
    position:absolute;
    left:0; top:0;
    width: 5px; height: 100%;
    background: rgba(17,24,39,.20);
}
.provider-health .map-item.ok:before{ background:#34d399; }
.provider-health .map-item.warn:before{ background:#fbbf24; }
.provider-health .map-item.bad:before{ background:#fb7185; }
.provider-health .map-k{ color: var(--muted); font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; }
.provider-health .map-v{ font-weight: 800; margin-top: 4px; color: var(--ink); }

/* Detalle */
.provider-health .ticket-detail{ margin-top: 10px; }
.provider-health .detail-inner{
    border-top: 1px dashed var(--line);
    padding-top: 12px;
}
.provider-health .detail-title{ font-weight: 900; margin-bottom: 8px; }
.provider-health .detail-list{
    display:flex;
    flex-wrap:wrap;
    gap: 8px;
}
.provider-health .detail-chip{
    border-radius: 999px;
    padding: 7px 10px;
    background: #111827;
    color:#fff;
    font-weight: 800;
    font-size: .82rem;
}
.provider-health .detail-foot{
    display:flex;
    justify-content: space-between;
    gap: 12px;
    margin-top: 12px;
    flex-wrap:wrap;
}
.provider-health .detail-note{ color: var(--muted); max-width: 720px; font-size: .9rem; }
.provider-health .detail-links{ display:flex; gap: 8px; flex-wrap:wrap; }

/* Color por nivel */
.provider-health .ticket-critico .ticket-band{ background:#fb7185; }
.provider-health .ticket-advertencia .ticket-band{ background:#fbbf24; }
.provider-health .ticket-saludable .ticket-band{ background:#34d399; }

.provider-health .ticket-critico .ticket-left{ background: linear-gradient(180deg, #111827, #7f1d1d); }
.provider-health .ticket-advertencia .ticket-left{ background: linear-gradient(180deg, #111827, #78350f); }
.provider-health .ticket-saludable .ticket-left{ background: linear-gradient(180deg, #111827, #064e3b); }

/* Modo presentación */
.provider-health.is-presentation .health-hero{ border-radius: 22px; }
.provider-health.is-presentation .ticket{ box-shadow: 0 20px 55px rgba(0,0,0,.10); }

/* Print */
@media print{
    .btn, #btn-presentacion, #btn-print { display:none !important; }
    .provider-health .health-hero{ box-shadow:none; }
    .provider-health .ticket{ break-inside: avoid; box-shadow:none; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const root = document.querySelector('.provider-health');
    const feed = document.getElementById('ticket-feed');
    const counter = document.getElementById('results-counter');

    const levelButtons = document.querySelectorAll('.strip-item[data-filter-level]');
    const btnPrint = document.getElementById('btn-print');
    const btnPresentacion = document.getElementById('btn-presentacion');

    let activeLevel = 'all';

    function setActive(el, groupSelector) {
        document.querySelectorAll(groupSelector).forEach(b => b.classList.remove('is-active'));
        el.classList.add('is-active');
    }

    function applyFilters() {
        const tickets = feed.querySelectorAll('.ticket');
        let shown = 0;

        tickets.forEach(t => {
            const level = t.getAttribute('data-level');
            const okLevel = (activeLevel === 'all') ? true : (level === activeLevel);

            t.style.display = okLevel ? '' : 'none';
            if (okLevel) shown++;
        });

        if (counter) {
            counter.textContent = `Mostrando ${shown} proveedor(es) (página actual)`;
        }
    }

    // Filtro por nivel
    levelButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            activeLevel = btn.getAttribute('data-filter-level');
            setActive(btn, '.strip-item[data-filter-level]');
            applyFilters();
        });
    });

    // Print
    if (btnPrint) {
        btnPrint.addEventListener('click', () => window.print());
    }

    // Modo presentación
    if (btnPresentacion && root) {
        btnPresentacion.addEventListener('click', () => {
            root.classList.toggle('is-presentation');
            btnPresentacion.textContent = root.classList.contains('is-presentation')
                ? 'Salir Presentación'
                : 'Modo Presentación';
        });
    }

    // Inicio: críticos por defecto
    const defaultBtn = document.querySelector('.strip-item[data-filter-level="critico"]');
    if (defaultBtn) defaultBtn.click();
});
</script>
@endsection
