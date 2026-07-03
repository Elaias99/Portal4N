/**
 * resources/js/suscripciones/generacion-mensual/dom.js
 *
 * Centraliza los selectores usados por la pantalla de preparación mensual.
 */

export function obtenerDomGeneracionMensual() {
    return {
        cantidadVariable: {
            asignacionSelect: document.getElementById('cantidad_mensual_asignacion_id'),
            cantidadInput: document.getElementById('cantidad_mensual_cantidad'),
            totalInput: document.getElementById('total_variable_estimado'),
            advertencia: document.getElementById('cantidad_variable_advertencia'),
        },

        comision: {
            proveedorSelect: document.getElementById('comision_proveedor_id'),
            transportistaSelect: document.getElementById('comision_transportista_id'),
            punto1Input: document.getElementById('comision_punto_1'),
            origenGastoInput: document.getElementById('comision_origen_gasto'),
            punto2Input: document.getElementById('comision_punto_2'),
            servicioInput: document.getElementById('comision_servicio'),
            costoInput: document.getElementById('comision_costo'),
            totalInput: document.getElementById('comision_total_estimado'),
            observacionInput: document.getElementById('comision_observacion'),
            agregarBtn: document.getElementById('btn-agregar-comision'),
            hiddenContainer: document.getElementById('comisiones-hidden-container'),
            resumenBody: document.getElementById('comisiones-resumen-body'),
            cantidadTexto: document.getElementById('comisiones-cantidad'),
            totalTexto: document.getElementById('comisiones-total'),
        },

        ajuste: {
            tipoSelect: document.getElementById('ajuste_tipo_ajuste'),
            tipoDescripcion: document.getElementById('ajuste_tipo_descripcion'),
            guiaOperativa: document.getElementById('ajuste_guia_operativa'),

            advertenciaWrapper: document.getElementById('ajuste_advertencia_asignacion_wrapper'),
            advertenciaAsignacion: document.getElementById('ajuste_advertencia_asignacion'),
            asignacionAyuda: document.getElementById('ajuste_asignacion_ayuda'),

            bloqueAsignacion: document.getElementById('bloque-ajuste-asignacion'),
            bloqueProveedor: document.getElementById('bloque-ajuste-proveedor'),
            bloqueTransportista: document.getElementById('bloque-ajuste-transportista'),
            bloqueConceptoPagoVariable: document.getElementById('bloque-ajuste-concepto-pago-variable'),
            bloqueConceptoPagoVariableManual: document.getElementById('bloque-ajuste-concepto-pago-variable-manual'),
            bloqueProveedorFacturacion: document.getElementById('bloque-ajuste-proveedor-facturacion'),
            bloqueTransportistaOverride: document.getElementById('bloque-ajuste-transportista-override'),

            asignacionSelect: document.getElementById('ajuste_suscripcion_asignacion_id'),
            proveedorSelect: document.getElementById('ajuste_suscripcion_proveedor_id'),
            transportistaSelect: document.getElementById('ajuste_suscripcion_transportista_id'),
            conceptoPagoVariableSelect: document.getElementById('ajuste_concepto_pago_variable_id'),
            conceptoPagoVariableManualInput: document.getElementById('ajuste_concepto_pago_variable_manual'),
            proveedorFacturacionSelect: document.getElementById('ajuste_suscripcion_proveedor_facturacion_id'),
            transportistaOverrideSelect: document.getElementById('ajuste_suscripcion_transportista_override_id'),

            punto1Input: document.getElementById('ajuste_punto_1'),
            origenGastoInput: document.getElementById('ajuste_origen_gasto'),
            punto2Input: document.getElementById('ajuste_punto_2'),
            codigoInput: document.getElementById('ajuste_codigo'),
            servicioInput: document.getElementById('ajuste_servicio'),
            grupoPrefacturaInput: document.getElementById('ajuste_grupo_prefactura'),
            costoInput: document.getElementById('ajuste_costo'),
            qCalendarioInput: document.getElementById('ajuste_q_calendario'),
            qInasistenciaInput: document.getElementById('ajuste_q_inasistencia'),
            cantidadInput: document.getElementById('ajuste_cantidad'),
            totalManualInput: document.getElementById('ajuste_total'),

            tipoDocumentoInput: document.getElementById('ajuste_tipo_documento'),
            detalleDocumentoInput: document.getElementById('ajuste_detalle_documento'),
            detalleImpuestoInput: document.getElementById('ajuste_detalle_impuesto'),
            finalInput: document.getElementById('ajuste_final'),

            totalEstimadoInput: document.getElementById('ajuste_total_estimado'),
            observacionInput: document.getElementById('ajuste_observacion'),
            agregarBtn: document.getElementById('btn-agregar-ajuste'),

            hiddenContainer: document.getElementById('ajustes-hidden-container'),
            resumenBody: document.getElementById('ajustes-resumen-body'),
            cantidadTexto: document.getElementById('ajustes-cantidad'),
            totalTexto: document.getElementById('ajustes-total'),
        },
    };
}