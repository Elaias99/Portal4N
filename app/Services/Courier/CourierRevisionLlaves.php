<?php

namespace App\Services\Courier;

use App\Models\CourierCoberturaComuna;

/*
 * ¿A este bulto le falta la llave de pago?
 *
 * Es la misma pregunta que se hace el cálculo (motivo sin_configuracion):
 * RUT del proveedor + RUT del cliente + servicio, con las mismas
 * excepciones. La usan las alertas del período y la revisión previa del
 * archivo, para que lo que avisan sea lo que después queda sin pagar.
 *
 * Sólo responde por la llave. Los demás motivos (comuna, RUT del agente,
 * llave ambigua…) tienen su propio aviso o los resuelve el cálculo.
 */
class CourierRevisionLlaves
{
    /** @param  array<string, string>  $cobertura  localidad_clave => nombre del agente */
    public function __construct(
        private readonly CourierLlaves $llaves,
        private readonly array $cobertura
    ) {
    }

    /** @param  array<string, string>  $cobertura  localidad_clave => nombre del agente */
    public static function desdeBase(array $cobertura): self
    {
        return new self(CourierLlaves::desdeBase(), $cobertura);
    }

    /**
     * La etiqueta «agente | comerciante | servicio» si falta la llave; null
     * si la hay, o si el cálculo no la necesita o se detiene antes por otro
     * motivo.
     *
     * @param  string  $agente  el de la comuna de destino, ya reconocida
     * @param  bool  $empiezaConDesde  el destinatario empieza con «Desde» (retorno)
     */
    public function faltante(
        string $agente,
        ?string $comerciante,
        ?string $servicio,
        ?string $repartidor,
        bool $empiezaConDesde
    ): ?string {
        /* El Mayorista que va a regiones se paga como RM, con el agente de CD QUILICURA. */
        if (CourierCalculoService::esMayoristaQueVuelve($comerciante, $servicio, $agente)) {
            $agente = $this->cobertura[CourierCoberturaComuna::clave(CourierCalculoService::MAYORISTA_COMUNA)] ?? '';

            if ($agente === '') {
                return null;
            }
        }

        $tipo = CourierCalculoService::tipoPago($comerciante, $servicio);

        /* Un retorno se paga por su comuna, no por la llave. Lanas manda sobre el retorno. */
        if ($empiezaConDesde && $tipo !== CourierCalculoService::TIPO_PEUMO && $tipo !== CourierCalculoService::TIPO_LANAS) {
            return null;
        }

        $rutProveedor = $this->llaves->rutProveedor($agente, $repartidor);

        if ($rutProveedor === null || CourierLlaves::sinProveedorCourier($rutProveedor)) {
            return null;
        }

        /* Peumo no busca la llave: sólo necesita el RUT del cliente. */
        $falta = $tipo === CourierCalculoService::TIPO_PEUMO
            ? $this->llaves->rutCliente($comerciante) === null
            : $this->llaves->buscar($rutProveedor, $comerciante, $servicio, $agente) === null;

        return $falta
            ? $agente . ' | ' . trim((string) $comerciante) . ' | ' . trim((string) $servicio)
            : null;
    }
}
