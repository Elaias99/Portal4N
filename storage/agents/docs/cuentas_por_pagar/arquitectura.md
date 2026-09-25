---
titulo: Arquitectura de Cuentas por Pagar
modulo: Cuentas por Pagar
estado: Vigente
ultima_actualizacion: 2026-09-24
---

# Arquitectura de Cuentas por Pagar

## 1. Entrada y capas

La entrada funcional es `/finanzas/compras`. El flujo central sigue esta
secuencia:

```text
Ruta → DocumentoCompraController → modelos/servicios → base de datos → Blade/Excel
```

`DocumentoCompraController` concentra listado, importación RCV, referencias,
exportaciones, cambios de estado, abonos, cruces y programación. Los pagos,
pronto pagos, eliminación de abonos/cruces y operaciones masivas se apoyan en
controladores transversales compartidos con Cuentas por Cobrar.

## 2. Componentes

| Componente | Responsabilidad |
|---|---|
| `DocumentoCompraController` | Flujo principal de CxP. |
| `ComprasImport` | Normaliza e inserta filas RCV. |
| `DocumentoCompra` | Relaciones, saldo, estado visible y vencimiento. |
| `CobranzaCompraController` | Mantención y reproceso de proveedores CxP. |
| `ReferenciaNotasCompraService` | Propone facturas para NC. |
| `SincronizarMovimientoReferenciaCompraService` | Materializa abono/pago automático por NC. |
| `PagoDocumentoController` | Pago individual y masivo; reversión de pago. |
| `ProntoPagoController` | Cierre/reversión de pronto pago. |
| `AbonoController`, `CruceController` | Edición o reversión de movimientos. |
| `PanelFinanzaController` | Operación diaria de pagos programados. |

## 3. Rutas relevantes

```text
GET    /finanzas/compras                         listado
POST   /finanzas/compras/import                  importación RCV
GET    /finanzas/compras/{documento}             detalle
PATCH  /finanzas/compras/{id}/estado             estado manual
POST   /finanzas/compras/{documento}/abono       abono
POST   /finanzas/compras/{documento}/cruce       cruce con CxC
POST   /finanzas/compras/proximo-pago            programación
POST   /finanzas-compras/proximo-pago/exportar   programación + archivo
POST   /compras/asignar-referencia(s)            referencias NC
```

También intervienen rutas globales para pago, pronto pago, edición/eliminación
de abonos y cruces, y el recurso `/cobranzas-compras` para proveedores.

## 4. Interfaz

Las pantallas principales son:

```text
resources/views/cobranzas/finanzas_compras/index.blade.php
resources/views/cobranzas/finanzas_compras/detalles.blade.php
resources/views/cobranzas/finanzas_compras/modal_estado.blade.php
resources/views/cobranzas/finanzas_compras/modal_proximo_pago.blade.php
```

`finanzas_compras_index.js` mantiene la selección de documentos en
`localStorage`. `finanzas_compras_proximo_pago.js` arma el modal, guarda la
programación y descarga archivos por empresa.

## 5. Integraciones internas

- CxC: un cruce usa `DocumentoFinanciero` del mismo RUT del proveedor.
- Panel Finanzas: muestra programaciones de hoy y atrasadas.
- Honorarios/Suscripciones: comparten la ficha `CobranzaCompra`; no deben
  asumir que una modificación es exclusiva de CxP.
- Excel: Laravel Excel importa RCV y genera reportes/archivos bancarios.

## 6. Fronteras transaccionales

El cruce CxP–CxC usa transacción y bloqueos de filas. La programación masiva
usa transacción. La importación, en cambio, procesa filas a través de Laravel
Excel y no está documentada como una transacción global del archivo.

Cambiar un flujo debe considerar que pago, archivo bancario y programación son
eventos distintos: una descarga Excel no confirma una transferencia bancaria.

## 7. Caminos históricos

Existe otro módulo bajo `/compras` (`CompraController`, modelo `Compra`). No
mezclar sus rutas, tablas, importadores ni reglas con `DocumentoCompra` sin una
decisión explícita de migración o compatibilidad.
