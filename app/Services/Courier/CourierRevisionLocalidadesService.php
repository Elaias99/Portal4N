<?php

namespace App\Services\Courier;

class CourierRevisionLocalidadesService
{
    /**
     * Comparación orientativa en memoria. No sustituye localidad_clave ni
     * confirma que dos nombres representen la misma localidad operativa.
     * Conserva números, puntuación y ñ para no confundir rutas diferentes.
     */
    public function claveComparacion(string $localidad): string
    {
        $texto = strtr(mb_strtolower($localidad, 'UTF-8'), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        ]);

        // Incluye espacios normales, tabulaciones y espacios no separables.
        return preg_replace('/[\p{Z}\s]+/u', '', $texto) ?? $texto;
    }

    /**
     * @param  iterable<object>  $coberturas
     * @return array{total: int, grupos: array, sin_nombre: array}
     */
    public function analizar(iterable $coberturas): array
    {
        $agrupados = [];
        $sinNombre = [];
        $total = 0;

        foreach ($coberturas as $cobertura) {
            $total++;
            $fila = [
                'id' => (int) $cobertura->id,
                'periodo_id' => (int) $cobertura->courier_periodo_id,
                'agente_id' => $cobertura->courier_agente_id === null ? null : (int) $cobertura->courier_agente_id,
                'agente' => $cobertura->agente_nombre,
                'localidad' => (string) $cobertura->localidad,
                'localidad_clave' => (string) $cobertura->localidad_clave,
                'zona' => $cobertura->zona,
                'pagar_retorno' => (bool) $cobertura->pagar_retorno,
                'valor_retorno' => $cobertura->valor_retorno === null ? null : (int) $cobertura->valor_retorno,
            ];
            $clave = $this->claveComparacion($fila['localidad']);

            if ($clave === '') {
                $sinNombre[] = $fila['id'];

                continue;
            }

            // Aun recibiendo varios períodos, nunca se mezclan sus coberturas.
            $grupo = json_encode([$fila['periodo_id'], $clave], JSON_THROW_ON_ERROR);
            $agrupados[$grupo]['clave'] = $clave;
            $agrupados[$grupo]['filas'][] = $fila;
        }

        $grupos = [];
        foreach ($agrupados as $grupo) {
            if (count($grupo['filas']) < 2) {
                continue;
            }

            $diferencias = [];
            foreach (['agente_id', 'zona', 'pagar_retorno', 'valor_retorno'] as $campo) {
                $valores = array_map(
                    fn (array $fila) => json_encode($fila[$campo], JSON_THROW_ON_ERROR),
                    $grupo['filas']
                );
                if (count(array_unique($valores)) > 1) {
                    $diferencias[] = $campo;
                }
            }

            if (in_array(null, array_column($grupo['filas'], 'agente'), true)) {
                $diferencias[] = 'agente_no_encontrado';
            }

            usort($grupo['filas'], fn (array $a, array $b) => $a['id'] <=> $b['id']);
            $grupo['diferencias'] = $diferencias;
            $grupo['conflicto'] = $diferencias !== [];
            $grupos[] = $grupo;
        }

        // Los conflictos se muestran primero; el orden es reproducible.
        usort($grupos, fn (array $a, array $b) =>
            ($b['conflicto'] <=> $a['conflicto'])
            ?: strcmp($a['clave'], $b['clave'])
            ?: ($a['filas'][0]['periodo_id'] <=> $b['filas'][0]['periodo_id'])
        );

        return ['total' => $total, 'grupos' => $grupos, 'sin_nombre' => $sinNombre];
    }
}
