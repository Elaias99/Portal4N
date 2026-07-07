/**
 * resources/js/suscripciones/generacion-mensual.js
 *
 * Entry principal del formulario:
 * resources/views/suscripciones/comisiones_mensuales/create.blade.php
 */

import { inicializarAcordeonesSuscripciones } from './generacion-mensual/acordeones';
import { obtenerDomGeneracionMensual } from './generacion-mensual/dom';
import { inicializarCantidadesVariables } from './generacion-mensual/cantidades-variables';
import { inicializarComisiones } from './generacion-mensual/comisiones';
import { inicializarComisionesMasivas } from './generacion-mensual/comisiones-masivas';
import { inicializarAjustesMensuales } from './generacion-mensual/ajustes-mensuales';
import { inicializarAjustesMasivos } from './generacion-mensual/ajustes-masivos';

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-generacion-mensual');

    if (!form) {
        return;
    }

    const config = window.suscripcionesGeneracionMensual || {};

    const comisionesIniciales = Array.isArray(config.comisionesIniciales)
        ? config.comisionesIniciales
        : [];

    const ajustesIniciales = Array.isArray(config.ajustesIniciales)
        ? config.ajustesIniciales
        : [];

    const dom = obtenerDomGeneracionMensual();

    inicializarAcordeonesSuscripciones();
    inicializarCantidadesVariables(dom);


    const comisionesApi = inicializarComisiones(dom, comisionesIniciales);
    inicializarComisionesMasivas(dom, comisionesApi);

    const ajustesMensualesApi = inicializarAjustesMensuales(dom, ajustesIniciales);
    inicializarAjustesMasivos(dom, ajustesMensualesApi);



});