# 05 · Decisiones tomadas, preguntas abiertas y próximos pasos

## Decisiones tomadas (y por qué)

| Decisión | Por qué |
|---|---|
| Los catálogos **no llevan período**. | Describen cómo se asigna hoy. Versionarlos por mes duplicaba todo sin que nadie lo usara. El período vive en bultos, pesajes e importaciones. |
| Las llaves comparan como el `BUSCARV` de Excel: minúsculas, sensibles a tildes y espacios internos, guardadas en `utf8mb4_bin`. | Con la colación por defecto, `Maipu` y `Maipú` se juntaban y se perdían filas del catálogo que van a agentes distintos. |
| Solo se recortan espacios y tabulaciones **de los extremos** al armar la llave de configuración. | Geolice llegó a mandar un comerciante con dos tabulaciones al final; un tab al final nunca distingue a dos clientes reales. |
| Las variantes de escritura de una comuna se **agregan al catálogo**; no se corrige la descarga. | La próxima descarga traería el mismo texto. Es lo que hacía el jefe. |
| El bulto guarda las 31 columnas de Geolice **tal cual** y el cálculo escribe en columnas aparte. | Nunca hay que volver a importar por un dato que faltó, y se puede recalcular sin perder el origen. |
| El código del bulto es único en todo el sistema; un bulto pertenece al primer período en que apareció. | Resuelve el traslape de descargas y reemplaza la hoja `PagadosMesAnterior`. |
| Los pesajes se guardan **todos**, uno por bulto y día. | Bodega pesa el mismo bulto en días distintos, a veces con kilos distintos; cuál manda es una regla de negocio que no está definida. |
| Courier **no se enlaza todavía** con `cobranza_compras` (maestro de proveedores que usa Suscripciones). `courier_proveedores.cobranza_compra_id` queda vacío. | Elías no quiere mezclar un módulo en construcción con tablas que ya se usan en producción. El enlace es un `UPDATE` por RUT normalizado cuando se decida. Hay proveedores Courier que no existen en el maestro. |
| Las comunas no reconocidas **no se emparejan automáticamente**. | Un emparejamiento "porque se parece" puede pagarle el bulto a otro proveedor. La idea acordada (no construida): el sistema sugiere y una persona confirma con un clic; al confirmar, la variante se guarda en el catálogo con registro de quién la aprobó. |
| Las cargas corren de forma síncrona, sin cola, en una transacción. | Mismo patrón que Suscripciones; suficiente para el tamaño actual. |
| Los conteos del diagnóstico son informativos; **el sistema no asume reglas** para lo que no calza. | Regla de trabajo de Elías: no inventar criterios. Lo que no tiene regla se lleva a Operaciones. |

## Preguntas abiertas para Operaciones

Ninguna bloquea construir; todas bloquean pagar un mes real.

1. **Pesajes del mes completo.** Hoy hay solo algunos días.
2. **`Retiro en ruta`** (Rendic, Verisure, Postalchile, Construmart, Maicao,
   Chilepost): ¿se paga? ¿con qué tabla? Además esos bultos vienen sin
   comuna y con códigos que parten con `SH`.
3. **`Cajas Los Andes` + `Servicio Standar (Cotizacion)`**: no existe en
   `PagosCentroCostos`.
4. **Mayorista de Revés Derecho**: el jefe dijo que se paga distinto y lo
   revisa a mano. ¿Cómo se calcula?
5. **Qué pesaje manda** cuando un bulto tiene varios con kilos distintos:
   ¿el más reciente, el mayor, el primero?
6. **Qué significa `REVISAR`** en la configuración de pago.
7. Un bulto que estuvo en la descarga del mes anterior pero **no se pagó**
   (por ejemplo, estaba pendiente) y ahora aparece entregado: ¿se paga este
   mes? La planilla lo tratará como "pagado el mes anterior" solo si estuvo
   en `BaseCL`; hay que confirmar la regla.
8. Comunas basura que manda Geolice (`#N/D`, `EDIFICIO CORPORATIVO`): ¿qué
   hacer con esos bultos?
9. Sus **bases nuevas con llave por RUT** (clientes, tipos de servicio
   normalizados, centros de costo): cuándo las entrega.

## Próximos pasos, en orden

1. **Rediseñar la pantalla raíz.** Antes de programar: acordar con Elías qué
   ve el usuario primero (una sola cosa a la vez: el paso en que está y su
   acción) y qué queda escondido hasta que lo pida. Bosquejo → OK → código.
2. **Cálculo** (`02-cadena-de-pago.md`): servicio que llene las columnas de
   cálculo de `courier_bultos`, repetible, con la regla de pesajes múltiples
   ya definida.
3. **Resumen por agente y nómina banco**, con IVA según tipo de documento.
4. **Pre-facturas y envío**, siguiendo el patrón de Suscripciones.
5. **Confirmación de comunas** desde pantalla (sugerir → confirmar → catálogo).
6. **Enlace con el maestro de proveedores** por RUT, cuando Elías lo decida.
7. Adaptar las llaves a las bases por RUT del jefe cuando existan.

## Lo que no se debe hacer

- No asumir qué se paga cuando falta la regla; marcarlo y preguntar.
- No "arreglar" textos de Geolice en la base.
- No normalizar tildes en las llaves.
- No tocar `cobranza_compras` ni nada de Suscripciones desde Courier.
- No construir pantallas nuevas sin acordar antes el diseño con Elías.
