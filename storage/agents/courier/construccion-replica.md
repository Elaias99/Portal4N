# Construcción de la réplica de la planilla Courier

Documento de referencia: `export-6236-packages (1).xlsx`, construida entre el 08 y el 10-09-2026
replicando la planilla oficial `202608_4N_COURIER_RESPALDOS.xlsx` del jefe de Operaciones.
Describe, en el orden en que se hizo, cómo se pasa de una descarga de Geolice a la nómina de pago a
Courier. Cada regla indica si está **confirmada por datos**, si es **inferencia respaldada** o si está
**pendiente de Operaciones**.

---

## 1. Punto de partida: la descarga de Geolice

- Origen: exportación de paquetes de Geolice, período **04-08-2026 al 04-09-2026**.
- Registros: **53.330 bultos**, uno por fila.
- La descarga se pega íntegra en la hoja `BaseGeolize` a partir de la **columna M**.
  Las columnas **A–L** se insertan delante y contienen las fórmulas de cálculo (sección 4).

### 1.1. Columnas que entrega Geolice (31), en el orden en que vienen

| En BaseGeolize | Columna Geolice | Ejemplo |
|---|---|---|
| M | Seguimiento paquete | `4N202608047778-876` |
| N | Peso | declarado por el cliente, puede venir vacío |
| O / P / Q | Largo / Ancho / Alto | |
| R | Código de seguimiento | `4N202608047778` |
| S | Código externo | `RD#255657` |
| T / U / V | Centro de costo / Orden de compra / Guía de despacho | |
| W | Estado de entrega | `Entregado` |
| X | Intentos de entrega | `1` |
| Y | Comerciante | `Revesderecho` |
| Z | Servicio | `Servicio Standar (Ecommerce)` |
| AA | Nombre de campaña | |
| AB / AC | Nombre / Empresa del destinatario | |
| AD | Dirección | |
| AE | Comuna de destino | `Las Condes` |
| AF / AG | Teléfono / Email del destinatario | |
| AH | Valor | `$0.00` |
| AI | Fecha de recepción | `04/08/2026 21:48` |
| AJ | Entrega estimada | |
| AK | Fecha de entrega | |
| AL | Retiro en comerciante | `No` |
| AM | Bodega de retiro | `CD-RD-Pudahuel` |
| AN | Ruta de entrega | `4N-R-887403` |
| AO | Nombre del repartidor | `Alvaro Conejeros` |
| AP | Teléfono del repartidor | |
| AQ | Usuario que realizó la entrega | `Alvaro Conejeros` |

### 1.2. Las únicas columnas de Geolice que el proceso usa

| Columna | Para qué |
|---|---|
| **M** Seguimiento paquete | Identificador único del bulto. Llave contra `PesoReal`, `PagadosMesAnterior`, `especiales`, `Retornos`, `Blue`. Además **codifica la fecha**: posiciones 7–8 = mes, 9–10 = día (`4N2026`**`08`**`04`…). |
| **N** Peso | Peso declarado por el cliente. Se usa sólo si no hay peso de bodega. |
| **Y** Comerciante | Parte de la llave de pago. Además decide si el bulto es Lanas (sección 6). |
| **Z** Servicio | Parte de la llave de pago. |
| **W** Estado de entrega | Control de descuento contra la hoja `Estados`. |
| **AE** Comuna de destino | Determina Agente y Zona vía la hoja `Operador`. |
| **AD** Dirección, **AO** Nombre del repartidor | Sólo se arrastran a `BaseCL` como información. |

Las otras 22 columnas se conservan pero ninguna fórmula las lee.

---

## 2. Catálogos copiados de la planilla del jefe

Se copian tal cual desde `202608_4N_COURIER_RESPALDOS.xlsx`, hoja completa, mismo nombre.
Se dividen en tres grupos según cómo cambian en el tiempo.

### 2.1. Catálogos estables (cambian poco, son reglas del negocio)

| Hoja | Filas | Qué es | Columnas que usan las fórmulas |
|---|---|---|---|
| `Operador` | 590 | Comuna → agente y zona. | **A** Localidad, **B** ComunaMatriz (= agente), **C** Zona (`RM` / `Regiones`). Además **D** `PAGAR RETORNO`, **E** `VALOR/RETORNO` (usadas por la hoja `Retornos`), **F** `NombreProveedor`. Trae G–N informativas (Frecuencia, TipoEntrega, Región, Ruta…). |
| `PagosCentroCostos` | 2.576 | Qué se paga y con qué tabla. | **A** Agente, **B** Comerciante, **C** Servicio, **D** Llave (= A&B&C), **E** Pagar (`SI` 1.853 / `NO` 698 / `REVISAR` 25), **F** Tabla (0–16). |
| `Pesos` | 5.003 | Matriz de tarifas. | Fila 2 = kilo adicional por tabla. Fila 3 = número de tabla (0–16). Filas 4–23 = **A** peso 1–20 kg, **B–R** valor de cada tabla. Desde 21 kg el valor es `valor(20) + (peso − 20) × kilo adicional`. Tabla 0 = $0 siempre. |
| `PesoTransformado` | 654 | Texto de peso de Geolice → entero. | **A** texto tal como viene (`"3.50 kg"`, `X`), **B** entero. Es una tabla dinámica pegada. |
| `Estados` | 7 | Estado de entrega → pagar o no. | **A** estado, **B** `PAGAR` / `DESCONTAR`. |
| `DatosProveedores` | 279 | Operador + usuario → a quién se le paga. | **A** Operador, **B** Usuario, **C** = A&B, **D** Transportista, **E** Razón Social, **F** Rut, **G** Empresa, **H** Tipo documento (`Factura` / `Boleta` / `Sin Documento`), **I–M** datos bancarios. |

### 2.2. Entradas mensuales (las llena Operaciones cada mes)

| Hoja | Filas | Qué es | Columnas que usan las fórmulas |
|---|---|---|---|
| `especiales` | 73 | Bultos con pago especial autorizado. | **F** ID bulto, **K** `DESCONTAR` (si aparece, el bulto no se paga por tabla). |
| `Retornos` | 284 | Bultos que son retorno. | **A** código (= R), **B** `DESCONTAR`. Trae su propio cálculo C–N contra `Operador!D:E`. |
| `Blue` | 743 | Bultos enviados por Blue Express. | **B** ID bulto, **G** `DESCONTAR`. |
| `Lanas` | 2.805 | Pistoleo de los repartidores de Lanas. | **A** código reconstruido desde el JSON de **C**, **G** Seguimiento paquete. En la réplica no la lee ninguna fórmula; queda como control. |
| `PagadosMesAnterior` | 26.164 | La `BaseCL` del mes anterior. | **C** ID bulto, **H** = `SI` (llega por `VLOOKUP(...,6)` desde C). Evita pagar dos veces. |
| `OC` | 61 | Órdenes de compra por zona y razón social. | **A** Zona, **B** Razón Social, **C** Total, **D** = A&B. |

### 2.3. Pesos de bodega

`PesoReal` no se copia: se **reconstruye** (sección 3).

---

## 3. `PesoReal` reconstruida desde `Historico Access.xlsx`

**Por qué**: la `PesoReal` del jefe (104.761 filas) es un corte de fechas del `Maestro`. Reconstruirla
desde la fuente entrega más bultos con peso de bodega.

**Fuente**: `Historico Access.xlsx`, hoja `Maestro`, 138.044 filas, columnas usadas:
**B** FechaProceso, **M** CodigoGeolize, **N** PesoP.

**Procedimiento**:

1. Copiar `Maestro` completo — sin filtro de fecha (26-05-2026 → 04-09-2026).
2. Eliminar duplicados por **código + fecha** → quedan **136.907** filas.
   *(Comprobado: 138.044 filas → 136.907 pares código+fecha distintos, exactamente lo que quedó.)*
3. Pegar en la hoja `PesoRealNuevo` con tres columnas: **A** CodigoGeolize, **B** PesoP, **C** FechaProceso.
4. Llevar a `PesoReal` respetando la estructura de la hoja del jefe (7 columnas):
   `A → A` (Codigo_S+Bulto), `B → B` (Notas = peso), `C → D` (Fecha de maestro). C, E, F, G quedan vacías.

**Resultado**: 136.907 filas; 134.004 con peso numérico, 2.903 sin peso. Quedan 1.029 códigos
repetidos con distinta fecha — `BUSCARV` toma el primero. Respecto de copiar la hoja del jefe,
**1.668 bultos más** obtuvieron peso de bodega.

---

## 4. `BaseGeolize`: las 12 fórmulas de cálculo (columnas A–L)

Se escriben en la fila 2 y se copian hacia abajo. Geolice ocupa M–AQ (sección 1).

| Col | Nombre | Fórmula (fila 2) | Qué hace |
|---|---|---|---|
| A | ConsiderarPago | `=SI(G2="SI";"NO";BUSCARV(D2;PagosCentroCostos!D:F;2;0))` | Si ya se pagó el mes anterior → `NO`. Si no, lo que diga `PagosCentroCostos` (`SI` / `NO` / `REVISAR`). |
| B | Zona | `=BUSCARV(AE2;Operador!A:C;3;0)` | Comuna → zona. |
| C | Agente | `=BUSCARV(AE2;Operador!A:C;2;0)` | Comuna → agente. |
| D | Llave | `=+C2&Y2&Z2` | Agente & Comerciante & Servicio. |
| E | Tabla | `=+BUSCARV(D2;PagosCentroCostos!D:F;3;0)` | Llave → número de tabla. |
| F | Valor | `=BUSCARV(H2;Pesos!$A$4:$R$5004;COINCIDIR(E2;Pesos!$A$3:$R$3;0);0)` | Peso final × tabla → valor. |
| G | PagadoMesAnterior | `=SI.ERROR(BUSCARV(M2;PagadosMesAnterior!C:H;6;0);"NO")` | `SI` si el bulto está en la nómina del mes pasado. |
| H | PesoFinal | `=+SI(SI.ERROR(BUSCARV(M2;PesoReal!A:B;2;0);0)=0;BUSCARV(N2;PesoTransformado!A:B;2;0);SI.ERROR(BUSCARV(M2;PesoReal!A:B;2;0);0))` | **Bodega tiene prioridad**: si hay peso en `PesoReal` se usa ése; si no, el declarado por el cliente transformado a entero. |
| I | Pesoreal | `=SI.ERROR(BUSCARV(M2;PesoReal!A:B;2;0);0)` | Peso de bodega, 0 si no hay. |
| J | PesoTransformado | `=BUSCARV(N2;PesoTransformado!A:B;2;0)` | Peso declarado → entero. |
| K | RevisarPesos | `=+BUSCARV(N2;PesoTransformado!A:B;2;0)` | Igual a J; sirve para filtrar los `#N/A` (pesos que no están en el catálogo). |
| L | fecha | `=EXTRAE(M2;9;2)&"-"&EXTRAE(M2;7;2)&"-2026"` | Fecha desde el código del bulto. |

**Comprobación obligatoria después de copiar**: seleccionar una celda de la columna C bajo la fila 2
y confirmar que la fórmula referencia `AE`, no `AF`. En la construcción se detectó que C3 hacia abajo
había quedado como `=BUSCARV(AF3;Operador!B:D;3;0)` (se copió B2 en C3 y todo se corrió una columna),
lo que dejó 53.329 errores. Se corrige copiando C2 hacia abajo; A, D, E y F se arreglan en cascada.

**Variantes de escritura**: cuando `Operador` no reconoce una comuna (ej. `SANTIAGO ` con espacio
final), la variante se agrega como fila nueva en `Operador`. **No se corrige la descarga de Geolice**,
porque la próxima descarga traería el mismo problema.

**Pesos que no están en `PesoTransformado`**: se listan en `ControlPesosPendientes` (col. A texto
original, col. B `=MAX(1;VALOR(IZQUIERDA(A2;ENCONTRAR(".";A2)-1)))`) y se agregan al catálogo.
Regla: **truncar al entero, mínimo 1**. *(Inferencia respaldada: 580/582 entradas del catálogo oficial
truncan; 2 redondean.)*

**Bultos sin peso por ninguna fuente**: se marcan con `X` en `PesoTransformado` (`X` → 1), igual que
en la planilla del jefe.

---

## 5. `BaseGeolize-Descuentos`: los cuatro controles

Copia de `BaseGeolize` con **cuatro columnas insertadas delante**. Por eso todas las fórmulas de la
sección 4 se corren cuatro columnas (E–P) y las referencias a Geolice también
(`M→Q`, `N→R`, `Y→AC`, `Z→AD`, `AE→AI`, `W→AA`, `AD→AH`, `AO→AS`).

| Col | Nombre | Fórmula (fila 2) | Descuenta si… |
|---|---|---|---|
| A | ESTADOS | `=+BUSCARV(AA2;Estados!A:B;2;0)` | el estado de entrega está marcado `DESCONTAR` (Anulado, Pendiente, En tránsito…). |
| B | ESPECIALES | `=SI.ERROR(BUSCARV(Q2;especiales!F:K;6;0);"PAGAR")` | el bulto está en `especiales` con `DESCONTAR`. |
| C | RETORNO | `=SI.ERROR(BUSCARV(Q2;Retornos!A:C;2;0);"PAGAR")` | el bulto está en `Retornos`. |
| D | BLUE | `=SI.ERROR(BUSCARV(Q2;Blue!B:G;6;0);"PAGAR")` | el bulto se envió por Blue. |

Si el bulto no aparece en la hoja de control, el resultado es `PAGAR`.

Layout final de la hoja: **A–D** controles · **E–P** cálculo · **Q–AU** Geolice.

---

## 6. Selección y separación: `BaseGeolize-trabajada` y `Geolize-Lanas`

### 6.1. Qué se paga

De los 53.330 bultos se toman los que cumplen **las cinco condiciones a la vez**:

```
ConsiderarPago = SI
ESTADOS = PAGAR
ESPECIALES = PAGAR
RETORNO = PAGAR
BLUE = PAGAR
```

Resultado: **25.106 bultos**. Es la traducción a filtros de los pasos 1–7 y 9–10 del
`Paso Paso BaseGeolize-trabajada` del jefe (eliminar sin comuna, anulados, pendientes, en tránsito,
retirados, demos, los `NO`, los retornos y los Blue).

### 6.2. Variables o Lanas

Los 25.106 se separan **por comerciante**:

| Hoja | Comerciantes | Bultos |
|---|---|---|
| `Geolize-Lanas` | `Revesderecho` (5.701), `Comercial Reginella Ltda` (925), `(Orquidea) Hilanderia Maisa` (179) | **6.805** |
| `BaseGeolize-trabajada` | todos los demás | **18.301** |

*(Confirmado por datos: ninguno de los tres comerciantes aparece en `trabajada`; ningún otro aparece en `Lanas`.)*

### 6.3. Bloque de salida (columnas AW–BL, en las dos hojas)

Es la estructura de `BaseCL`. Se escribe en la fila 2 y se copia hacia abajo.

| Col | Nombre | `trabajada` | `Geolize-Lanas` |
|---|---|---|---|
| AW | Zona | `=+F2` | `=+F2` |
| AX | tipo de Pago | `Variables` (texto fijo) | `Lanas` (texto fijo) |
| AY | ID Bulto | `=+Q2` | `=+Q2` |
| AZ | Fecha Carga | `=+P2` | `=+P2` |
| BA | Dirección | `=+AH2` | `=+AH2` |
| BB | Numero destino | vacía | vacía |
| BC | Depto destino | vacía | vacía |
| BD | Comuna Destino | `=+AI2` | `=+AI2` |
| BE | Razón Social | `=+AC2` (comerciante) | `=+AC2` |
| BF | Peso | `=+L2` (PesoFinal) | **`1` fijo** |
| BG | estado del envio | `=+AA2` | `=+AA2` |
| BH | Valor final | `=+J2` | `=+J2` |
| BI | Operador | `=+G2` (agente) | `=+G2` |
| BJ | Usuario | `=+AS2` (repartidor) | `=+AS2` |
| BK | Periodo | `AGOSTO 2026 - VARIABLES` | `AGOSTO 2026 - LANAS` |
| BL | Usuario2 | `=+BJ2` | `=+BJ2` |

En Lanas el peso se fija en 1 porque las tablas 4/5/6 son planas; el valor ya salió de la columna J.

---

## 7. `BaseCL` → `ResumenPagos` → `Banco`

### 7.1. `BaseCL`

- Columnas **A–P**: se pegan **como valores** los bloques AW–BL de `trabajada` y de `Geolize-Lanas`,
  uno debajo del otro. 25.106 filas.
- Columnas **Q–U**: fórmulas.

| Col | Nombre | Fórmula |
|---|---|---|
| Q | Transportista | `=+BUSCARV(M2&N2;DatosProveedores!C:G;2;0)` |
| R | Razon Social | `=+BUSCARV(M2&N2;DatosProveedores!C:G;3;0)` |
| S | Rut | `=+BUSCARV(M2&N2;DatosProveedores!C:G;4;0)` |
| T | Empresa | `=+BUSCARV(M2&N2;DatosProveedores!C:G;5;0)` |
| U | OC | `=+BUSCARV(A2&R2;OC!D:E;2;0)` |

La llave es **Operador & Usuario**: en 50 de 52 operadores da lo mismo el repartidor; en `4N RM`
(27 RUT) y `4N Temuco` (3 RUT) el repartidor define a quién se le paga.

La columna V `Archivo` del jefe no se replica: en su planilla apunta a `#REF!` en las 24.405 filas.

### 7.2. `ResumenPagos`

Tabla dinámica sobre `BaseCL`: filas = Zona, columnas = tipo de Pago, valor = suma de Valor final.

### 7.3. `Banco`

Tabla dinámica sobre `BaseCL` por Razon Social (suma de Valor final), más dos columnas:

| Col | Nombre | Fórmula (fila 4) |
|---|---|---|
| D | Tipo documento | `=INDICE(DatosProveedores!H:H;COINCIDIR(A4;DatosProveedores!E:E;0))` |
| E | Total con IVA | `=REDONDEAR(SI(D4="Factura";B4*1,19;B4);0)` |

**Regla**: el IVA (19 %) se agrega **sólo a quienes emiten Factura**. Boleta y Sin Documento van sin IVA.

---

## 8. Decisiones tomadas durante la construcción

| Decisión | Alternativa descartada | Motivo |
|---|---|---|
| Las variantes de escritura de comunas se agregan a `Operador`. | Corregir la descarga de Geolice. | La próxima descarga traería el mismo texto. |
| `PesoReal` se reconstruye desde `Maestro` sin filtro de fecha. | Copiar la `PesoReal` del jefe. | 1.668 bultos más con peso de bodega. |
| Pesos fuera del catálogo: truncar al entero, mínimo 1. | Redondear. | Es lo que hace el catálogo oficial en 580 de 582 casos. |
| Bultos sin peso por ninguna fuente: `X` → 1. | Dejarlos fuera. | Es lo que hace el jefe. |
| Se omite la columna V `Archivo` de `BaseCL`. | Replicarla. | Está rota en el original. |
| La columna E de `Banco` redondea. | Dejar decimales. | El banco paga pesos enteros. |

---

## 9. Resultado y validación

| | |
|---|---|
| Bultos para pago | **25.106** (18.301 Variables + 6.805 Lanas) |
| Total `BaseCL` | **$ 28.931.270** (Regiones $ 20.803.277 · RM $ 8.127.993) |
| Total `Banco` con IVA | **$ 34.092.205** |

**Comparación con la planilla del jefe** en los 13.357 códigos que ambas tienen: tipo de pago y
operador coinciden al 100 %.

### 9.1. Pendiente de Operaciones (comunicado al jefe el 10-09-2026)

- `Cajas Los Andes` + `Servicio Standar (Cotizacion)`: no está en `PagosCentroCostos` — 87 bultos sin tabla.
- Operador `Envio externo`: no está en `DatosProveedores` — 8 bultos.
- `4N Temuco` + usuario `Alejandra Valenzuela`: no está en `DatosProveedores` — 1 bulto.
- `Retiro en ruta`: servicio nuevo, en integración por Geolice. Códigos parten con `SH` en vez de `4N`,
  244 bultos sin comuna. Se dejan fuera.

### 9.2. Pendiente de Operaciones (todavía no comunicado)

- Qué significa `REVISAR` en `PagosCentroCostos` (25 llaves).
- La `BaseCL` de agosto del jefe tiene 1.236 valores y 308 zonas que no coinciden con lo que sus
  propias fórmulas producen hoy — es una hoja de valores pegados, desincronizada.
- Los 248 bultos de Lanas con `ConsiderarPago = NO` que en la planilla del jefe igual se pagan a
  $1.000. En la réplica se excluyeron.
- Por qué 149 de 284 retornos se pagan y 113 `Entregado` quedan fuera sin criterio visible.

### 9.3. Lo que la réplica no cubre

Los ocho tipos de pago manuales — `Acuerdos`, `Servicios`, `Visitas Regionales`, `Ruta CV`,
`Retornos`, `Especiales`, `Apoyo Alza`, `Fijo Base` — son hojas escritas a mano y se agregan a
`BaseCL` fuera de este flujo. En agosto sumaron 876 líneas y $ 73,3 M de $ 101,2 M.