<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Solicitud de Modificación</title>
</head>
<body>
    <h2>📄 Solicitud de Modificación</h2>

    <p><strong>Empleado:</strong> {{ $solicitud->trabajador->Nombre }} {{ $solicitud->trabajador->ApellidoPaterno }}</p>
    <p><strong>Campo solicitado a modificar:</strong> {{ $solicitud->campo }}</p>
    <p><strong>Descripción:</strong> {{ $solicitud->descripcion }}</p>

    @if ($solicitud->archivo)
        <p><strong>Archivo adjunto:</strong> Sí (subido en la plataforma)</p>
    @endif

    <hr>
    <p>Por favor, revise la solicitud en el sistema para aprobar o rechazar.</p>
</body>
</html>
