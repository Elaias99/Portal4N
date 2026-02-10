<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Documentos vencidos con saldo pendiente</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f8; padding: 20px;">

    <div style="max-width: 700px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); padding: 20px;">

        <h2 style="color: #c82333; text-align: center; margin-bottom: 10px;">
            Documentos financieros vencidos con saldo pendiente
        </h2>

        <p style="text-align: center; color: #555; margin-bottom: 20px;">
            Este informe muestra los documentos que ya <strong>superaron su fecha de vencimiento</strong> 
            y mantienen un saldo pendiente al día <strong>{{ now()->format('d/m/Y') }}</strong>.
        </p>

        {{-- Sección de Ventas --}}
        <h3 style="color: #0056b3; margin-top: 25px;">RCV_VENTAS</h3>

        @if ($ventas->isEmpty())
            <p style="color: #777;">No hay documentos de venta vencidos con saldo pendiente.</p>
        @else
            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="background-color: #0056b3; color: #ffffff;">
                        <th>Cliente</th>
                        <th>Folio</th>
                        <th>Fecha Vencimiento</th>
                        <th>Saldo Pendiente</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ventas as $doc)
                        <tr style="background-color: {{ $loop->even ? '#f9fafb' : '#ffffff' }}; border-bottom: 1px solid #ddd;">
                            <td>{{ $doc->razon_social ?? $doc->cliente->Nombre ?? 'Cliente desconocido' }}</td>
                            <td>{{ $doc->folio ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($doc->fecha_vencimiento)->format('d/m/Y') }}</td>
                            <td>${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Sección de Compras --}}
        {{-- <h3 style="color: #b35a00;">RCV_COMPRAS</h3>

        @if ($compras->isEmpty())
            <p style="color: #777;"> No hay documentos de compra vencidos con saldo pendiente.</p>
        @else
            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #b35a00; color: #ffffff;">
                        <th>Proveedor</th>
                        <th>Folio</th>
                        <th>Fecha Vencimiento</th>
                        <th>Saldo Pendiente</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($compras as $doc)
                        <tr style="background-color: {{ $loop->even ? '#f9fafb' : '#ffffff' }}; border-bottom: 1px solid #ddd;">
                            <td>{{ $doc->razon_social ?? 'Proveedor desconocido' }}</td>
                            <td>{{ $doc->folio ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($doc->fecha_vencimiento)->format('d/m/Y') }}</td>
                            <td>${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p style="margin-top: 25px; color: #555;">
             <em>Reporte generado automáticamente por el módulo de Finanzas.</em><br>
             Este correo es informativo; no requiere respuesta.
        </p>

        <hr style="margin: 25px 0; border: none; border-top: 1px solid #ddd;">
        <p style="font-size: 12px; text-align: center; color: #999;">
            © {{ date('Y') }} 4N Logística · Sistema de Gestión Financiera
        </p> --}}
    </div>
</body>
</html>
