<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pre Factura de Servicios</title>

    <style>
        @page {
            margin: 25px 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
        }

        .titulo {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        .periodo {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 22px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .header-table td {
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 110px;
        }

        .logo-box {
            text-align: right;
            vertical-align: top;
        }

        .logo-img {
            width: 155px;
            height: auto;
        }

        .black-title {
            background: #000;
            color: #fff;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            padding: 6px;
            margin-top: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .info-table .left {
            width: 50%;
            font-weight: bold;
        }

        .section-title {
            font-weight: bold;
            border: 1px solid #000;
            padding: 5px;
            background: #f2f2f2;
        }

        .detail-table {
            margin-top: 0;
        }

        .detail-table th {
            background: #000;
            color: #fff;
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-weight: bold;
        }

        .detail-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .text-end {
            text-align: right;
        }

        .fw-bold {
            font-weight: bold;
        }

        .totals-table {
            width: 47%;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .payment-box {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 20px;
            border: 1px solid #999;
            padding: 8px 12px;
        }

        .payment-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .footer {
            position: fixed;
            bottom: 2px;
            left: 0;
            right: 0;
            font-size: 10px;
            color: #444;
        }

        .footer-left {
            float: left;
        }

        .footer-right {
            float: right;
        }
    </style>
</head>
<body>

    @php
        $mesNombre = mb_strtoupper($meses[$detalle->mes] ?? $detalle->mes);

        $logoPath = public_path('logos/xwF2TD8hBXoM9sLQIkUNvyh5FIu5j5YVqrT1GM8o.png');
        $logoBase64 = null;

        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $tipo = mb_strtoupper(trim((string) ($proveedor?->tipo ?? '')));
        $detalleImpuesto = mb_strtoupper(trim((string) ($proveedor?->detalle_impuesto ?? 'IMPUESTO')));

        $porcentaje = 0;

        if (str_contains($tipo, 'FACTURA')) {
            $porcentaje = 19;
        } elseif (str_contains($tipo, 'BOLETA')) {
            $porcentaje = 15.25;
        }

        $nombreBanco = data_get($cobranzaCompra, 'banco.Nombre')
            ?? data_get($cobranzaCompra, 'banco.nombre')
            ?? data_get($cobranzaCompra, 'banco_id')
            ?? '—';

        $tipoCuenta = data_get($cobranzaCompra, 'tipoCuenta.nombre')
            ?? data_get($cobranzaCompra, 'tipoCuenta.Nombre')
            ?? data_get($cobranzaCompra, 'tipo_cuenta_id')
            ?? '—';

        $fechaDocumento = now()->locale('es')->translatedFormat('l, d \d\e F \d\e Y');
        $fechaPie = now()->locale('es')->translatedFormat('l, d \d\e F \d\e Y');
    @endphp

    <div class="titulo">PRE FACTURA DE SERVICIOS</div>
    <div class="periodo">{{ $mesNombre }} {{ $detalle->anio }}</div>

    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <table>
                    <tr>
                        <td class="label">PROVEEDOR</td>
                        <td>{{ $cobranzaCompra?->razon_social ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">RUT</td>
                        <td>{{ $cobranzaCompra?->rut_cliente ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">CENTRO COSTO</td>
                        <td>{{ $cobranzaCompra?->servicio ?? 'SUSCRIPCIONES' }}</td>
                    </tr>
                    <tr>
                        <td class="label">DIRECCION</td>
                        <td>{{ $cobranzaCompra?->direccion ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">COMUNA</td>
                        <td>{{ $cobranzaCompra?->comuna ?? '—' }}</td>
                    </tr>
                </table>
            </td>

            <td class="logo-box">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="logo-img" alt="PMCB">
                @else
                    <strong>PMCB</strong>
                @endif
            </td>
        </tr>
    </table>

    <div class="black-title">
        PRE FACTURA SUSCRIPCIONES
    </div>

    <div class="section-title">
        DATOS PARA GENERAR {{ $tipo ?: 'DOCUMENTO' }}
    </div>

    <table class="info-table">
        <tr>
            <td class="left">RAZON SOCIAL</td>
            <td>TRANSPORTES Y DISTRIBUCION PMCB SPA</td>
        </tr>
        <tr>
            <td class="left">RUT</td>
            <td>77.639.015-1</td>
        </tr>
        <tr>
            <td class="left">DIRECCION</td>
            <td>AV PDTE FREI MONTALVA 9215 - QUILICURA</td>
        </tr>
        <tr>
            <td class="left">CORREO</td>
            <td>proveedores@4nlogistica.cl</td>
        </tr>
        <tr>
            <td class="left">FECHA DE DOCUMENTO</td>
            <td>{{ $fechaDocumento }}</td>
        </tr>
    </table>

    <table class="detail-table">
        <thead>
            <tr>
                <th style="width: 54%;">DETALLE</th>
                <th style="width: 15%;">VALOR</th>
                <th style="width: 15%;">CANTIDAD</th>
                <th style="width: 16%;">TOTAL</th>
            </tr>
        </thead>

        <tbody>
            @foreach($detallesProveedor as $item)
                @php
                    $esValorFijo = str_ends_with(mb_strtoupper(trim($item->codigo)), '.COM');
                @endphp

                <tr>
                    <td>
                        {{ $item->asignacion?->punto_1 ?? '—' }}
                        /
                        {{ $item->asignacion?->servicio ?? '—' }}
                        ({{ $item->codigo }})
                    </td>

                    <td class="text-end">
                        {{ number_format($item->costo, 0, ',', '.') }}
                    </td>

                    <td class="text-end">
                        {{ $esValorFijo ? 1 : $item->cantidad }}
                    </td>

                    <td class="text-end">
                        {{ number_format($item->total, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="fw-bold text-end">TOTAL BRUTO</td>
            <td class="fw-bold text-end">
                {{ number_format($totalBruto, 0, ',', '.') }}
            </td>
        </tr>

        <tr>
            <td class="fw-bold text-end">
                {{ $detalleImpuesto }} {{ number_format($porcentaje, 2, ',', '.') }}%
            </td>
            <td class="fw-bold text-end">
                {{ number_format($totalImpuesto, 0, ',', '.') }}
            </td>
        </tr>

        <tr>
            <td class="fw-bold text-end">
                {{ mb_strtoupper($proveedor?->final ?? 'LIQUIDO') }}
            </td>
            <td class="fw-bold text-end">
                {{ number_format($totalLiquido, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <div class="payment-box">
        <div class="payment-title">DATOS PARA PAGO PROVEEDOR</div>

        <div style="margin-left: 120px;">
            <strong>{{ $cobranzaCompra?->nombre_cuenta ?? $cobranzaCompra?->razon_social ?? '—' }}</strong><br>
            {{ $cobranzaCompra?->rut_cuenta ?? $cobranzaCompra?->rut_cliente ?? '—' }}<br>
            {{ $nombreBanco }}<br>
            {{ $tipoCuenta }}<br>
            {{ $cobranzaCompra?->numero_cuenta ?? '—' }}
        </div>
    </div>

    <div class="footer">
        <span class="footer-left">{{ $fechaPie }}</span>
        <span class="footer-right">Página 1</span>
    </div>

</body>
</html>