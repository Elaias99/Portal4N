---
titulo: Protocolo de validación y auditoría del módulo Suscripciones
modulo: Suscripciones
tipo_documento: Protocolo operativo para análisis asistido por Codex
estado: Vigente
ultima_actualizacion: 2026-07-29
ubicacion_recomendada: storage/agents/docs/suscripciones/pruebas.md
---

# Protocolo de validación y auditoría del módulo Suscripciones

## 1. Propósito

Este documento define el protocolo que debe seguir Codex antes de analizar,
probar, regenerar o proponer cambios en el módulo **Suscripciones**.

El objetivo es trabajar en etapas:

```text
1. Verificar el entorno aislado.
2. Leer y comprender el módulo.
3. Demostrar la comprensión.
4. Inspeccionar mayo en modo solo lectura.
5. Analizar el Excel histórico de mayo.
6. Diseñar una conciliación reproducible.
7. Solicitar autorización antes de modificar la base de pruebas.
8. Regenerar mayo únicamente en la base aislada.
9. Comparar sistema contra Excel.
10. Explicar cualquier diferencia.
11. Iniciar una auditoría exploratoria libre.
12. Reportar hallazgos sin modificar código.
```

Este documento no limita a Codex a una cantidad fija de pruebas.

Codex puede construir todos los escenarios ficticios, casos límite, pruebas
automatizadas y consultas no destructivas que considere necesarios.

---

# 2. Regla principal de seguridad

La autorización para:

```text
leer
analizar
auditar
crear escenarios
ejecutar pruebas no destructivas
```

no autoriza a:

```text
modificar código
crear migraciones
cambiar documentación
escribir en la base
borrar datos
regenerar períodos
enviar correos
subir archivos a OneDrive
```

La autorización para regenerar mayo en la base aislada tampoco autoriza a
modificar la implementación.

Cada autorización debe ser explícita y limitada a la operación solicitada.

---

# 3. Entorno aislado definido

Codex debe trabajar sobre el entorno local preparado para el asistente.

## 3.1. Repositorio y rama

Ubicación esperada:

```text
C:\xampp\htdocs\Portal4N-Asistente
```

Rama esperada:

```text
feature/asistente
```

Codex debe verificar la rama con:

```bash
git branch --show-current
```

Resultado esperado:

```text
feature/asistente
```

Si la rama es distinta, debe detenerse e informar.

---

## 3.2. Base de datos

Motor esperado:

```text
MariaDB / MySQL compatible con el proyecto
```

Base aislada:

```text
asistente_portal_4n
```

La base principal:

```text
portal_4n
```

no debe utilizarse para escritura ni regeneración.

Codex debe verificar la conexión efectiva de Laravel antes de cualquier
operación.

Ejemplo no destructivo:

```bash
php artisan tinker --execute="dump(config('database.default')); dump(config('database.connections.mysql.host')); dump(config('database.connections.mysql.port')); dump(config('database.connections.mysql.database'));"
```

Resultado esperado para la base:

```text
asistente_portal_4n
```

Si Laravel apunta a otra base, debe detenerse.

---

## 3.3. Correo

Configuración esperada:

```text
MAIL_MAILER=log
```

Verificación:

```bash
php artisan tinker --execute="dump(config('mail.default'));"
```

Resultado esperado:

```text
log
```

En este entorno los correos deben registrarse localmente y no enviarse por
SMTP.

Codex debe revisar también si existe código que fuerce explícitamente otro
mailer o utilice una integración externa.

---

## 3.4. OneDrive

Configuración esperada:

```text
ONEDRIVE_ENABLED=false
```

Verificación:

```bash
php artisan tinker --execute="dump(config('services.onedrive.enabled'));"
```

Resultado esperado:

```text
false
```

La generación local de un ZIP puede mantenerse, pero no debe realizarse ninguna
llamada a Microsoft Graph.

Codex debe detenerse si la bandera devuelve `true` o `null`.

---

## 3.5. Colas

Configuración conocida:

```text
QUEUE_CONNECTION=database
```

Antes de ejecutar cualquier worker, Codex debe inspeccionar los jobs y confirmar
que no puedan:

- enviar correos reales;
- llamar a OneDrive;
- consumir APIs externas;
- modificar períodos distintos;
- procesar trabajos históricos pendientes.

No debe ejecutar:

```bash
php artisan queue:work
php artisan queue:listen
```

sin autorización explícita.

---

## 3.6. APIs externas

El entorno contiene configuraciones para integraciones externas.

Codex no debe ejecutar llamadas reales a:

- Microsoft Graph;
- tracking;
- correo SMTP;
- servicios externos;
- endpoints de producción.

Las pruebas de integración externa deben utilizar fakes, mocks o aislamiento.

---

# 4. Documentación obligatoria

Antes de analizar el código, Codex debe leer:

```text
AGENTS.md
storage/agents/docs/suscripciones/README.md
storage/agents/docs/suscripciones/arquitectura.md
storage/agents/docs/suscripciones/modelo-datos.md
storage/agents/docs/suscripciones/reglas-negocio.md
storage/agents/docs/suscripciones/generacion-mensual.md
storage/agents/docs/suscripciones/zonas-distribucion.md
storage/agents/docs/suscripciones/ajustes-mensuales.md
storage/agents/docs/suscripciones/prefacturas-y-envios.md
storage/agents/docs/suscripciones/pruebas.md
```

Si falta alguno, debe informarlo.

---

# 5. Código obligatorio a inspeccionar

Codex debe localizar y revisar, como mínimo:

## 5.1. Rutas

```text
routes/web.php
```

Debe identificar:

- rutas del formulario mensual;
- rutas de generación;
- rutas de detalles;
- rutas de PDF;
- rutas de ZIP;
- rutas de correo;
- rutas de OneDrive;
- middleware;
- permisos.

---

## 5.2. Controladores

```text
SuscripcionComisionMensualController
SuscripcionLiquidacionDetalleController
```

Debe identificar:

- métodos realmente expuestos;
- validaciones;
- transacciones;
- generación alternativa o histórica;
- acciones manuales;
- acciones externas.

---

## 5.3. Servicios

Debe localizar y revisar:

```text
SuscripcionGeneracionMensualService
SuscripcionAjusteMensualRegistroService
SuscripcionAjusteMensualAplicacionService
SuscripcionAjusteMensualService
SuscripcionLiquidacionResumenService
SuscripcionPrefacturaAgrupacionService
SuscripcionPrefacturaOcService
SuscripcionPrefacturaPdfService
SuscripcionPrefacturaZipService
SuscripcionPrefacturaEnvioService
SuscripcionOneDriveService
```

---

## 5.4. Modelos

Debe localizar y revisar:

```text
CobranzaCompra
SuscripcionProveedor
SuscripcionTransportista
SuscripcionZona
Asignaciones
SuscripcionZonaDiaOperativo
SuscripcionCantidadMensual
SuscripcionComisionMensual
SuscripcionAjusteMensual
SuscripcionLiquidacionDetalle
SuscripcionOPVPuntos
SuscripcionConceptoPagoVariable
```

---

## 5.5. Migraciones

Debe inspeccionar:

- estructura de tablas;
- claves foráneas;
- nulabilidad;
- índices únicos;
- precisión monetaria;
- políticas de borrado;
- diferencias entre migraciones y base real.

No debe ejecutar migraciones destructivas.

---

## 5.6. Vistas y JavaScript

Debe inspeccionar:

- formulario mensual;
- modal de zonas;
- novedades;
- pagos adicionales;
- filtros;
- vista individual;
- PDF;
- scripts de serialización;
- cálculos visuales;
- validaciones cliente;
- sincronización de período.

---

# 6. Primera entrega: demostración de comprensión

Antes de ejecutar generación o modificar datos, Codex debe entregar un informe
de comprensión.

No basta con responder:

```text
Entendido.
```

Debe explicar, citando archivos y métodos reales:

1. punto de entrada de la generación;
2. orden completo del proceso;
3. tablas de entrada;
4. tabla de resultado;
5. tipos de asignación;
6. fórmulas por tipo;
7. calendario zonal;
8. diferencia entre zona sin despacho e inasistencia;
9. OPV;
10. cantidades variables;
11. pagos adicionales;
12. ajustes mensuales;
13. proveedor efectivo;
14. agrupación de pre-facturas;
15. tributación;
16. PDF;
17. ZIP;
18. correo;
19. OneDrive;
20. identidad de duplicidad;
21. frontera transaccional;
22. caminos alternativos o históricos;
23. ambigüedades;
24. contradicciones entre documentación y código.

Después de esa entrega debe detenerse y esperar aprobación.

---

# 7. Archivo Excel de mayo

El archivo de referencia se guardará en:

```text
C:\xampp\htdocs\Portal4N-Asistente\storage\agents\docs
```

Ruta relativa:

```text
storage/agents/docs/
```

Codex debe descubrir el nombre exacto del archivo.

No debe asumir un nombre fijo.

Puede buscar archivos compatibles:

```text
*.xlsx
*.xls
*.csv
```

Antes de usarlo debe informar:

- nombre exacto;
- tamaño;
- extensión;
- hojas;
- rango de datos;
- columnas;
- período;
- fórmulas;
- filas de totales;
- posibles celdas combinadas;
- datos personales presentes;
- campos necesarios para la conciliación.

---

## 7.1. Tratamiento del Excel

El Excel representa una referencia histórica de mayo.

No reemplaza:

- reglas de negocio;
- código;
- migraciones;
- base;
- documentación.

Cuando Excel y sistema difieran, Codex debe investigar la causa.

No debe asumir automáticamente que:

```text
Excel correcto
sistema incorrecto
```

ni lo contrario.

---

## 7.2. Protección del archivo

El Excel puede contener:

- RUT;
- nombres;
- datos bancarios;
- costos;
- información comercial.

Codex no debe:

- publicarlo;
- copiarlo fuera del entorno;
- incluir datos sensibles completos en informes;
- agregarlo a Git sin autorización;
- modificar el original.

Debe trabajar sobre lectura o una copia local controlada.

---

# 8. Período histórico de referencia

Período objetivo:

```text
mayo
```

Codex debe confirmar el año desde:

- nombre del archivo;
- contenido;
- base de datos;
- indicación del usuario.

No debe asumir el año sin evidencia.

---

# 9. Inspección inicial de mayo

Antes de regenerar, Codex debe trabajar en modo solo lectura.

Debe determinar:

## 9.1. Datos maestros

- proveedores;
- transportistas;
- asignaciones;
- zonas;
- puntos OPV;
- conceptos;
- tipos documentales;
- grupos.

## 9.2. Entradas mensuales

- calendario zonal;
- cantidades variables;
- pagos adicionales;
- ajustes;
- reemplazos;
- inasistencias;
- cambios de facturación.

## 9.3. Resultados existentes

- detalles mensuales;
- pre-facturas;
- totales;
- grupos;
- documentos;
- archivos si existen.

---

# 10. Diferencia entre dos validaciones

## 10.1. Validación histórica de lectura

Consiste en comparar:

```text
detalles existentes de mayo
```

contra:

```text
Excel de mayo
```

Comprueba:

- agrupación;
- cantidades almacenadas;
- costos almacenados;
- totales;
- tributación;
- proveedor efectivo;
- grupos;
- pre-facturas.

No demuestra por sí sola que el motor actual pueda regenerar mayo desde cero.

---

## 10.2. Validación de regeneración

Consiste en usar la base aislada para:

1. conservar datos maestros;
2. conservar entradas mensuales;
3. retirar únicamente resultados de mayo;
4. ejecutar generación;
5. aplicar ajustes;
6. comparar el resultado con Excel.

Esta etapa modifica la copia de la base y requiere autorización explícita.

---

# 11. Problema de detalles existentes

El servicio actual evita crear un detalle cuando ya existe:

```text
suscripcion_asignacion_id + anio + mes
```

Por ello, ejecutar la generación sobre mayo ya generado puede producir:

```text
duplicados omitidos
```

sin demostrar el cálculo completo.

Codex debe distinguir claramente:

```text
ejecución idempotente sobre datos existentes
```

de:

```text
reproducción completa desde entradas
```

---

# 12. Calendario zonal de mayo

El calendario zonal fue agregado después de períodos históricos ya generados.

Para regenerar mayo con el código actual, Codex debe determinar si existen:

```text
suscripcion_zona_dias_operativos
```

para ese mes.

Si no existen, debe informar que la regeneración necesita una decisión funcional.

No debe asumir automáticamente:

```text
todas las zonas operaron todos los días
```

Debe comparar:

- datos históricos;
- Excel;
- asignaciones;
- novedades;
- información entregada por el usuario.

---

# 13. Plan obligatorio antes de modificar la base

Antes de eliminar o modificar cualquier registro de mayo en
`asistente_portal_4n`, Codex debe presentar:

1. base de datos confirmada;
2. período exacto;
3. tablas que leerá;
4. tablas que modificará;
5. registros aproximados afectados;
6. consultas de respaldo;
7. consultas de eliminación o actualización;
8. comandos Artisan;
9. tratamiento de ajustes;
10. tratamiento de calendario;
11. tratamiento de variables;
12. tratamiento de pagos;
13. tratamiento de archivos;
14. estrategia de restauración;
15. criterios de éxito;
16. criterios de detención.

Después debe esperar una autorización explícita.

---

# 14. Operaciones prohibidas

Codex no debe ejecutar:

```bash
php artisan migrate:fresh
php artisan migrate:reset
php artisan db:wipe
```

No debe ejecutar:

```sql
DROP DATABASE
DROP TABLE
TRUNCATE
```

No debe borrar períodos distintos a mayo.

No debe usar:

```text
portal_4n
```

como base de escritura.

No debe iniciar workers, enviar correos o llamar OneDrive.

---

# 15. Respaldo antes de regenerar

Antes de retirar resultados de mayo, Codex debe proponer un respaldo dentro de
la base aislada o un dump local.

Debe incluir como mínimo:

```text
suscripcion_liquidacion_detalles de mayo
suscripcion_ajustes_mensuales de mayo
suscripcion_cantidades_mensuales de mayo
suscripcion_comisiones_mensuales de mayo
suscripcion_zona_dias_operativos de mayo
```

No debe crear tablas de respaldo sin autorización.

---

# 16. Datos de entrada y resultado

Codex debe clasificar cada tabla.

## Entradas

```text
suscripcion_asignaciones
suscripcion_zonas
suscripcion_zona_dias_operativos
suscripcion_cantidades_mensuales
suscripcion_comisiones_mensuales
suscripcion_ajustes_mensuales
suscripcion_opv_puntos
suscripcion_proveedores
suscripcion_transportistas
cobranza_compras
```

## Resultado principal

```text
suscripcion_liquidacion_detalles
```

Debe verificar si alguna etapa crea o modifica asignaciones técnicas.

---

# 17. Ejecución controlada

La regeneración debe usar el mismo punto de entrada vigente en producción,
salvo que se justifique una llamada directa al servicio.

Codex debe indicar si utilizará:

- una ruta HTTP;
- un comando Artisan existente;
- Tinker;
- una prueba de integración;
- una llamada directa a servicio.

Debe preferir una ejecución reproducible y verificable.

---

# 18. Prohibición de efectos externos

Durante la regeneración:

```text
correo = log
OneDrive = false
```

Además, Codex debe revisar que:

- no exista `Mail::mailer('smtp')`;
- no exista cliente Graph alternativo;
- no exista job pendiente;
- no exista listener externo;
- no exista webhook.

---

# 19. Conciliación contra Excel

La comparación no debe limitarse al total general.

Debe realizarse por niveles.

## 19.1. Nivel de período

- cantidad total de pre-facturas;
- total general;
- total bruto o neto;
- total final.

## 19.2. Tipo documental

- cantidad de boletas;
- total de boletas;
- cantidad de facturas;
- total de facturas;
- cantidad de documentos;
- total de documentos.

## 19.3. Proveedor

- proveedor;
- RUT;
- tipo documental;
- total;
- líquido;
- cantidad de grupos.

## 19.4. Grupo de pre-factura

- proveedor efectivo;
- grupo;
- líneas;
- subtotal;
- impuesto o retención;
- total final.

## 19.5. Asignación o línea

- asignación;
- código;
- servicio;
- punto;
- zona;
- costo;
- `q_calendario`;
- `q_inasistencia`;
- cantidad;
- total.

## 19.6. Tributación

- base;
- IVA;
- retención;
- redondeo;
- líquido.

---

# 20. Riesgo de compensación

Una coincidencia del total general no garantiza que el resultado sea correcto.

Ejemplo:

```text
Proveedor A: +50.000
Proveedor B: -50.000
Total general: coincide
Detalle: incorrecto
```

La conciliación debe llegar al menor nivel identificable.

---

# 21. Normalización para comparar

Codex debe documentar cómo normaliza:

- RUT;
- nombres;
- tildes;
- espacios;
- códigos;
- grupos nulos;
- valores monetarios;
- fechas;
- tipos documentales.

No debe modificar los datos originales para hacerlos coincidir.

Debe conservar una tabla de correspondencias o diferencias.

---

# 22. Clasificación de diferencias

Cada diferencia debe clasificarse como:

```text
coincidencia exacta
diferencia de entrada
diferencia de calendario
diferencia de cantidad
diferencia de costo
diferencia de total
diferencia de agrupación
diferencia de proveedor efectivo
diferencia tributaria
diferencia de redondeo
diferencia de ajuste
diferencia histórica
dato ausente
posible bug
ambigüedad funcional
```

---

# 23. Informe de conciliación

El informe debe incluir:

1. archivo Excel usado;
2. hoja;
3. período;
4. base;
5. commit;
6. rama;
7. fecha y hora;
8. comandos ejecutados;
9. datos modificados;
10. resumen global;
11. resumen por tipo;
12. resumen por proveedor;
13. diferencias por grupo;
14. diferencias por línea;
15. explicación;
16. hallazgos;
17. datos faltantes;
18. conclusión;
19. posibilidad o no de usar mayo como línea base.

---

# 24. Puerta de aprobación

La auditoría exploratoria puede comenzar cuando:

```text
mayo coincide completamente
```

o:

```text
las diferencias fueron explicadas y aceptadas por el usuario
```

Codex debe esperar confirmación antes de pasar a la auditoría libre.

---

# 25. Auditoría exploratoria

Después de aprobar la línea base, Codex puede diseñar libremente:

- pruebas unitarias;
- pruebas de integración;
- pruebas funcionales;
- pruebas de regresión;
- pruebas de concurrencia;
- pruebas de idempotencia;
- pruebas de atomicidad;
- pruebas de integridad;
- pruebas de autorización;
- pruebas de archivos;
- pruebas de correo con fake;
- pruebas de HTTP con fake;
- pruebas de fechas;
- pruebas de redondeo;
- análisis estático;
- consultas diagnósticas.

No existe una cantidad máxima o mínima predeterminada.

---

# 26. Familias de escenarios

Codex puede explorar, entre otras:

## Calendario

- meses con 8, 9 o 10 fechas;
- calendario incompleto;
- zona sin despacho;
- zona inactiva;
- cambio de período;
- fecha duplicada.

## Asignaciones

- ruta sin zona;
- fijo con zona;
- variable;
- comisión;
- contenedor;
- código repetido;
- costo negativo;
- proveedor inactivo.

## OPV

- sin puntos;
- un punto;
- múltiples puntos;
- cambio histórico de puntos;
- inasistencia.

## Ajustes

- inasistencia;
- facturación;
- reemplazo;
- línea adicional;
- pagos;
- duplicidad;
- aplicación repetida;
- orden de ajustes.

## Generación

- primera ejecución;
- reintento;
- fallo parcial;
- concurrencia;
- detalle existente;
- costo cambiado;
- calendario cambiado.

## Pre-facturas

- varios grupos;
- grupo nulo;
- proveedor efectivo;
- tipo documental;
- tributación;
- redondeo.

## Salidas

- PDF;
- ZIP;
- nombres;
- temporales;
- correo con fake;
- OneDrive con fake;
- autorización.

La lista no limita la investigación.

---

# 27. Pruebas que modifican datos

Las pruebas automatizadas que escriban deben utilizar:

- base de pruebas separada;
- transacciones;
- factories;
- fixtures;
- datos sintéticos;
- rollback.

No deben depender de datos reales salvo en la conciliación controlada.

SQLite puede utilizarse para pruebas unitarias compatibles.

La validación histórica de mayo debe realizarse con MariaDB en
`asistente_portal_4n`.

---

# 28. Código durante la auditoría

Durante la auditoría inicial, Codex no debe modificar código.

Puede proponer:

- pruebas;
- consultas;
- correcciones;
- refactorizaciones;
- índices;
- validaciones.

Pero debe entregarlas como recomendaciones.

Una corrección solo se implementa cuando el usuario autoriza un hallazgo
específico.

---

# 29. Formato de hallazgos

Cada posible bug debe incluir:

```text
ID
título
severidad
estado
regla afectada
archivo
método
escenario
precondiciones
pasos
resultado actual
resultado esperado
evidencia
impacto
riesgo histórico
propuesta mínima
pruebas de confirmación
nivel de certeza
```

Estados posibles:

```text
confirmado
probable
hipótesis
ambigüedad funcional
falso positivo
riesgo aceptado
```

---

# 30. Severidad

## Crítica

Puede:

- afectar dinero de muchos proveedores;
- enviar información a destinatarios incorrectos;
- borrar datos;
- mezclar períodos;
- exponer credenciales;
- alterar producción.

## Alta

Puede:

- calcular incorrectamente una pre-factura;
- duplicar pagos;
- omitir líneas;
- aplicar ajustes incorrectos;
- mezclar grupos.

## Media

Puede:

- producir errores de reintento;
- mostrar información inconsistente;
- degradar rendimiento;
- generar archivos incorrectamente nombrados.

## Baja

Puede:

- afectar textos;
- causar confusión visual;
- generar advertencias;
- dificultar mantenimiento.

---

# 31. Criterio de detención

Codex debe detenerse cuando:

- detecte conexión a la base principal;
- mailer no sea `log`;
- OneDrive no sea `false`;
- falte una copia o respaldo requerido;
- el Excel no corresponda al período;
- no pueda distinguir entradas y resultados;
- la operación solicitada sea destructiva;
- encuentre una ambigüedad funcional que determine el resultado;
- necesite credenciales externas;
- la autorización no cubra el siguiente paso.

---

# 32. Primera instrucción recomendada para Codex

```text
Abre y recorre este repositorio en modo de solo lectura.

Lee AGENTS.md y toda la documentación ubicada en
storage/agents/docs/suscripciones/.

Verifica primero que:

- la rama sea feature/asistente;
- Laravel use la base asistente_portal_4n;
- el mailer sea log;
- OneDrive esté desactivado.

No modifiques archivos.
No escribas en la base de datos.
No ejecutes migraciones.
No envíes correos.
No llames servicios externos.

Después inspecciona rutas, controladores, servicios, modelos, migraciones,
vistas y JavaScript del módulo Suscripciones.

Entrega un informe detallado demostrando que comprendiste:

- el flujo mensual;
- las tablas;
- las fórmulas;
- las zonas;
- los ajustes;
- las pre-facturas;
- la tributación;
- los envíos;
- la idempotencia;
- la frontera transaccional;
- los caminos alternativos;
- las contradicciones o ambigüedades.

Detente después del informe y espera autorización.
```

---

# 33. Segunda instrucción recomendada

Después de aprobar la comprensión:

```text
Continúa en modo de solo lectura.

Localiza el Excel histórico de mayo ubicado en storage/agents/docs/.
Informa su nombre exacto, hojas, columnas, período, totales y estructura.

Inspecciona en asistente_portal_4n todos los datos de entrada y resultados de
mayo.

No regeneres todavía.
No elimines registros.
No modifiques la base.

Entrega:

- inventario de entradas;
- inventario de resultados;
- calendario zonal disponible;
- ajustes;
- cantidades variables;
- pagos adicionales;
- detalles existentes;
- estrategia de conciliación;
- plan de regeneración aislada;
- consultas y comandos que necesitarían autorización.

Detente y espera autorización.
```

---

# 34. Regla de mantenimiento

Actualizar este documento cuando cambie:

- rama;
- worktree;
- nombre de base aislada;
- política de correo;
- bandera de OneDrive;
- ubicación del Excel;
- período de referencia;
- estrategia de regeneración;
- entorno de pruebas;
- comandos;
- autorizaciones;
- formato de hallazgos.
