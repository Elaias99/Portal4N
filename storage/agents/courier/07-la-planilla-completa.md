# 07 · La planilla completa, hoja por hoja

Levantado el 28-09-2026 leyendo tres archivos de Operaciones:

| Archivo | Qué es |
|---|---|
| `202607_4N_COURIER_RESPALDOS.xlsx` | El mes de julio, cerrado. 28 hojas. |
| `202608_4N_COURIER_RESPALDOS (4).xlsx` | El mes de agosto. 25 hojas. |
| `Courier_Agosto_2026_TRABAJO.xlsx` | Archivo de trabajo intermedio, 11 hojas. Sólo controles. |

Nadie explicó nunca estas hojas: lo que sigue sale de leer sus fórmulas.
Los documentos `02-cadena-de-pago.md` y `04-modelo-de-datos.md` cubren la
parte que el sistema ya calcula; **este documento cubre el archivo entero**,
incluida la parte que se escribe a mano y que todavía no está en el sistema.

---

## 1. La idea que ordena todo

Todo pago de Courier —lo que se calcula y lo que se escribe a mano— termina
siendo **una fila con la misma forma** en la hoja `BaseCL`:

| Col | Campo | Col | Campo |
|---|---|---|---|
| A | Zona | I | Razón Social (cliente) |
| B | **tipo de Pago** | J | Peso |
| C | ID Bulto | K | estado del envío |
| D | Fecha Carga | L | **Valor final** |
| E | Dirección | M | Operador (agente) |
| F | Numero destino | N | Usuario (repartidor) |
| G | Depto destino | O | Periodo |
| H | Comuna Destino | P | Usuario2 |

Y `Q`–`U` se resuelven por fórmula contra `DatosProveedores` y `OC`:
Transportista, Razón Social, Rut, Empresa y OC.

De ahí sale todo lo demás: `ResumenPagos` es una tabla dinámica sobre
`BaseCL`, y `Banco` es la nómina que arma Finanzas.

**Consecuencia para el sistema:** los pagos manuales no necesitan un modelo
aparte. Son filas del mismo tipo, con otro `tipo de Pago` y sin bulto real
detrás.

---

## 2. Los diez tipos de pago

Las columnas de la tabla dinámica `ResumenPagos` son la lista completa:

| tipo de Pago | De dónde sale | ¿Está en el sistema? |
|---|---|---|
| **Variables** | Cálculo desde Geolice | Sí |
| **Lanas** | Cálculo desde Geolice, tarifa plana | Sí |
| **Retornos** | Hoja `Retornos`, con su propio valor | No |
| **Especiales** | Hoja `especiales` | No |
| **Ruta CV** | Hoja `Ruta CV` | No |
| **Servicios** | Hoja `Servicios` | No |
| **Visitas** | Hoja `Visitas Regionales` | No |
| **Acuerdos** | Hoja `Acuerdos` | No |
| **Apoyo Alza** | Hoja `Acuerdos` (mismas filas, otro tipo) | No |
| **Fijo Base** | Escrito a mano | No |

Julio tenía además **Revistas** (hoja `RevistaAmbientes`) y **Maquilado**
(hoja `Maquilado`), que en agosto no aparecen como tipo propio.

---

## 3. Lo que se calcula: `BaseGeolize`

La hoja `BaseGeolize` pega la descarga de Geolice desde la columna `Q` y le
escribe 16 columnas de cálculo a la izquierda. Estas fórmulas están tomadas
literalmente de `BaseGeolize-Descuentos`, que es la copia completa antes de
borrar filas:

| Col | Nombre | Fórmula |
|---|---|---|
| A | ESTADOS | `VLOOKUP(AA;Estados!A:B;2;0)` |
| B | ESPECIALES | `IFERROR(VLOOKUP(Q;especiales!F:K;6;0);"PAGAR")` |
| C | RETORNO | `IFERROR(VLOOKUP(Q;Retornos!A:C;2;0);"PAGAR")` |
| D | BLUE | `IFERROR(VLOOKUP(Q;Blue!B:G;6;0);"PAGAR")` |
| E | ConsiderarPago | `IF(K="SI";"NO";VLOOKUP(H;PagosCentroCostos!D:F;2;0))` |
| F | Zona | `VLOOKUP(AI;Operador!A:C;3;0)` |
| G | Agente | `VLOOKUP(AI;Operador!A:C;2;0)` |
| H | Llave | `G & AC & AD` (agente + comerciante + servicio) |
| I | Tabla | `VLOOKUP(H;PagosCentroCostos!D:F;3;0)` |
| J | Valor | `VLOOKUP(L;Pesos!$A$4:$R$5004;MATCH(I;Pesos!$A$3:$R$3;0);0)` |
| K | PagadoMesAnterior | `IFERROR(VLOOKUP(Q;PagadosMesAnterior!C:H;6;0);"NO")` |
| L | PesoFinal | `IF(Pesoreal=0; PesoTransformado; Pesoreal)` |
| M | Pesoreal | `IFERROR(VLOOKUP(Q;PesoReal!A:B;2;0);0)` |
| N | PesoTransformado | `VLOOKUP(R;PesoTransformado!A:B;2;0)` |
| O | RevisarPesos | igual que N |
| P | Fecha | `MID(Q;9;2)&"-"&MID(Q;7;2)&"-2026"` |

Notas que importan:

- **El valor no se calcula, se busca.** La hoja `Pesos` trae los pesos 1 a
  5.000 ya resueltos para cada tabla: fila 3 = número de tabla, fila 2 =
  kilo adicional, filas 4 en adelante = peso. El sistema reproduce ese
  resultado con la regla del tramo hasta 20 kg más el kilo adicional, y
  coincide.
- **`AI` es la Comuna de destino** y **`AA` el Estado de entrega**, contando
  desde la columna `Q` donde empieza la descarga.
- La fecha se saca **del propio código del bulto**, no de la fecha de Geolice.

### El peso declarado

El archivo de trabajo trae la fórmula exacta en `ControlPesosAgosto`:

```
=MAX(1; INT(VALUE(LEFT(texto; SEARCH("."; texto) - 1))))
```

Toma el texto antes del primer punto, lo convierte a número, lo trunca y
nunca baja de 1. Ojo: en su Excel la coma es separador decimal, así que
`"81,000.00 kg"` da **81 kg**, no ochenta y un mil.

Y al lado hay un control: *«Pesos fuera de rango»* cuenta los que quedan
bajo 1 o sobre 5.001. **El rango válido es 1 a 5.000 kilos.**

### Cómo se parte la base

`BaseGeolize` (todo) → `BaseGeolize-Descuentos` (copia de respaldo, con las
mismas fórmulas) → se borran filas hasta dejar `BaseGeolize-trabajada`
(Variables) y `Geolize-Lanas` (los tres comerciantes de Lanas). Las dos
escriben el bloque `AW`–`BL` con la forma de `BaseCL` y se pegan ahí.

En `Geolize-Lanas` el peso se fija en **1** y el valor sale igual de la
columna J: las tablas de Lanas son planas.

---

## 4. Los pasos que el jefe hace a mano

Julio trae una hoja `Paso Paso BaseGeolize-trabajada` donde él mismo anotó
lo que borra, en orden:

1. Eliminar los errores sin coincidencia de comunas
2. Eliminar Anulados
3. Eliminar Pendientes
4. Eliminar En Tránsito
5. Eliminar Retirado *(«la mayoría fueron Test»)*
6. Eliminar Demos
7. Eliminar los `NO` de ConsiderarPago
8. **Revés Derecho:**
   - 8.1 Filtrar Mayorista de regiones **excepto Curacaví y Transporte
     Mandame** y cambiarles la comuna a `SANTIAGO MAYORISTA`, para
     devolverlos a zona RM. *«Todos estos mayoristas se entregan en courier
     RM desde nuestra bodega.»*
   - 8.2 Sólo por ese mes, eliminar Revés Derecho de Mayorista, Retail y
     R. Tienda. *«Último mes que se cruza con dato de control en planilla de
     salida; desde el próximo mes el usuario mandará frente a pago.»*
   - 8.3 Reginella: sólo por ese mes se elimina RM. Misma razón.
9. Buscar los **traspasos desde regiones** y eliminarlos: se filtran por el
   texto inicial `"desde "` en el nombre del destinatario.

Los pasos 5, 8.2 y 8.3 dicen *«en esta ocasión»* y *«sólo por este mes»*:
son decisiones de ese mes, no reglas permanentes. **No se programan como
fijas.**

De acá sale también cómo se pagaba el Mayorista de Revés Derecho: con un
**pistoleo manual** (hoja `Lanas RM`, más abajo), y desde agosto pasa a
resolverse por el usuario que informa Geolice.

---

## 5. Lo que se escribe a mano, hoja por hoja

### `Acuerdos` — pagos fijos mensuales

Tiene la forma de `BaseCL` directo (A–P) más columnas de control.

- `tipo de Pago` = **Acuerdos** o **Apoyo Alza** (conviven en la misma hoja)
- `ID Bulto` = el concepto: `Servicio Fijo Courier`, `Valija SMU`,
  `Adicional de Servicios`, `Almacenaje Local`, `Apoyo Alza`, `Lanas`
- `Dirección` = la explicación: `Fijo mensual`, `Variable - Medida de apoyo
  según días de ruta`, `Fijo por día x cantidad de locales (4 Locales x 4000)`
- `estado del envío` = **`Gestion`**
- `Peso` = 1 · `Valor final` = el monto acordado, escrito a mano
- Columna `S` = `IF(Usuario = Razón Social; 1; 0)`, un chequeo de que el
  repartidor calza con quien factura

### `Servicios` — pagos por evento

Las columnas A–L son el registro (Fecha, Facturador, Turno, Operario, Banco,
TipoCuenta, NroCuenta, CentroCosto, TipoPago, Servicio, Valor, Cierre), y de
`N` a `AC` se arma el bloque `BaseCL` **por fórmula**:

- `Comuna Destino` = `CD 4N` fijo · `estado del envío` = `Gestionado`
- `Dirección` = `"Servicios - " & Servicio`
- `Valor final` = `+K` (el valor escrito)
- `Usuario` = el operario

### `Visitas Regionales` — visitas a locales

Forma de `BaseCL` directo, con un truco:

- `Numero destino` (F) se usa como **precio unitario** (1.000)
- `Peso` (J) se usa como **cantidad de visitas**
- `Valor final` = `F * J`
- `ID Bulto` = el día o frecuencia: `Miercoles`, `Jueves`, `Lunes A Viernes`

### `Ruta CV` — rutas de Cruz Verde

Es una grilla de asistencia, no tiene bloque `BaseCL`:

- A Zona · B Servicio · C Frecuencia · D Facturador · E Usuario ·
  F Detalle-Ruta · G Comuna · H Producto · **I Valor por día** · J Agente
- De `K` a `AD`: **20 columnas de día**, marcadas con `x`
- El total es `Valor × cantidad de x`, y se pega a `BaseCL` agrupado por
  frecuencia. Por eso en `BaseCL` aparecen filas cuyo «ID Bulto» dice
  `RUTA LU A VI`, `RUTA LU/MI/VI`, `RUTA MA/JU/VI`.

### `especiales`, `Retornos`, `Blue`

Las tres cumplen **dos papeles distintos** y conviene no confundirlos:

1. **Como control**: sacan el bulto del cálculo por tabla
   (columnas B, C y D de `BaseGeolize`).
2. **Como pago**: `Retornos` y `especiales` traen **su propio valor** y
   entran a `BaseCL` con su tipo de pago.

Hoy el sistema sólo hace lo primero. Por eso, al contrastar contra la
planilla, aparecen retornos y especiales como «la planilla lo paga y el
sistema no»: no es un criterio equivocado, es una regla que falta.

En julio los especiales viven en dos hojas: `EspecialesOperaciones`
(Fecha, Desde, Destino, Dirección, código, Valor) y `EspecialesRegiones`.

### `OC` — órdenes de compra

`Zona` + `Razón Social` → número de OC. La llave es `A&B`, y `BaseCL` la usa
en su columna `U`. Es lo que conecta el pago con la orden de compra.

### Hojas que sólo existen en julio

| Hoja | Qué es |
|---|---|
| `Lanas RM` | **Pistoleo manual**: fecha, facturador, turno, nombre, producto (`Reginella`, `RD-retail`, `RD-Mayorista`), cantidad y total = cantidad × $1.000. Así se pagaba el Mayorista de Revés Derecho. |
| `Maquilado` | Trabajo de planta: fecha, facturador, turno, operario, datos bancarios, centro de costo, servicio (`Maquilado` / `Planta`) y valor por turno. |
| `RevistaAmbientes` | Bultos de revista a $550 cada uno, con su propio listado tipo Geolice. |
| `Adicionales` | Es el `Acuerdos` de julio, con una columna `OC` extra. |
| `NuevaLlave` | Comerciante + Servicio → nombre normalizado de cliente, traído por `VLOOKUP` desde **otro libro externo**. Es la base nueva por llave que el jefe mencionó. |
| `Santiago` / `Regiones` | Tablas dinámicas de `BaseFinalCL` separadas por zona. |
| `Corte para Agosto(NoConsiderar)` | Bultos que se dejaron fuera para el mes siguiente. |

---

## 6. La salida

### `ResumenPagos`

Tabla dinámica sobre `BaseCL`: filas = Zona → Transportista → Razón Social,
columnas = los diez tipos de pago, valores = suma de `Valor final`.

### `Banco` — la nómina

Una fila por proveedor a pagar. 19 columnas:

`Orden · ESTADO · FECHA DE PAGO · OC · RUTA · EMPRESA · TIPO_PAGO ·
PROVEEDOR · RUT · AGENTE · RUT BANCO · TITULAR BANCO · NOMBRE BANCO ·
TIPO CUENTA · NUMERO CUENTA · TIPO · NRO_DOCUMENTO · Total · Total Sin Validar`

- `RUTA` es una etiqueta de negocio: `PLANTA`, `MAQUILADO PLANTA`,
  `4N TEMUCO`, `TRANSPORTE`, `VARIABLE STGO`, `CV.STGO + VARIABLES +
  AEROPUERTO`, `AGENCIA`…
- `TIPO_PAGO` acá **no** es el tipo de la tabla dinámica: es la condición de
  pago, `Contado` o `Quincena`.
- `TIPO` es el documento: `Factura`, `Factura Exenta`, `Boleta Honorarios`
  y, en julio, `Boleta de Terceros`.
- **El titular bancario puede ser distinto del proveedor**: hay filas donde
  `PROVEEDOR` es una empresa y `TITULAR BANCO` una persona, con otro RUT.
  Cualquier nómina que genere el sistema tiene que respetar esa diferencia.

### `Detalle1` y `DetalleFinal`

No son fuentes: son el resultado de hacer doble clic en una celda de la
tabla dinámica. `DetalleFinal` de julio agrupa por OC y razón social contra
el tipo de documento, y su valor se llama **Líquido**.

---

## 7. El archivo de trabajo

`Courier_Agosto_2026_TRABAJO.xlsx` no tiene pagos: tiene los cruces que el
jefe hace antes de armar el mes.

| Hoja | Qué hace |
|---|---|
| `ControlPesosAgosto` | La fórmula del peso declarado y el control de rango 1–5.000. |
| `ControlPesorealAgosto` | Arma filas nuevas de `PesoReal` desde `BaseGeolize` y marca cada código como `NUEVO` o `YA EXISTE` contra la `Pesoreal` histórica. |
| `ControlPagadosJulio` / `RespaldoPagadosAnterior` | La `BaseCL` del mes anterior reducida a Zona, Tipo de Pago, ID Bulto, Fecha, Razón Social, Operador, Usuario y `Considerar Pago`. Es el origen de `PagadosMesAnterior`. |

---

## 8. Qué le falta al sistema

El sistema cubre hoy **Variables y Lanas**. Contra la `BaseCL` de agosto,
sobre los bultos que ambos pagan, coincide en operador, peso y valor en la
gran mayoría, y el monto da idéntico.

Lo que falta, en orden de plata:

1. **Acuerdos y Apoyo Alza** — pagos fijos mensuales por proveedor.
2. **Ruta CV** — valor por día × días marcados.
3. **Servicios** — pagos por evento con datos bancarios propios.
4. **Visitas** — precio unitario × cantidad.
5. **Retornos y especiales como pago**, no sólo como control.
6. **Fijo Base**.
7. **Nómina de banco**, con la distinción proveedor / titular bancario.

Ninguno de estos necesita a Geolice. Todos son filas escritas por una
persona, con la forma de `BaseCL`, y el sistema podría recibirlas igual:
un período, un tipo de pago, un proveedor, un concepto y un monto.
