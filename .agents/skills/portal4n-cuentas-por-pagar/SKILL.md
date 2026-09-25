---
name: portal4n-cuentas-por-pagar
description: Usa esta skill cuando una tarea involucre Cuentas por Pagar de Portal4N, incluyendo RCV del SII, documentos de compra, proveedores, vencimientos, saldos, notas de crédito, referencias, abonos, cruces, pagos, próximos pagos o exportaciones bancarias.
---

# Portal4N — Cuentas por Pagar

## Propósito

Ayudar a analizar, desarrollar y mantener Cuentas por Pagar de Portal4N sin
alterar la integridad de documentos RCV, saldos, referencias ni trazabilidad.

La fuente principal de conocimiento es:

```text
storage/agents/docs/cuentas_por_pagar/
```

También deben respetarse las instrucciones generales de `AGENTS.md`.

## Modo de trabajo por defecto

Trabaja en **modo solo lectura** salvo que el usuario autorice explícitamente
una modificación.

En este modo se permite leer código y documentación, revisar rutas, modelos,
controladores, migraciones, vistas, JavaScript, exportadores, diferencias Git y
ejecutar comprobaciones estrictamente no destructivas. Se pueden proponer
cambios y pruebas, pero no aplicarlos.

## Reglas del dominio

- La identidad lógica de una importación RCV es `empresa_id +
  tipo_documento_id + rut_proveedor + folio`; nunca usar solo el folio.
- La empresa receptora se resuelve por el RUT incluido en el nombre del archivo
  RCV. No reasignar documentos entre empresas por coincidencias de proveedor.
- `CobranzaCompra` entrega crédito, forma de pago y datos bancarios. La falta de
  configuración no autoriza a inventar vencimiento ni datos de pago.
- `status_original` es automático por vencimiento; `estado` es una gestión
  manual/operativa. No tratarlos como el mismo campo.
- El saldo depende de pagos, pronto pagos, abonos, cruces, notas de crédito,
  notas de débito y referencias. Antes de modificar una fórmula, revisar todos
  sus caminos de creación y reversión.
- Una referencia documental debe mantenerse dentro de la misma empresa y RUT de
  proveedor. Programar o exportar un pago no equivale a pagarlo.
- `movimientos_compras` es trazabilidad financiera: no borrar ni reconstruir
  datos históricos sin autorización explícita y revisión del impacto.

## Modificación de código y datos

No modifiques código, documentación ni datos del repositorio por iniciativa
propia. Una autorización para un arreglo puntual no autoriza refactorizaciones,
cambios adicionales, operaciones Git de publicación ni acciones sobre otros
módulos.

Antes de modificar, identifica la regla afectada, archivos involucrados,
resultado esperado, riesgo histórico y pruebas necesarias.

Nunca por iniciativa propia:

- importes un RCV real;
- alteres documentos, saldos, referencias o movimientos históricos;
- ejecutes migraciones destructivas o escrituras masivas;
- generes pagos reales, envíes correos o uses integraciones bancarias;
- realices deploy o cambies producción.

## Documentación

Carga solo los documentos pertinentes de
`storage/agents/docs/cuentas_por_pagar/`; no los dupliques en esta skill.

- Para importación y proveedor: `importacion-rcv.md` y
  `proveedores-y-vencimientos.md`.
- Para saldo o estados: `saldos-y-estados.md`.
- Para NC: `referencias-notas-credito.md`.
- Para pagos y panel: `pagos-cruces-y-programacion.md` y
  `exportaciones-y-panel-finanzas.md`.
- Para cambios o auditorías: siempre revisar `riesgos-conocidos.md` y
  `pruebas.md`.

## Regla fundamental

Si existe duda sobre si una acción puede persistir información, modificar un
saldo, afectar una transferencia o tener efecto externo, detenerse en el
análisis y solicitar autorización explícita.
