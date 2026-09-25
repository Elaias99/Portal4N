---
titulo: Exportaciones y Panel de Finanzas de Cuentas por Pagar
modulo: Cuentas por Pagar
estado: Vigente
ultima_actualizacion: 2026-09-24
---

# Exportaciones y Panel de Finanzas

## 1. Exportaciones de documentos

`DocumentoCompraExport` produce Excel de documentos filtrados o masivos. Incluye
empresa, tipo, proveedor, folio, fechas, estados, montos, saldo, referencias y
metadatos de recepción/creación.

La exportación debe reflejar el saldo resultante de los movimientos, no solo el
monto original del RCV.

## 2. Exportación de proveedores

`ProveedoresCuentasBancariasExport` lista toda `cobranza_compras`, con proveedor,
RUT, beneficiario, cuenta, banco y correo. Es una salida sensible: revisar datos
bancarios antes de compartirla.

## 3. Archivo bancario

`PagosMasivosDocumentoCompraExport` genera filas de transferencia por operación
y empresa. Usa cuenta corriente de la empresa, cuenta/banco del proveedor,
beneficiario, monto, glosas y correo.

Reglas implementadas:

- `Portal Proveedor` no se exporta al banco.
- Transferencias mayores a $7.000.000 se dividen en filas.
- Para pago total de tipo documental 46, el monto exportado se limita al neto.
- El correo usa `correo_suscripciones` o una regla histórica por responsable.

## 4. Panel de Finanzas

El panel muestra compras programadas para hoy y atrasadas. Permite seleccionar
documentos para pagar o quitar programación mediante rutas compartidas de pago
masivo y programación.

El panel es una vista operativa; no debe ser la única fuente para determinar el
saldo ni reemplazar la trazabilidad almacenada en el documento.

## 5. Descargas temporales

Los archivos por empresa se guardan temporalmente en caché con token y se
consumen al descargar. Una URL vencida o descargada no debe interpretarse como
un error de pago ni como evidencia de transferencia confirmada.
