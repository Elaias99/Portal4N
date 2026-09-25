---
titulo: Referencias y notas de crédito de Cuentas por Pagar
modulo: Cuentas por Pagar
estado: Vigente
ultima_actualizacion: 2026-09-24
---

# Referencias y notas de crédito

## 1. Relación documental

`documentos_compras.referencia_id` apunta a otro documento de compra. La NC
tipo `61` puede referenciar una factura; la factura expone sus documentos
referenciados mediante `referenciados`.

Regla de dominio:

```text
misma empresa + mismo RUT proveedor + documento distinto
```

En la asignación manual desde detalle, una NC solo acepta una factura
electrónica tipo `33`.

## 2. Sugerencias

Después de importar NC recientes, `ReferenciaNotasCompraService` propone
facturas del mismo proveedor y empresa. Ordena por cercanía de fecha, cercanía
de monto y antigüedad. La sugerencia es ayuda operativa, no confirmación:
requiere asignación explícita.

## 3. Efecto financiero

Al asignar, quitar o cambiar una referencia se recalculan NC, factura nueva y,
si aplica, factura anterior. Para una factura tipo 33, el sincronizador:

- crea pago automático `referencia_nc` si NC la cubren totalmente;
- crea abono automático `referencia_nc` si la cobertura es parcial;
- elimina esos movimientos si ya no hay NC aplicables;
- no altera un cierre ya producido por pago real.

Los pagos con origen `referencia_nc` no se eliminan manualmente: se revierte la
referencia de la NC.

## 4. Endpoints

Existen asignación individual, masiva, desde detalle y eliminación. Todos deben
proteger las invariantes de empresa, proveedor y tipo documental. La validación
no debe confiar solamente en los candidatos mostrados por la interfaz.

## 5. Riesgo

Las rutas rápida y masiva revisadas validan existencia de IDs, pero no contienen
las mismas validaciones de empresa/RUT/tipo que el flujo manual de detalle. Ver
`riesgos-conocidos.md` antes de cambiar o consumir esos endpoints.
