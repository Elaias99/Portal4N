# Módulo Suscripciones

## Propósito

El módulo **Suscripciones** administra la preparación mensual, generación,
revisión, consolidación y distribución de liquidaciones y pre-facturas para
proveedores de servicios de reparto de fin de semana.

El módulo reemplaza un proceso operativo que históricamente se apoyaba en
planillas Excel y concentra:

- asignaciones de proveedores y transportistas;
- rutas y servicios;
- cantidades variables mensuales;
- pagos adicionales;
- novedades y ajustes;
- calendario operativo por zona;
- generación de detalles mensuales;
- cálculo tributario;
- agrupación de pre-facturas;
- generación de PDF y ZIP;
- envíos por correo;
- carga en OneDrive.

## Alcance funcional

El flujo mensual permite:

1. seleccionar año y mes;
2. revisar y configurar los días operativos por zona;
3. registrar cantidades variables;
4. registrar novedades mensuales;
5. registrar pagos adicionales;
6. generar los detalles mensuales;
7. aplicar ajustes sobre los detalles;
8. consolidar pre-facturas;
9. generar PDF o ZIP;
10. revisar destinatarios;
11. enviar correos de prueba o reales;
12. cargar paquetes en OneDrive.

## Principios del dominio

### La asignación es el centro operativo

La tabla `suscripcion_asignaciones` funciona como catálogo central de líneas
operativas y técnicas.

Una asignación relaciona, según corresponda:

- proveedor;
- transportista;
- zona;
- punto;
- origen del gasto;
- código;
- servicio;
- costo;
- grupo de pre-factura;
- tipo de asignación;
- regla de generación automática.

Los códigos pueden repetirse. La identidad histórica es el ID de asignación.

### La zona pertenece a la asignación

Una zona no pertenece directamente a un proveedor.

Un mismo proveedor puede tener asignaciones en una o varias zonas. La relación
correcta es:

`SuscripcionZona`
→ `hasMany Asignaciones`

`Asignaciones`
→ `belongsTo SuscripcionZona`

### Calendario zonal e inasistencia son distintos

Un día sin despacho zonal indica que la zona completa no operó durante esa
fecha. Ese día reduce el `q_calendario` de todas las asignaciones calendarizadas
de la zona.

Una inasistencia individual indica que una ruta específica no asistió aunque la
zona sí operó. Esa situación mantiene `q_calendario` y aumenta
`q_inasistencia`.

## Tipos principales de asignación

### `RUTA`

Asignación normal calculada según los días operativos de su zona.

### `FIJO_MENSUAL`

Pago automático con cantidad fija igual a 1.

### `VARIABLE`

Asignación cuya cantidad proviene de
`suscripcion_cantidades_mensuales`.

### `COMISION`

Asignación técnica creada para un pago adicional independiente. No se genera
automáticamente como ruta.

### `CONTENEDOR_AJUSTE`

Asignación técnica utilizada para líneas adicionales, pagos variables,
reemplazos u otras novedades que necesitan un soporte de asignación.

### OPV

Actualmente puede estar almacenada como `RUTA` y se detecta por tipo, código,
servicio u origen del gasto.

La cantidad se calcula con los días operativos de la zona multiplicados por la
cantidad de puntos OPV configurados.

## Componentes principales

### Controlador central mensual

`SuscripcionComisionMensualController`

Responsabilidades principales:

- preparar el formulario mensual;
- cargar zonas activas y fechas de fin de semana;
- recuperar calendarios zonales guardados;
- validar la matriz zona-fecha;
- guardar cantidades variables;
- crear pagos adicionales;
- registrar novedades;
- ejecutar la generación mensual;
- aplicar ajustes;
- redirigir al detalle del período.

### Generación de detalles

`SuscripcionGeneracionMensualService`

Responsabilidades principales:

- seleccionar asignaciones automáticas;
- excluir asignaciones técnicas y variables;
- validar calendario zonal;
- calcular `q_calendario`;
- generar rutas normales;
- generar OPV;
- generar fijos mensuales;
- incorporar cantidades variables;
- incorporar pagos adicionales;
- evitar duplicar detalles ya existentes.

### Registro de ajustes

`SuscripcionAjusteMensualRegistroService`

Responsabilidades principales:

- persistir novedades mensuales;
- crear o reutilizar asignaciones técnicas;
- guardar instantáneas y datos efectivos;
- conservar compatibilidad con tipos anteriores.

### Aplicación de ajustes

`SuscripcionAjusteMensualAplicacionService`

Responsabilidades principales:

- actualizar detalles existentes;
- crear o actualizar líneas adicionales;
- aplicar inasistencias;
- recalcular cantidades y totales;
- considerar cambios de facturación;
- aplicar la regla especial de OPV.

### Presentación y distribución

`SuscripcionLiquidacionDetalleController` y servicios de pre-factura.

Responsabilidades principales:

- filtrar y agrupar detalles;
- resolver proveedor efectivo;
- calcular resúmenes tributarios;
- generar PDF;
- generar ZIP;
- revisar destinatarios;
- enviar correos;
- cargar archivos en OneDrive.

## Modelo de datos principal

Las tablas relevantes incluyen:

- `cobranza_compras`
- `suscripcion_proveedores`
- `suscripcion_transportistas`
- `suscripcion_zonas`
- `suscripcion_asignaciones`
- `suscripcion_zona_dias_operativos`
- `suscripcion_cantidades_mensuales`
- `suscripcion_comisiones_mensuales`
- `suscripcion_ajustes_mensuales`
- `suscripcion_liquidacion_detalles`
- `suscripcion_opv_puntos`
- `suscripcion_conceptos_pago_variable`

La descripción detallada de relaciones, restricciones y responsabilidades se
documentará en `modelo-datos.md`.

## Fórmulas principales

### Ruta normal

`cantidad = max(0, q_calendario - q_inasistencia)`

`total = costo × cantidad`

### OPV

`cantidad = max(0, q_calendario - q_inasistencia) × puntos_opv`

`total = costo × cantidad`

### Fijo mensual

- `q_calendario = 1`
- `q_inasistencia = 0`
- `cantidad = 1`
- `total = costo`

### Cantidad variable

La cantidad proviene del registro mensual informado y no depende del calendario
zonal.

### Pago adicional

La tarifa y cantidad son informadas. El total se calcula como:

`total = tarifa × cantidad`

Cada pago adicional es independiente, aunque sus datos sean idénticos a los de
otro pago.

## Tributación

### Factura

- impuesto: 19 %;
- líquido calculado desde neto más impuesto.

### Boleta

- retención: 15,25 %;
- líquido calculado desde bruto menos retención.

### Documento

- sin impuesto ni retención;
- líquido igual al total informado.

Los detalles exactos se encuentran en
`SuscripcionLiquidacionResumenService`.

## Calendario de zonas

Las zonas se almacenan en `suscripcion_zonas`.

Cada combinación de zona y fecha se almacena explícitamente en
`suscripcion_zona_dias_operativos` con:

- `hubo_despacho = 1`, cuando la zona operó;
- `hubo_despacho = 0`, cuando la zona completa no operó.

El índice único debe impedir más de un registro para la misma zona y fecha.

### Comportamiento validado

En julio de 2026:

- existían 8 fechas de sábado o domingo;
- Zona 2 — Lo Barnechea no tuvo despacho los días 25 y 26;
- la zona quedó con 6 días operativos;
- la asignación `AM.01` se generó con:
  - costo unitario: 32.000;
  - `q_calendario = 6`;
  - `q_inasistencia = 0`;
  - `cantidad = 6`;
  - total bruto: 192.000;
  - retención 15,25 %: 29.280;
  - líquido a pagar: 162.720.

Este caso representa comportamiento confirmado, no una lista cerrada de pruebas.

## Documentación del módulo

La documentación se divide en:

- `arquitectura.md`: responsabilidades y dependencias entre componentes;
- `modelo-datos.md`: tablas, relaciones, índices y restricciones;
- `reglas-negocio.md`: fórmulas, invariantes y decisiones funcionales;
- `flujo-generacion-mensual.md`: orden exacto del proceso;
- `zonas-distribucion.md`: calendario zonal y relación con asignaciones;
- `ajustes-mensuales.md`: tipos de novedades y aplicación;
- `prefacturas-distribucion.md`: agrupación, PDF, ZIP, correo y OneDrive;
- `riesgos-y-consideraciones.md`: limitaciones, riesgos y decisiones pendientes.

## Estado actual

El calendario zonal fue implementado y validado en una primera generación de
julio de 2026.

El servicio de generación conserva el comportamiento de no recalcular detalles
que ya existan para la misma asignación, año y mes.

Por tanto, modificar un calendario después de generar un período no implica
necesariamente que los detalles históricos se actualicen automáticamente.

## Objetivo para agentes y revisores

Analiza exhaustivamente el módulo Suscripciones utilizando AGENTS.md y toda la
documentación de storage/agents/docs/suscripciones/.

Trabaja exclusivamente en modo auditoría.

No modifiques archivos existentes.
No crees archivos.
No ejecutes migraciones.
No escribas en la base de datos.
No implementes correcciones.

Puedes leer el código, revisar migraciones, buscar inconsistencias y ejecutar
comandos o pruebas estrictamente no destructivas que no alteren el repositorio
ni los datos.

Entrega solamente un informe de hallazgos, ordenado por severidad, incluyendo:

- archivo y método afectado;
- escenario que puede provocar el problema;
- regla de negocio comprometida;
- resultado actual;
- resultado esperado;
- evidencia encontrada;
- propuesta de corrección, sin aplicarla;
- pruebas que demostrarían la corrección.


