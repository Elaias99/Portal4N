---
titulo: Proveedores y vencimientos de Cuentas por Pagar
modulo: Cuentas por Pagar
estado: Vigente
ultima_actualizacion: 2026-09-24
---

# Proveedores y vencimientos

## 1. Ficha de proveedor

La ficha `CobranzaCompra` es necesaria para operar un proveedor: aporta días de
crédito, forma de pago, responsable, prioridad y datos para transferencia.

El CRUD está bajo `/cobranzas-compras`; además existe una vista de salud que
señala ausencia de servicio, crédito o datos bancarios.

## 2. Cálculo de vencimiento

Para un documento asociado:

```text
fecha_vencimiento = fecha_docto + créditos del proveedor
```

Con saldo positivo, el estado automático es `Vencido` si la fecha ya pasó y
`Al día` en caso contrario. El listado también actualiza estos estados según la
fecha actual.

## 3. Cambio de créditos

El evento `updated` de `CobranzaCompra` detecta modificaciones de `creditos` y
llama `actualizarFechaVencimiento()` para todos sus documentos asociados.

Por tanto, cambiar crédito tiene impacto histórico y operativo; no debe hacerse
sin confirmar que el nuevo plazo debe aplicarse a documentos ya importados.

## 4. Proveedor pendiente

Los documentos sin ficha no reciben vencimiento inventado. El reproceso busca
los documentos sin `cobranza_compra_id` para los RUT pendientes de la sesión,
asigna la nueva ficha y calcula fecha/estado. También respeta las formas de pago
automático.

## 5. Datos bancarios

La exportación bancaria usa cuenta de origen de la empresa y cuenta destino,
banco, RUT, nombre y correo desde la ficha del proveedor. Una ficha incompleta
puede impedir una transferencia útil aunque el documento sea válido tributariamente.
