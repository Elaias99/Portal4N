# 04 · Modelo de datos

Todas las tablas llevan el prefijo `courier_` y tienen `id` y `timestamps`
salvo que se indique. No hay claves foráneas hacia otros módulos: Courier es
autocontenido (ver decisión en `05-decisiones-y-pendientes.md`).

## Vista general

```
courier_periodos ──┬── courier_bultos ──(seguimiento)── courier_pesajes
                   └── courier_importaciones

courier_agentes ──┬── courier_cobertura_comunas   (comuna → agente, zona)
                  ├── courier_configuracions      (agente+comerciante+servicio → pagar, tabla)
                  └── courier_agente_proveedors   (titular del pago, informativo)

courier_tarifas ──── courier_tarifa_tramos        (tabla → valor por kilo)
courier_estados_entrega                           (estado → PAGAR / DESCONTAR)
courier_pesos_transformados                       (texto de peso → entero)
courier_proveedores                               (quién cobra: RUT, documento, banco)
```

## Catálogos (sin período)

### `courier_agentes`
Hoja `Operador`, columna ComunaMatriz. `nombre` (único), `activo`.
Modelo `CourierAgentes` (plural, así quedó).

### `courier_cobertura_comunas`
Hoja `Operador`. `courier_agente_id`, `localidad` (texto crudo, tal como
llega de Geolice), `localidad_clave` (= `mb_strtolower(localidad)`, colación
`utf8mb4_bin`, **única**), `zona` (`RM` / `Regiones` / null), `pagar_retorno`,
`valor_retorno`, `activo`. Modelo `CourierCoberturaComuna`, con
`clave(string)` como única regla de normalización.

### `courier_configuracions`
Hoja `PagosCentroCostos`. `courier_agente_id`, `comerciante` y `servicio`
(texto crudo), `llave` (= `mb_strtolower(trim(agente).trim(comerciante).trim(servicio))`,
`utf8mb4_bin`, **única**), `pagar` (`SI` / `NO` / `REVISAR`), `tabla`
(número de tarifa, nullable), `activo`. Modelo `CourierConfiguracion`, con
`llave(agente, comerciante, servicio)` como única regla.

### `courier_tarifas` y `courier_tarifa_tramos`
Hoja `Pesos`. Tarifa: `numero` (único, 0 a 16), `nombre`, `kilo_adicional`
(valor por kilo desde el 21). Tramo (sin timestamps): `courier_tarifa_id`,
`peso` (1 a 20), `valor`. Modelo `CourierTarifa` con `tramos()` y
`esPlana()` (mismo valor en todos los tramos). El valor para un peso se
calcula en `CourierCatalogoService::valorPorPeso()`.

### `courier_estados_entrega`
Hoja `Estados`. `estado` (único, texto tal como lo informa Geolice),
`considerar` (`PAGAR` / `DESCONTAR`). No es FK desde los bultos: Geolice
puede informar estados nuevos y hay que cargarlos igual y avisarlos.

### `courier_pesos_transformados`
Hoja `PesoTransformado`. `texto` (único, como lo manda Geolice: `"3.50 kg"`,
`X`), `peso` (entero), `origen` (`catalogo` / `regla`).

### `courier_proveedores`
Hoja `DatosProveedores`. `operador`, `usuario`, `llave` (= operador.usuario,
`utf8mb4_bin`, única), `transportista`, `razon_social`, `rut` (nullable;
hay filas con `N/A`), `empresa`, `tipo_documento` (texto libre; el IVA
aplica solo cuando es exactamente `Factura`), `titular_banco`,
`rut_titular_banco`, `banco`, `tipo_cuenta`, `nro_cuenta`,
`cobranza_compra_id` (nullable, **hoy siempre vacío**, ver decisiones),
`activo`.

### `courier_agente_proveedors`
`courier_agente_id`, `nombre_proveedor`, `principal`. Titular del pago por
agente, informativo; el pago real se resuelve por `courier_proveedores`.

## Datos mensuales

### `courier_periodos`
`codigo` (`AAAAMM`, único), `anio`, `mes`, `estado` (`abierto` / `cerrado`),
`observacion`. Modelo `CourierPeriodo` con `nombre` ("Septiembre 2026") y
`estaCerrado()`.

### `courier_bultos`
Una fila por bulto de Geolice. Modelo `CourierBulto`.

**Identidad y origen:** `courier_periodo_id` (período en que entró),
`archivo_origen`, `seguimiento` (código con sufijo, **único en todo el
sistema**), `codigo` (sin sufijo, indexado; cruza con pesajes).

**Grupo A — las 31 columnas de Geolice, tal cual, solo con formato limpio:**
`peso_declarado` (decimal, desde `"0.31 kg"`), `largo`, `ancho`, `alto`,
`codigo_externo`, `centro_costo` (código del cliente en Geolice; **no** es
la "tabla" del jefe), `orden_compra`, `guia_despacho`, `estado_entrega`,
`intentos_entrega`, `comerciante`, `servicio`, `campana`,
`destinatario_nombre`, `destinatario_empresa`, `direccion`,
`comuna_destino`, `destinatario_telefono`, `destinatario_email`,
`valor_envio` (valor declarado del envío, sin relación con el pago),
`fecha_recepcion`, `entrega_estimada`, `fecha_entrega`,
`retiro_en_comerciante`, `bodega_retiro`, `ruta_entrega`,
`repartidor_nombre`, `repartidor_telefono`, `usuario_entrega`.

**Grupo B — lo que llena el cálculo (vacío al importar):**
`courier_agente_id`, `zona`, `courier_configuracion_id`, `considerar_pago`
(copia de `pagar`), `tabla` (copia), `peso_bodega`, `peso_pago`,
`origen_peso` (`bodega` / `declarado` / `x`), `valor`, `estado_pago`
(`PAGAR` / `DESCONTAR`), `motivo` (por qué se descontó), `calculado_at`.
Se copian `pagar` y `tabla` para que el bulto conserve lo que se le aplicó
aunque el catálogo cambie después.

Índices: único `seguimiento`; `codigo`; (`periodo`, `agente`); (`periodo`,
`estado_pago`).

### `courier_pesajes`
Una fila por bulto **y por día de pesaje**. Modelo `CourierPesaje`.
`courier_periodo_id`, `seguimiento` (con sufijo), `codigo` (sin sufijo),
`fecha_pesaje` (del nombre del archivo), `kilos` (entero; 0 = pasó por la
balanza sin peso), `archivo_origen`. Único (`seguimiento`, `fecha_pesaje`).
Relación `bulto()` por `seguimiento`, sin FK: el bulto puede llegar después.

### `courier_importaciones`
Historial de cargas. Modelo `CourierImportacion`. `courier_periodo_id`,
`tipo` (`geolice` / `pesajes`), `archivo`, `user_id` (nullable: las cargas
por terminal no tienen usuario), `filas`, `nuevos`, `actualizados`,
`duracion_seg`, `resumen` (JSON con el diagnóstico completo de esa carga).
Es una foto del momento; las alertas vivas se recalculan desde los bultos.

## Reglas de integridad que no están en la base

- Un bulto pertenece al **primer período** en que apareció. Si vuelve a
  aparecer en una descarga de otro período, no se mueve ni se modifica.
- `estado_entrega` y `comuna_destino` se guardan crudos; los cruces se hacen
  al buscar, con las funciones `clave()` y `llave()` de los modelos.
- `peso_declarado` null y `peso_bodega` null son casos distintos de
  `peso_bodega = 0`; ver paso 3 de `02-cadena-de-pago.md`.
