---
titulo: Reglas de negocio del módulo Suscripciones
modulo: Suscripciones
tipo_documento: Referencia funcional para análisis asistido por Codex
estado: Vigente
ultima_actualizacion: 2026-07-29
ubicacion_recomendada: storage/agents/docs/suscripciones/reglas-negocio.md
---

# Reglas de negocio del módulo Suscripciones

## 1. Propósito de este documento

Este documento define las reglas funcionales que gobiernan el módulo
**Suscripciones**.

Su objetivo es permitir que una persona desarrolladora o un agente como Codex
pueda:

- comprender qué comportamientos son correctos;
- distinguir reglas operativas de decisiones técnicas;
- construir escenarios ficticios de validación;
- detectar resultados incompatibles con el negocio;
- reconocer contradicciones entre frontend, backend, base de datos y documentos;
- identificar regresiones;
- informar posibles errores o bugs sin asumir reglas inexistentes;
- evaluar el impacto de una modificación antes de proponerla.

Este documento no define una lista cerrada de pruebas.

Codex puede construir todos los escenarios ficticios, combinaciones y casos
límite que considere necesarios, siempre que:

- trabaje en modo de solo lectura cuando la tarea sea auditoría;
- no modifique código sin autorización explícita;
- no escriba datos reales;
- no ejecute operaciones destructivas;
- distinga hechos confirmados de inferencias;
- informe cualquier ambigüedad funcional.

---

# 2. Principio general del módulo

El módulo genera liquidaciones y pre-facturas mensuales para proveedores de
servicios de Suscripciones.

La generación mensual combina:

```text
catálogos maestros
+ configuración del período
+ calendario zonal
+ cantidades variables
+ pagos adicionales
+ novedades y ajustes
= detalles mensuales
= pre-facturas
```

El resultado mensual no debe depender únicamente de un archivo Excel, del
frontend o de una operación manual.

La fuente final de cálculo debe encontrarse en el backend y quedar persistida en
las tablas mensuales correspondientes.

---

# 3. Conceptos fundamentales

## 3.1. Período

Un período se identifica mediante:

```text
anio + mes
```

Ejemplo:

```text
2026 + 7
```

representa julio de 2026.

Reglas:

- el mes debe estar entre 1 y 12;
- el año debe ser válido;
- todos los datos enviados en una generación deben corresponder al mismo
  período;
- el calendario zonal debe haberse construido para ese mismo año y mes;
- un dato de otro período no debe mezclarse en la generación actual;
- las pre-facturas deben agrupar solamente detalles del período solicitado.

---

## 3.2. Asignación

Una asignación representa una línea operativa o técnica.

Puede contener:

- proveedor;
- transportista;
- zona;
- código;
- servicio;
- puntos;
- origen del gasto;
- costo;
- grupo de pre-factura;
- tipo de asignación;
- indicador de generación automática.

La identidad real es:

```text
suscripcion_asignacion_id
```

El código no es una identidad global.

---

## 3.3. Proveedor base

Es el proveedor asociado directamente a la asignación.

---

## 3.4. Proveedor efectivo

Es el proveedor que debe utilizarse para la facturación del período después de
considerar ajustes mensuales.

Puede coincidir o no con el proveedor base.

Toda operación que dependa de la facturación debe usar el proveedor efectivo
cuando exista una regla mensual válida.

Esto incluye:

- agrupación;
- filtros;
- vista individual;
- PDF;
- ZIP;
- destinatario;
- tipo documental;
- resumen tributario;
- datos bancarios;
- correo;
- OneDrive.

---

## 3.5. Transportista base y efectivo

La asignación puede contener un transportista base.

Una novedad mensual puede modificar el transportista efectivo para un período.

No debe modificarse el transportista maestro de toda la asignación cuando el
cambio solo corresponde a un mes.

---

## 3.6. Detalle mensual

Es el resultado calculado de una asignación para un período.

Debe conservar:

- asignación;
- año;
- mes;
- código;
- costo;
- cantidad de calendario;
- inasistencia;
- cantidad final;
- total.

El detalle mensual es la base de la pre-factura.

---

# 4. Tipos de asignación

Los tipos confirmados son:

```text
RUTA
FIJO_MENSUAL
VARIABLE
COMISION
CONTENEDOR_AJUSTE
```

OPV puede estar almacenada como `RUTA`.

No debe asumirse que todos los tipos se generan mediante la misma fórmula.

---

# 5. Regla de generación automática

Una asignación automática es elegible cuando:

```text
generar_automaticamente es null o 1
```

y no pertenece a un tipo excluido.

No deben generarse automáticamente como rutas:

```text
VARIABLE
COMISION
CONTENEDOR_AJUSTE
```

Las asignaciones variables y pagos adicionales se incorporan desde sus tablas
mensuales.

Las asignaciones técnicas existen como soporte del proceso, no como servicios
calendarizados.

---

# 6. Calendario zonal

## 6.1. Propósito

El calendario zonal registra si una zona tuvo despacho durante cada sábado y
domingo de un período.

---

## 6.2. Fechas consideradas

La versión actual utiliza:

```text
todos los sábados y domingos del mes
```

No incluye automáticamente:

- lunes;
- martes;
- miércoles;
- jueves;
- viernes;
- festivos fuera de fin de semana.

No debe ampliarse la regla sin decisión funcional explícita.

---

## 6.3. Matriz completa

La configuración mensual debe representar:

```text
zonas activas × fechas de sábado y domingo
```

Cada combinación debe existir una sola vez.

Ejemplo:

```text
17 zonas × 8 fechas = 136 registros
```

---

## 6.4. Significado de `hubo_despacho`

```text
hubo_despacho = 1
```

significa que la zona operó ese día.

```text
hubo_despacho = 0
```

significa que la zona completa no operó ese día.

---

## 6.5. Día zonal sin despacho

Un día zonal sin despacho:

- afecta todas las rutas calendarizadas de esa zona;
- reduce `q_calendario`;
- no aumenta `q_inasistencia`;
- no debe registrarse como ausencia individual;
- debe conservarse en `suscripcion_zona_dias_operativos`.

---

## 6.6. Zona e inasistencia no son equivalentes

Estas situaciones son diferentes.

### Zona sin despacho

```text
La zona completa no trabajó.
```

Resultado:

```text
q_calendario disminuye
q_inasistencia no cambia
```

### Ruta ausente

```text
La zona trabajó, pero una asignación específica no asistió.
```

Resultado:

```text
q_calendario se conserva
q_inasistencia aumenta
```

Nunca debe convertirse automáticamente una situación en la otra.

---

## 6.7. Relación de zona

La zona pertenece a la asignación.

No pertenece directamente al proveedor.

Un proveedor puede tener:

- múltiples rutas;
- múltiples zonas;
- asignaciones sin zona;
- asignaciones técnicas.

---

## 6.8. Asignaciones que requieren zona

Requieren zona para generar:

```text
RUTA normal automática
OPV con puntos configurados
```

No requieren zona para su cantidad:

```text
FIJO_MENSUAL
VARIABLE
COMISION
CONTENEDOR_AJUSTE
```

---

## 6.9. Calendario incompleto

La generación no debe comenzar cuando una zona utilizada por asignaciones
calendarizadas no tiene todas las fechas esperadas.

La ausencia de calendario no debe interpretarse como:

```text
todos los días operativos
```

ni como:

```text
ningún día operativo
```

Debe informarse como error de configuración.

---

# 7. Regla de ruta normal

## 7.1. Fórmula

```text
dias_efectivos = max(0, q_calendario - q_inasistencia)
cantidad = dias_efectivos
total = costo × cantidad
```

---

## 7.2. Restricciones

- `q_calendario` no debe ser negativo;
- `q_inasistencia` no debe ser negativo;
- la cantidad final no debe ser negativa;
- una ausencia no debe aumentar la cantidad;
- el total debe recalcularse en backend;
- la zona debe existir para una ruta automática calendarizada.

---

## 7.3. Inasistencia superior al calendario

La fórmula evita cantidades negativas mediante:

```text
max(0, ...)
```

Pero un caso con:

```text
q_inasistencia > q_calendario
```

debe considerarse sospechoso.

Codex debe informar si:

- el backend lo acepta;
- la base lo permite;
- la interfaz lo puede producir;
- el ajuste se aplica más de una vez;
- existe duplicidad de ausencias.

---

# 8. Regla OPV

## 8.1. Identificación

OPV puede estar almacenada como `RUTA`.

Puede detectarse mediante atributos como:

- código;
- servicio;
- origen del gasto;
- otros criterios implementados.

La regla exacta debe verificarse en el servicio vigente.

---

## 8.2. Puntos OPV

Una OPV requiere puntos configurados.

Los puntos representan el multiplicador de la cantidad diaria.

---

## 8.3. Fórmula

```text
dias_efectivos = max(0, q_calendario - q_inasistencia)

cantidad =
dias_efectivos × cantidad_puntos_opv

total = costo × cantidad
```

---

## 8.4. OPV sin puntos

Una OPV sin puntos:

- no debe calcularse como una ruta normal;
- no debe asumir un punto por defecto;
- debe omitirse;
- debe ser informada en el resultado de generación.

---

## 8.5. Modificación de puntos después de generar

Cambiar los puntos OPV después de generar un período no debe asumirse como
recálculo automático del detalle histórico.

Codex debe identificar si:

- el detalle conserva una instantánea;
- la vista usa los puntos actuales;
- un ajuste recalcula con puntos nuevos;
- el PDF puede diferir de la generación original.

---

# 9. Regla `FIJO_MENSUAL`

## 9.1. Fórmula

```text
q_calendario = 1
q_inasistencia = 0
cantidad = 1
total = costo
```

---

## 9.2. Independencia del calendario

Un fijo mensual no debe reducirse porque una zona tenga días sin despacho.

La presencia de `suscripcion_zona_id` en un fijo histórico no debe cambiar esta
regla.

---

## 9.3. Ajustes sobre fijo mensual

Una modificación de un fijo debe provenir de una regla explícita de ajuste.

No debe tratarse como ruta normal.

---

# 10. Regla `VARIABLE`

## 10.1. Fuente de cantidad

La cantidad proviene de:

```text
suscripcion_cantidades_mensuales
```

para:

```text
suscripcion_asignacion_id + anio + mes
```

---

## 10.2. Fórmula

```text
cantidad = cantidad informada
total = costo × cantidad
```

El total definitivo debe calcularse o validarse en backend.

---

## 10.3. Independencia del calendario

Una cantidad variable:

- no depende de sábados o domingos;
- no depende de la zona;
- no debe reducirse por inasistencia de ruta salvo regla explícita;
- no debe generarse desde la selección automática normal.

---

## 10.4. Duplicidad

Debe definirse una única cantidad mensual por asignación y período, salvo que el
modelo real permita expresamente múltiples líneas.

La implementación actual sugiere una sola entrada mensual por asignación.

---

# 11. Regla `COMISION` o pago adicional

## 11.1. Significado funcional

Aunque la tabla y el tipo utilicen el nombre `COMISION`, el flujo puede
representar pagos adicionales independientes.

Cada pago puede contener:

- proveedor;
- transportista;
- tarifa;
- cantidad;
- total;
- observación;
- período;
- asignación técnica.

---

## 11.2. Fórmula

```text
total = tarifa × cantidad
```

---

## 11.3. Independencia

Dos pagos con los mismos valores pueden ser operaciones distintas.

No deben fusionarse automáticamente por coincidir en:

- proveedor;
- tarifa;
- cantidad;
- observación;
- período.

---

## 11.4. Asignación técnica

El pago adicional utiliza una asignación de tipo:

```text
COMISION
```

Esa asignación:

- no debe tener zona;
- no debe generarse automáticamente como ruta;
- debe conservar trazabilidad hacia el pago.

---

## 11.5. Riesgo de colisión

Si dos pagos independientes comparten una asignación técnica y el detalle es
único por:

```text
asignacion + año + mes
```

puede producirse una colisión.

Codex debe revisar la estrategia real de creación o reutilización de
asignaciones técnicas.

---

# 12. Regla `CONTENEDOR_AJUSTE`

## 12.1. Propósito

Es una asignación técnica utilizada cuando una novedad necesita crear una línea
que no corresponde a una asignación operativa existente.

---

## 12.2. Restricciones

- no debe generarse automáticamente;
- no debe tratarse como ruta;
- normalmente no requiere zona;
- debe conservar relación con el ajuste que la originó;
- no debe aparecer como una ruta operativa disponible para inasistencias.

---

## 12.3. Reutilización

La posibilidad de reutilizar un contenedor depende de la implementación.

Codex debe verificar si la reutilización puede mezclar:

- proveedores;
- períodos;
- grupos;
- conceptos;
- tipos documentales;
- líneas independientes.

---

# 13. Novedades y ajustes mensuales

Los tipos conocidos son:

```text
INASISTENCIA
FACTURACION
LINEA_ADICIONAL
PAGO_VARIABLE
PAGO_ADICIONAL
REEMPLAZO
```

La lista exacta debe contrastarse con código y datos reales.

Cada tipo posee reglas diferentes.

No debe aplicarse un conjunto genérico de campos a todos los ajustes.

---

# 14. Regla de registro y aplicación de ajustes

El proceso separa:

```text
registrar ajuste
```

de:

```text
aplicar ajuste
```

Registrar un ajuste significa persistir la novedad.

Aplicar un ajuste significa modificar o crear detalles.

Un ajuste registrado pero no aplicado puede dejar el período inconsistente.

Un ajuste aplicado dos veces puede duplicar o distorsionar resultados.

Codex debe revisar idempotencia y reintentos.

---

# 15. Regla `INASISTENCIA`

## 15.1. Alcance

Afecta una asignación específica.

No afecta a toda la zona.

---

## 15.2. Resultado

```text
q_calendario se conserva
q_inasistencia aumenta
cantidad se recalcula
total se recalcula
```

---

## 15.3. Ruta normal

```text
cantidad =
max(0, q_calendario - q_inasistencia)
```

---

## 15.4. OPV

```text
cantidad =
max(0, q_calendario - q_inasistencia)
× puntos_opv
```

---

## 15.5. Restricciones

- no debe sobrescribir el calendario zonal;
- no debe aplicarse a todas las asignaciones del proveedor;
- no debe aplicarse a toda la zona;
- no debe duplicarse en un reintento;
- no debe producir cantidad negativa;
- debe apuntar a una asignación válida.

---

# 16. Regla `FACTURACION`

## 16.1. Propósito

Permite cambiar información de facturación para un período sin modificar la
asignación maestra.

Puede afectar:

- proveedor efectivo;
- transportista efectivo;
- tipo documental;
- grupo;
- otros datos efectivos.

---

## 16.2. Alcance temporal

El cambio corresponde al período indicado.

No debe aplicarse automáticamente a:

- períodos anteriores;
- períodos futuros;
- todas las asignaciones del proveedor;
- el catálogo maestro completo.

---

## 16.3. Consecuencia

Toda salida debe resolver de forma consistente:

```text
proveedor efectivo
tipo documental efectivo
grupo efectivo
```

La pantalla, PDF, ZIP y correo no deben usar identidades distintas.

---

# 17. Regla `LINEA_ADICIONAL`

## 17.1. Propósito

Crear una línea nueva que no existe como detalle base automático.

---

## 17.2. Datos esperados

Puede requerir:

- proveedor;
- transportista;
- concepto;
- código;
- servicio;
- costo;
- cantidad;
- total;
- grupo;
- observación.

La obligatoriedad exacta debe verificarse en el registro de ajustes.

---

## 17.3. Cálculo

```text
total = costo × cantidad
```

salvo regla explícita diferente.

---

## 17.4. Asignación técnica

Puede utilizar:

```text
CONTENEDOR_AJUSTE
```

La línea debe ser independiente de una ruta normal.

---

# 18. Regla `PAGO_VARIABLE`

## 18.1. Propósito

Representar un pago cuyo valor o cantidad se informa específicamente para el
período mediante una novedad.

No debe confundirse con una asignación maestra de tipo `VARIABLE` si la
implementación trata ambos conceptos por separado.

---

## 18.2. Cálculo

Debe utilizar los valores explícitos del ajuste y recalcular total en backend.

---

## 18.3. Independencia

No depende del calendario zonal salvo decisión funcional explícita.

---

# 19. Regla `PAGO_ADICIONAL`

## 19.1. Propósito

Agregar un pago independiente al período.

Puede coexistir con el flujo de `suscripcion_comisiones_mensuales`.

Codex debe comprobar si ambos nombres representan:

- el mismo flujo;
- dos flujos distintos;
- compatibilidad histórica.

---

## 19.2. Independencia

Dos pagos adicionales idénticos pueden ser válidos.

La deduplicación no debe eliminar operaciones reales.

---

# 20. Regla `REEMPLAZO`

## 20.1. Propósito

Representar un cambio operativo o de facturación para una asignación durante un
período.

Puede afectar:

- proveedor;
- transportista;
- datos efectivos;
- presentación de la línea.

---

## 20.2. Alcance

No debe modificar automáticamente el catálogo maestro si el reemplazo es
mensual.

La semántica exacta debe verificarse en los servicios vigentes.

---

# 21. Orden del proceso mensual

El flujo funcional esperado es:

```text
1. seleccionar año y mes
2. configurar calendario zonal
3. informar cantidades variables
4. informar pagos adicionales
5. registrar novedades
6. generar detalles base
7. aplicar ajustes
8. consolidar pre-facturas
9. calcular tributación
10. generar documentos
11. distribuir documentos
```

Cambiar el orden puede alterar resultados.

Ejemplos:

- aplicar inasistencia antes de crear el detalle base puede fallar;
- agrupar antes de resolver proveedor efectivo puede generar un documento
  incorrecto;
- calcular tributación antes de resolver tipo documental puede producir un
  total incorrecto.

---

# 22. Regla de idempotencia

## 22.1. Detalles existentes

La generación actual evita crear un nuevo detalle cuando ya existe:

```text
suscripcion_asignacion_id + anio + mes
```

El registro existente se contabiliza como duplicado y no se recalcula.

---

## 22.2. Consecuencia

Volver a ejecutar la generación:

- no debe duplicar detalles;
- puede no actualizar detalles existentes;
- puede dejar diferencias si el calendario cambió;
- puede dejar diferencias si el costo maestro cambió;
- puede dejar diferencias si los puntos OPV cambiaron;
- puede aplicar nuevamente ajustes si el servicio no es idempotente.

---

## 22.3. Recalcular no es lo mismo que regenerar

La implementación actual no debe asumirse como un motor de recálculo.

Un proceso de recálculo necesitaría reglas explícitas para:

- borrar o actualizar detalles;
- preservar documentos ya emitidos;
- volver a aplicar ajustes;
- evitar duplicidades;
- recalcular tributación;
- conservar auditoría.

---

# 23. Regla de atomicidad

El proceso mensual contiene varias etapas.

No todas están confirmadas dentro de una única transacción.

Puede ocurrir:

```text
calendario guardado
+ pagos guardados
+ generación fallida
```

o:

```text
ajustes registrados
+ aplicación fallida
```

Codex debe considerar estados parciales y reintentos.

No debe proponer una gran transacción sin analizar:

- duración;
- bloqueos;
- generación de archivos;
- correo;
- OneDrive;
- operaciones externas.

---

# 24. Regla de agrupación de pre-facturas

La clave conceptual es:

```text
proveedor efectivo
+ año
+ mes
+ grupo de pre-factura
```

No debe agruparse únicamente por:

```text
proveedor base
```

ni únicamente por:

```text
código
```

ni únicamente por:

```text
RUT
```

---

## 24.1. Grupo vacío o nulo

Debe existir una regla estable para:

```text
grupo_prefactura null
grupo_prefactura ''
grupo_prefactura con espacios
```

Pantalla, PDF, ZIP y correo deben normalizarlo de la misma forma.

---

## 24.2. Proveedor con varios grupos

Un mismo proveedor y período puede producir más de una pre-factura.

El listado general puede mostrar un consolidado, pero la vista individual y los
documentos deben respetar cada grupo.

---

# 25. Regla tributaria

Los tipos documentales funcionales conocidos son:

```text
FACTURA
BOLETA
DOCUMENTO
```

Las tasas descritas a continuación representan el comportamiento actual del
módulo documentado, no una afirmación sobre legislación futura.

---

## 25.1. Factura

Regla actual:

```text
IVA = 19 %
```

El resumen debe calcular el impuesto y el total final según la base utilizada
por el servicio vigente.

Codex debe verificar si los detalles almacenan:

- neto;
- bruto;
- impuesto;
- total final.

---

## 25.2. Boleta

Regla actual:

```text
retención = 15,25 %
```

Fórmula de referencia:

```text
retención = total_bruto × 15,25 %
líquido = total_bruto - retención
```

Debe respetarse la política de redondeo vigente.

---

## 25.3. Documento

Regla actual:

```text
sin IVA
sin retención
líquido = total
```

---

## 25.4. Tipo desconocido

Un tipo documental no reconocido no debe calcularse silenciosamente como otro.

Debe informarse como inconsistencia.

---

# 26. Regla de redondeo

Los valores monetarios deben ser consistentes entre:

- base de datos;
- listado;
- vista individual;
- PDF;
- ZIP;
- correo;
- resumen general.

Codex debe verificar:

- si se redondea por línea;
- si se redondea después de sumar;
- si se usan enteros en pesos;
- si se usa `round`, `floor` o `ceil`;
- si MySQL y PHP producen el mismo resultado;
- si el PDF formatea pero no recalcula.

No debe modificar la política de redondeo sin evidencia funcional.

---

# 27. Regla de totales

## 27.1. Línea

```text
total_linea = costo × cantidad
```

o:

```text
total_linea = tarifa × cantidad
```

según origen.

---

## 27.2. Pre-factura

```text
subtotal = suma de líneas
```

Luego se aplica la regla tributaria correspondiente.

---

## 27.3. Resumen general

El total general debe coincidir con la suma de:

```text
boletas + facturas + documentos
```

Los conteos deben representar documentos agrupados, no necesariamente cantidad
de líneas.

---

# 28. Regla de documentos

## 28.1. PDF

El PDF debe corresponder a:

```text
proveedor efectivo
+ período
+ grupo
```

Debe contener las mismas líneas y totales que la vista individual.

---

## 28.2. ZIP

El ZIP debe contener los PDF del conjunto solicitado.

No debe:

- omitir grupos;
- sobrescribir archivos con el mismo nombre;
- mezclar períodos;
- mezclar modo prueba y real;
- incluir documentos de otro proveedor.

---

## 28.3. Correo

El correo debe adjuntar el PDF correspondiente al destinatario.

Modo prueba:

- utiliza destinatarios de prueba;
- no debe enviar al proveedor real por accidente.

Modo real:

- utiliza el correo efectivo validado;
- debe informar faltantes;
- debe respetar confirmación explícita.

---

## 28.4. OneDrive

La copia subida debe corresponder al ZIP generado.

Un fallo de OneDrive no debe alterar los cálculos de la pre-factura.

La política exacta ante fallo debe verificarse:

- impedir descarga;
- permitir descarga y avisar;
- reintentar;
- registrar error.

---

# 29. Regla de historial

## 29.1. Cambios maestros

Modificar una asignación, proveedor, transportista, zona o puntos después de
generar un período no debe asumirse como actualización automática de ese
período.

---

## 29.2. Documentos históricos

Codex debe comprobar si los documentos históricos leen:

- instantáneas del detalle;
- datos maestros actuales;
- ajustes mensuales;
- una combinación.

Una modificación puede cambiar la representación histórica aunque no cambie el
detalle.

---

## 29.3. Eliminación

No debe eliminarse físicamente un catálogo con historial sin una política
explícita.

---

# 30. Regla de validación backend

El frontend puede ayudar al usuario, pero no es autoritativo.

El backend debe validar:

- período;
- IDs existentes;
- tipos;
- calendario completo;
- fechas esperadas;
- duplicados;
- cantidades;
- costos;
- totales;
- relaciones;
- proveedores efectivos;
- tipos documentales;
- campos obligatorios por ajuste.

Ocultar un campo o deshabilitar un botón no reemplaza la validación backend.

---

# 31. Reglas que nunca deben inferirse

Codex no debe asumir:

- que `codigo` es único;
- que un proveedor tiene una sola zona;
- que toda ruta usa 8 días;
- que todos los meses tienen 8 fechas de fin de semana;
- que todos los sábados y domingos hubo despacho;
- que zona sin despacho equivale a ausencia individual;
- que una asignación con zona depende de calendario;
- que una asignación sin zona es inválida;
- que OPV es un tipo exclusivo;
- que una OPV sin puntos vale 1 punto;
- que volver a generar recalcula;
- que dos pagos iguales son duplicados;
- que proveedor base es proveedor efectivo;
- que todas las pre-facturas de un proveedor comparten grupo;
- que el tipo documental no cambia mensualmente;
- que los datos actuales representan exactamente el historial.

---

# 32. Comportamiento confirmado de referencia

## 32.1. Período

```text
julio de 2026
```

## 32.2. Calendario

```text
8 fechas de sábado y domingo
17 zonas
136 combinaciones
```

## 32.3. Zona 2

```text
Lo Barnechea
sin despacho: 25 y 26 de julio
días con despacho: 6
```

## 32.4. Asignación

```text
AM.01
proveedor: ANDRES FERNANDO MUÑOZ FUENTES
costo: 32.000
```

Resultado:

```text
q_calendario = 6
q_inasistencia = 0
cantidad = 6
total bruto = 192.000
retención 15,25 % = 29.280
líquido = 162.720
```

Este caso confirma la integración:

```text
modal
→ controlador
→ calendario zonal
→ servicio de generación
→ detalle
→ pre-factura
→ PDF
```

No debe tratarse como el único escenario válido.

---

# 33. Escenarios que Codex puede construir libremente

La documentación no limita la auditoría.

Codex puede simular mentalmente, mediante pruebas automatizadas o mediante
consultas no destructivas escenarios como:

- meses con 8, 9 o 10 fechas de sábado y domingo;
- zona completa sin despacho todos los días;
- una sola zona incompleta;
- ruta ausente en un día operativo;
- múltiples inasistencias;
- OPV con cero, uno o varios puntos;
- cambio de puntos después de generar;
- proveedor con varias zonas;
- proveedor con varios grupos;
- proveedor efectivo distinto;
- cambio de tipo documental;
- dos pagos adicionales idénticos;
- reintento después de un fallo intermedio;
- dos solicitudes concurrentes;
- calendario modificado después de generar;
- detalle histórico con costo antiguo;
- asignación técnica incluida por error;
- grupo nulo o con espacios;
- correo faltante;
- fallo de PDF;
- fallo de ZIP;
- fallo de OneDrive;
- diferencias de redondeo;
- datos huérfanos;
- tipos no reconocidos;
- valores negativos;
- duplicados;
- inconsistencias entre pantalla y PDF.

La lista anterior es ilustrativa, no cerrada.

---

# 34. Criterio para reportar un posible bug

Un hallazgo debe indicar:

1. regla de negocio afectada;
2. archivo y método;
3. datos necesarios para reproducirlo;
4. secuencia de acciones;
5. resultado actual;
6. resultado esperado;
7. alcance;
8. severidad;
9. riesgo histórico;
10. evidencia;
11. propuesta mínima;
12. pruebas que demostrarían la corrección.

Cuando el resultado esperado no pueda determinarse, Codex debe clasificarlo
como:

```text
ambigüedad funcional
```

y no como bug confirmado.

---

# 35. Jerarquía para resolver contradicciones

Cuando existan diferencias entre fuentes, Codex debe comparar:

```text
1. comportamiento confirmado por el usuario
2. documentación funcional vigente
3. servicios de dominio actuales
4. controladores y validadores
5. modelos Eloquent
6. migraciones
7. esquema real
8. datos históricos
9. frontend
10. nombres históricos de clases o tablas
```

Esta jerarquía no significa ignorar la base de datos.

Significa que una contradicción debe reportarse y no resolverse únicamente por
el nombre de una columna.

---

# 36. Regla de auditoría sin modificación

Cuando la solicitud sea:

```text
analizar
auditar
revisar
buscar bugs
detectar errores
crear escenarios
evaluar riesgos
```

Codex debe:

- trabajar en solo lectura;
- recorrer el repositorio;
- leer esta documentación;
- inspeccionar rutas;
- inspeccionar migraciones;
- inspeccionar servicios;
- inspeccionar modelos;
- inspeccionar vistas y JavaScript;
- ejecutar únicamente pruebas y comandos no destructivos;
- construir escenarios ficticios;
- entregar un informe.

No debe:

- editar código;
- crear migraciones;
- modificar documentación;
- aplicar correcciones;
- escribir en la base real;
- ejecutar comandos destructivos.

La autorización de auditoría no implica autorización de implementación.

---

# 37. Regla de mantenimiento

Actualizar este documento cuando:

- cambie una fórmula;
- cambie la tasa tributaria configurada;
- se agregue un tipo documental;
- se agregue un tipo de asignación;
- se agregue un tipo de ajuste;
- cambie el calendario;
- cambie la detección OPV;
- cambie la regla de agrupación;
- cambie el proveedor efectivo;
- cambie la idempotencia;
- cambie la política de recálculo;
- cambie la estrategia de redondeo;
- cambie el orden del proceso;
- se confirme una ambigüedad pendiente.

Toda actualización debe marcar si la regla está:

```text
confirmada
inferida
pendiente de verificar
obsoleta
```
