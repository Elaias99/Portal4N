# Módulo Courier

Documentación para personas y agentes de IA que vayan a trabajar en el módulo
Courier de Portal4N. Léela en orden la primera vez; después cada archivo sirve
por separado.

## Qué es

4N Logística reparte bultos (paquetes) de sus clientes a través de una red de
**agentes** externos: transportistas que cubren una o varias comunas. Cada mes,
4N debe pagarle a cada agente por los bultos que repartió.

Ese cálculo lo hacía el jefe de Operaciones en una planilla Excel que solo él
entendía. El módulo Courier reemplaza esa planilla: carga los datos del mes,
aplica las mismas reglas y deja registro de todo.

## Para qué sirve

- Saber, cada mes, **cuánto se le paga a cada agente** y por qué (bulto por bulto).
- Detectar **qué bultos no se pueden pagar** porque les falta una regla
  (comuna desconocida, cliente sin tarifa definida, estado sin regla).
- Dejar **trazabilidad**: quién cargó qué archivo, cuándo, y qué se aplicó.
- Servir de base a Finanzas para pre-facturas y nómina de banco, con el mismo
  patrón que ya usa el módulo Suscripciones.

## Quiénes participan

| Rol | Quién | Qué hace |
|---|---|---|
| Operaciones | jefe de Operaciones (Luis) | Dueño de las reglas: qué agente cubre cada comuna, qué se paga y con qué tarifa. Entrega la planilla y los pesajes. |
| Finanzas | jefa de Finanzas (Natalia) | Usuario principal del resultado: cuánto pagar, a quién, con IVA o sin IVA. |
| Desarrollo | Elías | Único desarrollador. Aprendió el proceso por ingeniería inversa de la planilla. |

## Estado al 17-09-2026

| Fase | Estado |
|---|---|
| 1. Catálogos (reglas) cargados y consultables | Hecha |
| 2. Datos del mes: descarga de Geolice y pesajes de bodega, con diagnóstico | Hecha |
| 3. Cálculo del pago por bulto | No construida |
| 4. Resumen por agente, IVA, nómina banco, pre-facturas | No construida |
| Pantalla raíz (proceso del mes) | Existe, pero será rediseñada: muestra demasiado a la vez |

## Mapa de documentos

| Archivo | Qué explica |
|---|---|
| `01-negocio.md` | Cómo se pagaba Courier con la planilla, quién hacía qué y por qué eso no bastaba. |
| `02-cadena-de-pago.md` | La regla de pago bulto por bulto, tal como la entendimos. Es el corazón del módulo. |
| `03-lo-construido.md` | Qué existe hoy en Portal4N: tablas, comandos, pantallas, servicios y sus convenciones. |
| `04-modelo-de-datos.md` | Las tablas `courier_*`, sus columnas, llaves y relaciones. |
| `05-decisiones-y-pendientes.md` | Decisiones tomadas y por qué; preguntas abiertas para Operaciones; próximos pasos. |
| `06-como-trabajar-con-elias.md` | Reglas de trabajo que Elías pidió expresamente. Obligatorio para cualquier agente. |
| `construccion-replica.md` | Documento histórico y detallado de cómo se replicó la planilla en Excel (agosto 2026). Tiene cifras de esa réplica; úsalo solo como referencia de fórmulas. |

## Vocabulario mínimo

- **Bulto**: un paquete. Su código es la identidad en todo el proceso:
  `4N202608148928-529` = prefijo `4N` + fecha `20260814` + correlativo + sufijo.
- **Geolice**: sistema de seguimiento de envíos. De ahí se descarga la lista de
  bultos del mes (un xlsx de 31 columnas).
- **Agente** (la planilla dice *Operador*): el proveedor que reparte en una
  comuna. Ejemplos: `4N RM`, `Carlos Villaseca (Talca)`.
- **Comerciante**: cliente de 4N que despacha bultos. Ejemplos: `Revesderecho`,
  `Chilepost`.
- **Servicio**: tipo de envío contratado. Ejemplo: `Servicio Standar (Ecommerce)`.
- **Tabla** (el jefe la llama *centro de costo*): una tarifa, es decir, una
  columna de precios por kilo.
- **Configuración de pago**: la regla agente + comerciante + servicio →
  ¿se paga? ¿con qué tabla?
- **Pesaje**: peso de balanza tomado en la bodega de 4N.
- **Período**: mes de pago, código `AAAAMM`.
- **Lanas**: grupo de comerciantes (Revesderecho y otros) con tarifa plana por
  bulto. El resto son *Variables*.
