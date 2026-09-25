---
titulo: Módulo Cuentas por Pagar
modulo: Cuentas por Pagar
estado: Vigente
ultima_actualizacion: 2026-09-24
ubicacion: storage/agents/docs/cuentas_por_pagar/README.md
---

# Módulo Cuentas por Pagar

## 1. Propósito

El módulo **Cuentas por Pagar** registra y administra documentos de compra
recibidos desde el Registro de Compras (RCV) descargado desde el SII.

Su ruta funcional principal es:

```text
/finanzas/compras
```

El módulo convierte las filas del RCV en documentos internos por pagar y
permite controlar su vencimiento, saldo, regularización, referencias,
programación de pago y exportación bancaria.

No debe confundirse con el recurso histórico `/compras`, administrado por
`CompraController`. El flujo documentado aquí usa principalmente:

```text
DocumentoCompraController
DocumentoCompra
documentos_compras
```

## 2. Alcance funcional

El flujo vigente conocido permite:

1. descargar un RCV de compras desde el SII para una empresa;
2. importar el archivo sin editar su contenido;
3. identificar la empresa receptora mediante el RUT del nombre del archivo;
4. crear documentos de compra no duplicados;
5. asociar la configuración del proveedor por RUT;
6. calcular fecha de vencimiento según los días de crédito;
7. mostrar documentos pendientes, pagados, al día o vencidos;
8. registrar estados, abonos, cruces, pagos y pronto pago;
9. vincular notas de crédito con facturas de compra;
10. programar próximos pagos;
11. generar archivos de pago agrupados por empresa;
12. consultar la trazabilidad de las operaciones.

## 3. Flujo conceptual

```text
Archivo RCV descargado desde SII
        ↓
Validación del archivo y RUT en su nombre
        ↓
Resolución de empresa receptora
        ↓
Importación de filas en documentos_compras
        ↓
Asociación con cobranza_compras por RUT del proveedor
        ↓
Cálculo de vencimiento, estado automático y saldo inicial
        ↓
Gestión documental y financiera
        ↓
Programación, pago efectivo y exportación bancaria
```

## 4. Conceptos principales

### 4.1. Documento de compra

Un `DocumentoCompra` representa una fila importada desde el RCV. Conserva los
datos tributarios y comerciales del archivo, junto con campos internos de
gestión como saldo pendiente, vencimiento, estados y referencias.

Tabla principal:

```text
documentos_compras
```

### 4.2. Empresa receptora

La empresa se identifica desde el RUT incluido en el nombre del archivo RCV.
Por ejemplo:

```text
RCV_COMPRA_REGISTRO_77639015-1_202601
```

El RUT `77639015-1` debe coincidir con una empresa registrada antes de que el
archivo pueda importarse.

### 4.3. Configuración del proveedor

La tabla `cobranza_compras` contiene la configuración operacional y bancaria
del proveedor de Cuentas por Pagar, incluyendo días de crédito, forma de pago,
cuenta bancaria y correo.

La relación vigente es:

```text
documentos_compras.cobranza_compra_id
→ cobranza_compras.id
```

La asociación se busca por RUT del proveedor. Si no hay configuración, el
documento puede importarse sin vencimiento y queda disponible para el flujo de
creación y reprocesamiento del proveedor.

### 4.4. Estado automático y estado manual

El módulo separa dos conceptos:

- `status_original`: resultado automático de la fecha de vencimiento, como
  `Al día`, `Vencido`, `Pendiente` o `Sin cálculo`.
- `estado`: gestión manual u operativa, como `Abono`, `Cruce`, `Pago`,
  `Pronto pago` o `Cobranza judicial`.

El estado visible prioriza la gestión manual cuando existe.

### 4.5. Saldo pendiente

El saldo parte desde el monto total del documento, salvo reglas especiales.
Puede variar por pagos, pronto pago, abonos, cruces, notas de crédito, notas de
débito y referencias documentales.

El cálculo central se encuentra en el modelo `DocumentoCompra`. Ningún cambio
en esta fórmula debe hacerse sin revisar todos los flujos que registran o
revierten movimientos.

### 4.6. Referencias de notas de crédito

Una nota de crédito puede apuntar a una factura de compra mediante
`referencia_id`. Esta relación puede generar movimientos automáticos de abono o
pago sobre la factura referenciada, según el saldo resultante.

La referencia documental debe permanecer dentro de la misma empresa y del
mismo RUT de proveedor.

### 4.7. Próximo pago

Una programación de próximo pago registra una fecha y observación en
`documento_compra_pagos_programados`.

No equivale a un pago efectivo: no debe cerrar el documento ni reducir su saldo
por sí sola.

## 5. Invariantes críticas

### 5.1. Identidad de importación

La identidad lógica de un documento RCV es:

```text
empresa_id + tipo_documento_id + rut_proveedor + folio
```

No se debe identificar un documento histórico solo por folio, RUT, razón social
o tipo de documento.

### 5.2. Separación por empresa

Cada archivo RCV se importa para una única empresa. No se deben mezclar ni
reasignar documentos entre empresas a partir de coincidencias de proveedor o
folio.

### 5.3. Proveedor sin configuración

La falta de `CobranzaCompra` no autoriza a inventar días de crédito, datos
bancarios, destinatarios ni forma de pago. El documento debe seguir el flujo
explícito de proveedor pendiente y reprocesamiento.

### 5.4. Saldo y cierre

Una programación o exportación bancaria no representa confirmación de pago.
Solo los movimientos financieros válidos y las reglas de referencia pueden
alterar el saldo o cerrar un documento.

### 5.5. Trazabilidad

Las acciones relevantes deben conservarse en `movimientos_compras`. Los datos
históricos y movimientos no deben eliminarse o reconstruirse sin comprender la
operación que los originó.

## 6. Componentes conocidos

### Controlador principal

```text
app/Http/Controllers/DocumentoCompraController.php
```

Gestiona listado, filtros, importación, referencias, exportaciones, estados,
abonos, cruces, detalle y programación de próximos pagos.

### Modelos principales

```text
DocumentoCompra
CobranzaCompra
DocumentoCompraPagoProgramado
MovimientoCompra
Abono
Cruce
Pago
ProntoPago
```

### Servicios de referencias

```text
ReferenciaNotasCompraService
SincronizarMovimientoReferenciaCompraService
SincronizarPagoReferenciaCompraService
```

### Controladores transversales

```text
PagoDocumentoController
ProntoPagoController
AbonoController
CruceController
CobranzaCompraController
PanelFinanzaController
```

Estos controladores también atienden otros módulos; cualquier cambio debe
revisar expresamente la rama aplicable a documentos de compra.

## 7. Documentación complementaria

Este README es el mapa de entrada. Los documentos especializados deben detallar
las reglas confirmadas por código, base de datos y operación:

- `arquitectura.md`
- `modelo-datos.md`
- `importacion-rcv.md`
- `proveedores-y-vencimientos.md`
- `saldos-y-estados.md`
- `referencias-notas-credito.md`
- `pagos-cruces-y-programacion.md`
- `exportaciones-y-panel-finanzas.md`
- `riesgos-conocidos.md`
- `pruebas.md`

## 8. Regla de mantenimiento

Actualizar este documento cuando cambie el alcance del módulo, la identidad de
importación, el mecanismo de asociación de proveedores, el cálculo de saldo,
las reglas de estado, el flujo de referencias o la distinción entre programación
y pago efectivo.
