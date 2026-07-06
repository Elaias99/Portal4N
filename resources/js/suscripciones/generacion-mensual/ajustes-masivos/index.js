/**
 * resources/js/suscripciones/generacion-mensual/ajustes-masivos/index.js
 *
 * Inicializa los módulos de carga masiva de novedades mensuales.
 */

import { inicializarInasistenciasMasivas } from './inasistencias';
import { inicializarFacturacionesMasivas } from './facturacion';

export function inicializarAjustesMasivos(dom, ajustesMensualesApi = {}) {
    inicializarInasistenciasMasivas(dom, ajustesMensualesApi);
    inicializarFacturacionesMasivas(dom, ajustesMensualesApi);
}