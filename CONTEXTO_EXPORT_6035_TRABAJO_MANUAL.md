# Contexto verificado de `export-6035-packages.xlsx`

## 1. Propósito de este documento

Este documento explica cómo se construyó manualmente `export-6035-packages.xlsx`, qué partes de la plantilla oficial se replicaron y en qué punto quedó detenido el trabajo.

Se incluyen únicamente hechos comprobados mediante:

- Las fórmulas y valores presentes en el archivo.
- Los conteos obtenidos durante el trabajo manual.
- La comparación previa con `202608_4N_COURIER_RESPALDOS (2).xlsx`.
- Las acciones realizadas expresamente durante la construcción del libro.

Este documento no afirma que todas las intervenciones manuales representen una regla definitiva de Operaciones. El objetivo es permitir que otra persona o IA audite el trabajo sin tener que reconstruir la conversación completa.

---

## 2. Identificación y alcance

Archivo:

`export-6035-packages.xlsx`

Este libro se construyó a partir de una descarga de Geolice y se utilizó para intentar replicar manualmente parte de la plantilla oficial del jefe de Operaciones.

La descarga contiene:

- 46.192 registros de paquetes.
- 46.192 códigos completos de bulto únicos.
- Fechas desde el 31-07-2026 hasta el 31-08-2026.

Por lo tanto, el libro contempla agosto completo y además registros del 31 de julio.

Este archivo no es la plantilla oficial y no debe confundirse con `202608_4N_COURIER_RESPALDOS (2).xlsx`.

---

## 3. Hojas que no participan en el análisis actual

Las hojas cuyo nombre termina en `_Anterior` corresponden a respaldos o etapas anteriores.

Por indicación expresa del responsable del trabajo, esas hojas no deben utilizarse para interpretar el estado actual de `export-6035-packages.xlsx`.

---

## 4. Catálogos trasladados desde la plantilla oficial

Para replicar los cálculos se incorporaron al libro las siguientes hojas de referencia:

- `Retornos`
- `especiales`
- `Lanas`
- `Blue`
- `Estados`
- `PesoReal`
- `PagadosMesAnterior`
- `PesoTransformado`
- `Pesos`
- `PagosCentroCostos`
- `Operador`

En la comparación realizada, los valores almacenados de estas hojas coincidían con la plantilla oficial, salvo `PesoTransformado`, que fue ampliada manualmente en este libro.

La hoja `PesoReal` del export también coincidía con la hoja `PesoReal` de la plantilla oficial antes de iniciar el cruce nuevo con `Historico Access.xlsx`.

---

## 5. Construcción de `BaseGeolize`

Se agregaron doce columnas calculadas antes de las columnas originales provenientes de Geolice.

| Columna | Nombre |
|---|---|
| A | `ConsiderarPago` |
| B | `Zona` |
| C | `Agente` |
| D | `Llave` |
| E | `Tabla` |
| F | `Valor` |
| G | `PagadoMesAnterior` |
| H | `PesoFinal` |
| I | `Pesoreal` |
| J | `PesoTransformado` |
| K | `RevisarPesos` |
| L | `fecha` |

Las fórmulas fueron copiadas desde la plantilla oficial, se eliminaron sus referencias externas en estas columnas y se extendieron a los 46.192 registros.

### 5.1 Relaciones reproducidas

Las fórmulas implementan las siguientes relaciones:

```text
Comuna de destino
→ Operador
→ Agente y Zona
```

```text
Llave = Agente + Comerciante + Servicio
```

```text
Llave
→ PagosCentroCostos
→ Pagar y Tabla
```

```text
Código completo del bulto
→ PagadosMesAnterior
```

```text
Código completo del bulto
→ PesoReal
```

```text
Peso informado por Geolice
→ PesoTransformado
```

```text
Si Pesoreal es distinto de 0
    PesoFinal = Pesoreal
De lo contrario
    PesoFinal = PesoTransformado
```

```text
Tabla + PesoFinal
→ matriz Pesos
→ Valor
```

La búsqueda de tarifa es exacta por peso y número de tabla.

---

## 6. Trabajo realizado con los pesos informados por Geolice

Inicialmente se identificaron 84 registros cuyo peso venía informado por Geolice, pero no existía como equivalencia en `PesoTransformado`.

Se creó la hoja:

`ControlPesosPendientes`

Los 84 registros correspondían a 26 pesos originales únicos.

Se agregaron esas 26 equivalencias a `PesoTransformado`. Después de incorporarlas, los 84 registros pudieron obtener un `PesoTransformado` reconocido.

Las 26 equivalencias agregadas al export son:

| Peso original | Peso transformado |
|---:|---:|
| 1.09 kg | 1 |
| 1.47 kg | 1 |
| 2.29 kg | 2 |
| 2.30 kg | 2 |
| 3.35 kg | 3 |
| 3.75 kg | 3 |
| 4.07 kg | 4 |
| 7.15 kg | 7 |
| 8.10 kg | 8 |
| 9.38 kg | 9 |
| 10.85 kg | 10 |
| 13.45 kg | 13 |
| 13.65 kg | 13 |
| 14.20 kg | 14 |
| 14.67 kg | 14 |
| 15.10 kg | 15 |
| 15.32 kg | 15 |
| 15.83 kg | 15 |
| 17.33 kg | 17 |
| 17.95 kg | 17 |
| 18.22 kg | 18 |
| 18.60 kg | 18 |
| 19.60 kg | 19 |
| 19.76 kg | 19 |
| 135.00 kg | 135 |
| 144.00 kg | 144 |

Estas equivalencias describen lo realizado manualmente. Este documento no establece que el mismo criterio sea una regla universal para futuros pesos decimales.

---

## 7. Tratamiento manual con `X`

Después de resolver los pesos informados por Geolice que no estaban en el catálogo, se analizaron registros sin un peso utilizable en la descarga.

En los casos trabajados manualmente se colocó `X` en la columna `Peso`.

La hoja oficial `PesoTransformado` ya contenía la equivalencia:

```text
X → 1
```

En el estado actual del export existen 2.133 registros que cumplen simultáneamente:

```text
PesoGeolice = X
Pesoreal = 0
```

Esos 2.133 registros son 2.133 códigos de bulto distintos dentro de `BaseGeolize`.

La incorporación de `X` fue una intervención manual del libro. No debe confundirse con un valor crudo e inalterado de la descarga original de Geolice.

Después del trabajo de transformación y del uso de `X`, los 46.192 registros del export tenían un `PesoFinal` calculable.

Permanecían 192 registros con errores en `Tabla` y `Valor`, pero esos errores correspondían a configuraciones operativas o tarifarias no encontradas, no a la falta de `PesoFinal`.

---

## 8. Construcción de `BaseGeolize-Descuentos`

Se creó la hoja:

`BaseGeolize-Descuentos`

Se agregaron cuatro controles al comienzo de la hoja:

| Columna | Control | Hoja consultada |
|---|---|---|
| A | `ESTADOS` | `Estados` |
| B | `ESPECIALES` | `especiales` |
| C | `RETORNO` | `Retornos` |
| D | `BLUE` | `Blue` |

Las fórmulas utilizadas fueron equivalentes a:

```excel
=BUSCARV(EstadoDeEntrega;Estados!A:B;2;0)
```

```excel
=SI.ERROR(BUSCARV(CodigoSeguimiento;especiales!F:K;6;0);"PAGAR")
```

```excel
=SI.ERROR(BUSCARV(CodigoSeguimiento;Retornos!A:C;2;0);"PAGAR")
```

```excel
=SI.ERROR(BUSCARV(CodigoSeguimiento;Blue!B:G;6;0);"PAGAR")
```

Los resultados de esos controles son principalmente `PAGAR` o `DESCONTAR`.

Al exigir que los cuatro controles estuvieran en `PAGAR`, quedaron 40.103 registros.

---

## 9. Construcción de `Geolize-Lanas`

Con los cuatro controles anteriores filtrados en `PAGAR`, se seleccionaron tres comerciantes:

| Comerciante | Registros incorporados |
|---|---:|
| Revesderecho | 5.141 |
| Comercial Reginella Ltda | 879 |
| (Orquidea) Hilanderia Maisa | 96 |
| **Total** | **6.116** |

Esos registros se copiaron a la hoja:

`Geolize-Lanas`

Después se agregó una columna denominada `VALIDADOR LANAS` utilizando una búsqueda en la hoja `Lanas`:

```excel
=BUSCARV(SeguimientoPaquete;Lanas!A:E;5;0)
```

La búsqueda devuelve el nombre asociado en la quinta columna de `Lanas` cuando encuentra el código.

Dentro de los 6.116 registros se observó:

```text
ConsiderarPago = SI → 5.860 registros
ConsiderarPago = NO → 256 registros
```

Dentro de los 256 registros con `ConsiderarPago = NO`:

- 147 tenían un valor reconocido en `VALIDADOR LANAS`.
- 109 presentaban `#N/D` en el validador.

La comparación previa con la plantilla oficial mostró que esos mismos 147 códigos aparecen allí con:

```text
ConsiderarPago = SI
Tabla = 0
Valor = 1.000
tipo de Pago = Lanas
```

En nuestro export esos registros permanecen con:

```text
ConsiderarPago = NO
Tabla = 0
Valor = 0
```

Por ello se creó una hoja de control, pero no se aplicó todavía la reasignación.

---

## 10. `ControlReasignacionesLanas`

Se creó la hoja:

`ControlReasignacionesLanas`

Su propósito era documentar y revisar las posibles reasignaciones especiales de Lanas antes de modificar valores.

La hoja contiene encabezados, pero no se alcanzaron a cargar registros ni a definir una regla final.

No se debe interpretar esta hoja como un proceso terminado.

---

## 11. Incorporación posterior de `Historico Access.xlsx`

Posteriormente, Operaciones proporcionó otro archivo:

`Historico Access.xlsx`

La hoja relevante de ese libro es `Maestro`.

Operaciones confirmó que `Maestro` registra los pesos realizados en la bodega.

Las columnas utilizadas para el análisis fueron:

| Columna de `Maestro` | Contenido |
|---|---|
| B | `FechaProceso` |
| M | `CodigoGeolize` |
| N | `PesoP` |

Se comprobó mediante ejemplos que un código presente en `Maestro`, con su `PesoP` y `FechaProceso`, también podía aparecer en la hoja `PesoReal` de la plantilla Courier.

`Historico Access.xlsx` no está incluido dentro de `export-6035-packages.xlsx`; es un archivo externo que deberá adjuntarse por separado para validar el cruce.

---

## 12. Creación de `ControlCrucePesos`

Para comparar los bultos de `BaseGeolize` con la hoja `Maestro`, se creó:

`ControlCrucePesos`

La hoja contiene una fila por cada uno de los 46.192 códigos de bulto del export.

Sus columnas actuales son:

| Columna | Nombre |
|---|---|
| A | `CodigoBulto` |
| B | `CoincidenciasMaestro` |
| C | `EstadoCruce` |
| D | `PesoGeolice` |
| E | `PesoRealActual` |
| F | `PesoMaestro` |

### 12.1 `CoincidenciasMaestro`

Esta columna cuenta cuántas veces aparece cada código en `Historico Access.xlsx → Maestro → CodigoGeolize`.

Ejemplo de fórmula:

```excel
=CONTAR.SI('[Historico Access.xlsx]Maestro'!$M$2:$M$138045;A3)
```

Interpretación utilizada:

```text
0 → NO ENCONTRADO
1 → COINCIDENCIA ÚNICA
2 o más → REVISAR REPETIDOS
```

Los conteos observados para los 46.192 registros fueron:

| Estado | Registros |
|---|---:|
| Coincidencia única | 35.150 |
| Revisar repetidos | 1.443 |
| No encontrado | 9.599 |
| **Total** | **46.192** |

Estos conteos corresponden al cruce general y no significan que todos esos registros necesiten un nuevo peso.

### 12.2 Filtros aplicados para investigar pesos pendientes

Para aislar el grupo que se estaba investigando se aplicaron simultáneamente:

```text
PesoRealActual = 0
PesoGeolice = X
```

El resultado fue:

```text
2.133 registros
```

Después se agregó:

```text
CoincidenciasMaestro = 1
```

El resultado fue:

```text
1.532 registros
```

### 12.3 `PesoMaestro`

La columna se creó para traer `PesoP` desde `Historico Access.xlsx → Maestro`.

La fórmula prevista es:

```excel
=SI.ERROR(BUSCARV(CodigoBulto;'[Historico Access.xlsx]Maestro'!$M$2:$N$138045;2;FALSO);"")
```

La referencia a `CodigoBulto` debe pertenecer siempre a la misma fila de la fórmula.

Por ejemplo:

```text
F3 debe buscar A3
F250 debe buscar A250
```

---

## 13. Punto exacto donde quedó detenido `ControlCrucePesos`

El último análisis no se completó.

En la revisión visual final se detectó que una celda de `PesoMaestro` estaba desalineada:

```text
La celda F3 utilizaba A2 en su fórmula.
```

Esto significa que el valor mostrado en esa fila pertenecía al código de la fila anterior.

Mientras esa desalineación no se corrija y se compruebe en toda la columna, los conteos obtenidos mediante `PesoMaestro` no deben considerarse definitivos.

El último conteo visible fue de 1.210 registros al excluir vacíos y ceros, pero ese total quedó sin validar debido al desplazamiento de la fórmula.

No se alcanzó a determinar mediante esta hoja qué registros debían incorporarse finalmente a `PesoReal`.

No se copiaron nuevos registros desde `ControlCrucePesos` hacia `PesoReal`.

---

## 14. Estado actual de las hojas de control

| Hoja | Estado actual |
|---|---|
| `ControlPesosPendientes` | Completada para los 84 registros y 26 equivalencias nuevas. |
| `ControlReasignacionesLanas` | Creada, pero sin registros ni reasignación aplicada. |
| `ControlCrucePesos` | Contiene los 46.192 códigos y seis columnas, pero el análisis de `PesoMaestro` quedó inconcluso. |

---

## 15. Diferencias confirmadas respecto de la plantilla oficial

1. El export contiene 46.192 registros, mientras la `BaseGeolize` oficial contiene 80.533.
2. El export cubre hasta el 31-08-2026; la `BaseGeolize` oficial llega hasta el 23-08-2026.
3. `PesoTransformado` tiene 620 equivalencias en el export y 594 en la plantilla oficial. La diferencia corresponde exactamente a las 26 equivalencias agregadas manualmente.
4. La hoja `PesoReal` era idéntica en ambos libros antes del nuevo análisis con `Historico Access.xlsx`.
5. El export contiene hojas de control creadas para documentar nuestro trabajo; no forman parte de la plantilla oficial.
6. El export no contiene todavía una construcción final equivalente a toda la hoja `BaseCL` oficial.

En una comparación previa se encontraron 30.748 códigos presentes tanto en el export como en la plantilla oficial. En esos códigos, los resultados principales de las fórmulas coincidían casi completamente. Las diferencias principales correspondían a estados actualizados en momentos distintos y a un caso con un peso fuente diferente.

---

## 16. Situaciones que permanecen pendientes

No se ha confirmado todavía:

1. La regla completa utilizada para reasignar los casos especiales de Lanas.
2. Por qué determinados casos con tabla 0 y valor 0 aparecen posteriormente pagados a $1.000 en la plantilla oficial.
3. Qué hacer con los códigos repetidos en `Maestro` cuando tienen pesos distintos.
4. Qué hacer con códigos presentes en `Maestro` cuyo `PesoP` está vacío o en cero.
5. Qué hacer con los códigos que no aparecen en `Maestro`.
6. Si todos los nuevos pesos de bodega deben copiarse directamente a `PesoReal` o si existe una selección operativa adicional.
7. El proceso completo que genera todas las categorías de `BaseCL`.
8. El tratamiento definitivo de los 192 registros sin una tabla o tarifa válida.

Ninguno de estos puntos debe resolverse mediante una suposición.

---

## 17. Instrucciones para auditar este libro

Al revisar `export-6035-packages.xlsx`:

1. Tratarlo como una reconstrucción manual, no como la plantilla oficial.
2. Ignorar las hojas terminadas en `_Anterior`.
3. Comparar sus fórmulas principales con `202608_4N_COURIER_RESPALDOS (2).xlsx`.
4. Distinguir los datos originales de Geolice de las columnas y valores agregados manualmente.
5. Considerar que la columna `Peso` fue intervenida con `X` en determinados registros.
6. No considerar finalizada la hoja `ControlReasignacionesLanas`.
7. No considerar finalizada la hoja `ControlCrucePesos`.
8. No utilizar el conteo de 1.210 como resultado validado.
9. Revisar la alineación de todas las fórmulas de `PesoMaestro` antes de calcular nuevos conteos.
10. No agregar registros a `PesoReal` antes de validar el cruce con `Historico Access.xlsx`.
11. No modificar el archivo durante el análisis.
12. Clasificar las conclusiones como:
    - Confirmadas por fórmulas o datos.
    - Inferencias respaldadas.
    - Pendientes de confirmación con Operaciones.

---

## 18. Objetivo del siguiente análisis

El siguiente análisis debe determinar con precisión:

1. Qué partes de `export-6035-packages.xlsx` replican correctamente la plantilla oficial.
2. Qué partes fueron intervenciones manuales y requieren validación.
3. Si las hojas de control están estructuradas correctamente.
4. Cómo corregir y terminar `ControlCrucePesos` sin modificar `BaseGeolize` ni `PesoReal` prematuramente.
5. Qué información adicional deberá obtenerse desde `Historico Access.xlsx`.

En esta etapa no se debe diseñar todavía la importación a Laravel ni automatizar reglas que no estén confirmadas.
