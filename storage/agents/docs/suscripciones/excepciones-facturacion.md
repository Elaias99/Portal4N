---
titulo: Excepciones de facturación por fecha del módulo Suscripciones
modulo: Suscripciones
tipo_documento: Referencia funcional y técnica para análisis asistido por Codex
estado: Vigente
ultima_actualizacion: 2026-10-09
ubicacion_recomendada: storage/agents/docs/suscripciones/excepciones-facturacion.md
---

# Excepciones de facturación por fecha

## 1. Propósito de este documento

Este documento describe la funcionalidad de **excepciones de facturación por
fecha**: el mecanismo que permite que una o varias ejecuciones puntuales de una
ruta sean cobradas por un proveedor distinto al habitual, sin alterar la
asignación maestra ni el resto del mes.

La funcionalidad se incorporó en agosto de 2026 y no estaba cubierta por el
resto de la documentación, escrita en julio de 2026.

---

## 2. Qué problema resuelve

Una ruta pertenece a un proveedor. Durante el mes puede ocurrir que, en días
puntuales, esa ruta la ejecute y la cobre otro proveedor.

Ejemplo confirmado de agosto de 2026:

```text
BH.01, BH.02 y BH.03 son rutas de Benito Alex Herrera Pavez.
Durante todo el mes el repartidor tuvo problemas mecánicos.
Claudia Reyes (Sanrey SpA) asumió los repartos.
```

Antes de esta funcionalidad, el único mecanismo disponible era el ajuste mensual
de tipo `FACTURACION`, que traslada **todo el mes**. No existía forma de
trasladar sólo algunos días.

---

## 3. Diferencia con el ajuste mensual `FACTURACION`

Son dos mecanismos distintos y conviven.

| | Ajuste `FACTURACION` | Excepción por fecha |
|---|---|---|
| Alcance | mes completo | una fecha concreta |
| Tabla | `suscripcion_ajustes_mensuales` | `suscripcion_excepciones_facturacion` |
| Efecto | cambia el proveedor efectivo al leer | crea una línea técnica y descuenta del origen |
| Línea en la pre-factura | la misma, atribuida a otro proveedor | una línea nueva para el receptor |
| Tipos permitidos | cualquier asignación | sólo `RUTA` |

Un ajuste mensual **no modifica el detalle**: el proveedor efectivo se resuelve
en tiempo de lectura. Una excepción por fecha **sí modifica el detalle**: resta
ejecuciones a la ruta original y crea una línea receptora.

No deben mezclarse. Si una asignación necesita las dos cosas en el mismo
período, debe analizarse caso a caso antes de registrarlas.

---

## 4. Modelo de datos

Tabla: `suscripcion_excepciones_facturacion`

```text
id
suscripcion_asignacion_id            → asignación original (RUTA)
fecha                                → fecha exacta de la ejecución
suscripcion_proveedor_facturacion_id → proveedor que cobra
suscripcion_transportista_override_id→ transportista efectivo (NULL = original)
costo                                → NULL = costo habitual de la asignación
tipo_documento
detalle_documento
detalle_impuesto
final
observacion
activo
created_at
updated_at
```

Restricciones confirmadas:

```text
UNIQUE(suscripcion_asignacion_id, fecha)
INDEX(fecha)
INDEX(fecha, suscripcion_proveedor_facturacion_id)
FK suscripcion_asignacion_id            → restrictOnDelete
FK suscripcion_proveedor_facturacion_id → restrictOnDelete
FK suscripcion_transportista_override_id→ restrictOnDelete
```

**La clave lógica es asignación + fecha.** Una asignación puede tener tantas
excepciones como fechas tenga el período. El índice único impide registrar dos
veces la misma fecha para la misma asignación.

El campo `activo` permite desactivar una excepción sin borrarla. El servicio de
aplicación carga también las inactivas, porque necesita restaurar la cantidad
del detalle original cuando una excepción se desactiva.

---

## 5. Tipo de asignación técnica `EXCEPCION_FACTURACION`

El proveedor receptor no puede cobrar sobre la asignación del proveedor
original. El sistema crea una **asignación técnica** que le pertenece.

Código interno, generado por el servicio de aplicación:

```text
EXF-{asignacionOrigenId}-{proveedorId}-{transportistaId}-{costo}
```

Ejemplo:

```text
EXF-4-53-35-31000
```

Características de la asignación técnica:

```text
tipo_asignacion         = EXCEPCION_FACTURACION
generar_automaticamente = 0
suscripcion_zona_id     = NULL
suscripcion_proveedor_id = el proveedor receptor
grupo_prefactura        = el mismo de la asignación original
```

El **código visible del detalle** no es el código técnico. El detalle conserva
el código real de la ruta (`BH.01`), para que la pre-factura del receptor
muestre qué ruta ejecutó.

Las asignaciones técnicas no se eliminan cuando desaparece la excepción: se
reutilizan si vuelve a aparecer. Lo que se elimina es el detalle mensual.

---

## 6. Registro

Servicio: `SuscripcionExcepcionFacturacionRegistroService`

Entrada: arreglo `excepciones_facturacion` del formulario mensual.

Validaciones confirmadas:

- `suscripcion_asignacion_id` obligatorio y existente;
- `fecha` obligatoria, formato `Y-m-d`, dentro del período;
- `suscripcion_proveedor_facturacion_id` obligatorio y existente;
- la asignación debe ser de tipo `RUTA`; cualquier otro tipo se rechaza.

El guardado es por asignación y fecha. Volver a enviar la misma combinación
actualiza el registro existente en lugar de duplicarlo.

---

## 7. Aplicación

Servicio: `SuscripcionExcepcionFacturacionAplicacionService::aplicarPeriodo()`

Orden interno del proceso:

1. carga todas las excepciones del período, activas e inactivas;
2. recalcula la cantidad de cada detalle de origen afectado;
3. agrupa las excepciones activas que comparten línea receptora;
4. crea o reutiliza la asignación técnica de cada grupo;
5. crea o actualiza el detalle mensual del receptor;
6. elimina los detalles técnicos que ya no correspondan.

### 7.1. Recálculo del origen

La cantidad del detalle original **no se calcula restando sobre el valor
actual**. Se reconstruye siempre desde cero:

```text
cantidad = max(0, q_calendario - q_inasistencia) - excepciones_activas
```

Esto es lo que hace el proceso idempotente. Ejecutarlo varias veces sobre el
mismo período no produce `8 → 7 → 6 → 5`.

### 7.2. Tope de ejecuciones

Si una asignación tiene más excepciones activas que ejecuciones pagables, la
generación se detiene con un error de validación:

```text
La asignación {codigo} tiene {N} excepción(es) de facturación,
pero sólo dispone de {M} ejecución(es) pagables en el período.
```

### 7.3. Agrupación de la línea receptora

Las excepciones se agrupan por:

```text
asignación origen
+ proveedor receptor
+ transportista efectivo
+ costo efectivo
```

Cada grupo produce **una sola línea** en la pre-factura del receptor:

```text
q_calendario   = cantidad de ejecuciones trasladadas
q_inasistencia = 0
cantidad       = cantidad de ejecuciones trasladadas
total          = costo efectivo × cantidad
```

Si las cuatro claves coinciden, cuatro fechas distintas producen **una línea de
cantidad 4**, no cuatro líneas de cantidad 1.

### 7.4. Costo efectivo

```text
costo de la excepción
→ si es NULL, costo del detalle de origen ya recalculado
→ si no existe, costo de la asignación original
```

### 7.5. Limpieza

Los detalles de período cuyo `tipo_asignacion` sea `EXCEPCION_FACTURACION` y
que no correspondan a ninguna excepción activa se eliminan. Esto permite
desactivar una excepción y que el origen recupere su cantidad y la línea
receptora desaparezca.

---

## 8. Selección de varias fechas

Desde octubre de 2026 el modal de cambios de facturación masivos permite
seleccionar **una o varias fechas** por asignación, además de la opción de mes
completo.

Archivos involucrados:

```text
resources/views/suscripciones/comisiones_mensuales/partials/
    modal-ajustes-masivos-facturacion.blade.php

resources/js/suscripciones/generacion-mensual/ajustes-masivos/
    facturacion.js
```

### 8.1. Comportamiento de la interfaz

Cada asignación seleccionada muestra:

- una casilla **Todo el mes**, marcada por defecto;
- una grilla de casillas con los sábados y domingos del período.

Reglas de exclusión:

```text
marcar "Todo el mes"       → limpia todas las fechas
marcar una fecha           → desmarca "Todo el mes"
desmarcar la última fecha  → vuelve a marcar "Todo el mes"
```

Las asignaciones que no son de tipo `RUTA` sólo muestran **Todo el mes**, en
estado deshabilitado.

### 8.2. Estado interno

El estado de cada asignación seleccionada guarda:

```text
item.fechas = []                          → cambio mensual
item.fechas = ['2026-09-05', ...]         → excepciones por fecha
```

Antes de octubre de 2026 el campo era `item.fecha`, un único texto. Cualquier
análisis de versiones anteriores debe considerar ese nombre.

### 8.3. Expansión

`construirExcepcionesFacturacion()` emite **una entrada por cada fecha
marcada**, todas con los mismos datos de proveedor, transportista, costo y
campos documentales.

```text
1 asignación × 4 fechas = 4 entradas en excepciones_facturacion[]
```

El backend no distingue si esas cuatro entradas vinieron de un formulario con
selección múltiple o de cuatro cargas separadas. La agrupación de la sección
7.3 las vuelve a unir en una línea.

### 8.4. Qué no cambió

La selección múltiple es exclusivamente de frontend. No se modificaron:

```text
SuscripcionComisionMensualController      (validación)
SuscripcionExcepcionFacturacionRegistroService
SuscripcionExcepcionFacturacionAplicacionService
suscripcion_excepciones_facturacion       (migración)
```

La validación ya trataba `excepciones_facturacion` como arreglo, y el registro
ya iteraba una por una.

---

## 9. Presentación

`SuscripcionLiquidacionDetalleController::show()` reconstruye la relación entre
cada línea técnica y las excepciones que la originaron.

El procedimiento es:

1. leer el código técnico del detalle receptor;
2. extraer el ID de la asignación origen con `preg_match('/^EXF-(\d+)-/')`;
3. buscar las excepciones de esa asignación en el período;
4. filtrar las que coincidan en proveedor receptor, transportista efectivo y
   costo del detalle.

La vista muestra entonces, en la pre-factura del receptor, la lista de fechas
trasladadas y el proveedor de origen; y en la pre-factura del origen, cuántas
ejecuciones se reasignaron y hacia quién.

**Riesgo conocido:** la reconstrucción depende de que el código técnico conserve
el formato `EXF-{origen}-...` y de que los tres criterios de filtro sigan
coincidiendo. Si el costo del detalle receptor cambiara después de generado, el
cruce dejaría de encontrar las excepciones y las fechas no se listarían, aunque
los montos seguirían siendo correctos.

---

## 10. Invariantes

- La identidad de una excepción es **asignación + fecha**.
- Sólo las asignaciones de tipo `RUTA` admiten excepciones por fecha.
- El recálculo del origen siempre parte de `q_calendario - q_inasistencia`,
  nunca de la cantidad actual.
- Una excepción no puede trasladar más ejecuciones que las pagables.
- El código visible del detalle receptor es el de la ruta original, no el
  código técnico.
- Desactivar una excepción debe restaurar la cantidad del origen y eliminar el
  detalle receptor.
- `EXCEPCION_FACTURACION` nunca debe generarse como ruta calendarizada.

---

## 11. Escenarios de verificación

Escenarios que conviene reproducir ante cualquier cambio en esta funcionalidad:

- una sola fecha sobre una ruta;
- varias fechas sobre la misma ruta, mismo receptor;
- varias fechas sobre la misma ruta, receptores distintos;
- todas las fechas del período trasladadas;
- más fechas que ejecuciones pagables, que debe fallar con el mensaje de la
  sección 7.2;
- una excepción desactivada después de aplicada;
- reejecución del período, que no debe volver a descontar;
- cambio de facturación mensual y excepción por fecha sobre la misma
  asignación;
- excepción sobre una asignación sin detalle de origen generado;
- verificación visual en `show` de que las fechas se listan en ambas
  pre-facturas.

---

## 12. Mantenimiento

Actualizar este documento cuando:

- cambie el formato del código técnico;
- cambien los criterios de agrupación de la línea receptora;
- cambie la fórmula de recálculo del origen;
- se permitan excepciones sobre tipos distintos de `RUTA`;
- cambie la política de eliminación de detalles técnicos;
- cambie la forma de seleccionar fechas en la interfaz.
