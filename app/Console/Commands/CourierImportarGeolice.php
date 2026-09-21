<?php

namespace App\Console\Commands;

use App\Imports\Courier\GeoliceBultosImport;
use App\Models\CourierPeriodo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CourierImportarGeolice extends Command
{
    protected $signature = 'courier:importar-geolice
        {archivo : Ruta a la descarga de Geolice (export-NNNN-packages.xlsx)}
        {--periodo= : Período de pago AAAAMM al que pertenece la descarga (ej. 202609)}
        {--solo-mostrar : Ejecuta todo y deshace al final; muestra qué habría pasado}';

    protected $description = 'Carga los bultos de una descarga de Geolice en courier_bultos y muestra qué no calza con los catálogos. Idempotente.';

    public function handle(): int
    {
        $archivo = (string) $this->argument('archivo');
        $codigo = (string) $this->option('periodo');
        $soloMostrar = (bool) $this->option('solo-mostrar');

        if (! is_file($archivo)) {
            $this->error("No existe el archivo: {$archivo}");

            return self::FAILURE;
        }

        if (! preg_match('/^\d{4}(0[1-9]|1[0-2])$/', $codigo)) {
            $this->error('Indica el período con --periodo=AAAAMM (ej. --periodo=202609).');

            return self::FAILURE;
        }

        ini_set('memory_limit', '2048M');

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

            $this->info(
                "Período {$periodo->nombre}" . ($periodo->wasRecentlyCreated ? ' (nuevo)' : '')
                . '. Leyendo ' . basename($archivo) . '…'
            );

            $import = new GeoliceBultosImport($periodo, basename($archivo));

            Excel::import($import, $archivo);

            $this->mostrarResumen('Bultos', $import->resumen);
            $this->mostrarEstados($import);
            $this->mostrarLista('Comunas fuera del catálogo', 'Comuna de destino', $import->comunasFueraDeCatalogo);
            $this->mostrarLista('Sin configuración de pago', 'Agente | Comerciante | Servicio', $import->sinConfiguracion);

            if ($soloMostrar) {
                DB::rollBack();
                $this->warn('Modo --solo-mostrar: no se guardó nada.');
            } else {
                DB::commit();
                $this->info('Bultos guardados.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Falló la importación, no se guardó nada: ' . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function mostrarResumen(string $titulo, array $resumen): void
    {
        $this->table(
            [$titulo, 'Cantidad'],
            collect($resumen)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );
    }

    private function mostrarEstados(GeoliceBultosImport $import): void
    {
        $estados = $import->estados;
        arsort($estados);

        $this->table(
            ['Estado de entrega', 'Bultos', 'En catálogo'],
            collect($estados)
                ->map(fn ($n, $estado) => [$estado, $n, isset($import->estadosDesconocidos[$estado]) ? 'NO' : 'sí'])
                ->values()
                ->all()
        );
    }

    private function mostrarLista(string $titulo, string $encabezado, array $lista, int $max = 30): void
    {
        if ($lista === []) {
            $this->info("{$titulo}: ninguna.");

            return;
        }

        arsort($lista);

        $this->newLine();
        $this->warn("{$titulo}: " . count($lista) . ' distintas');

        $this->table(
            [$encabezado, 'Bultos'],
            collect(array_slice($lista, 0, $max, true))->map(fn ($n, $k) => [$k, $n])->values()->all()
        );

        if (count($lista) > $max) {
            $this->line('  … y ' . (count($lista) - $max) . ' más.');
        }
    }
}
