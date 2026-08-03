@extends('layouts.app')

@vite('resources/css/suscripciones_liquidaciones.css')

@section('content')
@php
    $meses = $meses ?? [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    $proveedorFiltro = $proveedor ?? request('proveedor');
    $rutFiltro = $rut ?? request('rut');
    $tipoFiltro = $tipo ?? request('tipo');
    $anioFiltro = $anio ?? request('anio');
    $mesFiltro = $mes ?? request('mes');

    $tiposDocumento = $tiposDocumento ?? collect(['FACTURA', 'BOLETA', 'DOCUMENTO']);

    $filtrosActivos = $filtrosActivos ?? collect([
        $proveedorFiltro,
        $rutFiltro,
        $tipoFiltro,
        $anioFiltro,
        $mesFiltro,
    ])->filter(fn ($valor) => $valor !== null && $valor !== '')->count();

    $resumenPorTipo = $resumenPorTipo ?? [];

    $resumenVacio = [
        'label' => '',
        'cantidad' => 0,
        'neto_bruto' => 0,
        'total_impuesto' => 0,
        'total_final' => 0,
    ];

    $resumenBoletas = $resumenPorTipo['BOLETA'] ?? array_merge($resumenVacio, ['label' => 'Boletas']);
    $resumenFacturas = $resumenPorTipo['FACTURA'] ?? array_merge($resumenVacio, ['label' => 'Facturas']);
    $resumenDocumentos = $resumenPorTipo['DOCUMENTO'] ?? array_merge($resumenVacio, ['label' => 'Documentos']);
    $resumenTotalGeneral = $resumenPorTipo['TOTAL'] ?? array_merge($resumenVacio, ['label' => 'Total general']);

    $buscarValorTipoDocumento = function (string $clave) use ($tiposDocumento) {
        return collect($tiposDocumento)
            ->first(function ($tipoDocumento) use ($clave) {
                return str_contains(
                    mb_strtoupper(trim((string) $tipoDocumento)),
                    $clave
                );
            }) ?? $clave;
    };

    $tipoDocumentoValue = $buscarValorTipoDocumento('DOCUMENTO');
    $tipoBoletaValue = $buscarValorTipoDocumento('BOLETA');
    $tipoFacturaValue = $buscarValorTipoDocumento('FACTURA');

    $tipoSeleccionado = function ($valor) use ($tipoFiltro) {
        return mb_strtoupper(trim((string) $tipoFiltro)) === mb_strtoupper(trim((string) $valor));
    };
@endphp

<div class="sl-page">
    <header class="sl-page-header">
        <a href="{{ route('cobranzas.general') }}" class="sl-back-link">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            <span>Volver al panel de Finanzas</span>
        </a>

        <h1 class="sl-page-title">Liquidaciones de Suscripciones</h1>
    </header>

    @if(session('success'))
        <div class="alert alert-success sl-alert" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info sl-alert" role="status">
            {{ session('info') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger sl-alert" role="alert">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="sl-workspace" aria-label="Operaciones de liquidaciones">
        <div class="sl-workspace-section sl-workspace-generate">
            <div class="sl-section-head">
                <h2 class="sl-section-title">Generar mes completo</h2>
            </div>

            <form
                method="GET"
                action="{{ route('suscripciones.comisiones-mensuales.create') }}"
                class="sl-generate-form"
            >
                <input type="hidden" name="proveedor_actual" value="{{ $proveedorFiltro }}">

                <div class="sl-fields-2">
                    <div class="sl-field">
                        <label for="sl-generar-anio">Año</label>
                        <input
                            id="sl-generar-anio"
                            type="number"
                            name="anio"
                            class="form-control form-control-sm"
                            value="{{ request('anio', now()->year) }}"
                            min="2020"
                            max="2100"
                            required
                        >
                    </div>

                    <div class="sl-field">
                        <label for="sl-generar-mes">Mes</label>
                        <select
                            id="sl-generar-mes"
                            name="mes"
                            class="form-select form-select-sm"
                            required
                        >
                            @foreach ($meses as $numeroMes => $nombreMes)
                                <option
                                    value="{{ $numeroMes }}"
                                    @selected((int) request('mes', now()->month) === (int) $numeroMes)
                                >
                                    {{ $nombreMes }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn sl-btn sl-btn-primary">
                    <i class="fa-solid fa-play" aria-hidden="true"></i>
                    <span>Continuar generación</span>
                </button>
            </form>
        </div>

        <div class="sl-workspace-section sl-workspace-filters">
            <div class="sl-section-head">
                <h2 class="sl-section-title">Filtros de búsqueda</h2>

                @if($filtrosActivos)
                    <span class="sl-section-meta">
                        {{ $filtrosActivos }} {{ $filtrosActivos === 1 ? 'filtro activo' : 'filtros activos' }}
                    </span>
                @endif
            </div>

            <form
                method="GET"
                action="{{ route('suscripciones.liquidacion-detalles.index') }}"
            >
                <div class="sl-filter-grid">
                    <div class="sl-field sl-filter-provider">
                        <label for="sl-filtro-proveedor">Proveedor</label>
                        <input
                            id="sl-filtro-proveedor"
                            type="text"
                            name="proveedor"
                            class="form-control form-control-sm"
                            placeholder="Ej: ANDRES FERNANDO MUÑOZ"
                            value="{{ $proveedorFiltro }}"
                        >
                    </div>

                    <div class="sl-field sl-filter-rut">
                        <label for="sl-filtro-rut">RUT</label>
                        <input
                            id="sl-filtro-rut"
                            type="text"
                            name="rut"
                            class="form-control form-control-sm"
                            placeholder="Ej: 10513948-9"
                            value="{{ $rutFiltro }}"
                        >
                    </div>

                    <div class="sl-field sl-filter-type">
                        <label for="sl-filtro-tipo">Tipo documento</label>
                        <select id="sl-filtro-tipo" name="tipo" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option
                                value="{{ $tipoDocumentoValue }}"
                                {{ $tipoSeleccionado($tipoDocumentoValue) ? 'selected' : '' }}
                            >
                                Documento ({{ number_format($resumenDocumentos['cantidad'], 0, ',', '.') }})
                            </option>
                            <option
                                value="{{ $tipoBoletaValue }}"
                                {{ $tipoSeleccionado($tipoBoletaValue) ? 'selected' : '' }}
                            >
                                Boleta Honorario ({{ number_format($resumenBoletas['cantidad'], 0, ',', '.') }})
                            </option>
                            <option
                                value="{{ $tipoFacturaValue }}"
                                {{ $tipoSeleccionado($tipoFacturaValue) ? 'selected' : '' }}
                            >
                                Factura ({{ number_format($resumenFacturas['cantidad'], 0, ',', '.') }})
                            </option>
                        </select>
                    </div>

                    <div class="sl-field sl-filter-year">
                        <label for="sl-filtro-anio">Año</label>
                        <input
                            id="sl-filtro-anio"
                            type="number"
                            name="anio"
                            class="form-control form-control-sm"
                            placeholder="2026"
                            value="{{ $anioFiltro }}"
                            min="2020"
                            max="2100"
                        >
                    </div>

                    <div class="sl-field sl-filter-month">
                        <label for="sl-filtro-mes">Mes</label>
                        <select id="sl-filtro-mes" name="mes" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach($meses as $numero => $nombre)
                                <option value="{{ $numero }}" {{ (int) $mesFiltro === $numero ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="sl-button-row">
                    <a
                        href="{{ route('suscripciones.liquidacion-detalles.index') }}"
                        class="btn sl-btn sl-btn-muted"
                    >
                        Limpiar
                    </a>

                    <button type="submit" class="btn sl-btn sl-btn-primary">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <span>Buscar</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="sl-workspace-section sl-workspace-documents">
            <div class="sl-section-head">
                <h2 class="sl-section-title">Pre-facturas PDF</h2>
            </div>

            <form
                method="POST"
                action="{{ route('suscripciones.liquidacion-detalles.pdf-masivo') }}"
                class="sl-document-form"
                data-long-loader="300000"
            >
                @csrf

                <input type="hidden" name="proveedor_pdf" value="{{ $proveedorFiltro }}">
                <input type="hidden" name="rut_pdf" value="{{ $rutFiltro }}">
                <input type="hidden" name="tipo_pdf" value="{{ $tipoFiltro }}">
                <input type="hidden" name="confirmacion_envio" id="confirmacion_envio_real" value="">

                <div class="sl-fields-2">
                    <div class="sl-field">
                        <label for="sl-pdf-anio">Año PDF</label>
                        <input
                            id="sl-pdf-anio"
                            type="number"
                            name="anio_pdf"
                            class="form-control form-control-sm"
                            value="{{ request('anio', 2026) }}"
                            min="2020"
                            max="2100"
                            required
                        >
                    </div>

                    <div class="sl-field">
                        <label for="sl-pdf-mes">Mes PDF</label>
                        <select
                            id="sl-pdf-mes"
                            name="mes_pdf"
                            class="form-select form-select-sm"
                            required
                        >
                            @foreach($meses as $numero => $nombre)
                                <option value="{{ $numero }}" {{ (int) request('mes', 4) === $numero ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="sl-document-actions">
                    <button type="submit" class="btn sl-btn sl-btn-outline">
                        <i class="fa-solid fa-file-zipper" aria-hidden="true"></i>
                        <span>Descargar ZIP de pre-facturas</span>
                    </button>

                    <button
                        type="submit"
                        class="btn sl-btn sl-btn-outline"
                        formaction="{{ route('suscripciones.liquidacion-detalles.revisar-destinatarios') }}"
                    >
                        <i class="fa-regular fa-envelope-open" aria-hidden="true"></i>
                        <span>Revisar destinatarios</span>
                    </button>

                    <button
                        type="submit"
                        class="btn sl-btn sl-btn-outline"
                        formaction="{{ route('suscripciones.liquidacion-detalles.enviar-correos-prueba-masivo') }}"
                        onclick="
                            document.getElementById('confirmacion_envio_real').value = '';

                            return confirm(
                                '¿Enviar una copia de cada pre-factura seleccionada únicamente a eliascorreap@gmail.com?'
                            );
                        "
                    >
                        <i class="fa-regular fa-paper-plane" aria-hidden="true"></i>
                        <span>Enviar pre-facturas de prueba</span>
                    </button>

                    <p class="sl-helper">
                        La prueba se dirige únicamente a eliascorreap@gmail.com.
                    </p>
                </div>

                <div class="sl-danger-zone">
                    <button
                        type="submit"
                        class="btn sl-btn sl-btn-danger w-100"
                        formaction="{{ route('suscripciones.liquidacion-detalles.enviar-correos-reales-masivo') }}"
                        onclick="
                            const confirmacion = prompt(
                                'ATENCIÓN: este envío llegará a los proveedores reales.\n\nEscribe ENVIAR para continuar:'
                            );

                            if (confirmacion !== 'ENVIAR') {
                                document.getElementById('confirmacion_envio_real').value = '';

                                alert('Envío cancelado. Debes escribir exactamente ENVIAR.');

                                return false;
                            }

                            document.getElementById('confirmacion_envio_real').value = 'ENVIAR';

                            return confirm(
                                'CONFIRMACIÓN FINAL:\n\n¿Enviar las pre-facturas seleccionadas a los correos reales de los proveedores, con copia a Finanzas y Luis de la Barra?'
                            );
                        "
                    >
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                        <span>Enviar pre-facturas a proveedores</span>
                    </button>

                    <p class="sl-danger-note">
                        Envío real al correo registrado de cada proveedor, con copia a Finanzas y Luis de la Barra.
                    </p>
                </div>
            </form>
        </div>
    </section>

    <section class="sl-summary" aria-label="Resumen de liquidaciones">
        <div class="sl-summary-item">
            <span class="sl-summary-label">Pre-facturas encontradas</span>
            <div class="sl-summary-line">
                <strong class="sl-summary-count">{{ number_format($cantidadRegistros ?? $prefacturas->total(), 0, ',', '.') }}</strong>
                <span class="sl-summary-amount">
                    ${{ number_format($resumenTotalGeneral['neto_bruto'], 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="sl-summary-item">
            <span class="sl-summary-label">Boletas</span>
            <div class="sl-summary-line">
                <strong class="sl-summary-count">{{ number_format($resumenBoletas['cantidad'], 0, ',', '.') }}</strong>
                <span class="sl-summary-amount">
                    ${{ number_format($resumenBoletas['total_final'], 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="sl-summary-item">
            <span class="sl-summary-label">Facturas</span>
            <div class="sl-summary-line">
                <strong class="sl-summary-count">{{ number_format($resumenFacturas['cantidad'], 0, ',', '.') }}</strong>
                <span class="sl-summary-amount">
                    ${{ number_format($resumenFacturas['total_final'], 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="sl-summary-item">
            <span class="sl-summary-label">Documentos</span>
            <div class="sl-summary-line">
                <strong class="sl-summary-count">{{ number_format($resumenDocumentos['cantidad'], 0, ',', '.') }}</strong>
                <span class="sl-summary-amount">
                    ${{ number_format($resumenDocumentos['total_final'], 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="sl-summary-item sl-summary-total">
            <span class="sl-summary-label">Total general</span>
            <div class="sl-summary-line">
                <strong class="sl-summary-count">{{ number_format($resumenTotalGeneral['cantidad'], 0, ',', '.') }}</strong>
                <span class="sl-summary-amount">
                    ${{ number_format($resumenTotalGeneral['total_final'], 0, ',', '.') }}
                </span>
            </div>
        </div>
    </section>

    <section class="sl-table-region" aria-label="Detalle de pre-facturas">
        @if($prefacturas->isEmpty())
            <div class="sl-empty">
                No hay pre-facturas registradas para los filtros seleccionados.
            </div>
        @else
            <x-finanzas.plain-table>
                <thead>
                    <tr>
                        <th>Año</th>
                        <th>Mes</th>
                        <th>Proveedor</th>
                        <th>RUT</th>
                        <th>Tipo</th>
                        <th class="text-end">Neto/Bruto</th>
                        <th class="text-end">Total Impuesto</th>
                        <th class="text-end">Final</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($prefacturas as $prefactura)
                        <tr>
                            <td class="text-nowrap">{{ $prefactura['anio'] }}</td>
                            <td class="text-nowrap">{{ $prefactura['mes_nombre'] }}</td>
                            <td>
                                <a
                                    href="{{ route('suscripciones.liquidacion-detalles.show', $prefactura['detalle_id']) }}"
                                    class="sl-provider-link"
                                >
                                    {{ $prefactura['proveedor'] }}
                                </a>
                            </td>
                            <td class="text-nowrap">{{ $prefactura['rut'] }}</td>
                            <td>
                                <span class="sl-doc-type">
                                    {{ $prefactura['tipo'] === 'BOLETA' ? 'Boleta Honorario' : $prefactura['tipo'] }}
                                </span>
                            </td>
                            <td class="text-end sl-money">
                                ${{ number_format($prefactura['neto_bruto'], 0, ',', '.') }}
                            </td>
                            <td class="text-end sl-money">
                                ${{ number_format($prefactura['total_impuesto'], 1, ',', '.') }}
                            </td>
                            <td class="text-end sl-money sl-money-final" title="{{ $prefactura['final'] }}">
                                ${{ number_format($prefactura['total_final'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-finanzas.plain-table>

            <div class="sl-pagination">
                {{ $prefacturas->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </section>
</div>
@endsection
