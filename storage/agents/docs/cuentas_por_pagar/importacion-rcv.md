---
titulo: Importación RCV de Cuentas por Pagar
modulo: Cuentas por Pagar
estado: Vigente
ultima_actualizacion: 2026-09-24
---

# Importación RCV

## 1. Origen

El usuario descarga desde el SII un RCV de compras por empresa y período. El
archivo se importa sin editar como CSV, TXT, XLS o XLSX.

Ejemplo de nombre:

```text
RCV_COMPRA_REGISTRO_77639015-1_202601
```

## 2. Empresa receptora

`DocumentoCompraController::import` extrae el RUT del nombre mediante el patrón
`########-#`, lo normaliza y busca una `Empresa` con ese RUT. Si no existe, la
importación completa se rechaza.

El RUT del archivo no es un dato decorativo: define `empresa_id` de todas las
filas importadas.

## 3. Procesamiento de filas

`ComprasImport` normaliza encabezados, omite filas vacías o sin folio y
transforma fechas/números. Persiste datos de proveedor, documento, fechas e
impuestos del RCV.

Antes de insertar valida la llave lógica:

```text
empresa + tipo documento + RUT proveedor + folio
```

Los duplicados dentro del archivo y en la base se omiten y se reportan. Los
registros válidos se agregan al conteo de importados.

## 4. Proveedor conocido o pendiente

Si existe `CobranzaCompra` para el RUT del proveedor, se guarda su ID y se
calcula vencimiento. Si no existe, el documento igualmente se inserta con:

```text
cobranza_compra_id = null
fecha_vencimiento = null
status_original = Pendiente
```

El importador deja una lista de proveedores pendientes en sesión. Después de
crear la ficha del proveedor, el usuario puede ejecutar el reprocesamiento.

## 5. Excepciones de forma de pago

Si la forma de pago de la ficha es `CAJA CHICA` o `FONDO POR RENDIR`, el
documento se cierra automáticamente: estado `Pago`, saldo `0` y registro de
pago con origen `forma_pago_automatica`.

## 6. Después de importar

Se registra una entrada global en `movimientos_compras` con archivo, empresa,
cantidad importada y duplicados. También se buscan NC nuevas sin referencia y
se guardan sugerencias en sesión para que el usuario las revise.

## 7. Precauciones

- No cambiar datos RCV para “hacer calzar” un proveedor.
- No asumir que un folio es globalmente único.
- No asociar manualmente otra empresa cuando el RUT del archivo no coincide.
- Revisar la protección de concurrencia antes de permitir importaciones
  simultáneas del mismo RCV.
