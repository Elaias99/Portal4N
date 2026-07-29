# AGENTS.md

## Propósito

Este repositorio contiene un módulo Laravel llamado **Suscripciones** que genera
liquidaciones y pre-facturas mensuales para proveedores de servicios de reparto
de fin de semana.

Las reglas del módulo no deben inferirse solamente desde nombres de columnas o
métodos. Antes de analizar, modificar o probar cualquier archivo relacionado con
Suscripciones, lee primero la documentación disponible en:

- `storage/agents/docs/suscripciones/README.md`
- `storage/agents/docs/suscripciones/arquitectura.md`
- `storage/agents/docs/suscripciones/modelo-datos.md`
- `storage/agents/docs/suscripciones/reglas-negocio.md`
- `storage/agents/docs/suscripciones/flujo-generacion-mensual.md`
- `storage/agents/docs/suscripciones/zonas-distribucion.md`
- `storage/agents/docs/suscripciones/ajustes-mensuales.md`
- `storage/agents/docs/suscripciones/prefacturas-distribucion.md`
- `storage/agents/docs/suscripciones/riesgos-y-consideraciones.md`

Algunos de esos documentos pueden agregarse progresivamente. Si un documento
referenciado todavía no existe, informa esa ausencia y continúa con los
documentos y el código disponibles.

## Libertad de análisis y pruebas

Puedes diseñar y ejecutar todas las pruebas **no destructivas** que consideres
necesarias para detectar:

- errores funcionales;
- regresiones;
- inconsistencias entre código y base de datos;
- problemas de integridad;
- condiciones de carrera;
- fallos de validación;
- errores tributarios;
- errores de agrupación;
- errores en generación de PDF, ZIP, correo u OneDrive;
- diferencias entre datos históricos y reglas actuales;
- cualquier otro riesgo técnico o funcional.

No limites el análisis a casos descritos explícitamente en la documentación.

Antes de escribir o modificar pruebas:

1. Identifica las reglas de negocio afectadas.
2. Identifica las tablas, modelos, controladores, servicios, vistas y scripts
   involucrados.
3. Revisa el comportamiento sobre períodos ya generados.
4. Revisa las restricciones reales de la base de datos.
5. Explica brevemente qué intenta comprobar cada grupo de pruebas.
6. Prefiere cambios pequeños, aislados y verificables.

## Seguridad de datos

No ejecutes operaciones destructivas o irreversibles sobre una base de datos
existente sin autorización explícita.

No ejecutes por iniciativa propia:

- `php artisan migrate:fresh`
- `php artisan migrate:reset`
- `php artisan db:wipe`
- `TRUNCATE`
- `DROP TABLE`
- `DELETE` masivos
- actualizaciones globales sin filtros estrictos
- migraciones que eliminen o transformen datos históricos

Cuando una prueba requiera modificar datos:

- usa una base de datos de pruebas separada;
- usa transacciones cuando corresponda;
- crea datos propios para la prueba;
- no dependas de datos reales existentes;
- no alteres períodos históricos del entorno principal.

## Reglas de trabajo

- No inventes reglas de negocio.
- Si el código, la base de datos y la documentación se contradicen, informa la
  contradicción antes de elegir una conducta.
- No asumas que un código de asignación es único.
- No identifiques registros históricos solamente por `codigo`.
- No asumas que un proveedor pertenece a una sola zona.
- No mezcles asignaciones operativas con asignaciones técnicas.
- No modifiques datos tributarios, agrupaciones o destinatarios sin revisar el
  flujo completo afectado.
- Conserva compatibilidad con períodos históricos ya generados.
- Antes de cambiar una fórmula, identifica todos los lugares donde se recalcula
  `cantidad`, `total`, impuesto, retención o líquido a pagar.

## Invariantes críticas del módulo Suscripciones

### Identidad de asignaciones

La identidad histórica y operativa de una línea es
`suscripcion_asignacion_id`.

El campo `codigo` puede repetirse entre proveedores, transportistas, puntos,
zonas o tipos de asignación.

### Zonas e inasistencias

La falta de despacho de una zona y la inasistencia individual de una ruta son
conceptos distintos.

- Día zonal sin despacho:
  - afecta a todas las asignaciones calendarizadas de esa zona;
  - reduce `q_calendario`;
  - se registra en `suscripcion_zona_dias_operativos`.

- Inasistencia individual:
  - afecta solamente a una asignación;
  - mantiene el `q_calendario` de la zona;
  - aumenta `q_inasistencia`;
  - se registra como ajuste mensual.

Nunca conviertas automáticamente uno de estos conceptos en el otro.

### Relación de zona

La zona pertenece a la asignación:

`suscripcion_asignaciones.suscripcion_zona_id`
→ `suscripcion_zonas.id`

No pertenece directamente al proveedor, porque un proveedor puede tener
asignaciones en distintas zonas.

### Asignaciones técnicas

Los tipos `COMISION` y `CONTENEDOR_AJUSTE` son técnicos y no deben generarse
automáticamente como rutas calendarizadas.

### Períodos existentes

El servicio de generación actual evita duplicar detalles que ya existen para la
misma asignación, año y mes.

No asumas que volver a guardar el calendario recalcula automáticamente un
período ya generado.

## Ubicaciones principales del código

### Controladores

- `app/Http/Controllers/SuscripcionComisionMensualController.php`
- `app/Http/Controllers/SuscripcionLiquidacionDetalleController.php`
- `app/Http/Controllers/SuscripcionCantidadMensualController.php`

### Servicios

- `app/Services/Suscripciones/SuscripcionGeneracionMensualService.php`
- `app/Services/Suscripciones/SuscripcionAjusteMensualRegistroService.php`
- `app/Services/Suscripciones/SuscripcionAjusteMensualAplicacionService.php`
- `app/Services/Suscripciones/SuscripcionAjusteMensualService.php`
- `app/Services/Suscripciones/SuscripcionLiquidacionResumenService.php`
- `app/Services/Suscripciones/SuscripcionPrefacturaAgrupacionService.php`
- `app/Services/Suscripciones/SuscripcionPrefacturaOcService.php`
- `app/Services/Suscripciones/SuscripcionPrefacturaPdfService.php`
- `app/Services/Suscripciones/SuscripcionPrefacturaZipService.php`
- `app/Services/Suscripciones/SuscripcionPrefacturaEnvioService.php`
- `app/Services/Suscripciones/SuscripcionOneDriveService.php`

### Vistas

- `resources/views/suscripciones/comisiones_mensuales/create.blade.php`
- `resources/views/suscripciones/comisiones_mensuales/partials/`
- `resources/views/suscripciones/liquidacion_detalles/index.blade.php`
- `resources/views/suscripciones/liquidacion_detalles/pdf.blade.php`

### JavaScript

- `resources/js/suscripciones/generacion-mensual.js`
- módulos auxiliares de ajustes masivos y pagos adicionales dentro de
  `resources/js/suscripciones/`

## Forma esperada de reportar hallazgos

Cuando encuentres un posible error, indica:

1. severidad;
2. archivo y método;
3. regla de negocio afectada;
4. escenario que lo reproduce;
5. resultado actual;
6. resultado esperado;
7. riesgo para datos históricos;
8. propuesta mínima de corrección;
9. pruebas necesarias para demostrar la corrección.

No apliques una corrección si la conducta funcional esperada no puede
determinarse con seguridad.

## Modo auditoría y autorización de cambios

Por defecto, cuando se solicite analizar, revisar, auditar, buscar errores,
detectar bugs o proponer pruebas:

- trabaja en modo de solo lectura;
- no modifiques archivos existentes;
- no crees archivos;
- no generes migraciones;
- no escribas en la base de datos;
- no apliques correcciones;
- entrega únicamente un informe de hallazgos.

La autorización para analizar no implica autorización para modificar.

Solo puedes editar código cuando el usuario solicite explícitamente implementar
o aplicar una corrección.

Aunque encuentres un error crítico, primero debes informarlo. No lo corrijas
automáticamente.

Una autorización para corregir un hallazgo no autoriza cambios adicionales no
relacionados.