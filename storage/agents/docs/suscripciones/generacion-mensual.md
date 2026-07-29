---
titulo: Generación mensual del módulo Suscripciones
modulo: Suscripciones
tipo_documento: Referencia funcional y técnica para análisis asistido por Codex
estado: Vigente
ultima_actualizacion: 2026-07-29
ubicacion_recomendada: storage/agents/docs/suscripciones/generacion-mensual.md
---

# Generación mensual del módulo Suscripciones

## 1. Propósito

Este documento describe el flujo completo de generación mensual del módulo
**Suscripciones**.

Su objetivo es permitir que Codex pueda:

- comprender el orden exacto de ejecución;
- identificar qué datos intervienen en cada etapa;
- distinguir datos maestros, datos mensuales y resultados;
- detectar estados parciales;
- construir escenarios ficticios;
- analizar idempotencia, duplicidad, atomicidad y consistencia;
- reportar posibles errores sin modificar código durante una auditoría.

Este documento no define una lista cerrada de pruebas.

---

## 2. Resultado esperado del proceso

La generación mensual transforma:

```text
asignaciones
+ calendario zonal
+ cantidades variables
+ pagos adicionales
+ ajustes mensuales
```

en:

```text
suscripcion_liquidacion_detalles
```

y posteriormente permite construir:

```text
pre-facturas
PDF
ZIP
correo
OneDrive
```

---

## 3. Identidad del período

El período se representa mediante:

```text
anio + mes
```

Reglas:

- `mes` debe estar entre 1 y 12;
- todos los datos recibidos deben pertenecer al mismo período;
- el calendario zonal debe corresponder exactamente al período enviado;
- cantidades, pagos y ajustes de otro período no deben mezclarse;
- la generación no debe crear detalles fuera del período solicitado.

---

## 4. Punto de entrada principal

Controlador:

```php
App\Http\Controllers\SuscripcionComisionMensualController
```

Métodos principales:

```php
create(Request $request)
store(...)
```

El método `create()` prepara el formulario.

El método `store()` valida, persiste datos mensuales, registra ajustes, ejecuta
la generación y aplica los ajustes.

---

## 5. Preparación del formulario mensual

El método `create()` debe:

1. obtener año y mes;
2. cargar zonas activas;
3. calcular sábados y domingos del mes;
4. recuperar calendario zonal existente;
5. construir la matriz zona-fecha;
6. cargar asignaciones variables;
7. cargar asignaciones para novedades;
8. cargar asignaciones fijas;
9. cargar conceptos de pago variable;
10. entregar los datos a la vista.

Variables principales:

```text
anio
mes
zonas
fechasFinSemana
calendarioZonas
calendarioZonasConfigurado
calendarioZonasIncompleto
proveedores
transportistas
asignacionesCantidadMensual
asignacionesAjustesMensuales
asignacionesFijasMensuales
conceptosPagoVariable
```

---

## 6. Datos enviados por el formulario

El formulario mensual puede enviar:

```text
anio
mes
zonas_operativas
cantidades_mensuales
pagos_adicionales
ajustes_mensuales
```

La ausencia de cantidades, pagos o novedades es válida.

La ausencia del calendario completo no es válida cuando existen rutas
calendarizadas.

---

## 7. Validación del calendario zonal

La matriz debe representar exactamente:

```text
zonas activas × fechas de sábado y domingo
```

Cada fila contiene:

```text
suscripcion_zona_id
fecha
hubo_despacho
observacion
```

La validación debe detectar:

- zonas inexistentes;
- zonas inactivas;
- fechas fuera del período;
- fechas que no corresponden a sábado o domingo;
- combinaciones duplicadas;
- combinaciones faltantes;
- combinaciones sobrantes;
- matriz construida para otro mes.

El método privado esperado es:

```php
private function validarCalendarioZonas(
    Collection $zonasOperativas,
    int $anio,
    int $mes
): void
```

---

## 8. Persistencia inicial

Dentro del bloque transaccional confirmado se guardan:

```text
calendario zonal
cantidades variables
pagos adicionales
asignaciones técnicas de comisión
```

El calendario se guarda mediante una operación equivalente a:

```php
updateOrCreate(
    [
        'suscripcion_zona_id' => ...,
        'fecha' => ...,
    ],
    [
        'hubo_despacho' => ...,
        'observacion' => ...,
    ]
)
```

Esto permite volver a guardar la configuración de una misma zona y fecha.

---

## 9. Frontera transaccional

El flujo completo no está confirmado dentro de una sola transacción.

Después del bloque transaccional se ejecutan:

```text
registro de ajustes
generación mensual
aplicación de ajustes
```

Por ello, un fallo puede dejar:

```text
calendario guardado
cantidades guardadas
pagos guardados
ajustes parciales
detalles parciales
```

Codex debe analizar reintentos, duplicidad y consistencia por etapa.

---

## 10. Servicio principal de generación

Clase:

```php
App\Services\Suscripciones\SuscripcionGeneracionMensualService
```

Método principal:

```php
generar(int $anio, int $mes): array
```

Responsabilidades:

- seleccionar asignaciones automáticas;
- excluir asignaciones técnicas;
- cargar relaciones necesarias;
- construir calendario zonal;
- validar zonas usadas;
- crear detalles automáticos;
- crear detalles variables;
- crear detalles de pagos adicionales;
- evitar duplicados;
- devolver resumen de ejecución.

---

## 11. Selección de asignaciones automáticas

Se incluyen asignaciones con:

```text
generar_automaticamente = 1
```

o:

```text
generar_automaticamente IS NULL
```

Se excluyen:

```text
VARIABLE
COMISION
CONTENEDOR_AJUSTE
```

Relaciones precargadas:

```text
transportista
suscripcionProveedor.cobranzaCompra
opvPuntos
zona
```

La generación no debe realizar consultas innecesarias por cada asignación.

---

## 12. Construcción del calendario

El servicio calcula todas las fechas de sábado y domingo del período.

Luego consulta:

```text
suscripcion_zona_dias_operativos
```

y resume por zona:

```text
fechas_configuradas
dias_con_despacho
```

El servicio debe usar el calendario persistido, no el estado visual del modal.

---

## 13. Asignaciones que requieren calendario

Requieren calendario:

```text
RUTA normal
OPV con puntos configurados
```

No requieren calendario para su cantidad:

```text
FIJO_MENSUAL
VARIABLE
COMISION
CONTENEDOR_AJUSTE
```

Una ruta automática sin zona debe impedir la generación.

Una OPV sin puntos debe omitirse y reportarse.

---

## 14. Validación previa a crear detalles

Antes de insertar cualquier detalle automático, el servicio debe detectar:

- asignaciones calendarizadas sin zona;
- zonas usadas sin todas las fechas;
- calendarios incompletos;
- configuración incompatible con el período.

La validación debe ocurrir antes de crear detalles automáticos.

Ante error, se lanza:

```php
ValidationException
```

---

## 15. Identidad de detalle mensual

La identidad lógica es:

```text
suscripcion_asignacion_id + anio + mes
```

Antes de crear, el servicio verifica si ya existe.

Cuando existe:

```text
no actualiza
no recalcula
lo cuenta como duplicado
continúa
```

Esto evita duplicación, pero no implementa recálculo automático.

---

## 16. Generación de ruta normal

Fórmula:

```text
q_calendario = días con despacho de la zona
q_inasistencia = 0 inicialmente
cantidad = max(0, q_calendario - q_inasistencia)
total = costo × cantidad
```

Después, un ajuste puede aumentar `q_inasistencia`.

---

## 17. Generación OPV

OPV puede estar almacenada como `RUTA`.

Fórmula:

```text
dias_efectivos = max(0, q_calendario - q_inasistencia)
cantidad = dias_efectivos × puntos_opv
total = costo × cantidad
```

Una OPV sin puntos:

```text
no se genera
se contabiliza como omitida
```

No debe tratarse como ruta normal de un punto.

---

## 18. Generación `FIJO_MENSUAL`

Fórmula:

```text
q_calendario = 1
q_inasistencia = 0
cantidad = 1
total = costo
```

No depende del calendario zonal.

---

## 19. Generación `VARIABLE`

Fuente:

```text
suscripcion_cantidades_mensuales
```

Regla:

```text
cantidad = valor informado
total = costo × cantidad
```

El detalle se crea con:

```text
q_calendario = 1
q_inasistencia = 0
```

No depende de zona ni de fines de semana.

---

## 20. Generación de pagos adicionales

Fuente:

```text
suscripcion_comisiones_mensuales
```

Cada registro contiene:

```text
tarifa
cantidad
total
observacion
asignación técnica
```

El detalle se crea con:

```text
q_calendario = 1
q_inasistencia = 0
cantidad = cantidad informada
costo = tarifa
total = tarifa × cantidad
```

Dos pagos idénticos pueden ser operaciones distintas.

---

## 21. Método interno de cálculo

Firma vigente:

```php
private function calcularDetalleMensual(
    Asignaciones $asignacion,
    int $qCalendario,
    int $inasistencias = 0
): array
```

El método recibe el calendario ya calculado.

No debe:

- volver a contar fines de semana;
- consultar otro período;
- inferir zona;
- reemplazar ajustes.

Debe devolver:

```text
costo
q_calendario
q_inasistencia
cantidad
total
```

---

## 22. Registro de ajustes

Servicio:

```php
SuscripcionAjusteMensualRegistroService
```

Responsabilidades:

- persistir novedades;
- crear o reutilizar asignaciones técnicas;
- guardar datos efectivos;
- conservar el período;
- mantener trazabilidad.

Registrar no equivale a aplicar.

---

## 23. Aplicación de ajustes

Servicio:

```php
SuscripcionAjusteMensualAplicacionService
```

Método principal:

```php
aplicarPeriodo(int $anio, int $mes)
```

Puede:

- actualizar detalles existentes;
- crear líneas adicionales;
- modificar inasistencias;
- modificar cantidad;
- modificar costo;
- modificar total;
- resolver proveedor o transportista efectivo.

---

## 24. Aplicación de inasistencia

La inasistencia individual:

```text
mantiene q_calendario
aumenta q_inasistencia
recalcula cantidad
recalcula total
```

Ruta normal:

```text
cantidad = max(0, q_calendario - q_inasistencia)
```

OPV:

```text
cantidad =
max(0, q_calendario - q_inasistencia)
× puntos_opv
```

No debe alterar el calendario de toda la zona.

---

## 25. Orden funcional obligatorio

El orden esperado es:

```text
1. validar período
2. validar calendario
3. guardar calendario
4. guardar cantidades variables
5. guardar pagos adicionales
6. registrar ajustes
7. generar detalles base
8. aplicar ajustes
9. consolidar pre-facturas
```

Cambiar el orden puede producir:

- ajustes sin detalle;
- detalles sin calendario;
- proveedor efectivo ignorado;
- total desactualizado;
- duplicidad.

---

## 26. Resultado devuelto por generación

El servicio devuelve un resumen similar a:

```text
creados
duplicados
variables_creadas
variables_duplicadas
comisiones_creadas
comisiones_duplicadas
opv_sin_puntos
```

Codex debe verificar los nombres exactos.

El mensaje mostrado al usuario debe reflejar el resultado real.

---

## 27. Idempotencia

La generación es parcialmente idempotente:

```text
evita nuevos duplicados por asignación y período
```

Pero no garantiza:

```text
recalcular detalles existentes
volver a aplicar ajustes de forma segura
revertir estados parciales
actualizar puntos OPV antiguos
actualizar costos históricos
```

---

## 28. Escenarios de fallo parcial

Posibles secuencias:

```text
calendario guardado
→ cantidad variable guardada
→ pago guardado
→ ajuste falla
```

```text
ajustes registrados
→ generación falla
```

```text
detalles creados
→ aplicación de ajustes falla
```

```text
generación completada
→ redirección falla
```

Codex debe analizar qué ocurre al reintentar cada escenario.

---

## 29. Concurrencia

Dos solicitudes simultáneas pueden:

1. comprobar que no existe detalle;
2. insertar ambas;
3. crear duplicidad si no hay índice único.

La consulta `exists()` no sustituye una restricción SQL.

Codex debe verificar índices reales.

---

## 30. Historial

Cambiar después de generar:

- zona;
- calendario;
- costo;
- puntos OPV;
- proveedor;
- grupo;
- tipo documental;

no implica que el detalle se actualice automáticamente.

El sistema debe distinguir:

```text
generar
regenerar
recalcular
editar
aplicar ajuste
```

---

## 31. Sincronización de período en interfaz

La matriz se construye para el período cargado en servidor.

Si el usuario cambia año o mes sin recargar, puede enviar:

```text
período nuevo
+ fechas del período anterior
```

La validación debe rechazarlo.

La interfaz debe mantener:

```text
período visible
=
período de la matriz
=
período enviado
```

---

## 32. Comportamiento confirmado de referencia

Julio de 2026:

```text
17 zonas
8 fechas de fin de semana
136 filas de calendario
```

Zona 2 — Lo Barnechea:

```text
25 y 26 sin despacho
6 días con despacho
```

Asignación AM.01:

```text
q_calendario = 6
q_inasistencia = 0
cantidad = 6
costo = 32.000
total bruto = 192.000
retención = 29.280
líquido = 162.720
```

Este caso confirma el recorrido:

```text
vista
→ controlador
→ calendario
→ generación
→ detalle
→ pre-factura
→ PDF
```

---

## 33. Escenarios que Codex puede construir

Codex puede explorar libremente:

- meses con 8, 9 o 10 fechas;
- todas las zonas operativas;
- una zona sin despacho completo;
- calendario incompleto;
- fecha duplicada;
- ruta sin zona;
- OPV sin puntos;
- OPV con múltiples puntos;
- fijo con zona;
- variable sin cantidad;
- dos pagos idénticos;
- ajuste duplicado;
- aplicación repetida;
- dos generaciones concurrentes;
- cambio de calendario después de generar;
- fallo después de persistencia inicial;
- cambio de costo maestro;
- diferencia entre pantalla y PDF;
- grupo nulo;
- proveedor efectivo distinto;
- tipos no reconocidos.

La lista no limita el análisis.

---

## 34. Auditoría en modo solo lectura

Cuando la tarea sea analizar, revisar o detectar bugs, Codex debe:

- leer código;
- leer rutas;
- leer migraciones;
- leer modelos;
- leer servicios;
- leer vistas;
- ejecutar pruebas no destructivas;
- construir escenarios ficticios;
- entregar hallazgos.

No debe:

- modificar archivos;
- crear migraciones;
- escribir en la base real;
- regenerar períodos reales;
- aplicar correcciones;
- eliminar detalles.

---

## 35. Regla de mantenimiento

Actualizar este documento cuando cambie:

- el orden del proceso;
- el servicio principal;
- la frontera transaccional;
- la identidad de duplicidad;
- la fórmula de cualquier tipo;
- la aplicación de ajustes;
- el calendario;
- la estrategia de recálculo;
- el resultado devuelto;
- la política de reintentos.
