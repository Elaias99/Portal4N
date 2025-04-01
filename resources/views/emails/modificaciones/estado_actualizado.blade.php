<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de la Solicitud</title>
</head>
<body>
    <h2>
        @if ($estado === 'aprobada')
            ✅ Solicitud de Modificación Aprobada
        @else
            ❌ Solicitud de Modificación Rechazada
        @endif
    </h2>

    <p><strong>Empleado:</strong> {{ $solicitud->trabajador->Nombre }} {{ $solicitud->trabajador->ApellidoPaterno }}</p>
    <p><strong>Campo:</strong> {{ $solicitud->campo }}</p>
    <p><strong>Descripción:</strong> {{ $solicitud->descripcion }}</p>

    <p><strong>Estado:</strong> {{ ucfirst($estado) }}</p>

    <p><strong>Comentario del administrador:</strong></p>
    <blockquote>{{ $solicitud->comentario_admin ?? 'Sin comentarios' }}</blockquote>

    <hr>
    <p>Este correo fue generado automáticamente por el sistema de Recursos Humanos.</p>
</body>
</html>
