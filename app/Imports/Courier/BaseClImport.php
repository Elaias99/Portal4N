<?php

namespace App\Imports\Courier;

use Illuminate\Support\Collection;

/*
 * Lee la hoja BaseCL de la planilla mensual de Operaciones, que es su
 * resultado final: un bulto por fila, ya con su peso, su valor y el
 * operador al que se le paga.
 *
 * No escribe nada. Sólo entrega lo que dice la planilla para poder
 * compararlo contra lo que calculó el sistema.
 *
 * Las columnas se ubican por el texto del encabezado, no por posición:
 * la planilla se arma a mano cada mes y una columna de más correría
 * todo lo demás sin que nadie se diera cuenta.
 */
class BaseClImport
{
    /*
     * campo => textos de encabezado que lo identifican, ya normalizados.
     * El primero que calce manda, así "Usuario" toma la columna N y no
     * la "Usuario2" del final.
     */
    private const ENCABEZADOS = [
        'zona' => ['zona'],
        'tipo_pago' => ['tipo de pago', 'tipo pago', 'tipopago'],
        'seguimiento' => ['id bulto', 'idbulto', 'id_bulto', 'bulto'],
        'peso' => ['peso'],
        'valor' => ['valor final', 'valorfinal', 'valor'],
        'operador' => ['operador'],
        'usuario' => ['usuario'],
    ];

    /* Hasta qué fila se busca el encabezado antes de darse por vencido. */
    private const FILAS_ENCABEZADO = 20;

    public array $resumen = [
        'filas_leidas' => 0,
        'bultos' => 0,
        'repetidos' => 0,
        'sin_codigo' => 0,
        'valor_total' => 0,
    ];

    /** @var array<string,int> campo => índice de columna */
    private array $posiciones = [];

    /**
     * @param  Collection<int,array<int,mixed>>  $filas
     * @return array<string,array<string,mixed>> seguimiento => datos de la planilla
     */
    public function leer(Collection $filas): array
    {
        $inicio = $this->ubicarEncabezado($filas);

        $bultos = [];

        foreach ($filas as $i => $fila) {
            if ($i <= $inicio || ! is_array($fila)) {
                continue;
            }

            $this->resumen['filas_leidas']++;

            $seguimiento = $this->codigo($this->celda($fila, 'seguimiento'));

            if ($seguimiento === '') {
                $this->resumen['sin_codigo']++;

                continue;
            }

            /*
             * Un bulto repetido en BaseCL se contaría dos veces al sumar.
             * Se conserva el primero, igual que el BUSCARV de la planilla.
             */
            if (isset($bultos[$seguimiento])) {
                $this->resumen['repetidos']++;

                continue;
            }

            $valor = $this->entero($this->celda($fila, 'valor'));

            $bultos[$seguimiento] = [
                'zona' => $this->texto($this->celda($fila, 'zona')),
                'tipo_pago' => $this->texto($this->celda($fila, 'tipo_pago')),
                'peso' => $this->entero($this->celda($fila, 'peso')),
                'valor' => $valor,
                'operador' => $this->texto($this->celda($fila, 'operador')),
                'usuario' => $this->texto($this->celda($fila, 'usuario')),
            ];

            $this->resumen['valor_total'] += $valor;
        }

        $this->resumen['bultos'] = count($bultos);

        return $bultos;
    }

    /*
     * Recorre las primeras filas buscando la que trae los encabezados y
     * deja anotada la posición de cada columna que hace falta.
     */
    private function ubicarEncabezado(Collection $filas): int
    {
        foreach ($filas as $i => $fila) {
            if ($i >= self::FILAS_ENCABEZADO) {
                break;
            }

            if (! is_array($fila)) {
                continue;
            }

            $posiciones = [];

            foreach ($fila as $j => $valor) {
                $texto = $this->normalizar($valor);

                if ($texto === '') {
                    continue;
                }

                foreach (self::ENCABEZADOS as $campo => $alias) {
                    if (! isset($posiciones[$campo]) && in_array($texto, $alias, true)) {
                        $posiciones[$campo] = $j;
                    }
                }
            }

            /* El código del bulto y el valor son los que no pueden faltar. */
            if (isset($posiciones['seguimiento'], $posiciones['valor'])) {
                $this->posiciones = $posiciones;

                return (int) $i;
            }
        }

        throw new \InvalidArgumentException(
            'No se encontró el encabezado de BaseCL. Se esperaban al menos las columnas "ID Bulto" y "Valor final".'
        );
    }

    private function celda(array $fila, string $campo): mixed
    {
        $posicion = $this->posiciones[$campo] ?? null;

        return $posicion === null ? null : ($fila[$posicion] ?? null);
    }

    /*
     * El código del bulto es la identidad de todo el proceso. Excel lo
     * guarda a veces con un apóstrofe adelante y con espacios al final.
     */
    private function codigo(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }

        $texto = ltrim(trim((string) $valor), "'");

        return mb_strtoupper(trim($texto));
    }

    private function texto(mixed $valor): string
    {
        return trim((string) ($valor ?? ''));
    }

    private function entero(mixed $valor): int
    {
        if ($valor === null || $valor === '') {
            return 0;
        }

        if (is_string($valor)) {
            /* Formatos de Excel: "1.000", "1,5", "$ 1.000". */
            $valor = str_replace(['$', ' ', '.'], '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return (int) round((float) $valor);
    }

    private function normalizar(mixed $valor): string
    {
        $texto = mb_strtolower(trim((string) ($valor ?? '')));

        return (string) preg_replace('/\s+/u', ' ', $texto);
    }
}