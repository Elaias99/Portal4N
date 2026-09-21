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

## Paso 2 · Agente + comerciante + servicio → ¿se paga? ¿con qué tabla?

Se arma una **llave** pegando los tres textos (agente, comerciante, servicio)
y se busca en la configuración de pago (hoja `PagosCentroCostos`). El
resultado tiene dos partes:

- **Pagar**: `SI`, `NO` o `REVISAR`.
- **Tabla**: el número de tarifa que aplica.

Tres situaciones que hay que distinguir bien:

| Situación | Significado |
|---|---|
| Configuración existe, pagar `SI`, tabla N | Se paga con la tarifa N. |
| Configuración existe, tabla `0` | Existe la regla y dice **explícitamente que no se paga**. |
| **La llave no existe** | Nadie ha definido si se paga. No es tabla 0. Hay que preguntarle a Operaciones. El sistema no asume nada. |

La llave se compara en minúsculas y **sin espacios ni tabulaciones en los
extremos** (Geolice a veces los agrega); todo lo demás debe calzar exacto.

> Ejemplo: `Claudio Castro (Antofagasta)` + `Chilepost` + `Servicio Standar` → pagar `SI`, tabla `2`.

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

## Paso 7 · A quién se le paga

Con **agente + repartidor** (en la planilla: Operador + Usuario) se busca en
`DatosProveedores` la **razón social, RUT, tipo de documento y datos
bancarios**. En casi todos los agentes el repartidor no cambia nada; en unos
pocos (`4N RM`, `4N Temuco`) el repartidor define a quién se le paga.

El jefe está pasando a identificar al que cobra por **RUT + razón social**;
el operador queda como dato extra. En Portal4N eso convierte a
`courier_proveedores` en el índice principal.

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
