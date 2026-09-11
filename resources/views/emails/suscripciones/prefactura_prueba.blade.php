<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prefactura Distribución Suscripciones</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.5;">

    <p>
        Estimado proveedor
        <strong>{{ $nombreProveedor }}</strong>,
    </p>

    <p>
        Adjunto encontrará prefactura correspondiente al servicio de
        distribución de suscripciones de
        {{ mb_strtolower($mesNombre) }} {{ $anio }}.
    </p>

    <p>
        Es indispensable que emita el documento correspondiente hasta el día
        martes 15/9, para que el pago sea realizado el día jueves 17/9.
        Si su documento es emitido fuera de plazo, el pago será realizado
        el día viernes 25/9.
    </p>

</body>
</html>