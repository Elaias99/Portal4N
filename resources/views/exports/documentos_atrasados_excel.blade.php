<table>
    <thead>
        <tr>
            <th colspan="4" style="text-align:center; font-weight:bold;">
                ⚠️ Documentos financieros vencidos con saldo pendiente
            </th>
        </tr>
        <tr>
            <th colspan="4" style="text-align:center;">
                Reporte generado al {{ $fecha->format('d/m/Y') }}
            </th>
        </tr>
    </thead>

    {{-- VENTAS --}}
    <tbody>
        <tr><td colspan="4" style="font-weight:bold; background-color:#f4cccc;">RCV_VENTAS</td></tr>
        <tr>
            <th>Cliente</th>
            <th>Folio</th>
            <th>Fecha Vencimiento</th>
            <th>Monto</th>
        </tr>

        @forelse ($ventas as $venta)
            <tr>
                <td>{{ $venta->razon_social ?? $venta->cliente->Nombre ?? 'Cliente desconocido' }}</td>

                <td>{{ $venta->folio }}</td>
                <td>{{ \Carbon\Carbon::parse($venta->fecha_vencimiento)->format('d/m/Y') }}</td>
                <td>{{ '$' . number_format($venta->saldo_pendiente, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No hay documentos de ventas vencidos.</td></tr>
        @endforelse
    </tbody>

    {{-- COMPRAS --}}
    {{-- <tbody>
        <tr><td colspan="4" style="font-weight:bold; background-color:#f9cb9c;">RCV_COMPRAS</td></tr>
        <tr>
            <th>Proveedor</th>
            <th>Folio</th>
            <th>Fecha Vencimiento</th>
            <th>Monto</th>
        </tr>

        @forelse ($compras as $compra)
            <tr>
                <td>{{ $compra->razon_social ?? $compra->proveedor->Nombre ?? 'Proveedor desconocido' }}</td>

                <td>{{ $compra->folio }}</td>
                <td>{{ \Carbon\Carbon::parse($compra->fecha_vencimiento)->format('d/m/Y') }}</td>
                <td>{{ number_format($compra->monto_total, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No hay documentos de compras vencidos.</td></tr>
        @endforelse
    </tbody> --}}
</table>
