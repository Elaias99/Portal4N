<?php

namespace App\Console\Commands;

use App\Imports\Courier\HojasImport;
use App\Imports\Courier\OperadorImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\Courier\PesosImport;
use App\Imports\Courier\PagosCentroCostosImport;
use App\Imports\Courier\DatosProveedoresImport;
use App\Imports\Courier\EstadosImport;
use App\Imports\Courier\PesoTransformadoImport;

class CourierImportarCatalogos extends Command
{
    protected $signature = 'courier:importar-catalogos
        {archivo : Ruta al Excel (la réplica o la planilla del jefe)}
        {--solo-mostrar : Ejecuta todo y deshace al final; muestra qué habría pasado}';

    protected $description = 'Carga los catálogos Courier desde las hojas del Excel. Idempotente: se puede correr cada mes.';

    public function handle(): int
    {
        $archivo = (string) $this->argument('archivo');

        if (! is_file($archivo)) {
            $this->error("No existe el archivo: {$archivo}");

            return self::FAILURE;
        }

        // El libro completo pesa ~63 MB; aunque sólo se carguen las hojas
        // pedidas, PhpSpreadsheet igual lee los textos compartidos.
        ini_set('memory_limit', '2048M');

        $soloMostrar = (bool) $this->option('solo-mostrar');

        $operador = new OperadorImport();
        $pesos = new PesosImport();
        $configuraciones = new PagosCentroCostosImport();
        $proveedores = new DatosProveedoresImport();
        $estados = new EstadosImport();
        $pesosTransformados = new PesoTransformadoImport();

        DB::beginTransaction();

        try {
            $this->info('Leyendo hojas de catálogo…');

            /*
             * El orden importa: PagosCentroCostos busca los agentes
             * que crea Operador.
             */
            Excel::import(
                new HojasImport([
                    'Operador' => $operador,
                    'Pesos' => $pesos,
                    'PagosCentroCostos' => $configuraciones,
                    'DatosProveedores' => $proveedores,
                    'Estados' => $estados,
                    'PesoTransformado' => $pesosTransformados,
                ]),
                $archivo
            );

            $this->mostrarResumen('Operador', $operador->resumen);
            $this->mostrarResumen('Pesos', $pesos->resumen);
            $this->mostrarResumen('PagosCentroCostos', $configuraciones->resumen);
            $this->mostrarResumen('DatosProveedores', $proveedores->resumen);
            $this->mostrarResumen('Estados', $estados->resumen);
            $this->mostrarResumen('PesoTransformado', $pesosTransformados->resumen);

            foreach ($configuraciones->agentesNoEncontrados as $nombre => $cantidad) {
                $this->warn("  Agente no encontrado en Operador: \"{$nombre}\" ({$cantidad} filas)");
            }

            if ($soloMostrar) {
                DB::rollBack();
                $this->warn('Modo --solo-mostrar: no se guardó nada.');
            } else {
                DB::commit();
                $this->info('Catálogos guardados.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Falló la importación, no se guardó nada: ' . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }


    private function mostrarResumen(string $hoja, array $resumen): void
    {
        $this->table(
            [$hoja, 'Cantidad'],
            collect($resumen)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );
    }

}