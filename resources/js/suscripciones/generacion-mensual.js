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




    const formularioGeneracion = document.getElementById(
        'form-generacion-mensual'
    );

    const botonGeneracion = document.getElementById(
        'btn-generar-mes-completo'
    );

    if (formularioGeneracion && botonGeneracion) {
        let formularioEnviado = false;

        const textoOriginal = botonGeneracion.textContent.trim();

        formularioGeneracion.addEventListener('submit', (event) => {
            if (formularioEnviado) {
                event.preventDefault();
                return;
            }

            formularioEnviado = true;

            botonGeneracion.disabled = true;
            botonGeneracion.setAttribute('aria-busy', 'true');

            botonGeneracion.innerHTML = `
                <span
                    class="spinner-border spinner-border-sm me-2"
                    aria-hidden="true"
                ></span>
                Guardando y generando...
            `;
        });

        /*
        * Algunos navegadores restauran la página desde su memoria al volver atrás.
        * En ese caso se habilita nuevamente el formulario.
        */
        window.addEventListener('pageshow', () => {
            formularioEnviado = false;

            botonGeneracion.disabled = false;
            botonGeneracion.removeAttribute('aria-busy');
            botonGeneracion.textContent = textoOriginal;
        });
    }



});