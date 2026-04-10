import React from 'react';
import { createRoot } from 'react-dom/client';

function valueOrDash(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return value;
}

function normalizeLatamDate(raw) {
    if (!raw) return '-';

    let value = String(raw).trim();

    value = value.replace(/(\d{4})(\d{2}:\d{2})$/, '$1 $2');

    const match = value.match(
        /^(\d{1,2})-([A-Za-z]{3})-(\d{4})(?:\s+(\d{2}:\d{2}))?$/
    );

    if (!match) {
        return value;
    }

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

    if (status.includes('entregado') || eventCode === 'DLV') {
        return 4;
    }

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

function getProgressPercent(stage) {
    switch (stage) {
        case 1:
            return 25;
        case 2:
            return 50;
        case 3:
            return 75;
        case 4:
            return 100;
        default:
            return 8;
    }
}

function getStatusBadgeClass(status) {
    const text = String(status || '').toLowerCase();

    if (text.includes('entregado')) {
        return 'bg-success-subtle text-success border border-success-subtle';
    }

    if (text.includes('tránsito') || text.includes('transito')) {
        return 'bg-primary-subtle text-primary border border-primary-subtle';
    }

    if (text.includes('llegó') || text.includes('llego')) {
        return 'bg-info-subtle text-info border border-info-subtle';
    }

    if (text.includes('recibido')) {
        return 'bg-secondary-subtle text-secondary border border-secondary-subtle';
    }

    return 'bg-light text-dark border';
}

function CompactFieldList({ items }) {
    return (
        <div className="small">
            {items.map((item) => (
                <div
                    key={item.label}
                    className="d-grid py-2 border-bottom"
                    style={{
                        gridTemplateColumns: '150px minmax(0, 1fr)',
                        columnGap: '12px',
                    }}
                >
                    <div className="text-secondary fw-semibold">{item.label}</div>
                    <div className="text-dark">{valueOrDash(item.value)}</div>
                </div>
            ))}
        </div>
    );
}

function SectionCard({ title, children }) {
    return (
        <div className="card border-0 shadow-sm h-100">
            <div className="card-body p-3">
                <div className="fw-semibold mb-2">{title}</div>
                {children}
            </div>
        </div>
    );
}

function ProgressBlock({ stage }) {
    const percent = getProgressPercent(stage);

    const steps = [
        { key: 1, label: 'Recibido' },
        { key: 2, label: 'En tránsito' },
        { key: 3, label: 'Llegó a destino' },
        { key: 4, label: 'Entregado' },
    ];

    return (
        <div className="card border-0 shadow-sm mb-3">
            <div className="card-body p-3">
                <div className="fw-semibold mb-2">Progreso del embarque</div>

                <div
                    className="progress"
                    role="progressbar"
                    aria-valuenow={percent}
                    aria-valuemin="0"
                    aria-valuemax="100"
                    style={{ height: '10px' }}
                >
                    <div
                        className="progress-bar"
                        style={{ width: `${percent}%` }}
                    />
                </div>

                <div className="row row-cols-4 g-2 mt-2 text-center small">
                    {steps.map((step) => {
                        const active = stage >= step.key;

                        return (
                            <div key={step.key}>
                                <div className={active ? 'fw-semibold text-dark' : 'text-secondary'}>
                                    {step.label}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

function HeaderSummary({ trackingResult }) {
    const label =
        trackingResult?.tracking?.label ||
        `${trackingResult?.query?.prefix || ''}-${trackingResult?.query?.code || ''}`;

    const origin = trackingResult?.tracking?.origin;
    const destination = trackingResult?.tracking?.destination;
    const route =
        origin && destination ? `${origin} → ${destination}` : '-';

    const status = trackingResult?.tracking?.status_summary || 'Sin estado';
    const eta = normalizeLatamDate(trackingResult?.tracking?.arrival_on_or_before);
    const pieces = valueOrDash(trackingResult?.tracking?.pieces);
    const weight = formatWeight(trackingResult?.tracking?.weight);
    const product = valueOrDash(trackingResult?.tracking?.product);

    return (
        <div className="card border-0 shadow-sm mb-3">
            <div className="card-body p-3">
                <div className="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                    <div>
                        <div className="text-secondary small mb-1">Guía consultada</div>
                        <div className="fs-5 fw-bold">{label}</div>
                        <div className="text-secondary">{route}</div>
                    </div>

                    <div className="text-lg-end">
                        <span className={`badge rounded-pill px-3 py-2 ${getStatusBadgeClass(status)}`}>
                            {status}
                        </span>
                    </div>
                </div>

                <div className="row g-2 mt-3">
                    <div className="col-md-4">
                        <div className="border rounded-3 p-2 h-100">
                            <div className="text-secondary small">Llegada comprometida</div>
                            <div className="fw-semibold">{eta}</div>
                        </div>
                    </div>

                    <div className="col-md-4">
                        <div className="border rounded-3 p-2 h-100">
                            <div className="text-secondary small">Producto</div>
                            <div className="fw-semibold">{product}</div>
                        </div>
                    </div>

                    <div className="col-md-4">
                        <div className="border rounded-3 p-2 h-100">
                            <div className="text-secondary small">Carga</div>
                            <div className="fw-semibold">
                                {pieces} pieza(s) · {weight}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function TrackingResultView({ trackingResult }) {
    const stage = getStageFromTracking(trackingResult);

    const summaryItems = [
        { label: 'Prefijo', value: trackingResult?.query?.prefix },
        { label: 'Código', value: trackingResult?.query?.code },
        { label: 'Origen', value: trackingResult?.tracking?.origin },
        { label: 'Destino', value: trackingResult?.tracking?.destination },
        { label: 'Producto', value: trackingResult?.tracking?.product },
        { label: 'Commodity', value: trackingResult?.tracking?.commodity },
        { label: 'Piezas', value: trackingResult?.tracking?.pieces },
        { label: 'Peso', value: formatWeight(trackingResult?.tracking?.weight) },
    ];

    const latestLegItems = [
        { label: 'Origen', value: trackingResult?.latest_leg?.origin },
        { label: 'Destino', value: trackingResult?.latest_leg?.destination },
        { label: 'Vuelo', value: trackingResult?.latest_leg?.flight },
        { label: 'ETD', value: normalizeLatamDate(trackingResult?.latest_leg?.etd) },
        { label: 'ETA', value: normalizeLatamDate(trackingResult?.latest_leg?.eta) },
        { label: 'Piezas', value: trackingResult?.latest_leg?.pieces },
        { label: 'Peso', value: formatWeight(trackingResult?.latest_leg?.weight) },
    ];

    const latestEventItems = [
        { label: 'Código', value: trackingResult?.latest_event?.code },
        { label: 'Descripción', value: trackingResult?.latest_event?.description },
        { label: 'Estación', value: trackingResult?.latest_event?.station },
        { label: 'Vuelo', value: trackingResult?.latest_event?.flight },
        { label: 'Actual PK', value: trackingResult?.latest_event?.actual_pk },
        { label: 'Hora real', value: normalizeLatamDate(trackingResult?.latest_event?.actual_time) },
    ];

    return (
        <div className="mt-4">
            <HeaderSummary trackingResult={trackingResult} />
            <ProgressBlock stage={stage} />

            <div className="row g-3">
                <div className="col-lg-4">
                    <SectionCard title="Resumen">
                        <CompactFieldList items={summaryItems} />
                    </SectionCard>
                </div>

                <div className="col-lg-4">
                    <SectionCard title="Último tramo">
                        <CompactFieldList items={latestLegItems} />
                    </SectionCard>
                </div>

                <div className="col-lg-4">
                    <SectionCard title="Último evento">
                        <CompactFieldList items={latestEventItems} />
                    </SectionCard>
                </div>
            </div>
        </div>
    );
}

function LatamTrackingApp({ trackingResult, trackingError }) {
    if (trackingError) {
        return (
            <div className="mt-4">
                <div className="card border-0 shadow-sm">
                    <div className="card-body p-3">
                        <div className="fw-semibold mb-2">Resultado consultado</div>
                        <div className="alert alert-danger mb-0">{trackingError}</div>
                    </div>
                </div>
            </div>
        );
    }

    if (!trackingResult?.ok) {
        return null;
    }

    return <TrackingResultView trackingResult={trackingResult} />;
}

const container = document.getElementById('latam-tracking-react');
const propsNode = document.getElementById('latam-tracking-react-props');

if (container && propsNode) {
    const props = JSON.parse(propsNode.textContent || '{}');
    createRoot(container).render(<LatamTrackingApp {...props} />);
}