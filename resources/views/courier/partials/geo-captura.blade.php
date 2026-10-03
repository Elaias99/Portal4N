@php
    $geo = $geo ?? [];
    $captura = $geo['capture'] ?? null;
    $activa = (bool) ($geo['active'] ?? ($captura['active'] ?? false));
    $correoGeo = $geo['account_email'] ?? null;
    $estadoGeo = $captura['state'] ?? 'pending';
    $resumenGeo = $captura['summary'] ?? null;
    $estadosGeo = [
        'pending' => 'En cola',
        'pendiente' => 'En cola',
        'conectando' => 'Conectando',
        'preparando' => 'Preparando exportación',
        'esperando' => 'Esperando a Geo',
        'descargando' => 'Descargando',
        'leyendo' => 'Preparando resumen',
        'listo' => 'Completado',
        'error' => 'Interrumpido',
        'cancelado' => 'Cancelado',
    ];
    $faseGeo = match ($estadoGeo) {
        'preparando', 'esperando' => 2,
        'descargando' => 3,
        'leyendo', 'listo' => 4,
        'pending', 'pendiente', 'conectando' => 1,
        default => 0,
    };
    $numeroGeo = fn ($valor) => number_format((int) ($valor ?? 0), 0, ',', '.');
@endphp

<style>
    .co-geo-account summary { cursor: pointer; }
    .co-geo-account summary span { color: var(--co-muted); font-size: .85rem; }
    .co-geo-fields { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)) auto; gap: 1rem; align-items: end; }
    .co-geo-account-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)) auto; gap: 1rem; align-items: end; }
    .co-geo-fields label, .co-geo-account-fields label { display: block; margin-bottom: .35rem; font-size: .85rem; font-weight: 600; }
    .co-geo-phases { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .6rem; margin: 1rem 0; padding: 0; list-style: none; }
    .co-geo-phases li { padding: .6rem 0; border-bottom: 3px solid var(--co-line); font-size: .85rem; color: var(--co-muted); }
    .co-geo-phases li.is-current { color: var(--co-accent-strong); border-color: var(--co-accent); font-weight: 600; }
    .co-geo-phases li.is-done { color: var(--co-ok); border-color: var(--co-ok); }
    .co-geo-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; margin-top: 1rem; }
    @media (max-width: 900px) {
        .co-geo-fields, .co-geo-account-fields { grid-template-columns: 1fr; }
        .co-geo-phases { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
</style>

@if(session('courier_geo_status'))
    <div class="co-alert co-alert-ok" role="status">{{ session('courier_geo_status') }}</div>
@endif

<details class="co-region co-geo-account" @if(!$correoGeo || !empty($geo['account_error'])) open @endif>
    <summary class="co-region-head">
        <strong>Cuenta de Geo</strong>
        @if($correoGeo)
            <span>{{ $correoGeo }} · Cuenta guardada</span>
        @else
            <span>Guarda tu cuenta para comenzar</span>
        @endif
    </summary>
    <div class="co-region-body">
        @if(!empty($geo['account_error']))
            <p class="co-alert co-alert-danger" role="alert">{{ $geo['account_error'] }}</p>
        @endif
        <form method="POST" action="{{ route('courier.geo.account') }}" data-upload>
            @csrf
            <div class="co-geo-account-fields">
                <div>
                    <label for="geo-email">Correo de Geo</label>
                    <input type="email" id="geo-email" name="email" class="form-control"
                           value="{{ old('email', $correoGeo) }}" autocomplete="username" required @disabled($activa)>
                </div>
                <div>
                    <label for="geo-password">Contraseña de Geo</label>
                    <input type="password" id="geo-password" name="password" class="form-control"
                           autocomplete="current-password" required @disabled($activa)>
                </div>
                <button type="submit" class="co-btn co-btn-primary" data-submit
                        data-loading-text="Guardando…" @disabled($activa)>Guardar cuenta</button>
            </div>
        </form>
        @if($correoGeo)
            <form method="POST" action="{{ route('courier.geo.account.forget') }}" class="co-geo-actions" data-upload>
                @csrf
                @method('DELETE')
                <button type="submit" class="co-btn co-btn-muted co-btn-sm" data-submit
                        data-loading-text="Desconectando…" @disabled($activa)>Desconectar cuenta de Geo</button>
            </form>
        @endif
    </div>
</details>

<section class="co-region">
    <div class="co-region-head">
        <div>
            <h2 class="co-region-title">Traer paquetes de Geo</h2>
            <p class="co-note">Selecciona las fechas de creación de los paquetes y, por separado, el mes que vas a pagar.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('courier.geo.capture') }}" class="co-region-body" data-upload>
        @csrf
        <div class="co-geo-fields">
            <div>
                <label for="geo-desde">Desde · fecha de creación</label>
                <input type="date" id="geo-desde" name="desde" class="form-control"
                       value="{{ old('desde', $geo['from'] ?? '') }}" required @disabled($activa)>
            </div>
            <div>
                <label for="geo-hasta">Hasta · fecha de creación</label>
                <input type="date" id="geo-hasta" name="hasta" class="form-control"
                       value="{{ old('hasta', $geo['to'] ?? '') }}" required @disabled($activa)>
            </div>
            <div>
                <label for="geo-periodo">Mes de pago</label>
                <input type="month" id="geo-periodo" name="periodo" class="form-control"
                       value="{{ old('periodo', $geo['payment_period'] ?? $mesSugerido) }}" required @disabled($activa)>
            </div>
            <button type="submit" class="co-btn co-btn-primary" data-submit
                    data-loading-text="Solicitando…" @disabled($activa || !$correoGeo)>Traer paquetes de Geo</button>
        </div>
        <p class="co-note" style="margin-top: 1rem; margin-bottom: 0;">
            La captura continúa en segundo plano. Puedes salir y volver para consultar el resultado.
            Después revisarás el archivo antes de confirmar su importación.
        </p>
    </form>
</section>

@if($captura)
    <section class="co-region" data-geo-capture
             data-status-url="{{ $captura['status_url'] ?? '' }}"
             data-active="{{ ($captura['active'] ?? false) ? '1' : '0' }}">
        <div class="co-region-head">
            <div>
                <h2 class="co-region-title">Estado de la captura</h2>
                <p class="co-note">Paquetes creados del {{ $captura['from_label'] }} al {{ $captura['to_label'] }}.</p>
                <p class="co-note">Mes de pago seleccionado: {{ $captura['period_label'] ?? $captura['payment_period'] }}.</p>
            </div>
            <span class="co-badge {{ $estadoGeo === 'listo' ? 'co-badge-si' : ($estadoGeo === 'error' ? 'co-badge-no' : 'co-badge-neutral') }}"
                  data-geo-state>{{ $estadosGeo[$estadoGeo] ?? 'En proceso' }}</span>
        </div>
        <div class="co-region-body">
            <ol class="co-geo-phases" aria-label="Etapas de captura">
                @foreach(['Conexión', 'Exportación', 'Descarga', 'Resumen'] as $etapa)
                    <li data-geo-phase="{{ $loop->iteration }}"
                        class="{{ $estadoGeo === 'listo' || $loop->iteration < $faseGeo ? 'is-done' : ($loop->iteration === $faseGeo ? 'is-current' : '') }}">
                        {{ $loop->iteration }}. {{ $etapa }}
                    </li>
                @endforeach
            </ol>
            <p class="co-note" data-geo-message role="status" aria-live="polite">{{ $captura['message'] }}</p>
            <p class="co-note" data-geo-minutes @if(empty($captura['minutes'])) hidden @endif>
                {{ $captura['minutes'] ?? 0 }} min transcurridos.
            </p>
            <p class="co-note" data-geo-poll-note role="status" hidden></p>
            @if(($captura['active'] ?? false) && in_array($estadoGeo, ['pending', 'pendiente'], true))
                <form method="POST" action="{{ route('courier.geo.cancel', ['capture' => $captura['id']]) }}" data-upload data-geo-cancel>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="co-btn co-btn-muted co-btn-sm" data-submit
                            data-loading-text="Cancelando…">Cancelar solicitud en cola</button>
                </form>
            @endif
        </div>
    </section>

    @if($estadoGeo === 'listo' && $resumenGeo)
        <section class="co-region">
            <div class="co-region-head">
                <h2 class="co-region-title">Resumen de paquetes</h2>
                <span class="co-region-meta">{{ $captura['finished_label'] ?? '' }}</span>
            </div>
            <div class="co-region-body">
                <p class="co-paso-dato" style="margin: 0 0 1rem;">
                    <span class="co-paso-numero">{{ $numeroGeo($resumenGeo['packages']) }}</span>
                    <span class="co-paso-unidad">paquetes únicos</span>
                </p>
                <div class="co-table-wrap">
                    <table class="co-table">
                        <thead><tr><th>Estado de entrega</th><th class="is-num">Paquetes</th></tr></thead>
                        <tbody>
                            @foreach($resumenGeo['statuses'] as $filaGeo)
                                <tr><td>{{ $filaGeo['status'] }}</td><td class="is-num">{{ $numeroGeo($filaGeo['packages']) }}</td></tr>
                            @endforeach
                            <tr class="co-fila-total"><td>Total</td><td class="is-num">{{ $numeroGeo($resumenGeo['packages']) }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="co-note" style="margin-top: 1rem;">
                    {{ $numeroGeo($resumenGeo['rows']) }} filas leídas ·
                    {{ $numeroGeo($resumenGeo['duplicates']) }} seguimientos repetidos ·
                    {{ $numeroGeo($resumenGeo['without_tracking']) }} filas sin seguimiento.
                </p>
                <p class="co-note">Este resumen corresponde al archivo de Geo. Todavía no se han importado bultos ni calculado pagos.</p>
                <div class="co-geo-actions">
                    @if(!empty($captura['review_url']))
                        <form method="POST" action="{{ $captura['review_url'] }}" data-upload>
                            @csrf
                            <input type="hidden" name="periodo" value="{{ $captura['payment_period'] }}">
                            <button type="submit" class="co-btn co-btn-primary" data-submit
                                    data-loading-text="Revisando…">Revisar para este mes</button>
                        </form>
                    @endif
                    @if(!empty($captura['download_url']))
                        <a href="{{ $captura['download_url'] }}" class="co-btn co-btn-muted">Descargar archivo</a>
                    @endif
                </div>
            </div>
        </section>
    @endif
@endif

@push('scripts')
<script>
(function () {
    const panel = document.querySelector('[data-geo-capture]');
    if (!panel || panel.dataset.active !== '1' || !panel.dataset.statusUrl) return;

    const state = panel.querySelector('[data-geo-state]');
    const message = panel.querySelector('[data-geo-message]');
    const minutes = panel.querySelector('[data-geo-minutes]');
    const note = panel.querySelector('[data-geo-poll-note]');
    const labels = {
        pending: 'En cola', pendiente: 'En cola', conectando: 'Conectando',
        preparando: 'Preparando exportación', esperando: 'Esperando a Geo',
        descargando: 'Descargando', leyendo: 'Preparando resumen',
        listo: 'Completado', error: 'Interrumpido', cancelado: 'Cancelado'
    };
    let stopped = false;

    async function poll() {
        if (stopped) return;
        try {
            const response = await fetch(panel.dataset.statusUrl, {
                headers: {Accept: 'application/json'}, credentials: 'same-origin', cache: 'no-store'
            });
            if (response.status === 401 || response.redirected) {
                stopped = true;
                note.textContent = 'Tu sesión de Portal4N terminó. Ingresa de nuevo para consultar la captura.';
                note.hidden = false;
                return;
            }
            if (!response.ok) throw new Error('status_unavailable');
            const payload = await response.json();
            const capture = payload.capture ?? payload;
            if (!capture || typeof capture.state !== 'string' || typeof capture.active !== 'boolean') {
                throw new Error('status_unavailable');
            }
            note.hidden = true;
            const cancel = panel.querySelector('[data-geo-cancel]');
            if (cancel) cancel.hidden = !['pending', 'pendiente'].includes(capture.state);
            state.textContent = labels[capture.state] ?? 'En proceso';
            if (typeof capture.message === 'string') message.textContent = capture.message;
            const elapsed = Number(capture.minutes);
            minutes.hidden = !Number.isFinite(elapsed) || elapsed < 1;
            if (!minutes.hidden) minutes.textContent = Math.floor(elapsed) + ' min transcurridos.';
            const phase = ['preparando', 'esperando'].includes(capture.state) ? 2
                : capture.state === 'descargando' ? 3
                : ['leyendo', 'listo'].includes(capture.state) ? 4
                : ['pending', 'pendiente', 'conectando'].includes(capture.state) ? 1 : 0;
            panel.querySelectorAll('[data-geo-phase]').forEach(function (item) {
                const number = Number(item.dataset.geoPhase);
                item.classList.toggle('is-done', capture.state === 'listo' || number < phase);
                item.classList.toggle('is-current', capture.state !== 'listo' && number === phase);
            });
            if (!capture.active) {
                stopped = true;
                window.location.reload();
                return;
            }
        } catch (error) {
            note.textContent = 'No se pudo actualizar el estado. Se intentará nuevamente; la captura sigue en segundo plano.';
            note.hidden = false;
        }
        if (!stopped) window.setTimeout(poll, 4500);
    }
    window.setTimeout(poll, 4500);
})();
</script>
@endpush
