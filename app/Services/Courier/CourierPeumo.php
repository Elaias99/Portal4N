<?php

namespace App\Services\Courier;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Peumo: Comercial Peumo con el servicio V. Trabajadores. No se paga por
 * kilo ni por la llave, sino por guía de despacho: el primer bulto de la
 * guía paga un valor y cada uno de los demás otro, según la localidad.
 *
 * Si la guía no viene, o si algún bulto de la guía no tiene una tarifa
 * única para su comuna, la guía entera queda sin valor: no se paga a
 * medias. Así lo calcula LogisticaCL.
 *
 * Sólo decide valores; quién cobra y si el bulto se paga lo resuelve el
 * cálculo, igual que para el resto de los bultos.
 */
class CourierPeumo
{
    public const COMERCIANTE = 'comercial peumo ltda';
    public const SERVICIO = 'servicio standar (v. trabajadores)';

    /* Lo que Geolice escribe cuando el bulto no tiene guía. */
    private const SIN_GUIA = ['', 'n/a', 's/n', 'sin guia', 'no aplica'];

    /** @param  array<string, list<array{primer: int, resto: int}>>  $tarifas  localidad_clave => tarifas */
    public function __construct(private readonly array $tarifas)
    {
    }

    public static function desdeBase(): self
    {
        return self::desdeFilas(
            DB::table('courier_tarifas_peumo')->get(['localidad', 'primer_bulto', 'resto'])->map(fn ($f) => (array) $f)->all()
        );
    }

    /** @param  list<array{localidad: string, primer_bulto: mixed, resto: mixed}>  $filas */
    public static function desdeFilas(array $filas): self
    {
        $tarifas = [];
        foreach ($filas as $fila) {
            $tarifas[self::claveLocalidad($fila['localidad'])][] = [
                'primer' => (int) $fila['primer_bulto'],
                'resto' => (int) $fila['resto'],
            ];
        }

        return new self($tarifas);
    }

    public static function esPeumo(?string $comerciante, ?string $servicio): bool
    {
        return CourierLlaves::claveTexto($comerciante) === self::COMERCIANTE
            && CourierLlaves::claveTexto($servicio) === self::SERVICIO;
    }

    /**
     * Valor de cada bulto de Peumo. Los bultos tienen que venir en el
     * orden en que llegaron en el archivo: el primero de cada guía es el
     * que paga la tarifa del primer bulto, aunque después no se pague.
     *
     * @param  iterable<object{id: int, guia_despacho: ?string, comuna_destino: ?string}>  $bultos
     * @return array<int, array{valor: int}|array{motivo: string}>  id => valor o motivo
     */
    public function valores(iterable $bultos): array
    {
        $guias = [];
        $resultado = [];

        foreach ($bultos as $bulto) {
            $guia = self::claveGuia($bulto->guia_despacho);

            if ($guia === '') {
                $resultado[$bulto->id] = ['motivo' => 'peumo_sin_guia'];

                continue;
            }

            $guias[$guia][] = $bulto;
        }

        foreach ($guias as $bultosGuia) {
            $tarifas = [];
            foreach ($bultosGuia as $bulto) {
                $tarifas[$bulto->id] = $this->tarifa($bulto->comuna_destino);
            }

            if (in_array(null, $tarifas, true)) {
                foreach ($bultosGuia as $bulto) {
                    $resultado[$bulto->id] = ['motivo' => 'peumo_sin_tarifa'];
                }

                continue;
            }

            foreach (array_values($bultosGuia) as $posicion => $bulto) {
                $tarifa = $tarifas[$bulto->id];
                $resultado[$bulto->id] = ['valor' => $posicion === 0 ? $tarifa['primer'] : $tarifa['resto']];
            }
        }

        return $resultado;
    }

    /** @return array{primer: int, resto: int}|null  null si no hay tarifa o hay varias distintas */
    private function tarifa(?string $comuna): ?array
    {
        $validas = [];
        foreach ($this->tarifas[self::claveLocalidad($comuna)] ?? [] as $tarifa) {
            if ($tarifa['primer'] > 0 && $tarifa['resto'] > 0) {
                $validas[$tarifa['primer'] . '|' . $tarifa['resto']] = $tarifa;
            }
        }

        return count($validas) === 1 ? reset($validas) : null;
    }

    /* Exacta salvo mayúsculas y espacios: dos escrituras de una comuna no se dan por iguales. */
    public static function claveLocalidad(?string $localidad): string
    {
        return CourierLlaves::claveTexto($localidad);
    }

    private static function claveGuia(?string $guia): string
    {
        $clave = Str::of((string) $guia)->squish()->lower()->ascii()->toString();

        return preg_match('/^0+$/', $clave) === 1 || in_array($clave, self::SIN_GUIA, true) ? '' : $clave;
    }
}
