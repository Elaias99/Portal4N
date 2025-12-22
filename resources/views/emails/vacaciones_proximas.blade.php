<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vacaciones Próximas</title>
</head>
<body>

    <p>Hola,</p>

    <p>
        Se informa que el trabajador
        <strong>
            {{ $vacacion->trabajador->Nombre }}
            {{ $vacacion->trabajador->ApellidoPaterno }}
        </strong>
        iniciará sus vacaciones en
        <strong>{{ $diasRestantes }}</strong> días.
    </p>

    <p>
        <strong>Fecha de inicio:</strong>
        {{ $vacacion->fecha_inicio->format('d/m/Y') }}<br>

        <strong>Fecha de término:</strong>
        {{ $vacacion->fecha_fin->format('d/m/Y') }}<br>

        <strong>Días solicitados:</strong>
        {{ $vacacion->dias }}
    </p>

    <p>
        Este es un aviso automático del sistema.
    </p>

</body>
</html>
