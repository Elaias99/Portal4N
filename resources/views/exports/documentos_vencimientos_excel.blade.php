<table>
    <thead>
        <tr>
            <th colspan="5" style="text-align:center; font-weight:bold;">
                Documentos con vencimiento esta semana
            </th>
        </tr>
        <tr>
            <th colspan="5" style="text-align:center;">
                Rango: {{ $inicio->format('d/m/Y') }} – {{ $fin->format('d/m/Y') }}
            </th>
        </tr>
    </thead>

    {{-- VENTAS --}}
    <tbody>
        <tr><td colspan="5" style="font-weight:bold; background-color:#d9ead3;">RCV_VENTAS</td></tr>
        <tr>
            <th>Cliente</th>
            <th>Folio</th>
            <th>Fecha Vencimiento</th>
            <th>Monto</th>
            <th>Estado</th>
        </tr>

        @forelse ($ventas as $venta)
            <tr>
                <td>{{ $venta->razon_social ?? $venta->cliente->Nombre ?? 'Cliente desconocido' }}</td>

                <td>{{ $venta->folio }}</td>
                <td>{{ \Carbon\Carbon::parse($venta->fecha_vencimiento)->format('d/m/Y') }}</td>
                <td>{{ '$' . number_format($venta->saldo_pendiente, 0, ',', '.') }}</td>
                <td>
                    {{ \Carbon\Carbon::parse($venta->fecha_vencimiento)->isPast() ? 'Vencido' : 'Al día' }}
                </td>
            </tr>
        @empty
            <tr><td colspan="5">No hay documentos de ventas para este período.</td></tr>
        @endforelse
    </tbody>

    {{-- COMPRAS --}}
    {{-- <tbody>
        <tr><td colspan="5" style="font-weight:bold; background-color:#c9daf8;">RCV_COMPRAS</td></tr>
        <tr>
            <th>Proveedor</th>
            <th>Folio</th>
            <th>Fecha Vencimiento</th>
            <th>Monto</th>
            <th>Estado</th>
        </tr>

        @forelse ($compras as $compra)
            <tr>
                <td>{{ $compra->razon_social ?? $compra->proveedor->Nombre ?? 'Proveedor desconocido' }}</td>

                <td>{{ $compra->folio }}</td>
                <td>{{ \Carbon\Carbon::parse($compra->fecha_vencimiento)->format('d/m/Y') }}</td>
                <td>{{ number_format($compra->monto_total, 0, ',', '.') }}</td>
                <td>
                    {{ \Carbon\Carbon::parse($compra->fecha_vencimiento)->isPast() ? 'Vencido' : 'Al día' }}
                </td>
            </tr>
        @empty
            <tr><td colspan="5">No hay documentos de compras para este período.</td></tr>
        @endforelse
    </tbody> --}}
</table>
