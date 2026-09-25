---
titulo: Pagos, cruces y programación de Cuentas por Pagar
modulo: Cuentas por Pagar
estado: Vigente
ultima_actualizacion: 2026-09-24
---

# Pagos, cruces y programación

## 1. Abono

El abono es parcial. Requiere monto positivo no superior al saldo y fecha no
futura. Crea `abonos`, recalcula saldo, marca `Abono` y registra
`MovimientoCompra`.

## 2. Pago y pronto pago

El pago y pronto pago cierran el saldo en cero y crean, respectivamente,
registros en `pagos` y `pronto_pagos`. Ambos validan fecha no futura y evitan
duplicar su propio tipo de movimiento. Su eliminación recalcula el documento y
restaura la gestión vigente.

## 3. Cruce CxP–CxC

Un cruce utiliza documentos `DocumentoFinanciero` de CxC cuyo RUT cliente es el
RUT proveedor de la compra. Excluye tipos 61 y 56, exige cobranza de CxC y usa
transacción con `lockForUpdate`.

El monto aplicado por documento CxC es:

```text
min(saldo CxP restante, saldo CxC disponible)
```

Se crea un cruce por cada documento CxC utilizado, se recalculan ambos lados y
se genera trazabilidad tanto en CxP como en CxC.

## 4. Pago masivo

El controlador global permite operaciones `pago` o `abono` por documento. Para
pagos genera saldo cero; para abonos valida el monto. Puede preparar archivos
por empresa, pero registrar una operación interna no acredita que el banco haya
ejecutado la transferencia.

## 5. Próximo pago

La programación valida fecha desde hoy y omite documentos sin saldo, cerrados,
con pronto pago, con pago o NC. Usa `updateOrCreate`, por lo que existe una
programación vigente por documento.

Programar y exportar agrupa por empresa y entrega URLs temporales. Los
proveedores con forma de pago `Portal Proveedor` pueden programarse, pero se
omiten del archivo bancario.

## 6. Eliminación de programación

Eliminar programación no revierte un pago porque no lo creó. La ruta tiene una
restricción explícita por IDs de usuarios financieros; cualquier cambio de este
criterio requiere una decisión de autorización, no solo una modificación visual.
