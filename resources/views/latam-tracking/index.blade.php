@extends('layouts.app')

@section('content')
    <style>
        :root {
            --bg: #f4f7fb;
            --panel: #ffffff;
            --panel-soft: #f8fafc;
            --border: #e2e8f0;
            --border-strong: #cbd5e1;
            --text: #0f172a;
            --muted: #475569;
            --muted-soft: #64748b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary: #64748b;
            --secondary-hover: #475569;
            --shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            --shadow-soft: 0 8px 20px rgba(15, 23, 42, 0.05);
            --radius-xl: 20px;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 10px;
        }

        .latam-tracking-view,
        .latam-tracking-view * {
            box-sizing: border-box;
        }

        .latam-tracking-view {
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.06), transparent 28%),
                radial-gradient(circle at top right, rgba(14, 165, 233, 0.06), transparent 26%),
                var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 24px;
        }

        .latam-tracking-view .page {
            max-width: 1180px;
            margin: 0 auto;
        }

        .latam-tracking-view .hero {
            background: var(--panel);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            padding: 28px;
        }

        .latam-tracking-view .hero-header {
            margin-bottom: 24px;
        }

        .latam-tracking-view .hero-label {
            margin: 0 0 10px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--primary);
        }

        .latam-tracking-view .hero-title {
            margin: 0;
            font-size: 2.2rem;
            line-height: 1.1;
            color: var(--text);
        }

        .latam-tracking-view .hero-description {
            margin: 12px 0 0;
            max-width: 780px;
            font-size: 1rem;
            line-height: 1.7;
            color: var(--muted);
        }

        .latam-tracking-view .section-card {
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
        }

        .latam-tracking-view .filters {
            padding: 22px;
            margin-bottom: 22px;
        }

        .latam-tracking-view .section-title {
            margin: 0 0 6px;
            font-size: 1.35rem;
            color: var(--text);
        }

        .latam-tracking-view .section-description {
            margin: 0 0 18px;
            font-size: 0.95rem;
            color: var(--muted-soft);
            line-height: 1.6;
        }

        .latam-tracking-view .filters-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .latam-tracking-view .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .latam-tracking-view .field label {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text);
        }

        .latam-tracking-view .field input {
            width: 100%;
            height: 46px;
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-sm);
            background: #fff;
            padding: 0 14px;
            font-size: 0.95rem;
            color: var(--text);
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .latam-tracking-view .field input::placeholder {
            color: #94a3b8;
        }

        .latam-tracking-view .field input:focus {
            border-color: rgba(37, 99, 235, 0.5);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
            background: #fff;
        }

        .latam-tracking-view .filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 18px;
        }

        .latam-tracking-view .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 12px;
            border: none;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.2s ease, background 0.2s ease, opacity 0.2s ease;
        }

        .latam-tracking-view .btn:hover {
            transform: translateY(-1px);
        }

        .latam-tracking-view .btn-primary {
            color: #fff;
            background: var(--primary);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
        }

        .latam-tracking-view .btn-primary:hover {
            background: var(--primary-hover);
        }

        .latam-tracking-view .btn-secondary {
            color: #fff;
            background: var(--secondary);
        }

        .latam-tracking-view .btn-secondary:hover {
            background: var(--secondary-hover);
        }

        .latam-tracking-view .results-card {
            padding: 22px;
        }

        .latam-tracking-view .results-header {
            margin-bottom: 18px;
        }

        .latam-tracking-view .results-table-wrapper {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #fff;
        }

        .latam-tracking-view .results-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
        }

        .latam-tracking-view .results-table thead th {
            background: #f8fafc;
            color: var(--text);
            text-align: left;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .latam-tracking-view .results-table tbody td {
            padding: 15px 16px;
            border-bottom: 1px solid #edf2f7;
            font-size: 0.95rem;
            vertical-align: middle;
        }

        .latam-tracking-view .results-table tbody tr:last-child td {
            border-bottom: none;
        }

        .latam-tracking-view .results-table tbody tr:hover {
            background: #f8fbff;
        }

        .latam-tracking-view .code-cell,
        .latam-tracking-view .prefix-cell,
        .latam-tracking-view .date-cell {
            white-space: nowrap;
        }

        .latam-tracking-view .tracking-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }

        .latam-tracking-view .tracking-link:hover {
            text-decoration: underline;
        }

        .latam-tracking-view .mobile-results {
            display: none;
        }

        .latam-tracking-view .mobile-card-list {
            display: grid;
            gap: 14px;
        }

        .latam-tracking-view .mobile-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            box-shadow: var(--shadow-soft);
        }

        .latam-tracking-view .mobile-card-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 8px 0;
            border-bottom: 1px solid #eef2f7;
        }

        .latam-tracking-view .mobile-card-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .latam-tracking-view .mobile-card-label {
            min-width: 110px;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--muted-soft);
        }

        .latam-tracking-view .mobile-card-value {
            flex: 1;
            text-align: right;
            font-size: 0.92rem;
            color: var(--text);
            word-break: break-word;
        }

        .latam-tracking-view .empty {
            margin-top: 22px;
            padding: 18px 20px;
            border: 1px solid #fde68a;
            background: #fffbea;
            border-radius: 14px;
            color: #854d0e;
            line-height: 1.6;
        }

        @media (max-width: 1024px) {
            .latam-tracking-view .filters-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .latam-tracking-view .hero {
                padding: 24px;
            }

            .latam-tracking-view .hero-title {
                font-size: 1.95rem;
            }
        }

        @media (max-width: 640px) {
            .latam-tracking-view {
                padding: 14px;
            }

            .latam-tracking-view .hero {
                padding: 18px;
                border-radius: 18px;
            }

            .latam-tracking-view .hero-header {
                margin-bottom: 18px;
            }

            .latam-tracking-view .hero-title {
                font-size: 1.7rem;
            }

            .latam-tracking-view .hero-description {
                font-size: 0.95rem;
                margin-top: 10px;
            }

            .latam-tracking-view .filters,
            .latam-tracking-view .results-card {
                padding: 16px;
            }

            .latam-tracking-view .filters-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .latam-tracking-view .filter-actions {
                flex-direction: column;
            }

            .latam-tracking-view .btn {
                width: 100%;
            }

            .latam-tracking-view .desktop-results {
                display: none;
            }

            .latam-tracking-view .mobile-results {
                display: block;
            }

            .latam-tracking-view .section-title {
                font-size: 1.2rem;
            }
        }
    </style>

    <div class="latam-tracking-view">
        <div class="page">
            <div class="hero">
                <div class="hero-header">
                    <p class="hero-label">Consulta interna</p>
                    <h1 class="hero-title">LATAM Tracking</h1>
                    <p class="hero-description">
                        Aquí se muestran los códigos almacenados para consulta y seguimiento.
                    </p>
                </div>

                <div class="section-card filters">
                    <h2 class="section-title">Filtrar registros</h2>
                    <p class="section-description">
                        Busca por prefijo, código tracking, destino o fecha de proceso.
                    </p>

                    <form method="GET" action="{{ route('latam.tracking.index') }}">
                        <div class="filters-grid">
                            <div class="field">
                                <label for="prefijo">Prefijo</label>
                                <input
                                    type="text"
                                    id="prefijo"
                                    name="prefijo"
                                    value="{{ request('prefijo') }}"
                                    placeholder="Ej: 972"
                                >
                            </div>

                            <div class="field">
                                <label for="codigo_tracking">Código tracking</label>
                                <input
                                    type="text"
                                    id="codigo_tracking"
                                    name="codigo_tracking"
                                    value="{{ request('codigo_tracking') }}"
                                    placeholder="Ej: 03574362"
                                >
                            </div>

                            <div class="field">
                                <label for="destino">Destino</label>
                                <input
                                    type="text"
                                    id="destino"
                                    name="destino"
                                    value="{{ request('destino') }}"
                                    placeholder="Ej: SCL ARICA"
                                >
                            </div>

                            <div class="field">
                                <label for="fecha_proceso">Fecha proceso</label>
                                <input
                                    type="date"
                                    id="fecha_proceso"
                                    name="fecha_proceso"
                                    value="{{ request('fecha_proceso') }}"
                                >
                            </div>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="{{ route('latam.tracking.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        </div>
                    </form>
                </div>

                @if(isset($rows) && count($rows) > 0)
                    <div class="section-card results-card">
                        <div class="results-header">
                            <h2 class="section-title">Trackings almacenados</h2>
                            <p class="section-description">
                                Visualiza los registros encontrados y accede al tracking correspondiente.
                            </p>
                        </div>

                        <div class="desktop-results">
                            <div class="results-table-wrapper">
                                <table class="results-table">
                                    <thead>
                                        <tr>
                                            <th>Prefijo</th>
                                            <th>Código</th>
                                            <th>Destino</th>
                                            <th>Fecha proceso</th>
                                            <th>Tracking LATAM</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rows as $row)
                                            <tr>
                                                <td class="prefix-cell">{{ $row['prefix'] }}</td>
                                                <td class="code-cell">{{ $row['code'] }}</td>
                                                <td>{{ $row['destino'] }}</td>
                                                <td class="date-cell">{{ $row['fecha_proceso'] }}</td>
                                                <td>
                                                    <a href="{{ $row['url'] }}" target="_blank" class="tracking-link">
                                                        Abrir tracking
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mobile-results">
                            <div class="mobile-card-list">
                                @foreach ($rows as $row)
                                    <div class="mobile-card">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Prefijo</span>
                                            <span class="mobile-card-value">{{ $row['prefix'] }}</span>
                                        </div>

                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Código</span>
                                            <span class="mobile-card-value">{{ $row['code'] }}</span>
                                        </div>

                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Destino</span>
                                            <span class="mobile-card-value">{{ $row['destino'] }}</span>
                                        </div>

                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Fecha proceso</span>
                                            <span class="mobile-card-value">{{ $row['fecha_proceso'] }}</span>
                                        </div>

                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Tracking LATAM</span>
                                            <span class="mobile-card-value">
                                                <a href="{{ $row['url'] }}" target="_blank" class="tracking-link">
                                                    Abrir tracking
                                                </a>
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="empty">
                        No se encontraron registros con esos filtros.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection