# 03 · Qué existe hoy en Portal4N

Estado al 17-09-2026, rama `feature/courier-catalogo`. Todo el módulo vive
bajo el prefijo `courier`/`Courier`; no toca tablas de otros módulos.

## Dónde está cada cosa

| Capa | Ubicación |
|---|---|
| Rutas | `routes/web.php`, grupo `/courier`, nombres `courier.*`, middleware `auth`. |
| Controladores | `app/Http/Controllers/CourierPagoController.php` (proceso del mes) y `CourierCatalogoController.php` (catálogos). |
| Servicios | `app/Services/Courier/CourierPagoService.php` (períodos, cargas, diagnóstico) y `CourierCatalogoService.php` (consultas de catálogo, valor por peso). |
| Importadores | `app/Imports/Courier/*Import.php` (uno por hoja de catálogo, más `GeoliceBultosImport` y `PesajesImport`). |
| Comandos | `app/Console/Commands/CourierImportarCatalogos.php`, `CourierImportarGeolice.php`, `CourierImportarPesajes.php`, `CourierRevisarLocalidades.php`. |
| Modelos | `app/Models/Courier*.php`. |
| Migraciones | `database/migrations/*courier*`. |
| Vistas | `resources/views/courier/*.blade.php` y `courier/partials/`. |
| Estilos | `resources/css/courier.css`, prefijo de clases `co-`, mismo lenguaje visual que Suscripciones (`sl-`). Se compila con `npm run build`. |
| Documentación | `storage/agents/courier/`. |

## Fase 1 · Catálogos (hecha)

Las reglas del negocio viven en nueve tablas `courier_*` que se cargan desde
la planilla de Operaciones con:

```
php artisan courier:importar-catalogos {xlsx} [--solo-mostrar]
```

- Lee seis hojas (`Operador`, `Pesos`, `PagosCentroCostos`, `DatosProveedores`,
  `Estados`, `PesoTransformado`) **por posición de columna**, con fórmulas
  calculadas.
- Es **idempotente**: se puede correr cada mes; crea, actualiza o deja igual
  cada fila y lo informa.
- `--solo-mostrar` ejecuta todo dentro de una transacción y la deshace al
  final: muestra qué pasaría sin guardar nada. Todos los comandos del módulo
  tienen esta opción.
- Los catálogos **no llevan período**: describen cómo se asigna hoy. El
  período vive en los datos mensuales.

Pantallas de catálogo (`/courier/agentes`, `/courier/agentes/{id}`,
`/courier/comunas`, `/courier/proveedores`; `/courier/tarifas` y
`/courier/configuraciones` existen como ruta pero sin pestaña):

- **Agentes**: lista con conteos, y una **ficha** por agente con su
  configuración de pago, su titular de pago, sus comunas y sus tarifas
  (tramos de peso comprimidos y una calculadora tabla + kilos → valor).
- **Comunas**: buscador por comuna o agente, con zona y retorno.
- **Proveedores**: quién cobra, con qué documento, datos bancarios.

## Fase 2 · Datos del mes (hecha)

### Períodos

`courier_periodos`: un registro por mes de pago (`AAAAMM`), estado `abierto`
o `cerrado`. Se crea solo al hacer la primera carga del mes. Un período
cerrado no acepta cargas.

### Bultos: la descarga de Geolice

```
php artisan courier:importar-geolice {xlsx} --periodo=AAAAMM [--solo-mostrar]
```

- Guarda **las 31 columnas tal como vienen**, solo limpiando formato (peso
  `"0.31 kg"` → número, fechas texto → fecha, apóstrofes de Excel), más
  columnas vacías que llenará el cálculo (agente, tabla, kilos, valor,
  estado de pago).
- Lee el archivo por lotes; la descarga es grande.
- **Idempotencia y traslape**: el código del bulto es único en todo el
  sistema. Bulto nuevo → se inserta en el período indicado. Bulto ya cargado
  **en el mismo período** → se actualiza con la descarga nueva (el estado de
  entrega pudo cambiar). Bulto que ya existe **de un período anterior** → no
  se toca y se cuenta; qué hacer con él es decisión del cálculo (es el
  control "pagado el mes anterior").
- Al terminar, informa un **diagnóstico** contra los catálogos: estados no
  conocidos, comunas fuera del catálogo, combinaciones agente + comerciante +
  servicio sin configuración, bultos sin comuna, con y sin peso declarado.
  El diagnóstico solo informa; no escribe nada del cálculo.

### Pesajes: los CSV de bodega

```
php artisan courier:importar-pesajes {archivos*} --periodo=AAAAMM [--fecha=AAAA-MM-DD] [--solo-mostrar]
```

- Formato `Codigo;Notas;Cod_seguimiento`: código con sufijo (el mismo de
  Geolice), kilos enteros, código sin sufijo.
- La **fecha del pesaje sale del nombre del archivo**
  (`Proceso del dia 04-09-2026.csv`).
- Se guarda **un pesaje por bulto y por día**. Un bulto pesado en varios días
  conserva todos sus pesajes; cuál manda lo decide el cálculo.
- Descarta filas con código ilegible (errores de Excel como `#¡VALOR!`),
  cuenta los pesajes en 0 kg y cruza cada código con los bultos ya cargados.

### Historial de cargas

`courier_importaciones`: un registro por archivo cargado (período, tipo
`geolice` o `pesajes`, nombre del archivo, usuario, conteos, duración y el
diagnóstico completo en JSON). Es lo que la planilla nunca tuvo.

### Pantalla raíz `/courier` (existe, se rediseñará)

Hoy muestra, todo en una misma página: selector de período, los cuatro pasos
del proceso, un carrusel con el resultado de la última carga, los formularios
de carga de Geolice y de pesajes, las alertas vivas del período y el
historial de cargas. Funciona, pero Elías la evaluó como sobrecargada: **se
va a rediseñar para mostrar una sola cosa a la vez** (el paso actual y su
acción). No construir sobre ella sin acordar antes el nuevo diseño.

Las **alertas vivas** (`CourierPagoService::alertas`) se recalculan desde
`courier_bultos` en cada visita: comunas no reconocidas, combinaciones sin
configuración, estados fuera del catálogo, bultos con y sin pesaje. Cuando el
catálogo se corrige, la alerta desaparece sola.

Las cargas desde pantalla usan las **mismas clases de importación** que los
comandos, dentro de una transacción; si algo falla no queda nada a medias.

## Fase 3 · Cálculo (no construida)

Lo que falta: un servicio que recorra los bultos del período y llene sus
columnas de cálculo siguiendo `02-cadena-de-pago.md` (agente, zona,
configuración, tabla, kilos y su origen, valor, estado de pago y motivo).
Las columnas ya existen en `courier_bultos`; el diseño está pensado para que
el cálculo se pueda repetir sin volver a importar.

## Fase 4 · Resumen, banco y pre-facturas (no construida)

Suma por agente y por zona, IVA según tipo de documento, nómina para banco.
El patrón a seguir es el del módulo Suscripciones (pre-factura → PDF → ZIP →
correo), documentado en `storage/agents/docs/suscripciones/`.

## Convenciones técnicas que hay que respetar

- **Llaves de búsqueda** (`localidad_clave`, `llave`): minúsculas, guardadas
  con colación `utf8mb4_bin`. Son sensibles a tildes, eñes y espacios
  internos, como el `BUSCARV` de la planilla. Solo se recortan espacios y
  tabulaciones de los extremos. La regla vive en un solo lugar por catálogo:
  `CourierCoberturaComuna::clave()` y `CourierConfiguracion::llave()`.
- **Los bultos guardan el texto crudo** de Geolice; cualquier normalización
  se hace al buscar, nunca al guardar.
- **Nada se corrige en la descarga**: las variantes de escritura se agregan
  al catálogo.
- **Agrupar por texto en SQL** requiere `COLLATE utf8mb4_bin`; la colación
  por defecto juntaría `CONCÓN` con `Concón` y escondería justo lo que se busca.
- **Importaciones idempotentes y transaccionales**, con `--solo-mostrar`.
- Después de tocar CSS: `npm run build`. Después de tocar rutas o vistas:
  `php artisan optimize:clear`. Estos comandos los corre Elías.
- En las piezas nuevas de la pantalla raíz no se usan íconos (petición de
  Elías); las pantallas de catálogo sí usan Font Awesome.
