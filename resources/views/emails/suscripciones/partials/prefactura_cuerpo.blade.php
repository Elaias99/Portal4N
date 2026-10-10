{{--
    Cuerpo del correo de pre-facturas.
    Se usa en el correo y en la vista previa de "Texto del correo",
    para que ambos se vean exactamente igual.
--}}
<p>
    Estimado proveedor
    <strong>{{ $nombreProveedor }}</strong>,
</p>

<p>
    Adjunto encontrará prefactura correspondiente al servicio de
    distribución de suscripciones de
    {{ mb_strtolower($mesNombre) }} {{ $anio }}.
</p>

@foreach($parrafosCuerpo as $parrafo)
    <p>{!! nl2br(e($parrafo)) !!}</p>
@endforeach
