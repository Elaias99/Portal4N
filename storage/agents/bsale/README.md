# Guías de despacho Bsale

## Para qué sirve

Este módulo importa un Excel exportado desde Access, agrupa sus filas en
despachos y permite crear una guía por grupo mediante la API de Bsale. Guarda
los grupos y el resultado de cada envío en la tabla `bsales`. Cuando Bsale
confirma la creación, la pantalla muestra el número de guía y el enlace a su
PDF. El PDF lo genera Bsale con el formato configurado en su cuenta.

La integración actual usa la cuenta de producción de **4 NORTES LOGISTICA SPA**
(empresa Bsale `101346`), el tipo de documento `7` y `declareSii: 0`. Con esta
configuración, el módulo no solicita la declaración del documento ante el SII.

## Entrada y agrupación

La pantalla `/bsale/guias` acepta un `.xlsx` de una hoja, hasta 5 MB y 5.000
filas de datos. La primera fila debe contener estas columnas, en este orden:

| Columna | Función |
| --- | --- |
| `FechaGuiaTransporte` | Fecha del despacho y de emisión enviada a Bsale. |
| `Troncal` | Información de transporte. |
| `Posta` | Nombre del destino o tramo mostrado en pantalla. |
| `DestinoCarga` | Descripción general del transporte. |
| `DireccionOrigen` | Dirección de origen mostrada en pantalla. |
| `DireccionDestino` | Dirección de despacho enviada a Bsale. |
| `Patente` | Información de transporte. |
| `Chofer` | Información de transporte. |
| `RutChofer` | Información de transporte. |
| `Detalle` | Descripción de una línea de carga. |
| `Bultos` | Cantidad de esa línea. |
| `PesoTotal` | Peso en kilos de esa línea. |

La lectura rechaza fórmulas, convierte las fechas numéricas de Excel a
`YYYY-MM-DD` y omite las filas completamente vacías. Las filas se agrupan cuando
coinciden los nueve campos desde `FechaGuiaTransporte` hasta `RutChofer`.
Cada grupo conserva sus líneas originales y los totales calculados de bultos
y kilos.

Importar guarda los grupos; **no crea guías en Bsale**. El modelo genera un
`identificador` único para cada registro local. `huella_datos` es un hash del
encabezado y las líneas: para el mismo usuario, ambiente y empresa Bsale, una
importación posterior con idéntico contenido reutiliza el registro existente.
El Excel no aporta un identificador independiente de viaje; dos viajes
legítimos con contenido idéntico se tratarían como el mismo grupo.

## Pantalla y generación

El historial de `/bsale/guias` muestra 15 grupos por página y consulta los
registros del usuario autenticado en la cuenta de producción. Cada grupo tiene
su propio botón **Generar guía** cuando el estado permite enviarlo.

Antes del envío, el formulario pide comuna y ciudad de destino. Propone el
valor de `Posta` en ambos campos si todavía no hay valores guardados; es una
propuesta editable, ya que `Posta` también puede nombrar una ruta o un tramo.

Al generar, el controlador:

1. Recupera de `bsales` el grupo del usuario y valida fecha, cabecera, detalle,
   bultos y pesos.
2. Construye una línea por cada `Detalle`; la `quantity` de Bsale corresponde a
   `Bultos`. Los kilos se utilizan para calcular el total mostrado como texto.
3. Añade al comentario de la **última línea** `DestinoCarga`, troncal, posta,
   patente, chofer, RUT del chofer y totales. Esto deja juntas las descripciones
   de los clientes dentro de la tabla del PDF.
4. Guarda el JSON de salida y cambia el estado a `enviando` antes de llamar a
   Bsale. La petición HTTP se ejecuta fuera de la transacción de base de datos.
5. Envía `POST https://api.bsale.io/v1/shippings.json`. Guarda la respuesta,
   los identificadores, el número y los enlaces devueltos por Bsale.

El destinatario incluido en el JSON es 4 NORTES LOGISTICA SPA, RUT
`77346078-7`. `DireccionDestino` se envía como dirección de despacho. La fecha
se envía como marca de tiempo UTC correspondiente al día del Excel. Los datos
de origen no se envían como campos independientes de la API. Patente, chofer y
RUT del chofer se imprimen actualmente como texto del último ítem, no como
variables independientes del diseñador de PDF.

El servicio fija `documentTypeId: 7`, `declareSii: 0` y `sendEmail: 0`. El JSON
actual no contiene precio unitario ni `shippingTypeId`; Bsale procesa esos
valores según su configuración. Un PDF disponible confirma la creación en
Bsale, no una declaración aceptada por el SII.

## Estados y datos persistidos

| Estado | Interpretación |
| --- | --- |
| `pendiente` | Grupo importado y todavía no enviado desde este registro. |
| `enviando` | Envío iniciado sin resultado definitivo guardado. |
| `generada` | Bsale devolvió el identificador de la guía. |
| `error` | El servicio no pudo iniciar el envío, por ejemplo, por falta de token. |
| `incierta` | No hubo confirmación suficiente; requiere comprobar el resultado en Bsale. |

Solo `pendiente` y `error` admiten iniciar un envío desde el registro. El
controlador bloquea la fila en la base de datos antes de cambiarla a
`enviando`, para impedir dos envíos concurrentes del mismo registro. No hay
reintento automático de una respuesta incierta.

`bsales` guarda el usuario, archivo de origen, grupo importado, comuna y
ciudad revisadas, estado, JSON enviado, respuesta HTTP, identificadores de
despacho y documento, folio, enlaces al PDF y vista pública, mensajes y fechas
de seguimiento. El token de acceso no se guarda en la tabla.

Los registros existentes antes de incorporar la tabla no se reconocen
automáticamente al reimportar el mismo Excel. También se pueden crear
registros separados si diferentes usuarios importan el mismo grupo, porque
la búsqueda por huella se limita al usuario. Para consultar el historial
completo de documentos efectivamente creados debe contrastarse con Bsale.

## Ubicación del código

| Archivo | Responsabilidad |
| --- | --- |
| `routes/web.php` | Rutas bajo `/bsale/guias`, autenticación y bloqueo de sesión. |
| `app/Http/Controllers/BsaleGuiaImportacionController.php` | Lectura, validación, agrupación, persistencia y armado del JSON. |
| `app/Services/BsaleGuiaService.php` | Autenticación y petición HTTP a Bsale; interpreta la respuesta. |
| `app/Models/Bsale.php` | Modelo Eloquent, estados y conversiones de datos JSON. |
| `database/migrations/2026_09_24_131253_create_bsales_table.php` | Esquema de `bsales`. |
| `resources/views/bsale/guias/importar.blade.php` | Importación, historial, revisión de destino y acceso al PDF. |
| `config/services.php` | Lectura de `BSALE_DEMO_TOKEN` y `BSALE_PRODUCTION_TOKEN`. |
| `config/session.php` | Almacén de bloqueo `file` para las rutas del módulo. |

La ruta `POST /bsale/guias/limpiar` borra claves temporales de sesión; no
elimina registros de `bsales` ni documentos de Bsale. El historial y los
enlaces al PDF permanecen después de cerrar sesión.
