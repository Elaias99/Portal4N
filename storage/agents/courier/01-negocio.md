# 01 · Cómo se pagaba Courier con la planilla

Este documento describe el proceso **como lo hacía Operaciones antes del
módulo**. Sirve para entender qué se está reemplazando y por qué las reglas
son como son.

## El ciclo de cada mes

1. **Descarga de Geolice.** El jefe de Operaciones exporta desde Geolice todos
   los bultos recibidos en el mes. La descarga se toma con **traslape**: parte
   unos días antes del mes y termina unos días después, para alcanzar los
   bultos que se entregaron tarde. Por eso un mismo bulto puede aparecer en
   dos descargas consecutivas.

2. **Pesajes de bodega.** Los bultos que pasan por el centro de distribución
   se pesan en balanza. Bodega entrega esos pesos como un CSV por día
   (`Proceso del dia dd-mm-aaaa.csv`). El peso de balanza manda sobre el peso
   que declaró el cliente.

3. **La planilla.** El jefe pega la descarga en su archivo
   `AAAAMM_4N_COURIER_RESPALDOS.xlsx`. Con fórmulas `BUSCARV`, cada bulto se
   cruza contra hojas-catálogo para saber quién lo repartió, si se paga, con
   qué tarifa y cuánto vale.

4. **Filtrado y pegado.** Se eliminan los bultos que no se pagan (anulados,
   pendientes, ya pagados el mes anterior, retornos, enviados por Blue…), se
   separan en *Variables* y *Lanas*, y el resultado se pega **como valores** en
   la hoja `BaseCL`.

5. **Resumen y banco.** Dos tablas dinámicas sobre `BaseCL`: `ResumenPagos`
   (por zona y tipo de pago) y `Banco` (por razón social del proveedor, con
   IVA cuando corresponde).

6. **Finanzas paga** y gestiona los documentos (factura, boleta o sin
   documento) de cada proveedor.

## Las hojas de la planilla, por grupo

**Catálogos (reglas del negocio, cambian poco):**

| Hoja | Qué responde |
|---|---|
| `Operador` | Esta comuna, ¿quién la reparte y en qué zona (RM / Regiones)? ¿Paga retorno? |
| `PagosCentroCostos` | Este agente, con este cliente y este servicio, ¿se paga? ¿con qué tabla? |
| `Pesos` | Con esta tabla y estos kilos, ¿cuánto vale el bulto? |
| `PesoTransformado` | El texto de peso que manda Geolice, ¿a qué entero corresponde? |
| `Estados` | Este estado de entrega, ¿se paga o se descuenta? |
| `DatosProveedores` | A este agente (y repartidor), ¿a quién se le paga, con qué documento, a qué cuenta? |

**Entradas del mes (las llena Operaciones cada vez):** `especiales`, `Retornos`,
`Blue`, `Lanas`, `PagadosMesAnterior`, `OC`, y la hoja `PesoReal` con los
pesajes de bodega.

**Cálculo:** `BaseGeolize` (descarga + fórmulas), `BaseGeolize-Descuentos`
(controles), `BaseGeolize-trabajada` y `Geolize-Lanas` (lo que se paga).

**Salida:** `BaseCL`, `ResumenPagos`, `Banco`.

**Pagos manuales, fuera del flujo automático:** `Acuerdos`, `Servicios`,
`Visitas Regionales`, `Ruta CV`, `Retornos`, `Especiales`, `Apoyo Alza`,
`Fijo Base`. Son hojas escritas a mano que se suman a `BaseCL`. El módulo
todavía no las cubre.

## Por qué la planilla no bastaba

- **El conocimiento está en una persona.** Nadie más sabe por qué un bulto se
  paga o no. El jefe no explicó la planilla; se entendió por ingeniería inversa.
- **Las llaves son texto.** Una tilde, una mayúscula o un espacio de más
  cambian a qué agente se le paga o hacen que el bulto quede sin regla, y
  nadie lo nota.
- **`BaseCL` son valores pegados.** Se desincroniza de sus propias fórmulas
  y no hay forma de saber qué se aplicó.
- **No hay registro** de qué archivo se cargó, cuándo ni quién.
- **Lo que no calza desaparece en silencio.** Un bulto sin comuna reconocida o
  sin configuración simplemente no se paga, sin que quede una lista de
  pendientes.

## Cómo piensa el jefe de Operaciones (para no confundirse)

- A la tarifa la llama **"centro de costo"**.
- Al agente lo llama **"operador"**; en su planilla el operador se identifica
  por nombre, y quien cobra sale de `DatosProveedores` (RUT + razón social).
- Está rearmando su planilla con llaves por **RUT del cliente + tipo de
  servicio normalizado + RUT del operador**, en vez de nombres. Cuando la
  entregue, el módulo deberá adaptar sus llaves; la lógica no cambia.
- Tiene reglas que no están escritas en ninguna parte (por ejemplo, cómo se
  paga el servicio *Mayorista* de Revés Derecho). Ver `05-decisiones-y-pendientes.md`.
