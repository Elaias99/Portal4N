<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Solicitud</title>
</head>
<body>
    <h2>
        @if ($estado === 'aprobada')
            ✅ Solicitud de Vacaciones Aprobada
        @else
            ❌ Solicitud de Vacaciones Rechazada
        @endif
    </h2>

    <p><strong>Estimado/a {{ $solicitud->trabajador->Nombre }} {{ $solicitud->trabajador->ApellidoPaterno }},</strong></p>

    <p>
        Su solicitud de vacaciones correspondiente al periodo del
        <strong>{{ $solicitud->vacacion->fecha_inicio }}</strong> al
        <strong>{{ $solicitud->vacacion->fecha_fin }}</strong>
        ha sido <strong>{{ $estado }}</strong>.
    </p>

    <p><strong>Comentario del administrador:</strong></p>
    <blockquote>{{ $solicitud->comentario_admin ?? 'Sin comentarios adicionales.' }}</blockquote>

    <p>Por favor, revise la plataforma para más detalles.</p>

    <hr>
    <p>Saludos cordiales,<br>Equipo de Recursos Humanos</p>
</body>
</html>
