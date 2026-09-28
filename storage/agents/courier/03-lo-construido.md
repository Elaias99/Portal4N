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

### Pantallas del período

Un selector de período y cuatro vistas, cada una con su tema
(`partials/periodo-nav.blade.php`):

| Vista | Ruta | Qué muestra |
|---|---|---|
| Resumen del período | `/courier` | Estado, datos cargados, **lo que se puede pagar**, accesos a las demás y la carga de archivos. |
| Pago a proveedores | `/courier/pago` | Totales por zona y tipo de pago, detalle por proveedor con IVA, y por qué no se pagan los demás. |
| Distribución por agente | `/courier/distribucion` | Cuántos bultos le tocan a cada agente, por zona. |
| Pendientes | `/courier/pendientes` | Comunas no reconocidas, combinaciones sin configuración y estados con su regla, con las listas completas. |

La regla de diseño, que Elías pidió expresamente: **una cosa a la vez**. La
portada no repite el detalle de las otras; sólo lo cuenta y enlaza. No volver
a apilar secciones en ella.

La carga de Geolice tiene **revisión previa**: se sube el archivo, se muestra
qué trae y qué no calza, y recién al confirmar se guardan los bultos.

Las **alertas vivas** (`CourierPagoService::alertas`) se recalculan desde
`courier_bultos` en cada visita: comunas no reconocidas, combinaciones sin
configuración, estados fuera del catálogo, bultos con y sin pesaje. Cuando el
catálogo se corrige, la alerta desaparece sola.

Las cargas desde pantalla usan las **mismas clases de importación** que los
comandos, dentro de una transacción; si algo falla no queda nada a medias.

### Datos del mes desde la planilla de Operaciones

Cuando no se guardaron los CSV diarios de bodega, los pesos y los controles
se sacan del propio Excel del mes:

```
php artisan courier:importar-mes {xlsx} --periodo=AAAAMM [--hojas=Retornos,Blue] [--solo-mostrar]
```

- Carga `PesoReal` → `courier_pesajes` y `especiales`, `Retornos`, `Blue` y
  `PagadosMesAnterior` → `courier_controles`.
- **Lee una hoja a la vez**, con un filtro de columnas
  (`App\Imports\Courier\FiltroColumnas`): las cinco juntas agotan la memoria
  (`PesoReal` sola pasa las cien mil filas).
- Las celdas con fórmula se resuelven con el **último valor calculado** que
  guardó Excel (`getOldCalculatedValue`). En `Retornos` el código del bulto y
  su valor son fórmulas; sin esto la hoja entera se descarta.
- `--hojas` permite recargar sólo una, sin repetir `PesoReal`.

## Fase 3 · Cálculo (hecha)

```
php artisan courier:calcular --periodo=AAAAMM
```

`App\Services\Courier\CourierCalculoService` recorre los bultos del período y
llena sus columnas de cálculo siguiendo `02-cadena-de-pago.md`. Se puede
repetir cuantas veces se quiera: reescribe el cálculo y no toca lo que trajo
Geolice.

- Cada bulto que no se paga queda con un **motivo**; los textos están en
  `CourierCalculoService::MOTIVOS`. Distingue los motivos que son una
  decisión ya tomada (configuración `NO`, estado que descuenta, pagado el mes
  anterior) de los que **necesitan que Operaciones defina algo**
  (`sin_configuracion`, `comuna_desconocida`, `sin_comuna`, `sin_tabla`,
  `sin_tarifa`, `configuracion_revisar`).
- Los kilos salen de bodega, del peso declarado (truncado, mínimo 1) o de 1 kg
  por defecto, y queda registrado en `origen_peso` cuál se usó.
- Cuando un bulto tiene varios pesajes se toma el primero, como el `BUSCARV`
  de la planilla.
- `Lanas` vs `Variables` se decide por comerciante, con la lista verificada
  contra la réplica de agosto.
- Tarda unos minutos: actualiza bulto por bulto. Si molesta, se puede pasar a
  actualizaciones por lote.

## Fase 4 · Resumen y pago (parcial)

`CourierPagoService::resumenPago()` arma lo que se puede pagar: totales por
zona, por tipo de pago y **por proveedor con IVA** (19% sólo cuando el tipo de
documento es exactamente `Factura`, como la hoja Banco). Vive en la pantalla
`/courier/pago`, y la portada muestra el total.

Falta: la nómina para banco con sus datos de cuenta, y los **pagos manuales**
que Operaciones lleva en hojas aparte (acuerdos, servicios, ruta CV, visitas,
apoyo alza, fijo base, especiales), que son la mayor parte del total del mes.
Para las pre-facturas el patrón a seguir es el de Suscripciones, documentado
en `storage/agents/docs/suscripciones/`.

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
