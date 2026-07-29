---
titulo: Ajustes mensuales del módulo Suscripciones
modulo: Suscripciones
tipo_documento: Referencia funcional y técnica para análisis asistido por Codex
estado: Vigente
ultima_actualizacion: 2026-07-29
ubicacion_recomendada: storage/agents/docs/suscripciones/ajustes-mensuales.md
---

# Ajustes mensuales del módulo Suscripciones

## 1. Propósito

Este documento describe la arquitectura, reglas funcionales y riesgos del
submódulo de **ajustes mensuales** de Suscripciones.

Su objetivo es permitir que Codex pueda:

- comprender por qué los ajustes se registran por período;
- distinguir registro de aplicación;
- identificar qué ajustes modifican detalles existentes;
- identificar qué ajustes crean líneas nuevas;
- reconocer qué campos son efectivos solamente durante un mes;
- detectar conflictos, duplicidades o reintentos inseguros;
- construir escenarios ficticios;
- reportar posibles bugs sin modificar código durante una auditoría.

Este documento no define una lista cerrada de pruebas.

Cuando una conducta no esté confirmada por documentación y código, Codex debe
reportarla como ambigüedad funcional.

---

## 2. Alcance

Los ajustes mensuales representan novedades que modifican o complementan la
liquidación de un período sin alterar necesariamente los catálogos maestros.

Los tipos funcionales conocidos son:

```text
INASISTENCIA
FACTURACION
LINEA_ADICIONAL
PAGO_VARIABLE
PAGO_ADICIONAL
REEMPLAZO
```

La lista exacta instalada debe verificarse en:

- validadores;
- servicios;
- migraciones;
- modelo;
- datos históricos;
- JavaScript;
- formularios.

---

## 3. Principio temporal

Un ajuste pertenece a:

```text
asignación + año + mes
```

o a una asignación técnica creada para representar una novedad del período.

El ajuste no debe aplicarse automáticamente a:

- meses anteriores;
- meses posteriores;
- todas las asignaciones del proveedor;
- toda una zona;
- el catálogo maestro completo.

---

## 4. Componentes principales

### Registro

```php
App\Services\Suscripciones\SuscripcionAjusteMensualRegistroService
```

Responsabilidad:

- validar reglas específicas por tipo;
- persistir la novedad;
- crear o reutilizar asignaciones técnicas;
- guardar datos efectivos;
- conservar trazabilidad.

### Aplicación

```php
App\Services\Suscripciones\SuscripcionAjusteMensualAplicacionService
```

Responsabilidad:

- leer ajustes del período;
- modificar detalles existentes;
- crear líneas adicionales;
- recalcular cantidades y totales;
- mantener proveedor o transportista efectivo;
- preservar el calendario zonal cuando corresponda.

### Resolución efectiva

```php
App\Services\Suscripciones\SuscripcionAjusteMensualService
```

Responsabilidad:

- resolver proveedor efectivo;
- resolver transportista efectivo;
- resolver tipo documental efectivo;
- entregar información consistente a filtros, agrupación, PDF y envío.

---

## 5. Separación entre registrar y aplicar

Registrar un ajuste no equivale a modificar un detalle.

Flujo conceptual:

```text
formulario
→ registro del ajuste
→ persistencia en suscripcion_ajustes_mensuales
→ generación de detalles base
→ aplicación del ajuste
→ detalle final
```

Esta separación permite trazabilidad, pero crea riesgos:

- ajuste registrado y no aplicado;
- ajuste aplicado sin registro válido;
- ajuste aplicado dos veces;
- detalle base inexistente;
- fallo después de registrar;
- reintento no idempotente.

Codex debe analizar cada tipo de ajuste bajo estas condiciones.

---

## 6. Tabla principal

```text
suscripcion_ajustes_mensuales
```

Campos funcionales esperados, según el tipo:

```text
id
suscripcion_asignacion_id
anio
mes
tipo_ajuste
q_calendario
q_inasistencia
cantidad
costo
total
suscripcion_proveedor_id efectivo
suscripcion_transportista_id efectivo
tipo_documento efectivo
codigo
servicio
punto
origen
grupo_prefactura
observacion
created_at
updated_at
```

Los nombres exactos, nulabilidad e índices deben verificarse.

---

## 7. Asignaciones operativas y técnicas

Un ajuste puede apuntar a:

- una asignación operativa existente;
- una asignación técnica `CONTENEDOR_AJUSTE`.

Las asignaciones técnicas:

- no representan necesariamente una ruta real;
- no deben generarse automáticamente;
- normalmente no requieren zona;
- existen para soportar líneas creadas por novedades;
- deben conservar trazabilidad hacia el ajuste.

---

## 8. Regla de inasistencia

### Alcance

Afecta una asignación específica.

No afecta:

- toda la zona;
- todas las rutas del proveedor;
- el calendario zonal;
- otras asignaciones con el mismo código.

### Resultado

```text
q_calendario se conserva
q_inasistencia aumenta
cantidad se recalcula
total se recalcula
```

### Ruta normal

```text
cantidad = max(0, q_calendario - q_inasistencia)
```

### OPV

```text
cantidad =
max(0, q_calendario - q_inasistencia)
× puntos_opv
```

### Restricciones

- no sobrescribir el calendario zonal;
- no permitir cantidad negativa;
- no aplicar más de una vez por reintento;
- no usar `codigo` como única identidad;
- no aplicar a una asignación técnica;
- verificar que el detalle base exista;
- verificar que la asignación corresponda al período.

---

## 9. Regla de facturación

### Propósito

Modificar para un período la identidad utilizada en la pre-factura.

Puede afectar:

- proveedor efectivo;
- transportista efectivo;
- tipo documental;
- grupo de pre-factura;
- otros datos efectivos confirmados por el código.

### Regla temporal

No debe modificar directamente el proveedor base de la asignación cuando el
cambio es mensual.

### Efectos posteriores

Debe ser respetado por:

- filtros;
- listado;
- agrupación;
- vista individual;
- PDF;
- ZIP;
- correo;
- destinatarios;
- datos bancarios;
- tributación;
- OC;
- OneDrive.

### Riesgo principal

Que una salida use proveedor base y otra proveedor efectivo.

---

## 10. Regla de línea adicional

### Propósito

Crear una nueva línea en la liquidación del período.

### Datos posibles

```text
proveedor
transportista
concepto
código
servicio
punto
origen
costo
cantidad
total
grupo
observación
```

La obligatoriedad exacta depende del formulario y validadores.

### Cálculo

```text
total = costo × cantidad
```

salvo regla explícita distinta.

### Asignación técnica

Puede utilizar:

```text
CONTENEDOR_AJUSTE
```

### Independencia

Dos líneas con datos similares pueden ser operaciones distintas.

No deben fusionarse únicamente por coincidencia de valores.

---

## 11. Regla de pago variable

### Propósito

Representar un valor o cantidad informada específicamente como novedad del
período.

No debe confundirse automáticamente con:

```text
asignación maestra VARIABLE
```

si ambos flujos están implementados por separado.

### Cálculo

El backend debe validar o recalcular:

```text
total = costo × cantidad
```

### Calendario

No depende del calendario zonal salvo regla explícita confirmada.

---

## 12. Regla de pago adicional

### Propósito

Agregar un pago independiente al período.

Puede coexistir con:

```text
suscripcion_comisiones_mensuales
```

Codex debe verificar si:

- ambos nombres representan el mismo concepto;
- existen dos caminos;
- uno es legado;
- uno crea asignaciones técnicas distintas;
- ambos terminan en detalles equivalentes.

### Independencia

Dos pagos idénticos pueden ser válidos.

No debe deduplicarse por:

```text
proveedor + tarifa + cantidad + observación
```

sin una regla confirmada.

---

## 13. Regla de reemplazo

### Propósito

Representar un cambio operativo o de facturación durante el período.

Puede afectar:

- proveedor;
- transportista;
- datos de presentación;
- identidad efectiva.

### Alcance

No debe cambiar el catálogo maestro si el reemplazo es temporal.

La semántica exacta debe verificarse en servicios y formularios.

---

## 14. Uso de `q_calendario`

Un ajuste puede contener un campo `q_calendario`, pero su uso debe analizarse
con cuidado.

Regla crítica:

```text
INASISTENCIA no debe reemplazar el q_calendario zonal
```

Cuando el ajuste no informa calendario, la aplicación debe usar el del detalle.

Codex debe revisar si el frontend envía un valor no vacío por defecto y si eso
puede sobrescribir el cálculo zonal.

---

## 15. Uso de `q_inasistencia`

Debe representar ausencias individuales.

Reglas:

- entero no negativo;
- no debe superar silenciosamente `q_calendario`;
- no debe afectar otras asignaciones;
- no debe aplicarse dos veces;
- debe recalcular cantidad y total.

---

## 16. Costo, cantidad y total

Los valores definitivos deben validarse en backend.

Regla general:

```text
total = costo × cantidad
```

Codex debe revisar:

- tipos decimal;
- redondeo;
- valores negativos;
- valores cero;
- total recibido desde JavaScript;
- diferencias entre registro y aplicación;
- diferencias entre detalle y ajuste.

---

## 17. Proveedor efectivo

Un ajuste puede cambiar el proveedor para un período.

La resolución debe ser centralizada.

No deben existir fórmulas distintas en:

- controlador;
- listado;
- PDF;
- ZIP;
- correo;
- OneDrive.

La identidad efectiva debe resolverse antes de agrupar documentos.

---

## 18. Transportista efectivo

Puede cambiar por ajuste o reemplazo.

Debe mostrarse de forma coherente en:

- detalle;
- pre-factura;
- PDF;
- exportaciones;
- filtros si corresponde.

No debe alterar el transportista maestro para otros períodos.

---

## 19. Tipo documental efectivo

Puede ser:

```text
FACTURA
BOLETA
DOCUMENTO
```

Un cambio mensual debe provocar recálculo tributario coherente.

No basta con cambiar una etiqueta visual.

Debe afectar:

- impuesto;
- retención;
- líquido;
- textos;
- agrupación si corresponde;
- destinatario si depende del proveedor efectivo.

---

## 20. Grupo de pre-factura efectivo

Un ajuste puede influir en el grupo.

La clave conceptual es:

```text
proveedor efectivo + año + mes + grupo
```

El grupo debe normalizarse igual en:

- vista;
- PDF;
- ZIP;
- correo;
- nombre de archivo.

---

## 21. Aplicación sobre detalle existente

Para ajustes que modifican una línea base:

1. localizar por `suscripcion_asignacion_id`;
2. limitar por año y mes;
3. cargar relaciones necesarias;
4. aplicar solamente campos permitidos por tipo;
5. recalcular cantidad;
6. recalcular total;
7. persistir una sola vez.

No debe localizarse únicamente por código.

---

## 22. Creación de detalle nuevo

Para una línea adicional:

1. resolver o crear asignación técnica;
2. conservar el período;
3. validar proveedor y transportista;
4. definir costo y cantidad;
5. recalcular total;
6. crear o actualizar el detalle correcto;
7. evitar colisión con otra línea independiente.

---

## 23. Idempotencia

La aplicación debe ser segura ante reintentos.

Codex debe revisar si:

- usa `updateOrCreate`;
- suma valores nuevamente;
- reemplaza valores;
- marca ajustes como aplicados;
- consulta estado;
- recrea contenedores;
- duplica líneas.

Un ajuste aplicado dos veces no debe duplicar el efecto.

---

## 24. Orden de ajustes

Cuando una asignación tiene múltiples ajustes, el orden puede cambiar el
resultado.

Ejemplos:

```text
FACTURACION + INASISTENCIA
REEMPLAZO + LINEA_ADICIONAL
PAGO_VARIABLE + FACTURACION
```

Codex debe verificar:

- orden de consulta;
- orden de aplicación;
- prioridad explícita;
- comportamiento cuando hay dos ajustes incompatibles;
- restricción única de la tabla.

---

## 25. Restricción única

Debe inspeccionarse la clave única de:

```text
suscripcion_ajustes_mensuales
```

Riesgos:

- una clave que no incluya `tipo_ajuste` impide múltiples tipos;
- una clave demasiado permisiva permite duplicados;
- una clave basada en código pierde identidad;
- una clave que incluye demasiados campos impide idempotencia.

---

## 26. Validación por tipo

El backend debe definir campos obligatorios específicos.

Ejemplo conceptual:

### Inasistencia

```text
asignación
período
cantidad de inasistencia
```

### Facturación

```text
asignación
período
proveedor efectivo y/o documento efectivo
```

### Línea adicional

```text
concepto
costo
cantidad
proveedor
```

No todos los campos deben ser obligatorios para todos los tipos.

---

## 27. Compatibilidad histórica

Pueden existir:

- tipos anteriores;
- nombres antiguos;
- columnas heredadas;
- ajustes creados antes de zonas;
- asignaciones técnicas antiguas;
- valores nulos.

Codex no debe “normalizar” datos históricos sin autorización.

---

## 28. Relación con calendario zonal

Los ajustes no sustituyen la matriz zonal.

La secuencia correcta es:

```text
calendario zonal
→ detalle base
→ ajuste individual
```

No:

```text
ajuste individual
→ calendario de toda la zona
```

---

## 29. Relación con generación mensual

El controlador:

1. guarda calendario y entradas mensuales;
2. registra ajustes;
3. genera detalles;
4. aplica ajustes.

Esto puede dejar estados parciales.

Codex debe analizar qué ocurre si falla cada paso.

---

## 30. Relación con pre-facturas

Los ajustes pueden cambiar:

- proveedor;
- transportista;
- documento;
- cantidad;
- costo;
- total;
- grupo;
- líneas.

Por ello, la agrupación y tributación deben ocurrir después de resolverlos.

---

## 31. Escenarios que Codex puede construir

- una inasistencia;
- varias inasistencias;
- inasistencia mayor al calendario;
- ajuste repetido;
- ajuste aplicado dos veces;
- detalle base ausente;
- ajuste de otro período;
- proveedor efectivo inexistente;
- dos cambios de facturación;
- cambio de documento;
- línea adicional con total incorrecto;
- dos líneas adicionales idénticas;
- contenedor técnico reutilizado;
- pago variable con cantidad cero;
- reemplazo más inasistencia;
- grupo nulo;
- asignación eliminada;
- ajuste registrado y aplicación fallida;
- generación reintentada;
- dos usuarios registrando a la vez;
- OPV con inasistencia;
- fijo mensual con ajuste;
- tipo desconocido;
- datos históricos nulos.

La lista no limita el análisis.

---

## 32. Posibles bugs a detectar

- inasistencia sobrescribe `q_calendario`;
- OPV recalculada como ruta normal;
- proveedor efectivo ignorado en PDF;
- ajuste aplicado a otra asignación por código repetido;
- ajuste duplicado;
- línea adicional fusionada;
- detalle técnico generado automáticamente;
- total confiado al cliente;
- tipo documental cambia sin recalcular impuesto;
- reintento suma dos veces;
- ajuste de otro período aplicado;
- restricción única incompatible;
- asignación técnica compartida entre líneas independientes;
- filtro usa proveedor base;
- correo usa proveedor distinto al PDF;
- observación perdida;
- ajuste registrado fuera de transacción;
- orden de aplicación no determinista.

---

## 33. Consultas de diagnóstico no destructivas

### Tipos existentes

```sql
SELECT
    tipo_ajuste,
    COUNT(*) AS cantidad
FROM suscripcion_ajustes_mensuales
GROUP BY tipo_ajuste
ORDER BY tipo_ajuste;
```

### Posibles duplicados

```sql
SELECT
    suscripcion_asignacion_id,
    anio,
    mes,
    tipo_ajuste,
    COUNT(*) AS cantidad
FROM suscripcion_ajustes_mensuales
GROUP BY
    suscripcion_asignacion_id,
    anio,
    mes,
    tipo_ajuste
HAVING COUNT(*) > 1;
```

### Ajustes sin asignación válida

```sql
SELECT aj.*
FROM suscripcion_ajustes_mensuales aj
LEFT JOIN suscripcion_asignaciones sa
    ON sa.id = aj.suscripcion_asignacion_id
WHERE sa.id IS NULL;
```

Los nombres exactos deben ajustarse al esquema real.

---

## 34. Auditoría en solo lectura

Cuando la tarea sea revisar ajustes, Codex debe:

- inspeccionar validadores;
- inspeccionar JavaScript;
- inspeccionar registro;
- inspeccionar aplicación;
- inspeccionar resolución efectiva;
- inspeccionar índices;
- construir escenarios;
- entregar hallazgos.

No debe:

- crear ajustes reales;
- aplicar ajustes;
- editar detalles;
- crear asignaciones técnicas;
- modificar código;
- ejecutar migraciones.

---

## 35. Regla de mantenimiento

Actualizar este documento cuando:

- se agregue un tipo;
- cambien campos obligatorios;
- cambie la prioridad;
- cambie la asignación técnica;
- cambie la idempotencia;
- cambie proveedor efectivo;
- cambie aplicación OPV;
- cambie la restricción única;
- cambie la frontera transaccional.
