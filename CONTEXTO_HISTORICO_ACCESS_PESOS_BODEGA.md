# Contexto verificado de `Historico Access.xlsx`

## 1. Propósito de este documento

Este documento explica los hechos confirmados sobre `Historico Access.xlsx` y su relación con el proceso de pesos Courier.

Su objetivo es apoyar el análisis de:

- Los pesos registrados en bodega.
- La relación entre `Maestro` y `PesoReal`.
- El cruce pendiente en `export-6035-packages.xlsx → ControlCrucePesos`.

No contiene una regla definitiva para copiar registros a `PesoReal`. Esa decisión todavía debe validarse mediante los datos y, cuando corresponda, con Operaciones.

---

## 2. Origen operativo confirmado

`Historico Access.xlsx` fue proporcionado por otro supervisor del área de Operaciones.

Operaciones confirmó que este archivo se utiliza diariamente y que los valores de peso relevantes se originan en el pesaje realizado en la bodega.

Por lo tanto, para este análisis se considera confirmado que:

```text
Maestro.PesoP = peso registrado por Operaciones para el bulto en bodega
```

No debe reinterpretarse `PesoP` como un peso estimado de Geolice.

---

## 3. Estructura del libro

El archivo contiene tres hojas:

1. `Base Aerea`
2. `Base Terrestre`
3. `Maestro`

La hoja utilizada para el cruce actual es:

`Maestro`

`Maestro` contiene 138.044 filas de datos y utiliza columnas desde A hasta BI.

Las fechas observadas en `FechaProceso` abarcan desde:

- 26-05-2026
- Hasta 04-09-2026

---

## 4. Columnas principales para el cruce

Las columnas necesarias para relacionar `Maestro` con la plantilla Courier son:

| Columna | Encabezado | Uso confirmado |
|---|---|---|
| B | `FechaProceso` | Fecha del registro operativo. |
| M | `CodigoGeolize` | Código completo del bulto. |
| N | `PesoP` | Peso registrado por Operaciones en bodega. |
| O | `Operador` | Operador registrado en la fila. |
| P | `PesoLanas` | Columna distinta de `PesoP`; no se utiliza actualmente en `ControlCrucePesos`. |

`CodigoGeolize` contiene el identificador completo, incluido el código del bulto después del guion.

Ejemplo:

```text
4N202607318099-452
```

---

## 5. Cobertura de datos observada

En `Maestro` se observaron:

- 138.044 filas de datos.
- 135.789 códigos completos únicos.
- 2.161 códigos que aparecen más de una vez.
- 2.255 filas adicionales producidas por esas repeticiones.

Respecto de `PesoP`:

| Estado de `PesoP` | Filas |
|---|---:|
| Peso positivo | 133.055 |
| Vacío | 2.903 |
| Valor 0 | 2.086 |
| **Total** | **138.044** |

Por lo tanto, la presencia de un código en `Maestro` no garantiza por sí sola que la fila ya tenga un peso positivo utilizable.

---

## 6. Estados de entrega presentes

En la columna `Estado de entrega` se observaron registros con los siguientes estados:

- `En tránsito`, incluyendo variantes de escritura o codificación.
- `Fallido`.
- `Pendiente`.
- `Entregado`.
- `En reparto`.

No se observaron registros con:

- `Anulado`.
- `Retirado`.

Esta ausencia explica por qué determinados códigos de la descarga de Geolice pueden no existir en `Maestro`.

No debe concluirse que todo código ausente es un error del cruce.

---

## 7. Relación comprobada con `PesoReal`

Se comparó `Historico Access.xlsx → Maestro` con la hoja `PesoReal` de la plantilla Courier oficial.

Resultados confirmados:

- `PesoReal` contiene 104.761 filas de datos.
- Contiene 104.330 códigos completos únicos.
- Todos los códigos únicos de `PesoReal` fueron encontrados en `Maestro`.
- Para esos códigos existe al menos una coincidencia de `FechaProceso` con la fecha registrada en `PesoReal`.
- 101.574 valores actuales de `PesoReal` coinciden con al menos un `PesoP` registrado en `Maestro`.
- 2.723 filas con `PesoReal = 0` corresponden a coincidencias donde `PesoP` está vacío.
- Se observaron 33 diferencias restantes entre el valor almacenado en `PesoReal` y los valores encontrados en `Maestro`.

Esto confirma que `PesoReal` se construyó utilizando información de `Maestro`.

También confirma que `PesoReal` no es una copia automática e íntegra de todas las filas de `Maestro`, porque:

- `Maestro` contiene más registros.
- Existen códigos repetidos.
- Algunos registros tienen peso vacío o cero.
- Existen 33 diferencias históricas que requieren revisión individual antes de definir una regla de importación.

---

## 8. Ejemplo comprobado de la relación

Código:

```text
4N202607318099-452
```

En `Historico Access.xlsx → Maestro` aparece con:

```text
FechaProceso = 31-07-2026
CodigoGeolize = 4N202607318099-452
PesoP = 13
```

En la plantilla Courier aparece en `PesoReal` con:

```text
Codigo_S+Bulto = 4N202607318099-452
Notas = 13
Fecha de maestro = 31-07-2026
```

El mismo código también aparece en las hojas relacionadas con Lanas.

Este ejemplo demuestra el recorrido del peso de bodega desde `Maestro` hasta `PesoReal` para ese bulto concreto.

---

## 9. Códigos repetidos en `Maestro`

Un código completo puede aparecer más de una vez en `Maestro`.

Se observaron 402 códigos repetidos cuyos registros no conservan exactamente el mismo conjunto de valores en `PesoP`.

Al agrupar simultáneamente por:

```text
CodigoGeolize + FechaProceso
```

se encontraron 1.137 grupos repetidos, pero dentro de esos grupos no se observaron pesos diferentes para la misma combinación de código y fecha.

Por lo tanto:

- Contar coincidencias por código es necesario.
- Un resultado mayor que 1 no debe eliminarse automáticamente.
- La fecha puede ayudar a distinguir registros históricos del mismo código.
- Si un mismo código tiene pesos diferentes en fechas diferentes, no debe escogerse uno sin una regla confirmada.

---

## 10. Relación con `export-6035-packages.xlsx`

`export-6035-packages.xlsx` contiene 46.192 códigos de bulto únicos.

Dentro de su hoja `BaseGeolize` se identificaron 2.133 bultos que cumplen:

```text
PesoGeolice = X
PesoRealActual = 0
```

El objetivo de `ControlCrucePesos` fue buscar esos bultos en `Maestro` para determinar cuáles cuentan con un peso de bodega disponible.

La comparación directa previa entre los 2.133 códigos y los datos de `Maestro` produjo esta clasificación:

| Clasificación | Códigos |
|---|---:|
| Una coincidencia con peso positivo | 1.311 |
| Código repetido, siempre con el mismo peso positivo | 12 |
| Una coincidencia sin peso positivo | 221 |
| Código repetido, todas sus filas sin peso | 1 |
| Código repetido con pesos distintos | 1 |
| No encontrado en `Maestro` | 587 |
| **Total** | **2.133** |

Estos números fueron obtenidos mediante comparación directa de los archivos, no mediante los valores en caché actualmente guardados en las fórmulas externas de Excel.

### Conflicto concreto detectado

El código:

```text
4N202608298568-690
```

aparece con dos pesos diferentes:

```text
31-08-2026 → 6 kg
02-09-2026 → 10 kg
```

Ese código requiere revisión. No debe seleccionarse automáticamente uno de los dos valores.

---

## 11. Estado actual de `ControlCrucePesos`

La hoja contiene estas seis columnas:

| Columna | Nombre |
|---|---|
| A | `CodigoBulto` |
| B | `CoincidenciasMaestro` |
| C | `EstadoCruce` |
| D | `PesoGeolice` |
| E | `PesoRealActual` |
| F | `PesoMaestro` |

### Problema de las columnas B y C

`CoincidenciasMaestro` utiliza una fórmula equivalente a:

```excel
=CONTAR.SI('[Historico Access.xlsx]Maestro'!$M$2:$M$138045;A2)
```

`CONTAR.SI` sobre un libro externo cerrado puede devolver `#¡VALOR!`.

En el archivo guardado, las 46.192 fórmulas de B y C tienen actualmente ese error en sus valores almacenados. Los conteos se mostraron correctamente durante la sesión manual porque ambos libros estaban abiertos.

### Problema de la columna F

`PesoMaestro` contiene fórmulas únicamente en las 1.532 filas que habían quedado visibles después de aplicar:

```text
PesoGeolice = X
PesoRealActual = 0
CoincidenciasMaestro = 1
```

Las 1.532 fórmulas quedaron desplazadas una fila.

El patrón observado es:

```text
F3 busca A2
F5 busca A4
F46192 busca A46191
```

La fórmula correcta debe buscar siempre el código de la misma fila:

```text
F3 busca A3
F5 busca A5
F46192 busca A46192
```

Debido a ese desplazamiento, el conteo visible de 1.210 pesos no es válido.

No se copiaron registros desde este análisis hacia `PesoReal`.

---

## 12. Qué debe resolver el siguiente análisis

El siguiente análisis debe concentrarse en:

1. Validar directamente la clasificación de los 2.133 bultos.
2. Corregir conceptualmente el cruce de `ControlCrucePesos`, sin modificar todavía el libro.
3. Determinar una forma estable de consultar `Maestro` aunque el archivo externo esté cerrado.
4. Separar claramente:
   - Códigos únicos con peso positivo.
   - Códigos únicos con peso vacío o cero.
   - Códigos repetidos con el mismo peso.
   - Códigos repetidos con pesos diferentes.
   - Códigos ausentes.
5. Comparar `FechaProceso` cuando exista más de una coincidencia.
6. Determinar cuáles registros podrían convertirse en candidatos para `PesoReal`.

El análisis no debe copiar todavía información a `PesoReal`.

---

## 13. Forma solicitada de comunicación

A partir de esta etapa se solicita una conversación más fluida y menos técnica.

Antes de entregar fórmulas, tablas extensas o código:

1. Explicar en lenguaje simple qué significa el hallazgo para el proceso de Operaciones.
2. Indicar qué pregunta concreta se está intentando responder.
3. Proponer una sola comprobación por vez.
4. Esperar el resultado antes de avanzar al paso siguiente.
5. No convertir una correlación en regla definitiva sin indicarlo.
6. No modificar archivos ni desarrollar código Laravel durante esta etapa.

La finalidad empresarial es comprender y posteriormente digitalizar el proceso de determinación y pago de servicios Courier. El cruce de pesos es una parte de ese proceso, no el objetivo final completo.

---

## 14. Reglas que este documento no afirma

Este documento no confirma:

- Que los 2.133 registros deban agregarse completos a `PesoReal`.
- Que todo código encontrado una vez deba copiarse automáticamente.
- Que un valor cero represente un peso utilizable.
- Que un código repetido sea necesariamente un error.
- Qué fecha debe prevalecer cuando un código aparece varias veces.
- Qué valor debe utilizarse cuando existen pesos distintos.
- Que los registros ausentes de `Maestro` sean errores.
- Que el cruce deba implementarse mediante `BUSCARV` o `CONTAR.SI` en el sistema definitivo.

Estas decisiones requieren el análisis conjunto de los archivos y, cuando corresponda, confirmación del área de Operaciones.
