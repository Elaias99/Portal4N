# AGENTS.md

## Propósito

Este repositorio contiene un módulo Laravel llamado **Suscripciones** que genera
liquidaciones y pre-facturas mensuales para proveedores de servicios de reparto
de fin de semana.

Este repositorio contiene además el módulo **Courier**, que lleva el pago a
proveedores Courier. Sus reglas están en la sección **Módulo Courier**, al final
de este archivo. Las secciones «Seguridad de datos» y «Modo auditoría y
autorización de cambios» valen para los dos módulos; las invariantes y las
ubicaciones de código que aparecen antes de la sección Courier son de
Suscripciones.

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

## Módulo Courier

### Qué es

Calcula el pago mensual a los proveedores Courier de 4N Logística. Parte de la
descarga de paquetes de Geo (Geolice), calcula el valor de cada bulto, suma los
pagos manuales (acuerdos, ruta CV, servicios, visitas, especiales, apoyo alza) y
cierra el mes con órdenes de compra (OC), IVA y retención.

- Rutas: `/courier`, con nombres `courier.*`.
- Controladores: `CourierPagoController`, `CourierCatalogoController`,
  `CourierGeoliceCaptureController`.
- Servicios: `app/Services/Courier/`.
- Comandos: `app/Console/Commands/Courier*.php` (`courier:*`).
- Tablas: `courier_*`.
- Vistas: `resources/views/courier/`.
- Estilos: `resources/css/courier.css`, con prefijo `co-`.
- JavaScript propio: `resources/js/geolice-capture.js`.

### Qué leer primero

Antes de analizar o modificar algo de Courier, lee en `storage/agents/courier/`:

- `README.md`
- `01-negocio.md`
- `02-cadena-de-pago.md`
- `03-lo-construido.md`
- `04-modelo-de-datos.md`
- `05-decisiones-y-pendientes.md`
- `06-como-trabajar-con-elias.md`
- `07-la-planilla-completa.md`
- `herramientas/README.md`

Los documentos 03, 04 y 05 todavía no incluyen lo último construido: pantallas
por pasos, captura desde Geo, llave de pago por RUT, Peumo, pagos manuales y
cierre. Si un documento y el código se contradicen, manda el código y se informa
la contradicción.

### Reparto de trabajo entre dos agentes

En Courier trabajan dos agentes sobre la misma carpeta del proyecto: uno de
front y uno de back.

**Agente de front.** Solo toca:

- `resources/views/courier/`
- `resources/css/courier.css`
- `resources/js/geolice-capture.js`

**Agente de back.** Toca todo lo demás de Courier: controladores, servicios,
comandos, modelos, migraciones, rutas y configuración. No toca las vistas, el
CSS ni el JS de la lista anterior.

Reglas entre los dos:

1. Trabaja uno a la vez. El usuario hace un commit cuando termina cada uno.
2. Al terminar una tarea, el agente lista los archivos que tocó.
3. El punto de contacto es el controlador: el back entrega a la vista variables
   con nombre y tipo definidos. Esa lista es el contrato de la pantalla. El back
   no cambia el nombre ni el tipo de una variable sin avisar al usuario antes.
4. Si una vista necesita un dato que el contrato no trae, el front se detiene y
   se lo dice al usuario para que el back lo agregue. No modifica controladores
   ni servicios para conseguirlo.
5. Ninguno de los dos toca las vistas ni el código de Suscripciones.

### Reglas del agente de front

- Una pantalla a la vez. Antes de programar, acuerda con el usuario qué ve
  primero y qué queda escondido hasta que lo pida, muestra un bosquejo y espera
  su OK.
- Usa solo las variables del contrato de la pantalla.
- No calcula valores de pago ni aplica reglas de negocio en Blade ni en
  JavaScript. Muestra lo que entrega el back.
- Todo número en pantalla debe poder abrirse hasta el bulto de origen, o indicar
  de dónde sale.
- En las piezas nuevas no usa íconos.
- Sigue el lenguaje visual existente: `resources/css/courier.css`, prefijo `co-`,
  clon del estilo `sl-` de Suscripciones.
- Los cambios de CSS y JavaScript requieren `npm run build`; lo corre el
  usuario.

### Reglas de negocio que ningún agente decide

- El sistema calcula y marca. No decide lo que no tiene regla; eso lo define
  Operaciones.
- Nunca se relacionan ni se corrigen automáticamente dos escrituras de una
  comuna. Cada variante se muestra con su cantidad de bultos y el agente al que
  iría, y una persona decide caso a caso.
- No se hace `UPDATE` a mano sobre `courier_bultos`.
- Courier no lee ni escribe `cobranza_compras` ni las tablas de Suscripciones.
- Un período cerrado no se modifica. `courier_cierres` y
  `courier_pagos_cerrados` tienen triggers que lo impiden, y un bulto cerrado
  no se paga dos veces.

### Seguridad en Courier

- No leer ni mostrar el `.env`, claves, tokens ni contraseñas. La cuenta de Geo
  de cada usuario está cifrada y no se abre.
- No copiar datos de destinatarios, repartidores ni cuentas bancarias a
  documentos, chats ni archivos del repositorio.
- No ejecutar comandos que modifiquen datos o el entorno sin permiso expreso del
  usuario para esa tarea: `php artisan migrate`, `courier:importar-*`,
  `courier:calcular`, `courier:cerrar`, `npm run build` y `docker compose`. El
  agente los entrega listos y el usuario los corre.
- El proyecto corre en Docker. Los comandos se entregan como
  `docker compose exec app php artisan …`, uno por bloque. Los que leen archivos
  de Descargas van con el `php artisan` de Windows, porque el contenedor no ve
  esa carpeta.

### Cómo trabajar con el usuario

- Responde en español, corto y un paso a la vez.
- No inventes criterios ni pasos que el usuario no pidió. Si ves algo
  conveniente, menciónalo como observación aparte.
- Al explicar el proceso, no uses cifras; solo la regla y, si hace falta, un
  ejemplo. Al verificar datos, sí.
- Los documentos de `storage/agents/courier/` llevan solo instrucciones
  conocidas, sin preguntas ni dudas.
