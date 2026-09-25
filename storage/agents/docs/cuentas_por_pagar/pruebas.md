---
titulo: Pruebas de Cuentas por Pagar
modulo: Cuentas por Pagar
estado: Vigente
ultima_actualizacion: 2026-09-24
---

# Pruebas de Cuentas por Pagar

## 1. Regla de seguridad

Las pruebas que escriban datos deben usar una base separada o transacciones
revertibles. Nunca importar RCV ni modificar documentos históricos del entorno
principal como parte de una prueba.

## 2. Importación

Comprobar:

- RUT válido/inválido en nombre de archivo.
- Empresa encontrada/no encontrada.
- Fila vacía y folio vacío.
- Normalización de encabezados, fechas y montos.
- Duplicado en archivo, en base y en importación concurrente.
- Proveedor existente y proveedor pendiente.
- Caja chica y fondo por rendir.

## 3. Vencimiento y estados

Comprobar crédito cero, fechas antes/hoy/después, cambio de créditos y
documentos sin proveedor. Verificar que estado automático y manual se mantengan
separados.

## 4. Saldos y movimientos

Comprobar pago, pronto pago, abono parcial, varios abonos, cruce parcial/completo,
reversión y valores que no pueden dejar saldo negativo. Verificar trazabilidad
en `movimientos_compras`.

## 5. Referencias

Comprobar NC sin referencia, parcial, total, cambio y eliminación de referencia.
Incluir explícitamente intentos entre otra empresa, otro proveedor, mismo
documento y tipo no permitido.

## 6. Programación y exportación

Comprobar documento cerrado, NC, fecha pasada, actualización de programación,
eliminación autorizada/no autorizada, agrupación por empresa, `Portal Proveedor`,
división sobre $7.000.000 y expiración del token de descarga.

## 7. Cruces CxP–CxC

Comprobar RUT distinto, saldo CxC cero, documento CxC sin cobranza, varios
documentos, orden de selección, concurrencia y reversión que recalcule ambos
lados.

## 8. Salida esperada

Toda suite debe explicar qué regla valida, datos propios usados, tablas afectadas
y resultado esperado. Las pruebas de integración deben confirmar tanto saldo y
estado como movimientos y exportación cuando corresponda.
