---
titulo: Saldos y estados de Cuentas por Pagar
modulo: Cuentas por Pagar
estado: Vigente
ultima_actualizacion: 2026-09-24
---

# Saldos y estados

## 1. Dos estados distintos

`status_original` es el estado calculado por vencimiento. `estado` registra la
gestión manual u operativa. El valor mostrado al usuario prioriza `estado`; un
estado manual `Pago` se presenta como `Pagado`.

No usar una etiqueta visual como sustituto de saldo o de un movimiento real.

## 2. Saldo pendiente

El accesor `saldo_pendiente` devuelve el valor almacenado si no existen
movimientos que obliguen recalcular. Si existen pagos, abonos, cruces, pronto
pago, referencias o documentos referenciados, llama a
`DocumentoCompra::recalcularSaldoPendiente()`.

Reglas confirmadas:

1. Pago real o pago automático por referencia: saldo `0`.
2. Pronto pago: saldo `0`.
3. NC tipo 61: con referencia saldo `0`; sin referencia saldo igual a su total.
4. Factura: parte desde monto total.
5. NC referenciadas restan; ND tipo 56 referenciadas suman.
6. Abonos y cruces restan.
7. El resultado se limita a cero como mínimo.

## 3. Operaciones y estado resultante

| Operación | Saldo | Estado manual |
|---|---:|---|
| Abono | disminuye | `Abono` |
| Cruce | disminuye | `Cruce` |
| Pago | 0 | `Pago` |
| Pronto pago | 0 | `Pronto pago` |
| Programación | no cambia | no debe cambiar |

Al revertir pago o pronto pago, el código recompone el estado con prioridad:

```text
Pago > Pronto pago > Cruce > Abono > estado automático
```

## 4. Fecha de gestión

`fecha_estado_manual` registra cambios manuales. La fecha de última gestión se
obtiene desde la fecha máxima de abono, cruce, pago o pronto pago cargados.

## 5. Regla de mantenimiento

Antes de cambiar saldo, estado o una reversión, revisar el modelo
`DocumentoCompra`, los cuatro controladores de movimientos, referencias NC y
pagos masivos. Cambiar solo una capa provoca divergencias entre listado,
detalle, exportación y panel.
