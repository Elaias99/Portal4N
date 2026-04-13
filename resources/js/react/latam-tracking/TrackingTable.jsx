import React from 'react';

function valueOrDash(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }
    return value;
}

function formatDate(value) {
    if (!value) return '-';

    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleDateString('es-CL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function normalizeLatamDate(raw) {
    if (!raw) return '-';

    let value = String(raw).trim();
    value = value.replace(/(\d{4})(\d{2}:\d{2})$/, '$1 $2');

    const match = value.match(
        /^(\d{1,2})-([A-Za-z]{3})-(\d{4})(?:\s+(\d{2}:\d{2}))?$/
    );

    if (!match) return value;

    const [, day, month, year, time] = match;

    const months = {
        Jan: 'Ene',
        Feb: 'Feb',
        Mar: 'Mar',
        Apr: 'Abr',
        May: 'May',
        Jun: 'Jun',
        Jul: 'Jul',
        Aug: 'Ago',
        Sep: 'Sep',
        Oct: 'Oct',
        Nov: 'Nov',
        Dec: 'Dic',
    };

    const monthEs = months[month] ?? month;

    return time
        ? `${day.padStart(2, '0')} ${monthEs} ${year}, ${time}`
        : `${day.padStart(2, '0')} ${monthEs} ${year}`;
}

function formatWeight(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    const numeric = Number(value);
    if (Number.isNaN(numeric)) {
        return String(value);
    }

    return `${numeric} kg`;
}

function getStageFromTracking(trackingResult) {
    const status = String(trackingResult?.tracking?.status_summary || '').toLowerCase();
    const eventCode = String(trackingResult?.latest_event?.code || '').toUpperCase();

    if (status.includes('entregado') || eventCode === 'DLV') return 4;

    if (
        status.includes('llegó') ||
        status.includes('llego') ||
        ['RCF', 'ARR', 'NFD'].includes(eventCode)
    ) {
        return 3;
    }

    if (
        status.includes('tránsito') ||
        status.includes('transito') ||
        ['DEP', 'MAN'].includes(eventCode)
    ) {
        return 2;
    }

    if (
        status.includes('recibido') ||
        ['BKD', 'FOH', 'RCS'].includes(eventCode)
    ) {
        return 1;
    }

    return 0;
}

function getStatusClass(status) {
    const text = String(status || '').toLowerCase();

    if (text.includes('entregado')) return 'lt-status-badge is-delivered';
    return 'lt-status-badge is-neutral';
}

function TrackingActions({ row, actions, isSelected }) {
    const {
        processUrl,
        csrfToken,
        currentPrefijo,
        currentCodigoTracking,
        currentDestino,
        currentFechaProceso,
    } = actions;

    return (
        <div className="lt-table-actions">
            <form method="POST" action={processUrl} className="m-0 js-track-form">
                <input type="hidden" name="_token" value={csrfToken} />
                <input type="hidden" name="tracking_prefijo" value={row.prefix} />
                <input type="hidden" name="tracking_codigo_tracking" value={row.code} />
                <input type="hidden" name="tracking_doc_type" value="SO" />

                <input type="hidden" name="filter_prefijo" value={currentPrefijo || ''} />
                <input type="hidden" name="filter_codigo_tracking" value={currentCodigoTracking || ''} />
                <input type="hidden" name="filter_destino" value={currentDestino || ''} />
                <input type="hidden" name="filter_fecha_proceso" value={currentFechaProceso || ''} />

                <button
                    type="submit"
                    className={`btn btn-sm js-track-submit ${isSelected ? 'btn-dark' : 'btn-outline-dark'}`}
                >
                    {isSelected ? 'Consultado' : 'Consultar'}
                </button>
            </form>

            <a
                href={row.url}
                target="_blank"
                rel="noreferrer"
                className="lt-link-inline"
            >
                LATAM
            </a>
        </div>
    );
}

function ProgressBlock({ stage }) {
    const steps = [
        { key: 1, label: 'Recibido' },
        { key: 2, label: 'En tránsito' },
        { key: 3, label: 'Llegó a destino' },
        { key: 4, label: 'Entregado' },
    ];

    return (
        <div className="lt-stepper">
            {steps.map((step) => {
                const active = stage >= step.key;

                return (
                    <div
                        key={step.key}
                        className={`lt-step ${active ? 'is-active' : ''}`}
                    >
                        {step.label}
                    </div>
                );
            })}
        </div>
    );
}

function DetailContent({ trackingResult, trackingError, trackingLookup }) {
    if (trackingError) {
        return (
            <div className="alert alert-danger mb-0">
                {trackingError}
            </div>
        );
    }

    if (!trackingResult?.ok) {
        return (
            <div className="alert alert-warning mb-0">
                No se encontraron datos para la guía {trackingLookup?.prefix}-{trackingLookup?.code}.
            </div>
        );
    }

    const stage = getStageFromTracking(trackingResult);
    const status = trackingResult?.tracking?.status_summary || 'Sin estado';

    return (
        <div className="lt-inline-detail">
            <div className="lt-inline-header">
                <div>
                    <div className="text-muted small">Resultado consultado</div>
                    <div className="lt-inline-title">
                        {trackingResult?.tracking?.label || `${trackingResult?.query?.prefix}-${trackingResult?.query?.code}`}
                    </div>
                    <div className="text-muted">
                        {valueOrDash(trackingResult?.tracking?.origin)} → {valueOrDash(trackingResult?.tracking?.destination)}
                    </div>
                </div>

                <span className={getStatusClass(status)}>
                    {status}
                </span>
            </div>

            <div className="lt-inline-summary">
                <div className="lt-mini-card">
                    <div className="lt-mini-label">Llegada comprometida</div>
                    <div className="lt-mini-value">
                        {normalizeLatamDate(trackingResult?.tracking?.arrival_on_or_before)}
                    </div>
                </div>

                <div className="lt-mini-card">
                    <div className="lt-mini-label">Producto</div>
                    <div className="lt-mini-value">
                        {valueOrDash(trackingResult?.tracking?.product)}
                    </div>
                </div>

                <div className="lt-mini-card">
                    <div className="lt-mini-label">Carga</div>
                    <div className="lt-mini-value">
                        {valueOrDash(trackingResult?.tracking?.pieces)} pieza(s) · {formatWeight(trackingResult?.tracking?.weight)}
                    </div>
                </div>
            </div>

            <div className="mt-3">
                <ProgressBlock stage={stage} />
            </div>

            <div className="row g-3 mt-1">
                <div className="col-lg-4">
                    <div className="lt-panel-card">
                        <div className="lt-panel-title">Resumen</div>
                        <div className="lt-field-list">
                            <div className="lt-field-row"><div className="lt-field-label">Prefijo</div><div className="lt-field-value">{valueOrDash(trackingResult?.query?.prefix)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Código</div><div className="lt-field-value">{valueOrDash(trackingResult?.query?.code)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Origen</div><div className="lt-field-value">{valueOrDash(trackingResult?.tracking?.origin)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Destino</div><div className="lt-field-value">{valueOrDash(trackingResult?.tracking?.destination)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Producto</div><div className="lt-field-value">{valueOrDash(trackingResult?.tracking?.product)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Commodity</div><div className="lt-field-value">{valueOrDash(trackingResult?.tracking?.commodity)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Piezas</div><div className="lt-field-value">{valueOrDash(trackingResult?.tracking?.pieces)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Peso</div><div className="lt-field-value">{formatWeight(trackingResult?.tracking?.weight)}</div></div>
                        </div>
                    </div>
                </div>

                <div className="col-lg-4">
                    <div className="lt-panel-card">
                        <div className="lt-panel-title">Último tramo</div>
                        <div className="lt-field-list">
                            <div className="lt-field-row"><div className="lt-field-label">Origen</div><div className="lt-field-value">{valueOrDash(trackingResult?.latest_leg?.origin)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Destino</div><div className="lt-field-value">{valueOrDash(trackingResult?.latest_leg?.destination)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Vuelo</div><div className="lt-field-value">{valueOrDash(trackingResult?.latest_leg?.flight)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">ETD</div><div className="lt-field-value">{normalizeLatamDate(trackingResult?.latest_leg?.etd)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">ETA</div><div className="lt-field-value">{normalizeLatamDate(trackingResult?.latest_leg?.eta)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Piezas</div><div className="lt-field-value">{valueOrDash(trackingResult?.latest_leg?.pieces)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Peso</div><div className="lt-field-value">{formatWeight(trackingResult?.latest_leg?.weight)}</div></div>
                        </div>
                    </div>
                </div>

                <div className="col-lg-4">
                    <div className="lt-panel-card">
                        <div className="lt-panel-title">Último evento</div>
                        <div className="lt-field-list">
                            <div className="lt-field-row"><div className="lt-field-label">Código</div><div className="lt-field-value">{valueOrDash(trackingResult?.latest_event?.code)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Descripción</div><div className="lt-field-value">{valueOrDash(trackingResult?.latest_event?.description)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Estación</div><div className="lt-field-value">{valueOrDash(trackingResult?.latest_event?.station)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Vuelo</div><div className="lt-field-value">{valueOrDash(trackingResult?.latest_event?.flight)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Actual PK</div><div className="lt-field-value">{valueOrDash(trackingResult?.latest_event?.actual_pk)}</div></div>
                            <div className="lt-field-row"><div className="lt-field-label">Hora real</div><div className="lt-field-value">{normalizeLatamDate(trackingResult?.latest_event?.actual_time)}</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function TrackingTable({
    rows = [],
    actions,
    trackingLookup,
    trackingResult,
    trackingError,
}) {
    const selectedKey = `${trackingLookup?.prefix || ''}-${trackingLookup?.code || ''}`;

    if (!rows.length) {
        return (
            <div className="lt-card p-4 text-muted">
                No hay registros para mostrar.
            </div>
        );
    }

    return (
        <div className="lt-card overflow-hidden">
            <div className="lt-card-header">
                <div>
                    <div className="lt-section-title">Registros almacenados</div>
                    <div className="lt-section-subtitle">
                        Consulta el detalle sin perder de vista la fila seleccionada.
                    </div>
                </div>

                <span className="latam-soft-badge">{rows.length} registro(s)</span>
            </div>

            <div className="table-responsive">
                <table className="table lt-table-clean align-middle mb-0">
                    <thead>
                        <tr>
                            <th className="ps-4">Prefijo</th>
                            <th>Código</th>
                            <th>Destino</th>
                            <th>Fecha proceso</th>
                            <th className="pe-4 text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, index) => {
                            const key = `${row.prefix}-${row.code}`;
                            const isSelected = key === selectedKey;

                            return (
                                <React.Fragment key={`${key}-${index}`}>
                                    <tr className={isSelected ? 'is-selected' : ''}>
                                        <td className="ps-4">
                                            <span className="lt-chip">{valueOrDash(row.prefix)}</span>
                                        </td>
                                        <td>
                                            <span className="fw-bold text-dark">{valueOrDash(row.code)}</span>
                                        </td>
                                        <td>{valueOrDash(row.destino)}</td>
                                        <td className="text-muted">{formatDate(row.fecha_proceso)}</td>
                                        <td className="pe-4 text-end">
                                            <TrackingActions
                                                row={row}
                                                actions={actions}
                                                isSelected={isSelected}
                                            />
                                        </td>
                                    </tr>

                                    {isSelected && (
                                        <tr className="lt-expand-row">
                                            <td colSpan="5">
                                                <DetailContent
                                                    trackingResult={trackingResult}
                                                    trackingError={trackingError}
                                                    trackingLookup={trackingLookup}
                                                />
                                            </td>
                                        </tr>
                                    )}
                                </React.Fragment>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}