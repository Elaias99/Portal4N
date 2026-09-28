<?php

namespace App\Console\Commands;

use App\Models\CourierImportacion;
use App\Models\CourierPeriodo;
use App\Services\Courier\CourierPagoService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/*
 * Carga una descarga de Geolice en courier_bultos.
 *
 * Delega en CourierPagoService::importarGeolice, que es el mismo camino
 * que usa la pantalla. Antes este comando leía el archivo con Laravel
 * Excel, que en un CSV vuelve a abrir el archivo completo en cada lote:
 * con ochenta mil filas eso son varios minutos. El servicio recorre el
 * CSV una sola vez.
 *
 * Vocabulario, el mismo en todo el módulo:
 *   Geolice  = la descarga de bultos (export-NNNN-packages.csv/xlsx)
 *   Planilla = el Excel mensual de Operaciones (AAAAMM_4N_COURIER_RESPALDOS.xlsx)
 */
class CourierImportarGeolice extends Command
{
    protected $signature = 'courier:importar-geolice
        {archivo : Ruta a la descarga de Geolice (export-NNNN-packages.csv o .xlsx)}
        {--periodo= : Período de pago AAAAMM al que pertenece la descarga (ej. 202608)}
        {--solo-mostrar : Ejecuta todo y deshace al final; muestra qué habría pasado}';

    protected $description = 'Carga los bultos de una descarga de Geolice en courier_bultos y muestra qué no calza con los catálogos. Idempotente.';

    public function handle(CourierPagoService $servicio): int
    {
        $archivo = (string) $this->argument('archivo');
        $codigo = (string) $this->option('periodo');
        $soloMostrar = (bool) $this->option('solo-mostrar');

        if (! is_file($archivo)) {
            $this->error("No existe el archivo: {$archivo}");

            return self::FAILURE;
        }

        if (! preg_match('/^\d{4}(0[1-9]|1[0-2])$/', $codigo)) {
            $this->error('Indica el período con --periodo=AAAAMM (ej. --periodo=202608).');

            return self::FAILURE;
        }

        $periodo = CourierPeriodo::where('codigo', $codigo)->first();

        if ($periodo && $periodo->estaCerrado()) {
            $this->error("El período {$codigo} está cerrado; no se puede cargar sobre él.");

            return self::FAILURE;
        }

        $this->info(
            'Período ' . ($periodo?->nombre ?? $codigo . ' (nuevo)')
            . '. Leyendo ' . basename($archivo) . '…'
        );

        /*
         * El servicio recibe un UploadedFile porque su otro llamador es
         * el formulario de la pantalla. Acá el archivo ya está en disco,
         * así que se envuelve en modo prueba: no se mueve ni se copia.
         */
        $subida = new UploadedFile($archivo, basename($archivo), null, null, true);

        /*
         * --solo-mostrar envuelve todo en una transacción propia y la
         * deshace. El servicio abre la suya dentro, que en MySQL queda
         * como punto de retorno, así que este rollback borra todo.
         */
        if ($soloMostrar) {
            DB::beginTransaction();
        }

        try {
            $importacion = $servicio->importarGeolice($subida, $codigo, null);

            $this->mostrar($importacion);

            if ($soloMostrar) {
                DB::rollBack();
                $this->warn('Modo --solo-mostrar: no se guardó nada.');

                return self::SUCCESS;
            }

            $this->info('Bultos guardados en ' . $this->n($importacion->duracion_seg) . ' segundos.');
            $this->line('Ahora calcula el pago: php artisan courier:calcular --periodo=' . $codigo);
        } catch (\Throwable $e) {
            if ($soloMostrar) {
                DB::rollBack();
            }

            $this->error('Falló la importación, no se guardó nada: ' . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /*
     * El diagnóstico completo quedó guardado en courier_importaciones,
     * así que se muestra desde ahí: lo que se ve en pantalla es
     * exactamente lo que quedó registrado.
     */
    private function mostrar(CourierImportacion $importacion): void
    {
        $datos = $importacion->resumen ?? [];

        $this->seccion('Qué entró');
        $this->tabla($datos['resumen'] ?? []);

        $this->seccion('Estados de entrega');
        $this->estados($datos['estados'] ?? [], $datos['estados_desconocidos'] ?? []);

        $this->seccion('Comunas fuera del catálogo');
        $this->lista('Comuna tal como la manda Geolice', $datos['comunas_fuera_de_catalogo'] ?? []);

        $this->seccion('Sin configuración de pago');
        $this->lista('Agente | Comerciante | Servicio', $datos['sin_configuracion'] ?? []);
    }

    private function estados(array $estados, array $desconocidos): void
    {
        if ($estados === []) {
            $this->line('  Sin estados informados.');

            return;
        }

        arsort($estados);

        $this->table(
            ['Estado de entrega', 'Bultos', 'En catálogo'],
            collect($estados)
                ->map(fn ($n, $estado) => [$estado, $this->n($n), isset($desconocidos[$estado]) ? 'NO' : 'sí'])
                ->values()
                ->all()
        );
    }

    private function lista(string $encabezado, array $lista, int $max = 30): void
    {
        if ($lista === []) {
            $this->line('  Ninguna. Todo calza con el catálogo.');

            return;
        }

        arsort($lista);

        $this->warn('  ' . count($lista) . ' distintas.');

        $this->table(
            [$encabezado, 'Bultos'],
            collect(array_slice($lista, 0, $max, true))
                ->map(fn ($n, $k) => [$k, $this->n($n)])
                ->values()
                ->all()
        );

        if (count($lista) > $max) {
            $this->line('  … y ' . (count($lista) - $max) . ' más. La lista completa quedó en courier_importaciones.');
        }
    }

    private function seccion(string $titulo): void
    {
        $this->newLine();
        $this->line("<comment>{$titulo}</comment>");
    }

    private function tabla(array $datos): void
    {
        $this->table(
            ['', ''],
            collect($datos)->map(fn ($v, $k) => [$k, $this->n($v)])->values()->all()
        );
    }

    private function n(int|float|string|null $valor): string
    {
        return is_numeric($valor)
            ? number_format((int) $valor, 0, ',', '.')
            : (string) $valor;
    }
}
