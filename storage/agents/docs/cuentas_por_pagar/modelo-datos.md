---
titulo: Modelo de datos de Cuentas por Pagar
modulo: Cuentas por Pagar
estado: Vigente
ultima_actualizacion: 2026-09-24
---

# Modelo de datos de Cuentas por Pagar

## 1. Entidad central: `documentos_compras`

Representa una fila del RCV. Sus grupos de datos son:

| Grupo | Campos funcionales |
|---|---|
| Identidad | `id`, `empresa_id`, `tipo_documento_id`, `rut_proveedor`, `folio` |
| RCV | `nro`, `tipo_compra`, razón social, fechas, impuestos y montos |
| Gestión | `cobranza_compra_id`, `fecha_vencimiento`, `status_original`, `estado`, `fecha_estado_manual`, `saldo_pendiente` |
| Referencia | `referencia_id` |

Identidad lógica de importación:

```text
empresa_id + tipo_documento_id + rut_proveedor + folio
```

La migración revisada no declara un índice único para esta llave; el importador
la controla a nivel de aplicación. Ver `riesgos-conocidos.md`.

## 2. Relaciones

```text
Empresa 1 ── * DocumentoCompra
TipoDocumento 1 ── * DocumentoCompra
CobranzaCompra 1 ── * DocumentoCompra
DocumentoCompra 1 ── * Abono / Cruce / Pago / ProntoPago
DocumentoCompra 1 ── 0..1 DocumentoCompraPagoProgramado
DocumentoCompra 1 ── * DocumentoCompra referenciado
```

`referencia_id` es una autorrelación: una NC normalmente apunta a su factura;
`referenciados` permite encontrar las NC/ND que afectan una factura.

## 3. Proveedor: `cobranza_compras`

Aunque su nombre es histórico, es la ficha del proveedor para CxP. Incluye RUT,
razón social, servicio, `creditos`, forma de pago, responsable y datos bancarios
(`nombre_cuenta`, `rut_cuenta`, banco, tipo y número de cuenta).

La asociación importada se busca por:

```text
cobranza_compras.rut_cliente = documentos_compras.rut_proveedor
```

## 4. Movimientos y saldo

| Tabla | Rol |
|---|---|
| `abonos` | Pagos parciales; puede tener `origen`. |
| `cruces` | Compensación entre CxP y CxC. |
| `pagos` | Cierre por pago manual, automático o por referencia. |
| `pronto_pagos` | Cierre por pronto pago. |
| `movimientos_compras` | Auditoría de operaciones y cambios. |
| `documento_compra_pagos_programados` | Fecha/observación de próximo pago. |

Un movimiento financiero apunta a `documento_compra_id`; las tablas compartidas
también soportan documentos de CxC mediante `documento_financiero_id`.

## 5. Restricciones relevantes

- `documento_compra_pagos_programados.documento_compra_id` es único: hay una
  programación vigente por documento.
- Relaciones financieras hacia compras tienen borrado en cascada según las
  migraciones revisadas.
- La ficha del proveedor no es necesariamente única por RUT en la estructura
  revisada; el código usa la primera coincidencia.

## 6. Datos históricos

No reconstruir ni normalizar masivamente documentos, movimientos o referencias
sin revisar impactos en saldos. `estado` y `status_original` no son sinónimos y
ambos forman parte del historial operativo.
