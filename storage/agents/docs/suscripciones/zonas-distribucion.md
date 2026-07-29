---
titulo: Zonas de distribución del módulo Suscripciones
modulo: Suscripciones
tipo_documento: Referencia funcional y técnica para análisis asistido por Codex
estado: Vigente
ultima_actualizacion: 2026-07-29
ubicacion_recomendada: storage/agents/docs/suscripciones/zonas-distribucion.md
---

# Zonas de distribución del módulo Suscripciones

## 1. Propósito

Este documento describe la funcionalidad de zonas de distribución y calendario
operativo del módulo **Suscripciones**.

Su objetivo es permitir que Codex pueda:

- comprender la relación entre zona y asignación;
- distinguir suspensión zonal de inasistencia individual;
- analizar la matriz mensual de fechas;
- revisar validaciones, persistencia y cálculo;
- construir escenarios ficticios;
- detectar posibles bugs sin modificar código durante una auditoría.

---

## 2. Entidades principales

```text
suscripcion_zonas
suscripcion_asignaciones
suscripcion_zona_dias_operativos
suscripcion_liquidacion_detalles
```

Modelos:

```php
SuscripcionZona
Asignaciones
SuscripcionZonaDiaOperativo
SuscripcionLiquidacionDetalle
```

---

## 3. Catálogo de zonas

Tabla:

```text
suscripcion_zonas
```

Campos confirmados:

```text
id
numero_zona
despacho
activo
created_at
updated_at
```

Restricciones:

```text
UNIQUE(numero_zona)
INDEX(activo)
```

---

## 4. Catálogo inicial confirmado

| Número | Despacho |
|---:|---|
| 1 | Vitacura |
| 2 | Lo Barnechea |
| 3 | Las Condes |
| 4 | Providencia |
| 5 | La Reina |
| 6 | Ñuñoa |
| 7 | Peñalolén |
| 8 | Plaza Oeste 5 |
| 9 | Centro |
| 10 | San Miguel |
| 11 | Macul |
| 12 | Bellavista 1 |
| 13 | Plaza Oeste 2 |
| 14 | Plaza Oeste 1 |
| 15 | Bellavista 2 |
| 16 | Plaza Oeste 3 |
| 17 | Plaza Oeste 4 |

Este catálogo puede crecer o cambiar.

No debe asumirse que siempre existirán exactamente 17 zonas.

---

## 5. Modelo `SuscripcionZona`

Relaciones:

```php
public function asignaciones(): HasMany
```

```php
public function diasOperativos(): HasMany
```

Casts:

```text
numero_zona → integer
activo → boolean
```

La desactivación no debe borrar historial.

---

## 6. Relación con asignaciones

Campo:

```text
suscripcion_asignaciones.suscripcion_zona_id
```

Clave foránea:

```text
suscripcion_zonas.id
```

Política confirmada:

```text
restrictOnDelete()
```

La zona pertenece a la asignación.

No pertenece directamente al proveedor.

---

## 7. Nulabilidad de zona

`suscripcion_zona_id` es nullable.

Esto es válido para tipos que no dependen de calendario:

```text
VARIABLE
COMISION
CONTENEDOR_AJUSTE
```

Una ruta automática normal sin zona es inválida funcionalmente.

Un fijo mensual puede tener zona por historial, pero no depende de ella.

---

## 8. Asignaciones con zona cargada

Resultado inicial confirmado:

```text
429 asignaciones totales
170 con zona
165 RUTA
4 FIJO_MENSUAL
1 VARIABLE
0 COMISION con zona
0 CONTENEDOR_AJUSTE con zona
```

Este snapshot no es una invariante.

---

## 9. Poblamiento inicial

La asignación de zona se realizó utilizando una equivalencia entre:

```text
punto_1
```

y:

```text
zona
```

Mapeo confirmado:

```text
VITACURA → 1
LA DEHESA → 2
LAS CONDES → 3
PROVIDENCIA → 4
GALES → 5
ÑUÑOA → 6
PEÑALOLEN → 7
PLAZA OESTE 5 → 8
CENTRO → 9
SAN MIGUEL → 10
MACUL → 11
BELLAVISTA 1 → 12
PLAZA OESTE 2 → 13
PLAZA OESTE 1 → 14
BELLAVISTA 2 → 15
PLAZA OESTE 3 → 16
PLAZA OESTE 4 → 17
```

Se creó un respaldo:

```text
suscripcion_asignaciones_backup_zonas_20260728
```

Codex no debe depender de esa tabla para pruebas automáticas.

---

## 10. Calendario zonal

Tabla:

```text
suscripcion_zona_dias_operativos
```

Campos:

```text
id
suscripcion_zona_id
fecha
hubo_despacho
observacion
created_at
updated_at
```

---

## 11. Restricciones del calendario

```text
UNIQUE(suscripcion_zona_id, fecha)
INDEX(fecha)
FOREIGN KEY suscripcion_zona_id
RESTRICT ON DELETE
```

La clave lógica es:

```text
zona + fecha
```

---

## 12. Fechas consideradas

La implementación actual utiliza:

```text
todos los sábados y domingos del mes
```

No incluye automáticamente días hábiles ni festivos de lunes a viernes.

El nombre “fines de semana” puede referirse en la interfaz a días individuales
de sábado y domingo.

---

## 13. Semántica de `hubo_despacho`

```text
1 = la zona operó
0 = la zona completa no operó
```

No representa asistencia individual.

---

## 14. Diferencia crítica

### Zona sin despacho

```text
Toda la zona no trabajó.
```

Consecuencia:

```text
reduce q_calendario de todas las rutas de la zona
q_inasistencia no cambia
```

### Inasistencia individual

```text
La zona trabajó, pero una ruta no asistió.
```

Consecuencia:

```text
q_calendario se mantiene
q_inasistencia aumenta
```

Estas reglas nunca deben mezclarse.

---

## 15. Ejemplo funcional

Mes con 8 fechas de sábado y domingo.

Zona 2 sin despacho en 2 fechas:

```text
q_calendario = 6
```

Para una ruta sin inasistencias:

```text
q_inasistencia = 0
cantidad = 6
```

Para una ruta con una inasistencia individual:

```text
q_calendario = 6
q_inasistencia = 1
cantidad = 5
```

---

## 16. Aplicación por tipo de asignación

| Tipo | Usa calendario zonal | Regla |
|---|---:|---|
| `RUTA` normal | Sí | días con despacho menos inasistencias |
| OPV | Sí | días efectivos × puntos |
| `FIJO_MENSUAL` | No | cantidad 1 |
| `VARIABLE` | No | cantidad informada |
| `COMISION` | No | tarifa × cantidad |
| `CONTENEDOR_AJUSTE` | No | ajuste explícito |

---

## 17. Matriz mensual

La vista debe construir:

```text
zonas activas × fechas del período
```

Ejemplo:

```text
17 zonas × 8 fechas = 136 combinaciones
```

Cada combinación contiene:

```text
zona
fecha
hubo_despacho
observacion
```

---

## 18. Valores por defecto

Cuando una fila aún no existe:

```text
hubo_despacho = 1
```

Esto significa que inicialmente el día aparece marcado como operativo.

El usuario puede desmarcarlo.

---

## 19. Modal de configuración

Vista:

```text
resources/views/suscripciones/comisiones_mensuales/partials/modal-zonas-distribucion.blade.php
```

Características:

- muestra el período;
- agrupa fechas;
- muestra todas las zonas;
- incluye inputs ocultos de zona y fecha;
- envía `0` cuando un checkbox está desmarcado;
- envía `1` cuando está marcado;
- incluye observación;
- vive dentro del formulario principal.

El botón “Aplicar selección” solo cierra el modal.

No guarda por sí mismo.

---

## 20. Patrón de checkbox

Cada fecha utiliza:

```text
hidden hubo_despacho = 0
checkbox hubo_despacho = 1
```

Esto asegura que el backend reciba también los días desmarcados.

Codex debe revisar que ambos inputs usen índices correctos.

---

## 21. Persistencia

El controlador utiliza `updateOrCreate` por:

```text
suscripcion_zona_id + fecha
```

Esto permite:

- crear la fila;
- modificar `hubo_despacho`;
- modificar observación.

No debe crear duplicados.

---

## 22. Validación exacta

El backend debe comparar:

```text
combinaciones esperadas
```

contra:

```text
combinaciones recibidas
```

Debe rechazar:

- faltantes;
- duplicados;
- zonas extras;
- fechas extras;
- fechas de otro mes;
- fechas no válidas;
- zona inexistente.

---

## 23. Servicio de generación

El servicio carga asignaciones con relación:

```text
zona
```

Luego consulta el calendario y agrupa por zona:

```text
fechas_configuradas
dias_con_despacho
```

Antes de crear detalles valida que las zonas usadas tengan todas las fechas.

---

## 24. Ruta normal

```text
q_calendario = días con despacho
q_inasistencia = 0 al generar
cantidad = q_calendario
```

Después de ajustes:

```text
cantidad = max(0, q_calendario - q_inasistencia)
```

---

## 25. OPV

```text
q_calendario = días con despacho
cantidad = q_calendario × puntos
```

Después de ajustes:

```text
cantidad =
max(0, q_calendario - q_inasistencia)
× puntos
```

Una OPV sin puntos se omite.

---

## 26. Fijo mensual

Aunque tenga zona:

```text
q_calendario = 1
cantidad = 1
```

No debe reducirse por días sin despacho.

---

## 27. Calendario incompleto

La ausencia de filas no debe interpretarse como:

```text
día operativo
```

ni como:

```text
día sin despacho
```

Debe generar error de configuración.

---

## 28. Zona inactiva

La vista usa zonas activas.

Una asignación histórica puede seguir apuntando a una zona inactiva.

Codex debe analizar:

- si puede generarse;
- si el calendario la incluye;
- si el servicio la considera incompleta;
- si debe impedirse desactivar una zona con rutas activas.

No debe asumir la respuesta sin revisar la regla vigente.

---

## 29. Cambio de período

La matriz se genera en servidor.

Si el usuario cambia año o mes sin recargar:

```text
puede enviar fechas del período anterior
```

La validación backend debe impedirlo.

La interfaz debería garantizar:

```text
período visible = período de la matriz
```

---

## 30. Cambios después de generar

Modificar el calendario después de generar no recalcula automáticamente
detalles existentes.

La generación actual omite detalles ya existentes.

Esto puede producir:

```text
calendario actualizado
detalle antiguo
PDF antiguo o inconsistente
```

Codex debe reportar el riesgo sin asumir que debe recalcularse automáticamente.

---

## 31. Inasistencia posterior

Cuando se registra una inasistencia:

```text
q_calendario debe conservar el valor zonal
```

El ajuste solo debe modificar:

```text
q_inasistencia
cantidad
total
```

---

## 32. Comportamiento confirmado de julio de 2026

Período:

```text
julio 2026
```

Fechas:

```text
8 sábados y domingos
```

Matriz:

```text
17 zonas × 8 fechas = 136 filas
```

Zona 2 — Lo Barnechea:

```text
25/07/2026 = sin despacho
26/07/2026 = sin despacho
6 días con despacho
2 días sin despacho
```

Asignación AM.01:

```text
q_calendario = 6
q_inasistencia = 0
cantidad = 6
costo = 32.000
total bruto = 192.000
```

Este caso confirma que el calendario zonal llegó hasta la pre-factura.

---

## 33. Consultas de diagnóstico no destructivas

### Rutas automáticas sin zona

```sql
SELECT
    id,
    codigo,
    tipo_asignacion
FROM suscripcion_asignaciones
WHERE tipo_asignacion = 'RUTA'
  AND COALESCE(generar_automaticamente, 1) = 1
  AND suscripcion_zona_id IS NULL;
```

### Duplicados de zona-fecha

```sql
SELECT
    suscripcion_zona_id,
    fecha,
    COUNT(*) AS cantidad
FROM suscripcion_zona_dias_operativos
GROUP BY suscripcion_zona_id, fecha
HAVING COUNT(*) > 1;
```

### Resumen de un período

```sql
SELECT
    sz.numero_zona,
    sz.despacho,
    COUNT(szdo.id) AS fechas_configuradas,
    SUM(szdo.hubo_despacho = 1) AS dias_con_despacho,
    SUM(szdo.hubo_despacho = 0) AS dias_sin_despacho
FROM suscripcion_zonas sz
LEFT JOIN suscripcion_zona_dias_operativos szdo
    ON szdo.suscripcion_zona_id = sz.id
   AND szdo.fecha BETWEEN :inicio AND :fin
GROUP BY
    sz.id,
    sz.numero_zona,
    sz.despacho
ORDER BY sz.numero_zona;
```

---

## 34. Escenarios que Codex puede analizar

- mes con 8 fechas;
- mes con 9 fechas;
- mes con 10 fechas;
- todas las zonas operativas;
- una zona completamente detenida;
- una fecha sin despacho en todas las zonas;
- calendario parcial;
- zona duplicada;
- fecha duplicada;
- fecha fuera del mes;
- ruta sin zona;
- zona inactiva con ruta activa;
- fijo con zona;
- OPV con cero puntos;
- OPV con varios puntos;
- una inasistencia individual;
- varias inasistencias;
- cambio de calendario después de generar;
- dos usuarios guardando a la vez;
- cambio de zona de una asignación histórica;
- observación vacía;
- observación de 500 caracteres;
- nombres de zonas repetidos;
- renumeración de zona;
- desactivación de zona;
- borrado restringido.

La lista es ilustrativa.

---

## 35. Posibles bugs a detectar

Codex debe buscar, sin limitarse a estos casos:

- conteo incorrecto de sábados o domingos;
- uso de zona del proveedor en vez de la asignación;
- calendario incompleto aceptado;
- fecha desmarcada no enviada;
- checkbox duplicado;
- observación asignada a otra fecha;
- error de índices Blade;
- rutas de otra zona afectadas;
- FIJO_MENSUAL reducido por calendario;
- VARIABLE afectada por zona;
- COMISION con zona;
- OPV calculada como ruta simple;
- inasistencia que cambia `q_calendario`;
- detalles existentes no coherentes con calendario nuevo;
- zona inactiva omitida silenciosamente;
- concurrencia sin índice único;
- fechas con zona horaria incorrecta.

---

## 36. Auditoría en solo lectura

Cuando la tarea sea revisar esta funcionalidad, Codex debe:

- inspeccionar modelos;
- inspeccionar migraciones;
- inspeccionar controlador;
- inspeccionar Blade;
- inspeccionar JavaScript;
- inspeccionar servicio de generación;
- inspeccionar servicio de ajustes;
- revisar índices;
- construir escenarios ficticios;
- entregar hallazgos.

No debe:

- modificar código;
- guardar calendarios reales;
- regenerar meses reales;
- borrar detalles;
- cambiar zonas;
- ejecutar migraciones.

---

## 37. Regla de mantenimiento

Actualizar este documento cuando cambie:

- catálogo de zonas;
- relación con asignaciones;
- días considerados;
- estructura de la matriz;
- validación;
- persistencia;
- fórmula de ruta;
- fórmula OPV;
- comportamiento de zonas inactivas;
- política de recálculo;
- sincronización de período.
