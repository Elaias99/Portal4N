---
titulo: Riesgos conocidos de Cuentas por Pagar
modulo: Cuentas por Pagar
estado: Vigente
ultima_actualizacion: 2026-09-24
---

# Riesgos conocidos

Este documento registra observaciones de revisión estática. No autoriza cambios
ni afirma que haya datos históricos afectados.

## 1. Referencias rápida y masiva sin validación completa

**Severidad: crítica.** `asignarReferencia` y `asignarReferencias` validan que
los IDs existan, pero no replican las validaciones de misma empresa, mismo RUT,
documento distinto y tipo compatible que sí tiene `asignarNuevaReferencia`.

Escenario: una petición manipulada o integración envía una NC y factura de
empresas/proveedores diferentes. Resultado posible: referencia y recálculo de
saldos ajenos. Corrección mínima: centralizar y aplicar las mismas validaciones
en todos los endpoints. Pruebas: referencias cruzadas por empresa, RUT y tipo.

## 2. Llave de importación sin índice único confirmado

**Severidad: alta.** La prevención de duplicados se realiza con `exists()` en
el importador; no se observó una restricción única equivalente en las migraciones
de `documentos_compras`.

Escenario: dos importaciones simultáneas pasan ambas la consulta previa. Riesgo:
duplicar saldo y operaciones. Corrección mínima: índice único compuesto y manejo
de conflicto. Pruebas: importaciones concurrentes.

## 3. Orden de rutas de sugerencias

**Severidad: media.** `/finanzas/compras/{documento}` está declarada antes de
`/finanzas/compras/sugerencias`. Debe verificarse mediante prueba de ruta si el
segmento `sugerencias` es capturado como parámetro del detalle. Corrección mínima:
declarar la ruta estática primero. Prueba: request autenticada a ambas URLs.

## 4. Impacto histórico de crédito

**Severidad: media, decisión funcional pendiente.** Cambiar `creditos` de una
ficha reprocesa vencimientos de todos los documentos asociados, incluidos los
históricos. Confirmar con Finanzas si esa es la política esperada.

## 5. Principio de mantenimiento

Todo hallazgo nuevo debe indicar severidad, archivo/método, regla afectada,
escenario, resultado actual, esperado, riesgo histórico, corrección mínima y
pruebas necesarias.
