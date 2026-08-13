/**
 * resources/js/suscripciones/generacion-mensual.js
 *
 * Entry principal del formulario:
 * resources/views/suscripciones/comisiones_mensuales/create.blade.php
 */

import {
    inicializarAcordeonesSuscripciones,
} from './generacion-mensual/acordeones';

import {
    obtenerDomGeneracionMensual,
} from './generacion-mensual/dom';

import {
    inicializarCantidadesVariables,
} from './generacion-mensual/cantidades-variables';

import {
    inicializarComisiones,
} from './generacion-mensual/comisiones';

import {
    inicializarComisionesMasivas,
} from './generacion-mensual/comisiones-masivas';

import {
    inicializarAjustesMensuales,
} from './generacion-mensual/ajustes-mensuales';

import {
    inicializarAjustesMasivos,
} from './generacion-mensual/ajustes-masivos';


document.addEventListener(
    'DOMContentLoaded',
    function () {
        const form =
            document.getElementById(
                'form-generacion-mensual'
            );

        if (!form) {
            return;
        }

        /*
         * Configuración entregada desde Blade.
         */
        const config =
            window.suscripcionesGeneracionMensual
            || {};

        /*
         * Pagos adicionales restaurados.
         */
        const comisionesIniciales =
            Array.isArray(
                config.comisionesIniciales
            )
                ? config.comisionesIniciales
                : [];

        /*
         * Novedades mensuales restauradas.
         */
        const ajustesIniciales =
            Array.isArray(
                config.ajustesIniciales
            )
                ? config.ajustesIniciales
                : [];

        /*
         * Excepciones de facturación por fecha
         * restauradas.
         *
         * Se mantienen separadas de ajustesIniciales
         * porque el backend las recibe mediante:
         *
         * excepciones_facturacion[]
         */
        const excepcionesFacturacionIniciales =
            Array.isArray(
                config.excepcionesFacturacionIniciales
            )
                ? config.excepcionesFacturacionIniciales
                : [];

        /*
         * Referencias principales del DOM.
         */
        const dom =
            obtenerDomGeneracionMensual();

        /*
         * Acordeones.
         */
        inicializarAcordeonesSuscripciones();

        /*
         * Cantidades variables.
         */
        inicializarCantidadesVariables(
            dom
        );

        /*
         * Pagos adicionales.
         */
        const comisionesApi =
            inicializarComisiones(
                dom,
                comisionesIniciales
            );

        inicializarComisionesMasivas(
            dom,
            comisionesApi
        );

        /*
         * Novedades mensuales + excepciones
         * de facturación por fecha.
         *
         * El mismo API central administra:
         *
         * - ajustes_mensuales[]
         * - excepciones_facturacion[]
         */
        const ajustesMensualesApi =
            inicializarAjustesMensuales(
                dom,
                ajustesIniciales,
                excepcionesFacturacionIniciales
            );

        /*
         * Modales masivos:
         *
         * - inasistencias
         * - facturación
         * - líneas adicionales
         * - pagos variables
         *
         * facturacion.js utilizará:
         *
         * agregarAjustesMasivos()
         *
         * o:
         *
         * agregarExcepcionesFacturacionMasivas()
         *
         * dependiendo de si existe fecha específica.
         */
        inicializarAjustesMasivos(
            dom,
            ajustesMensualesApi
        );

        /*
         * Protección contra doble envío del formulario.
         */
        const formularioGeneracion =
            document.getElementById(
                'form-generacion-mensual'
            );

        const botonGeneracion =
            document.getElementById(
                'btn-generar-mes-completo'
            );

        if (
            formularioGeneracion
            && botonGeneracion
        ) {
            let formularioEnviado =
                false;

            const textoOriginal =
                botonGeneracion
                    .textContent
                    .trim();

            formularioGeneracion
                .addEventListener(
                    'submit',
                    function (event) {
                        if (formularioEnviado) {
                            event.preventDefault();
                            return;
                        }

                        formularioEnviado =
                            true;

                        botonGeneracion.disabled =
                            true;

                        botonGeneracion
                            .setAttribute(
                                'aria-busy',
                                'true'
                            );

                        botonGeneracion.innerHTML = `
                            <span
                                class="spinner-border spinner-border-sm me-2"
                                aria-hidden="true"
                            ></span>
                            Guardando y generando...
                        `;
                    }
                );

            /*
             * Algunos navegadores restauran la página
             * desde su memoria al volver atrás.
             *
             * En ese caso se habilita nuevamente
             * el formulario.
             */
            window.addEventListener(
                'pageshow',
                function () {
                    formularioEnviado =
                        false;

                    botonGeneracion.disabled =
                        false;

                    botonGeneracion
                        .removeAttribute(
                            'aria-busy'
                        );

                    botonGeneracion.textContent =
                        textoOriginal;
                }
            );
        }
    }
);