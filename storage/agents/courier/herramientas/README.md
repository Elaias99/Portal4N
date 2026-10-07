# Herramientas: archivos del mes desde LogisticaCL

Portal4N no lee la base de LogisticaCL. Cada mes se generan archivos Excel
desde esa base, en **modo lectura**, y se cargan con los comandos de Portal4N.
Los scripts de esta carpeta son los que se usaron para agosto y septiembre de
2026. Se corren con el PHP de XAMPP y el `vendor` de Portal4N, y dejan los
archivos en `C:\Users\elias\Downloads`.

Base de LogisticaCL, siempre en modo lectura:

```
DB="file:///C:/Users/elias/proyectos/LogisticaCL/backend/database/database.sqlite?mode=ro&immutable=1"
T="(select id from tenants where code='4N')"
P=202609   # período
```

## 1. Llaves (`armar_llaves.php <periodo>` → `Llaves_Courier_<periodo>.xlsx`)

Lee TSV de la carpeta `llaves/`, junto al script:

| TSV | Consulta (sqlite3 -cmd ".mode tabs") |
|---|---|
| `agentes.tsv` | `select c.matrix_commune_name, upper(trim(coalesce(p.tax_id,c.provider_tax_id))), max(coalesce(p.legal_name,c.provider_name_source)) from coverages c left join providers p on p.id=c.provider_id where c.is_active=1 and c.tenant_id=$T group by 1,2 order by 1` |
| `clientes.tsv` | `select source_merchant_name, upper(trim(tax_id)), legal_name from clients where is_active=1 and tenant_id=$T order by 1` |
| `servicios.tsv` | `select name, service_code from service_types order by service_code` |
| `llaves.tsv` | `select upper(trim(coalesce(p.tax_id,k.provider_tax_id))), k.agent_name, upper(trim(coalesce(cl.tax_id,k.client_tax_id))), k.merchant_name, k.service_code, k.service_name, upper(trim(k.payment_status)), k.cost_center_code from llave_centro_costos k left join providers p on p.id=k.provider_id left join clients cl on cl.id=k.client_id where k.is_active=1 and k.tenant_id=$T order by 1,3,5` |
| `repartidores.tsv` | `select upper(trim(RutProveedor)), ComunaMatriz, NombreRepartidor, upper(trim(NuevoRutProveedor)) from Proveedores_usuarios_4N where upper(trim(NuevoRutProveedor))='N/A' or upper(trim(NuevoRutProveedor)) in (select tax_id from providers where tenant_id=$T) order by 2,3` |
| `tarifas.tsv` | `select c.cost_center_code, c.dispatch_guide_detail, c.additional_kilo_value, group_concat(r.value, char(9)) from cost_centers c join (select * from cost_center_weight_rates where is_active=1 order by cost_center_code, final_weight) r on r.cost_center_code=c.cost_center_code where c.is_active=1 group by c.cost_center_code order by c.cost_center_code` |
| `peumo_ok.tsv` | `backend/database/data/peumo_tariffs.tsv` de LogisticaCL sin la cabecera y sin las filas que no traen números (hay una con `#N/D`). |
| `proveedores_<periodo>.tsv` | Proveedores (RUT, razón social, tipo de documento, nombre operacional) que cobran en `Maestro_Pagos` del período, más los RUT de la cobertura activa y de `Proveedores_usuarios_4N`. Sin `77346078-7` (4N) ni RUT inválidos (`0-0`, `N/A`). |
| `aplicado_4n_<periodo>.tsv` | Lo que LogisticaCL aplicó ese mes a los repartidores de 4N: `select comuna_matriz, lower(trim(nombre_repartidor)), upper(trim(rut_proveedor)), count(*) from Pago_Movimientos_Courier where periodo='$P' and tipo_pago in ('Variables','Lanas','Peumo') and comuna_matriz like '4N%' group by 1,2,3` |
| `agentes_extra_<periodo>.tsv` / `repartidores_extra_<periodo>.tsv` | Filas que el mes necesita y la cobertura vigente ya no tiene (ver abajo). |

**Por qué hay archivos "extra" y "aplicado":** LogisticaCL sobrescribe sus
maestros; no guarda cómo estaban cada mes. El Excel de un mes debe reflejar
lo que Operaciones aplicó ese mes:
- agosto 2026: Calama → agente `Victor Robledo (Calama)` con el RUT de Victor
  Robledo, y un repartidor de 4N RM asignado a DS Group;
- septiembre 2026: Calama → RUT de Marcelo Avendaño, salvo lo que entregó el
  repartidor Victor Robledo, que va a su RUT (fila en `repartidores_extra_202609.tsv`).
  Es la transición que LogisticaCL tiene escrita en `CalamaProviderTransition`,
  pasada a datos.

## 2. Pesajes y controles (`armar_mes_202609.php` → `Mes_<periodo>.xlsx`)

Para `courier:importar-mes`, con el formato de la planilla de Operaciones. Lee `sep/`:

| TSV | Consulta |
|---|---|
| `pesoreal.tsv` | `select seguimiento_paquete, peso_real, fecha_proceso from peso_real where tenant_id=$T and peso_real is not null and substr(seguimiento_paquete,3,8) >= '<inicio del rango>' order by id` |
| `blue.tsv` | `select distinct tracking_number from envios_externos where tenant_id=$T and exclude_provider_payment=1 and tracking_number like '4N%'` |
| `especiales.tsv` | `select distinct seguimiento_paquete from Pago_Movimientos_Courier where tenant_id=$T and periodo='$P' and tipo_pago='Especiales' and seguimiento_paquete like '4N%'` |

Hojas: `PesoReal` (A seguimiento, B kilos, D fecha), `Blue` (B seguimiento, G `DESCONTAR`), `especiales` (F seguimiento, K `DESCONTAR`).

## 3. Acuerdos y procesos (`armar_base_acuerdos_202609.php`, `armar_procesos_202609.php`)

Leen JSON de `sep/` (sqlite3 -json). Generan `Base_Acuerdos_<periodo>.xlsx`,
`Base_Ruta_CV_`, `Base_Servicios_`, `Base_Visitas_`, `Especiales_` y `Apoyo_Alza_<periodo>.xlsx`.

```
lcl_acuerdos.json  SELECT a.id, a.fila_origen, a.proveedor_origen, a.rut_proveedor_origen, a.agencia, a.tipo_servicio, a.marca, a.servicio, a.costo, a.dias_calendario, a.inasistencias, a.adicionales, a.cantidad, a.glosa_factor, a.factor, a.total, a.razon_social_cliente_origen, a.comerciante_pila_origen, a.rut_cliente_origen, a.nombre_comercial_origen, a.empresa_mandante, COALESCE(m.zona, a.zona) AS zona FROM acuerdos a LEFT JOIN Maestro_Pagos m ON m.acuerdo_id = a.id WHERE a.periodo='$P' ORDER BY a.fila_origen IS NULL, a.fila_origen, a.id;
lcl_reglas.json    SELECT servicio, modo, dias_semana, cantidad_fija FROM acuerdo_service_rules WHERE periodo='$P' ORDER BY id;
lcl_dias.json      SELECT fecha, es_feriado FROM acuerdo_calendar_days WHERE periodo='$P' ORDER BY fecha;
x_rutas.json       SELECT r.*, p.tax_id AS prov_rut, m.zona AS mz FROM Rutas_CV r LEFT JOIN providers p ON p.id=r.provider_id LEFT JOIN Maestro_Pagos m ON m.ruta_cv_id=r.id WHERE r.periodo='$P' ORDER BY r.fila_origen, r.id;
x_servicios.json   SELECT s.*, p.tax_id AS prov_rut, m.zona AS mz FROM Base_Servicios s LEFT JOIN providers p ON p.id=s.provider_id LEFT JOIN Maestro_Pagos m ON m.base_servicio_id=s.id WHERE s.periodo='$P' ORDER BY s.fila_origen, s.id;
x_visitas.json     SELECT v.*, p.tax_id AS prov_rut, m.zona AS mz FROM Visitas_Diarias v LEFT JOIN providers p ON p.id=v.provider_id LEFT JOIN Maestro_Pagos m ON m.visita_diaria_id=v.id WHERE v.periodo='$P' ORDER BY v.fila_origen, v.id;
x_especiales.json  SELECT e.*, p.tax_id AS prov_rut, m.zona AS mz FROM courier_special_payments e LEFT JOIN providers p ON p.id=e.provider_id LEFT JOIN Maestro_Pagos m ON m.seguimiento_paquete=e.finalized_tracking_number WHERE e.periodo='$P-Especiales' ORDER BY e.fila_origen, e.id;
x_apoyo.json       SELECT a.*, p.tax_id AS prov_rut, m.zona AS mz FROM apoyo_alzas a LEFT JOIN providers p ON p.id=a.provider_id LEFT JOIN Maestro_Pagos m ON m.apoyo_alza_id=a.id WHERE a.periodo='$P' ORDER BY a.fila_origen, a.id;
```

## 4. Comparar con lo que pagó LogisticaCL

`comparar_sep.php` cruza bulto a bulto el resultado de Portal4N
(`seguimiento, tipo, estado_pago, valor, rut_proveedor, motivo, tabla`) con
`Pago_Movimientos_Courier` del período (`seguimiento, tipo_pago, condicion_pago, valor, rut_proveedor`)
y agrupa las diferencias. `probar_peumo.php` prueba la regla de Peumo contra
los bultos Peumo del período en LogisticaCL.

**Cuidado:** los TSV/JSON de entrada traen nombres de proveedores y de
repartidores. No se guardan en esta carpeta ni se muestran en el chat.
