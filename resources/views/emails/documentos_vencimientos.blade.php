<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Documentos por vencer esta semana</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f8; padding: 20px;">
    <div style="max-width: 700px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); padding: 20px;">

        <h2 style="color: #0056b3; text-align: center;">
            📅 Documentos con vencimiento esta semana
        </h2>

        <p style="text-align: center; color: #555;">
            Rango: 
            <strong>{{ now()->startOfWeek()->format('d/m/Y') }}</strong> – 
            <strong>{{ now()->endOfWeek()->format('d/m/Y') }}</strong>
        </p>

        {{-- 🔹 SECCIÓN RCV_VENTAS --}}
        <h3 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 4px;">
            RCV_VENTAS
        </h3>

        @if ($ventas->isEmpty())
            <p style="color: #777;">✅ No hay documentos de ventas por vencer esta semana.</p>
        @else
            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-bottom: 25px;">
                <thead>
                    <tr style="background-color: #0056b3; color: #ffffff; text-align: left;">
                        <th style="border-radius: 6px 0 0 6px;">Cliente</th>
                        <th>Folio</th>
                        <th>Fecha Vencimiento</th>
                        <th>Saldo Pendiente</th>
                        <th style="border-radius: 0 6px 6px 0;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ventas as $doc)
                        <tr style="background-color: {{ $loop->even ? '#f9fafb' : '#ffffff' }}; border-bottom: 1px solid #ddd;">
                            <td>{{ $doc->razon_social ?? $doc->cliente->Nombre ?? 'Cliente desconocido' }}</td>
                            <td>{{ $doc->folio ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($doc->fecha_vencimiento)->format('d/m/Y') }}</td>
                            <td>${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}</td>
                            <td>
                                @if ($doc->saldo_pendiente <= 0)
                                    <span style="color: #28a745; font-weight: bold;">Pagado</span>
                                @elseif (\Carbon\Carbon::parse($doc->fecha_vencimiento)->isPast())
                                    <span style="color: #dc3545; font-weight: bold;">Vencido</span>
                                @else
                                    <span style="color: #007bff; font-weight: bold;">Al día</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- 🔸 SECCIÓN RCV_COMPRAS --}}
        <h3 style="color: #28a745; border-bottom: 2px solid #28a745; padding-bottom: 4px;">
            RCV_COMPRAS
        </h3>

        @if ($compras->isEmpty())
            <p style="color: #777;">✅ No hay documentos de compras por vencer esta semana.</p>
        @else
            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse; margin-bottom: 25px;">
                <thead>
                    <tr style="background-color: #28a745; color: #ffffff; text-align: left;">
                        <th style="border-radius: 6px 0 0 6px;">Proveedor</th>
                        <th>Folio</th>
                        <th>Fecha Vencimiento</th>
                        <th>Saldo Pendiente</th>
                        <th style="border-radius: 0 6px 6px 0;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($compras as $doc)
                        <tr style="background-color: {{ $loop->even ? '#f9fafb' : '#ffffff' }}; border-bottom: 1px solid #ddd;">
                            <td>{{ $doc->razon_social ?? $doc->cliente->Nombre ?? 'Proveedor desconocido' }}</td>
                            <td>{{ $doc->folio ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($doc->fecha_vencimiento)->format('d/m/Y') }}</td>
                            <td>${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}</td>
                            <td>
                                @if ($doc->saldo_pendiente <= 0)
                                    <span style="color: #28a745; font-weight: bold;">Pagado</span>
                                @elseif (\Carbon\Carbon::parse($doc->fecha_vencimiento)->isPast())
                                    <span style="color: #dc3545; font-weight: bold;">Vencido</span>
                                @else
                                    <span style="color: #007bff; font-weight: bold;">Al día</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p style="margin-top: 25px; color: #555;">
            🕓 <em>Reporte generado automáticamente por el módulo de Finanzas.</em><br>
            📧 Este correo es informativo; no requiere respuesta.
        </p>

        <hr style="margin: 25px 0; border: none; border-top: 1px solid #ddd;">
        <p style="font-size: 12px; text-align: center; color: #999;">
            © {{ date('Y') }} 4N Logística · Sistema de Gestión Financiera
        </p>
    </div>
</body>
</html>
