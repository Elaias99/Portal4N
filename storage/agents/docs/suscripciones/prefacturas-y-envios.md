---
titulo: Pre-facturas y envíos del módulo Suscripciones
modulo: Suscripciones
tipo_documento: Referencia funcional y técnica para análisis asistido por Codex
estado: Vigente
ultima_actualizacion: 2026-07-29
ubicacion_recomendada: storage/agents/docs/suscripciones/prefacturas-y-envios.md
---

# Pre-facturas y envíos del módulo Suscripciones

## 1. Propósito

Este documento describe el flujo de consolidación, tributación, generación y
distribución de pre-facturas del módulo **Suscripciones**.

Su objetivo es permitir que Codex pueda:

- comprender cómo se agrupan los detalles;
- distinguir proveedor base de proveedor efectivo;
- revisar cálculo tributario;
- verificar consistencia entre pantalla, PDF, ZIP y correo;
- analizar destinatarios;
- revisar integración con OneDrive;
- construir escenarios ficticios;
- reportar posibles bugs sin modificar código durante una auditoría.

---

## 2. Origen de datos

Las pre-facturas se construyen desde:

```text
suscripcion_liquidacion_detalles
```

considerando:

- asignación;
- proveedor base;
- proveedor efectivo;
- transportista;
- ajustes;
- período;
- grupo de pre-factura;
- tipo documental;
- costo;
- cantidad;
- total.

---

## 3. Componentes principales

### Controlador

```php
App\Http\Controllers\SuscripcionLiquidacionDetalleController
```

Responsabilidades conocidas:

- listado;
- filtros;
- resumen;
- visualización individual;
- PDF;
- ZIP;
- revisión de destinatarios;
- correo de prueba;
- envío real;
- OneDrive.

### Servicios

```php
SuscripcionAjusteMensualService
SuscripcionPrefacturaAgrupacionService
SuscripcionLiquidacionResumenService
SuscripcionPrefacturaOcService
SuscripcionPrefacturaPdfService
SuscripcionPrefacturaZipService
SuscripcionPrefacturaEnvioService
SuscripcionOneDriveService
```

---

## 4. Proveedor base y efectivo

### Proveedor base

Proviene de la asignación.

### Proveedor efectivo

Resulta después de considerar ajustes mensuales.

Toda operación de pre-factura debe utilizar el efectivo cuando exista.

No deben diferir:

- listado;
- vista individual;
- PDF;
- ZIP;
- correo;
- datos bancarios;
- destinatario;
- tributación;
- OC.

---

## 5. Clave de agrupación

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
código
RUT
transportista
```

---

## 6. Grupo de pre-factura

Campo origen:

```text
grupo_prefactura
```

Puede ser:

- valor explícito;
- nulo;
- vacío;
- con espacios;
- efectivo por ajuste.

Debe normalizarse mediante un servicio común.

Pantalla, PDF, ZIP y correo deben usar la misma regla.

---

## 7. Proveedor con varios grupos

Un proveedor puede tener más de una pre-factura en el mismo período.

Ejemplo conceptual:

```text
Proveedor A
├── Grupo 1
├── Grupo 2
└── Sin grupo
```

El listado general puede consolidar por proveedor, pero los documentos deben
respetar cada grupo.

---

## 8. Servicio de agrupación

Clase:

```php
SuscripcionPrefacturaAgrupacionService
```

Responsabilidades:

- resolver clave de grupo;
- normalizar nulos y vacíos;
- producir etiqueta;
- seleccionar detalles del grupo;
- garantizar consistencia entre salidas.

Codex debe revisar si hay lógica duplicada en controladores o vistas.

---

## 9. Listado general

El método `index()` puede:

1. filtrar por período;
2. cargar detalles;
3. resolver proveedor efectivo;
4. aplicar filtros;
5. agrupar resultados;
6. calcular resúmenes;
7. paginar;
8. mostrar totales por tipo.

Los filtros que dependen de proveedor efectivo pueden aplicarse en colección y
no totalmente en SQL.

Esto puede afectar:

- rendimiento;
- conteos;
- paginación;
- memoria;
- resultados parciales.

---

## 10. Resumen esperado

El listado muestra categorías como:

```text
BOLETA
FACTURA
DOCUMENTO
TOTAL
```

Cada categoría puede incluir:

```text
cantidad de pre-facturas
total final
```

El conteo representa documentos agrupados, no líneas individuales.

---

## 11. Servicio tributario

Clase:

```php
SuscripcionLiquidacionResumenService
```

Debe ser la fuente común de:

- subtotal;
- impuesto;
- retención;
- líquido;
- textos tributarios;
- total final.

No conviene recalcular por separado en:

- Blade;
- PDF;
- controlador;
- correo;
- JavaScript.

---

## 12. Factura

Regla documentada:

```text
IVA = 19 %
```

El servicio debe definir si el total de líneas representa neto o base distinta.

Codex debe verificar la fórmula real.

La vista y PDF deben mostrar los mismos valores.

---

## 13. Boleta

Regla documentada:

```text
retención = 15,25 %
```

Fórmula de referencia:

```text
retención = total bruto × 15,25 %
líquido = total bruto - retención
```

Debe respetarse una única política de redondeo.

---

## 14. Documento

Regla documentada:

```text
sin IVA
sin retención
líquido = total
```

Un tipo desconocido no debe tratarse silenciosamente como documento.

---

## 15. Redondeo

Codex debe verificar:

- redondeo por línea;
- redondeo sobre total;
- enteros en pesos;
- uso de `round`;
- diferencias PHP/SQL;
- formateo visual;
- suma de valores ya redondeados.

Pantalla, PDF y correo deben coincidir.

---

## 16. OC / NRO

Servicio:

```php
SuscripcionPrefacturaOcService
```

Responsabilidades:

- resolver número;
- usar período correcto;
- evitar colisiones;
- mantener consistencia entre vista y PDF.

Codex debe verificar si depende de:

- proveedor;
- grupo;
- período;
- secuencia;
- fecha.

---

## 17. Vista individual

El flujo debe:

1. recibir un detalle de referencia;
2. resolver proveedor efectivo;
3. resolver grupo;
4. recuperar todos los detalles del grupo;
5. calcular resumen;
6. mostrar pre-factura.

No debe incluir detalles de:

- otro grupo;
- otro período;
- otro proveedor efectivo.

---

## 18. PDF individual

Servicio:

```php
SuscripcionPrefacturaPdfService
```

Responsabilidades:

- reunir detalles correctos;
- resolver proveedor efectivo;
- resolver grupo;
- calcular resumen;
- resolver OC;
- renderizar Blade PDF;
- generar nombre de archivo;
- devolver contenido y metadatos.

---

## 19. Contenido esperado del PDF

Puede incluir:

```text
período
proveedor
RUT
centro de costo
dirección
comuna
OC / NRO
datos para generar documento
fecha
detalle
valor unitario
cantidad
total
subtotal o bruto
impuesto o retención
líquido
datos bancarios
```

Los datos deben corresponder al proveedor efectivo.

---

## 20. Consistencia PDF

El PDF debe coincidir con:

- vista individual;
- resumen tributario;
- líneas;
- cantidades;
- costos;
- totales;
- grupo;
- proveedor;
- período.

No debe recalcular con otra fórmula.

---

## 21. Nombre de archivo

Debe:

- identificar tipo;
- proveedor;
- período;
- grupo cuando corresponda;
- evitar caracteres inválidos;
- evitar colisiones;
- ser estable;
- no exponer datos innecesarios.

Codex debe revisar tildes, espacios, barras y nombres duplicados.

---

## 22. ZIP masivo

Servicio:

```php
SuscripcionPrefacturaZipService
```

Responsabilidades:

- agrupar documentos;
- generar PDFs;
- evitar colisiones;
- construir ZIP;
- devolver ruta y nombre;
- administrar temporales.

---

## 23. Selección para ZIP

Debe respetar:

- año;
- mes;
- filtros;
- proveedor efectivo;
- grupo;
- tipo documental si aplica.

No debe incluir documentos fuera del conjunto solicitado.

---

## 24. Archivos temporales

Codex debe revisar:

- carpeta;
- permisos;
- limpieza;
- nombres;
- concurrencia;
- colisión;
- exposición pública;
- archivos huérfanos;
- tamaño.

Un fallo no debe dejar datos sensibles indefinidamente.

---

## 25. OneDrive

Servicio:

```php
SuscripcionOneDriveService
```

Responsabilidades:

- autenticar;
- seleccionar carpeta;
- subir ZIP;
- reportar resultado;
- no recalcular documentos.

Codex debe revisar:

- tokens;
- expiración;
- secretos;
- reintentos;
- timeouts;
- carpetas;
- nombres;
- errores parciales.

---

## 26. Política ante fallo de OneDrive

Debe verificarse si:

- se permite descarga local;
- se bloquea toda la operación;
- se informa advertencia;
- se reintenta;
- se registra error.

No debe asumirse.

---

## 27. Servicio de envío

Clase:

```php
SuscripcionPrefacturaEnvioService
```

Responsabilidades:

- preparar destinatarios;
- distinguir prueba y real;
- generar adjunto correcto;
- enviar;
- reportar resultado por documento;
- manejar faltantes.

---

## 28. Revisión de destinatarios

Antes del envío real, el sistema debe permitir revisar:

- proveedor;
- grupo;
- correo;
- tipo documental;
- período;
- archivo.

La revisión debe usar proveedor efectivo.

---

## 29. Modo prueba

Debe:

- usar destinatario controlado;
- no enviar al proveedor real;
- adjuntar el mismo PDF que usaría el envío real;
- indicar que es una prueba;
- mantener trazabilidad.

Codex debe revisar que una dirección real no se mezcle accidentalmente.

---

## 30. Modo real

Debe:

- requerir confirmación explícita;
- validar correo;
- adjuntar documento correcto;
- registrar fallos;
- no duplicar envíos por reintento;
- informar resultado.

---

## 31. Destinatarios faltantes

Un proveedor sin correo:

- puede tener pre-factura;
- puede generar PDF;
- no debería enviarse silenciosamente;
- debe aparecer como pendiente o error.

La ausencia de correo no debe eliminar el documento.

---

## 32. Reintentos de correo

Codex debe analizar:

- si existe registro de envío;
- si un reintento duplica correo;
- si se usan colas;
- si hay jobs;
- si se marca estado;
- si se puede reanudar parcialmente.

No debe asumir idempotencia.

---

## 33. Correo y adjunto

El archivo adjunto debe corresponder exactamente a:

```text
proveedor efectivo + período + grupo
```

Riesgos:

- adjunto de otro grupo;
- nombre correcto con contenido incorrecto;
- PDF regenerado con datos cambiados;
- destinatario base con proveedor efectivo distinto.

---

## 34. Datos bancarios

Deben corresponder al proveedor que recibe el pago.

Codex debe verificar si se leen desde:

- proveedor efectivo;
- cobranza_compras;
- snapshot;
- ajuste.

Un cambio maestro puede alterar documentos históricos.

---

## 35. Fecha del documento

Debe distinguirse entre:

- período liquidado;
- fecha de generación;
- fecha de documento;
- fecha de envío.

No deben confundirse.

---

## 36. Integridad histórica

Regenerar un PDF después de cambiar:

- proveedor;
- banco;
- cuenta;
- costo;
- grupo;
- puntos;
- tipo documental;

puede producir un documento diferente para el mismo período.

Codex debe identificar si existe snapshot suficiente.

---

## 37. Caché

Si existe caché de:

- PDFs;
- grupos;
- destinatarios;
- resúmenes;
- OC;

debe invalidarse correctamente cuando cambian datos efectivos.

Codex debe verificar si hay caché real antes de reportar.

---

## 38. Seguridad

Codex debe revisar:

- autorización para ver pre-facturas;
- autorización para descargar;
- autorización para envío real;
- validación de IDs;
- exposición de RUT;
- datos bancarios;
- rutas temporales;
- archivos públicos;
- destinatarios;
- logs con datos sensibles.

---

## 39. Consistencia entre salidas

Para una misma pre-factura deben coincidir:

```text
proveedor
RUT
período
grupo
líneas
cantidades
costos
totales
impuesto
retención
líquido
OC
```

en:

```text
listado
vista individual
PDF
ZIP
correo
OneDrive
```

---

## 40. Escenarios que Codex puede construir

- proveedor con un grupo;
- proveedor con varios grupos;
- grupo nulo;
- grupo vacío;
- grupo con espacios;
- proveedor efectivo distinto;
- cambio de tipo documental;
- factura;
- boleta;
- documento;
- tipo desconocido;
- redondeo con decimales;
- PDF con muchas líneas;
- nombres repetidos;
- dos proveedores con mismo nombre;
- ZIP concurrente;
- fallo de PDF;
- fallo de ZIP;
- falta de espacio;
- fallo de OneDrive;
- token vencido;
- correo faltante;
- correo inválido;
- modo prueba;
- modo real;
- reintento;
- envío parcial;
- cambio de datos después de generar;
- filtros sobre proveedor efectivo;
- paginación;
- OC duplicada;
- caracteres especiales.

La lista no limita el análisis.

---

## 41. Posibles bugs a detectar

- agrupación por proveedor base;
- mezcla de grupos;
- resumen tributario distinto;
- conteo de líneas como documentos;
- paginación antes de resolver proveedor;
- PDF con otro grupo;
- nombre de archivo colisionado;
- ZIP omite documentos;
- ZIP incluye otro período;
- correo real en modo prueba;
- destinatario incorrecto;
- adjunto incorrecto;
- datos bancarios del proveedor base;
- OneDrive recibe ZIP incompleto;
- archivos temporales no eliminados;
- doble envío;
- OC duplicada;
- tipo desconocido tratado como documento;
- redondeo inconsistente;
- filtros incompletos;
- falta de autorización;
- datos sensibles en logs.

---

## 42. Comportamiento confirmado de referencia

Julio de 2026:

```text
Pre-facturas: 62
Total general: 39.052.185
Boletas: 29
Final boletas: 12.181.965
Facturas: 32
Final facturas: 26.820.220
Documentos: 1
Final documentos: 50.000
```

Proveedor:

```text
ANDRES FERNANDO MUÑOZ FUENTES
```

Detalle:

```text
LA DEHESA / Reparto Fin de semana
valor: 32.000
cantidad: 6
total bruto: 192.000
retención 15,25 %: 29.280
líquido: 162.720
```

Este caso confirma consistencia entre generación y PDF para esa línea.

El snapshot no debe convertirse en una invariante permanente.

---

## 43. Consultas de diagnóstico no destructivas

### Grupos por proveedor y período

```sql
SELECT
    sa.suscripcion_proveedor_id,
    sld.anio,
    sld.mes,
    sa.grupo_prefactura,
    COUNT(*) AS lineas,
    SUM(sld.total) AS total
FROM suscripcion_liquidacion_detalles sld
JOIN suscripcion_asignaciones sa
    ON sa.id = sld.suscripcion_asignacion_id
GROUP BY
    sa.suscripcion_proveedor_id,
    sld.anio,
    sld.mes,
    sa.grupo_prefactura;
```

Esta consulta usa proveedor base y no reemplaza la resolución efectiva.

### Totales por período

```sql
SELECT
    anio,
    mes,
    COUNT(*) AS lineas,
    SUM(total) AS total_lineas
FROM suscripcion_liquidacion_detalles
GROUP BY anio, mes
ORDER BY anio, mes;
```

---

## 44. Auditoría en solo lectura

Cuando la tarea sea revisar pre-facturas y envíos, Codex debe:

- inspeccionar controlador;
- inspeccionar servicios;
- inspeccionar Mail;
- inspeccionar vistas PDF;
- inspeccionar almacenamiento;
- inspeccionar rutas;
- inspeccionar autorización;
- inspeccionar configuración OneDrive;
- construir escenarios;
- entregar hallazgos.

No debe:

- generar envíos reales;
- subir archivos;
- crear ZIP en carpetas productivas;
- modificar destinatarios;
- cambiar código;
- borrar temporales;
- ejecutar operaciones externas.

---

## 45. Regla de mantenimiento

Actualizar este documento cuando cambie:

- agrupación;
- proveedor efectivo;
- tipo documental;
- tasa;
- redondeo;
- OC;
- PDF;
- ZIP;
- destinatarios;
- correo;
- OneDrive;
- autorización;
- archivos temporales;
- política de reintentos.
