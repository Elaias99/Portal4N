<?php

namespace App\Imports\Courier;

use App\Models\CourierPesaje;
use App\Models\CourierPeriodo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as FechaExcel;

/*
 * Hoja PesoReal de la planilla de Operaciones → courier_pesajes.
 *
 * Columnas: A Codigo_S+Bulto · B Notas (kilos) · C Cod_seguimiento ·
 *           D Fecha de maestro
 *
 * Es la misma información que los CSV diarios de bodega, pero ya
 * acumulada por Operaciones. Sirve para cerrar un mes del que no se
 * guardaron los CSV día por día.
 *
 * Un peso de 0 significa que el bulto pasó por la balanza sin peso: se
 * guarda igual, y es el cálculo el que decide que no sirve.
 */
class PesoRealImport implements ToCollection
{
    public array $resumen = [
        'filas' => 0,
        'nuevos' => 0,
        'actualizados' => 0,
        'sin_cambio' => 0,
        'en_cero' => 0,
        'sin_fecha' => 0,
        'descartados' => 0,
    ];

    public function __construct(
        private CourierPeriodo $periodo,
        private string $archivoOrigen,
    ) {
    }

    public function collection(Collection $rows): void
    {
        $pendientes = [];

        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue; // encabezado
            }

            $seguimiento = strtoupper(trim((string) ($row[0] ?? '')));

            if (! preg_match(PesajesImport::PATRON_CODIGO, $seguimiento)) {
                $this->resumen['descartados']++;

                continue;
            }

            $kilos = $row[1] ?? null;

            if (! is_numeric($kilos)) {
                $this->resumen['descartados']++;

                continue;
            }

            $this->resumen['filas']++;

            $kilos = (int) $kilos;

            if ($kilos === 0) {
                $this->resumen['en_cero']++;
            }

            $fecha = $this->fecha($row[3] ?? null);

            if ($fecha === null) {
                $this->resumen['sin_fecha']++;
                /*
                 * La fecha es parte de la clave. Sin ella se usa el
                 * primer día del período, para no perder el pesaje.
                 */
                $fecha = sprintf('%04d-%02d-01', $this->periodo->anio, $this->periodo->mes);
            }

            /*
             * La hoja trae el mismo código en días distintos. Se
             * conserva el primero, igual que el BUSCARV de la planilla.
             */
            $pendientes[$seguimiento . '|' . $fecha] ??= [
                'seguimiento' => $seguimiento,
                'fecha' => $fecha,
                'kilos' => $kilos,
            ];
        }

        foreach (array_chunk($pendientes, 2000) as $lote) {
            $this->guardar($lote);
        }
    }

    private function guardar(array $lote): void
    {
        $codigos = array_column($lote, 'seguimiento');

        $existentes = CourierPesaje::query()
            ->whereIn('seguimiento', $codigos)
            ->get(['id', 'seguimiento', 'fecha_pesaje', 'kilos'])
            ->keyBy(fn ($p) => $p->seguimiento . '|' . $p->fecha_pesaje->format('Y-m-d'));

        $nuevos = [];
        $ahora = now()->format('Y-m-d H:i:s');

        foreach ($lote as $fila) {
            $clave = $fila['seguimiento'] . '|' . $fila['fecha'];
            $pesaje = $existentes->get($clave);

            if ($pesaje === null) {
                $nuevos[] = [
                    'courier_periodo_id' => $this->periodo->id,
                    'seguimiento' => $fila['seguimiento'],
                    'codigo' => strtok($fila['seguimiento'], '-'),
                    'fecha_pesaje' => $fila['fecha'],
                    'kilos' => $fila['kilos'],
                    'archivo_origen' => $this->archivoOrigen,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
                $this->resumen['nuevos']++;

                continue;
            }

            if ((int) $pesaje->kilos !== $fila['kilos']) {
                $pesaje->update([
                    'kilos' => $fila['kilos'],
                    'archivo_origen' => $this->archivoOrigen,
                ]);
                $this->resumen['actualizados']++;
            } else {
                $this->resumen['sin_cambio']++;
            }
        }

        if ($nuevos !== []) {
            CourierPesaje::insert($nuevos);
        }
    }

    private function fecha(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        if (is_numeric($valor)) {
            return Carbon::instance(FechaExcel::excelToDateTimeObject((float) $valor))->format('Y-m-d');
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $formato) {
            try {
                return Carbon::createFromFormat('!' . $formato, trim((string) $valor))->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
