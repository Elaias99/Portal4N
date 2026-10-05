<?php

namespace App\Console\Commands;

use App\Services\Courier\CourierLlavesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/*
 * Carga la llave de pago de Operaciones desde Llaves_Courier.xlsx
 * (hojas Agentes, Clientes, Servicios, Llaves, Repartidores 4N, Peumo,
 * Tarifas y Proveedores).
 *
 * No depende del período: reemplaza los maestros completos. Para que
 * los bultos la usen hay que volver a calcular el período.
 */
class CourierImportarLlaves extends Command
{
    protected $signature = 'courier:importar-llaves
        {archivo : Ruta a Llaves_Courier.xlsx}
        {--solo-mostrar : Ejecuta todo y deshace al final; muestra qué habría pasado}';

    protected $description = 'Carga la llave de pago (RUT proveedor + RUT cliente + servicio) desde Llaves_Courier.xlsx.';

    public function handle(CourierLlavesService $llaves): int
    {
        $archivo = (string) $this->argument('archivo');
        $soloMostrar = (bool) $this->option('solo-mostrar');

        if (! is_file($archivo)) {
            $this->error("No existe el archivo: {$archivo}");

            return self::FAILURE;
        }

        $this->info('Leyendo ' . basename($archivo) . '…');

        if ($soloMostrar) {
            DB::beginTransaction();
        }

        try {
            $r = $llaves->importar($archivo);
        } catch (\Throwable $e) {
            if ($soloMostrar) {
                DB::rollBack();
            }

            $this->error('No se cargó nada: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Hoja', 'Filas'], [
            ['Agentes', $this->n($r['agentes'])],
            ['Clientes', $this->n($r['clientes'])],
            ['Servicios', $this->n($r['servicios'])],
            ['Llaves', $this->n($r['llaves'])],
            ['Repartidores 4N', $this->n($r['repartidores'])],
            ['Peumo', $this->n($r['peumo'])],
            ['Tarifas', $this->n($r['tarifas'])],
            ['Proveedores (nuevos en el catálogo)', $this->n($r['proveedores']) . ' (' . $this->n($r['proveedores_agregados']) . ')'],
        ]);

        if ($soloMostrar) {
            DB::rollBack();
            $this->warn('Modo --solo-mostrar: no se guardó nada.');

            return self::SUCCESS;
        }

        $this->info('Llaves guardadas. Vuelve a calcular el período para aplicarlas.');

        return self::SUCCESS;
    }

    private function n($valor): string
    {
        return number_format((int) $valor, 0, ',', '.');
    }
}
