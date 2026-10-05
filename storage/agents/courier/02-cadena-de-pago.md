# 02 · La cadena de pago, bulto por bulto

Esta es la regla que el módulo tiene que aplicar a **cada bulto** de la
descarga de Geolice. Es la traducción de las fórmulas de la planilla,
verificada con bultos reales. El orden importa: cada paso usa el resultado
del anterior.

Ejemplo que acompaña los pasos: bulto `4N202608046469-683`, del comerciante
`Chilepost`, con destino `Antofagasta`.

## Paso 1 · Comuna → agente y zona

La **comuna de destino** que trae Geolice se busca en el catálogo de comunas
(hoja `Operador`). El resultado es el **agente** que la reparte y la **zona**
(`RM` o `Regiones`).

- La comparación es **insensible a mayúsculas** pero **sensible a tildes,
  eñes y espacios**. Esto es a propósito: en el catálogo `Maipu` y `Maipú`
  son filas distintas que van a agentes distintos. Normalizar tildes cambiaría
  a quién se le paga.
- Si la comuna no está en el catálogo, **no hay agente y el bulto no se puede
  pagar**. La solución no es corregir la descarga (la próxima traería lo
  mismo) sino agregar la variante al catálogo, después de que una persona
  confirme a qué comuna corresponde.
- Si Geolice no informa comuna, tampoco hay agente.

> Ejemplo: `Antofagasta` → agente `Claudio Castro (Antofagasta)`, zona `Regiones`.

## Paso 2 · RUT proveedor + RUT cliente + servicio → ¿se paga? ¿con qué tabla?

Es la llave de pago de Operaciones. Se carga desde `Llaves_Courier_AAAAMM.xlsx`
con `php artisan courier:importar-llaves <archivo>` (tablas `courier_llave_*`
y `courier_llaves`); cada carga reemplaza la anterior. La hoja `Tarifas` del
mismo archivo actualiza los centros de costo (`courier_tarifas`).

1. El **RUT del proveedor** sale del agente (paso 7).
2. El **RUT del cliente** sale del comerciante (hoja `Clientes`).
3. El **código del servicio** sale del servicio (hoja `Servicios`).
4. Con los tres se busca en la hoja `Llaves`. Si hay varias, manda la del
   agente del bulto. El resultado tiene dos partes:
   - **Pagar**: `SI`, `NO` o `REVISAR`.
   - **Tabla**: el número de tarifa (centro de costo) que aplica.

| Situación | Motivo |
|---|---|
| Llave `SI`, tabla N | Se paga con la tarifa N. |
| Llave `SI`, tabla `0` | Existe la regla y dice que no se paga (`tabla_cero`). |
| Llave `NO` | `configuracion_no`. |
| Llave `REVISAR` | `configuracion_revisar`. |
| Varias llaves que no dicen lo mismo | `llave_ambigua`. |
| **No hay llave** (o el comerciante o el servicio no están en las hojas) | `sin_configuracion`. El sistema no asume nada. |

Comerciante y servicio se comparan en minúsculas y sin espacios de más
(Geolice a veces agrega tabulaciones).

La llave se carga para el mes que se va a calcular: agosto 2026 se calcula
con `Llaves_Courier_202608.xlsx`, que refleja cómo Operaciones aplicó la
llave ese mes.

> Ejemplo: `Claudio Castro (Antofagasta)` → RUT del proveedor; `Chilepost` → RUT del cliente; `Servicio Standar` → código; la llave dice pagar `SI`, tabla `2`.

## Paso 3 · ¿Con cuántos kilos?

El peso que se usa para pagar se decide con esta prioridad:

1. **Peso de bodega** (pesaje de balanza), si existe y es mayor que 0.
2. Si no, el **peso declarado por el cliente** que trae Geolice, transformado a
   entero: se **trunca** (no se redondea) y el mínimo es 1.
3. Si no hay ninguno, el bulto se marca `X` y se paga como **1 kg**.

Un pesaje con **0 kg** significa que pasó por la balanza sin peso: no cuenta
como peso de bodega.

> Ejemplo: pesaje de bodega 3 kg → se paga con 3 kg.

## Paso 4 · Tabla + kilos → valor

Cada tabla tiene un valor por kilo desde 1 hasta 20 (hoja `Pesos`). A partir
de 21 kg:

```
valor = valor(20 kg) + (kilos − 20) × kilo adicional de la tabla
```

Las tablas de Lanas son **planas**: el mismo valor a cualquier peso. Por eso
en la planilla esos bultos aparecen con peso 1.

> Ejemplo: tabla 2 con 3 kg → $1.000.

## Paso 5 · Controles: ¿se descuenta por algo?

El bulto queda fuera del pago si **cualquiera** de estos controles dice
`DESCONTAR`:

| Control | Fuente | Regla |
|---|---|---|
| Estado de entrega | catálogo `Estados` | Cada estado tiene `PAGAR` o `DESCONTAR`. Ojo: `Fallido` se paga; es decisión de Operaciones. |
| Especiales | hoja del mes `especiales` | Bultos con pago especial autorizado; los marcados se descuentan del flujo normal. |
| Retornos | hoja del mes `Retornos` | Un retorno no se paga por tabla; tiene su propio valor fijo por comuna. |
| Blue | hoja del mes `Blue` | Enviados por Blue Express; no se pagan al agente. |
| Pagado el mes anterior | `BaseCL` del mes previo | Si el bulto ya estuvo en la nómina anterior, no se paga otra vez. Es el control del traslape de descargas. |
| Considerar pago | paso 2 | Solo se pagan los `SI`. |

Un bulto que no aparece en una hoja de control se considera `PAGAR` para ese
control.

> Ejemplo: estado `Entregado` → `PAGAR`; no está en especiales, retornos ni Blue; no se pagó el mes anterior → **se paga**.

## Paso 6 · Variables o Lanas

Los bultos que se pagan se separan **por comerciante**: Revesderecho,
Comercial Reginella y Orquídea/Hilandería Maisa son **Lanas**; el resto,
**Variables**. Es solo una clasificación para el resumen; el valor ya salió
del paso 4.

## Peumo · por guía de despacho

Los bultos de `Comercial Peumo Ltda` con servicio `Servicio Standar (V.
Trabajadores)` son el tipo de pago **Peumo**. No pasan por la llave ni por
el peso:

1. Se agrupan por **guía de despacho**, en el orden en que llegaron en el
   archivo de Geo. Los ya pagados el mes anterior y los de pago especial no
   cuentan.
2. Cada bulto busca su comuna en la hoja `Peumo` de la llave (tabla
   `courier_tarifas_peumo`): valor del **primer bulto** y del **resto**. La
   comparación es exacta salvo mayúsculas.
3. El primer bulto de la guía paga el valor del primero y los demás el del
   resto, aunque el primero después no se pague (por estado, por ejemplo).
4. Si la guía no viene (`peumo_sin_guia`), o si algún bulto de la guía no
   tiene una tarifa única para su comuna (`peumo_sin_tarifa`), la guía
   entera queda sin pagar.

A quién se le paga y si se paga (estado, Blue, personal de 4N) sigue la
misma cadena que el resto, y hace falta el RUT del cliente (hoja `Clientes`).

## Paso 7 · A quién se le paga

Se calcula antes que el paso 2, porque la llave parte de este RUT.

1. El **agente** del paso 1 da el **RUT del proveedor** (hoja `Agentes`).
   Si el agente no está en la hoja, el motivo es `sin_rut_proveedor`.
2. Si el bulto lo entregó personal de 4N que trabaja para otro proveedor, la
   hoja `Repartidores 4N` (RUT + agente + repartidor) da el RUT de ese
   proveedor. `N/A` = se queda en 4N.
3. Lo que queda a nombre de **4 Nortes Logística** (`77346078-7`) lo entregó
   personal propio y no se paga (`proveedor_interno`). Los agentes con RUT
   `0-0` (Envío externo, Latam) no son proveedores Courier: lo que entregan
   no se paga (`sin_proveedor_courier`), tampoco retornos ni Peumo.
4. Con el RUT se busca el proveedor en `courier_proveedores` (razón social,
   tipo de documento, datos bancarios). El bulto guarda el RUT en
   `rut_proveedor` aunque no esté en el catálogo.
5. La hoja `Proveedores` del Excel de llaves agrega a `courier_proveedores`
   los RUT que cobran y todavía no están (llave `rut:…`, sin datos
   bancarios). Los que ya están no se modifican.

## Paso 8 · Resumen y banco

- **Resumen**: suma del valor por zona y tipo de pago (Variables / Lanas).
- **Banco**: suma por razón social. Se agrega **19 % de IVA solo cuando el
  tipo de documento es exactamente `Factura`**. Boleta y sin documento van
  sin IVA. El resultado se redondea a pesos enteros.

## Lo que esta cadena no cubre

- Los **pagos manuales** (acuerdos, servicios, visitas regionales, ruta CV,
  apoyo alza, fijo base): se suman aparte.
- El **valor de los retornos** (fijo por comuna, hoja `Operador`).
- El servicio **Mayorista de Revés Derecho**: el jefe dijo que se paga
  distinto y lo revisa a mano. Regla pendiente.
