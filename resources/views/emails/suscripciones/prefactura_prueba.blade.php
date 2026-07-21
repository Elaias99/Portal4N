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
        Agradeceremos generar el documento correspondiente hasta el día
        miércoles 22 de julio, para que el pago pueda ser realizado este
        viernes 24 de julio.
    </p>

</body>
</html>