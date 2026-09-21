<?php

namespace App\Console\Commands;

use App\Imports\Courier\PesajesImport;
use App\Models\CourierPeriodo;
use App\Services\Courier\CourierPagoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CourierImportarPesajes extends Command
{
    protected $signature = 'courier:importar-pesajes
        {archivos* : Uno o más CSV de bodega (Proceso del dia dd-mm-aaaa.csv)}
        {--periodo= : Período de pago AAAAMM al que pertenecen (ej. 202609)}
        {--fecha= : Fecha del pesaje AAAA-MM-DD; solo con un archivo y si el nombre no la trae}
        {--solo-mostrar : Ejecuta todo y deshace al final; muestra qué habría pasado}';

    protected $description = 'Carga los pesajes de bodega (CSV) en courier_pesajes y los cruza con los bultos. Idempotente.';

    public function handle(): int
    {
        $archivos = (array) $this->argument('archivos');
        $codigo = (string) $this->option('periodo');
        $fechaOpcion = (string) $this->option('fecha');
        $soloMostrar = (bool) $this->option('solo-mostrar');

        foreach ($archivos as $archivo) {
            if (! is_file($archivo)) {
                $this->error("No existe el archivo: {$archivo}");

                return self::FAILURE;
            }
        }

        if (! preg_match('/^\d{4}(0[1-9]|1[0-2])$/', $codigo)) {
            $this->error('Indica el período con --periodo=AAAAMM (ej. --periodo=202609).');

            return self::FAILURE;
        }

        if ($fechaOpcion !== '' && count($archivos) > 1) {
            $this->error('--fecha solo se puede usar con un archivo; con varios, la fecha sale del nombre.');

            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            $periodo = CourierPeriodo::firstOrCreate(
                ['codigo' => $codigo],
                [
                    'anio' => (int) substr($codigo, 0, 4),
                    'mes' => (int) substr($codigo, 4, 2),
                    'estado' => 'abierto',
                ]
            );

            if ($periodo->estaCerrado()) {
                DB::rollBack();
                $this->error("El período {$codigo} está cerrado; no se puede cargar sobre él.");

                return self::FAILURE;
            }

            $this->info("Período {$periodo->nombre}" . ($periodo->wasRecentlyCreated ? ' (nuevo)' : '') . '.');

            foreach ($archivos as $archivo) {
                $nombre = basename($archivo);
                $fecha = $fechaOpcion !== '' ? $fechaOpcion : CourierPagoService::fechaDesdeNombre($nombre);

                if ($fecha === null) {
                    throw new \InvalidArgumentException("No se pudo leer la fecha del pesaje en \"{$nombre}\"; el nombre debe traer dd-mm-aaaa o usa --fecha.");
                }

                $this->newLine();
                $this->info("Leyendo {$nombre} (pesaje del {$fecha})…");

                $import = new PesajesImport($periodo, $fecha, $nombre);
                $import->importar($archivo);

                $this->table(
                    ['Pesajes', 'Cantidad'],
                    collect($import->resumen)->map(fn ($v, $k) => [$k, $v])->values()->all()
                );

                foreach ($import->codigosInvalidos as $texto => $veces) {
                    $this->warn("  Código inválido: \"{$texto}\" ({$veces} filas)");
                }
            }

            if ($soloMostrar) {
                DB::rollBack();
                $this->warn('Modo --solo-mostrar: no se guardó nada.');
            } else {
                DB::commit();
                $this->info('Pesajes guardados.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Falló la importación, no se guardó nada: ' . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
