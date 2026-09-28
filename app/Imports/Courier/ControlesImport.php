<?php

namespace App\Imports\Courier;

use App\Models\CourierControl;
use App\Models\CourierPeriodo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/*
 * Hojas del mes que sacan bultos del pago → courier_controles.
 *
 * Las cuatro hacen lo mismo (marcar un bulto), sólo cambia en qué
 * columna viene el código y en cuál la marca:
 *
 *   especiales          F ID        · K NO PAGAR = DESCONTAR
 *   Retornos            A Codigo    · B NO PAGAR = DESCONTAR (D trae el valor)
 *   Blue                B ID        · G NO PAGAR = DESCONTAR
 *   PagadosMesAnterior  C ID Bulto  · H          = SI
 *
 * Los códigos se pasan a mayúsculas: la hoja de especiales trae
 * algunos escritos en minúscula y no calzarían con el bulto.
 */
class ControlesImport implements ToCollection, WithCalculatedFormulas
{
    public array $resumen = [
        'filas' => 0,
        'marcados' => 0,
        'nuevos' => 0,
        'sin_cambio' => 0,
        'descartados' => 0,
    ];

    public function __construct(
        private CourierPeriodo $periodo,
        private string $tipo,
        private int $columnaCodigo,
        private int $columnaMarca,
        private string $valorMarca,
        private string $archivoOrigen,
        private ?int $columnaValor = null,
    ) {
    }

    public function collection(Collection $rows): void
    {
        $pendientes = [];

        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue; // encabezado
            }

            $codigo = strtoupper(trim((string) ($row[$this->columnaCodigo] ?? '')));

            if ($codigo === '') {
                continue;
            }

            $this->resumen['filas']++;

            $marca = mb_strtoupper(trim((string) ($row[$this->columnaMarca] ?? '')));

            if ($marca !== $this->valorMarca) {
                continue;
            }

            if (! preg_match(PesajesImport::PATRON_CODIGO, $codigo)) {
                $this->resumen['descartados']++;

                continue;
            }

            $this->resumen['marcados']++;

            $valor = null;

            if ($this->columnaValor !== null && is_numeric($row[$this->columnaValor] ?? null)) {
                $valor = (int) $row[$this->columnaValor];
            }

            $pendientes[$codigo] ??= $valor;
        }

        foreach (array_chunk($pendientes, 2000, true) as $lote) {
            $this->guardar($lote);
        }
    }

    private function guardar(array $lote): void
    {
        $existentes = CourierControl::query()
            ->delPeriodo($this->periodo->id)
            ->deTipo($this->tipo)
            ->whereIn('seguimiento', array_keys($lote))
            ->pluck('seguimiento')
            ->flip();

        $nuevos = [];
        $ahora = now()->format('Y-m-d H:i:s');

        foreach ($lote as $codigo => $valor) {
            if (isset($existentes[$codigo])) {
                $this->resumen['sin_cambio']++;

                continue;
            }

            $nuevos[] = [
                'courier_periodo_id' => $this->periodo->id,
                'tipo' => $this->tipo,
                'seguimiento' => $codigo,
                'valor' => $valor,
                'archivo_origen' => $this->archivoOrigen,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
            $this->resumen['nuevos']++;
        }

        if ($nuevos !== []) {
            CourierControl::insert($nuevos);
        }
    }
}
