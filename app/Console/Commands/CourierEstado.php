<?php

namespace App\Console\Commands;

use App\Models\CourierControl;
use App\Models\CourierImportacion;
use App\Models\CourierPeriodo;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/*
 * Responde una sola pregunta: ¿qué tengo cargado en este período?
 *
 * Nació de un error real: se comparó el cálculo contra la planilla de
 * Operaciones usando una descarga de Geolice que no correspondía, y no
 * había forma de darse cuenta sin mirar la base a mano.
 *
 * Sólo lee. No escribe nada.
 *
 * Vocabulario, el mismo en todo el módulo:
 *   Geolice  = la descarga de bultos (export-NNNN-packages.csv/xlsx)
 *   Planilla = el Excel mensual de Operaciones (AAAAMM_4N_COURIER_RESPALDOS.xlsx)
 */
class CourierEstado extends Command
{
    protected $signature = 'courier:estado
        {--periodo= : Período de pago AAAAMM (ej. 202608). Sin esto, el más reciente}';

    protected $description = 'Muestra qué hay cargado en un período: qué Geolice se importó, cuántos bultos, si están calculados y qué datos de la Planilla existen. No escribe nada.';

    public function handle(): int
    {
        $codigo = (string) $this->option('periodo');

        $periodo = $codigo !== ''
            ? CourierPeriodo::where('codigo', $codigo)->first()
            : CourierPeriodo::orderByDesc('codigo')->first();

        if (! $periodo) {
            $this->error($codigo !== ''
                ? "El período {$codigo} no existe."
                : 'Todavía no hay ningún período creado. Importa una descarga de Geolice.');

            return self::FAILURE;
        }

        $this->periodo($periodo);
        $this->archivos($periodo);
        $this->bultos($periodo);
        $this->calculo($periodo);
        $this->datosDeLaPlanilla($periodo);
        $this->queSigue($periodo);

        return self::SUCCESS;
    }

    private function periodo(CourierPeriodo $periodo): void
    {
        $this->seccion('1 · Período');

        $this->tabla([
            'Código' => $periodo->codigo,
            'Nombre' => $periodo->nombre,
            'Estado' => $periodo->estado,
        ]);
    }

    /*
     * Qué archivos entraron a este período y cuándo. Es la respuesta a
     * "¿cuál Geolice estoy mirando?".
     */
    private function archivos(CourierPeriodo $periodo): void
    {
        $this->seccion('2 · Archivos importados');

        $importaciones = CourierImportacion::query()
            ->where('courier_periodo_id', $periodo->id)
            ->orderBy('id')
            ->get(['tipo', 'archivo', 'filas', 'nuevos', 'actualizados', 'duracion_seg', 'created_at']);

        if ($importaciones->isEmpty()) {
            $this->warn('  Ninguno. Este período no tiene archivos importados.');

            return;
        }

        $this->table(
            ['Tipo', 'Archivo', 'Filas', 'Nuevos', 'Actualizados', 'Segundos', 'Cuándo'],
            $importaciones->map(fn ($i) => [
                $i->tipo === CourierImportacion::TIPO_GEOLICE ? 'Geolice' : 'Pesajes',
                $i->archivo,
                $this->n($i->filas),
                $this->n($i->nuevos),
                $this->n($i->actualizados),
                $this->n($i->duracion_seg),
                optional($i->created_at)->format('d-m-Y H:i'),
            ])->all()
        );
    }

    /*
     * Cuántos bultos hay y de qué fechas son. El rango de fechas es lo
     * que delata una descarga que no corresponde al mes que se paga.
     */
    private function bultos(CourierPeriodo $periodo): void
    {
        $this->seccion('3 · Bultos cargados');

        $b = DB::table('courier_bultos')
            ->where('courier_periodo_id', $periodo->id)
            ->selectRaw('COUNT(*) total')
            ->selectRaw('SUM(calculado_at IS NOT NULL) calculados')
            ->selectRaw('MIN(fecha_recepcion) desde')
            ->selectRaw('MAX(fecha_recepcion) hasta')
            ->first();

        if ((int) $b->total === 0) {
            $this->warn('  Ninguno. Importa la descarga de Geolice.');

            return;
        }

        $this->tabla([
            'Bultos' => $this->n($b->total),
            'Calculados' => $this->n($b->calculados),
            'Sin calcular' => $this->n((int) $b->total - (int) $b->calculados),
            'Recepción desde' => $this->fecha($b->desde),
            'Recepción hasta' => $this->fecha($b->hasta),
        ]);

        /*
         * Reparto por mes de la fecha de recepción. Si el mes que se paga
         * no aparece acá, la descarga cargada no es la que corresponde.
         */
        $meses = DB::table('courier_bultos')
            ->where('courier_periodo_id', $periodo->id)
            ->whereNotNull('fecha_recepcion')
            ->selectRaw("DATE_FORMAT(fecha_recepcion, '%Y-%m') mes, COUNT(*) bultos")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        if ($meses->isNotEmpty()) {
            $this->table(
                ['Mes de recepción', 'Bultos'],
                $meses->map(fn ($m) => [$m->mes, $this->n($m->bultos)])->all()
            );
        }
    }

    private function calculo(CourierPeriodo $periodo): void
    {
        $this->seccion('4 · Cálculo del pago');

        $c = DB::table('courier_bultos')
            ->where('courier_periodo_id', $periodo->id)
            ->selectRaw('MAX(calculado_at) cuando')
            ->selectRaw("SUM(estado_pago = 'PAGAR') pagar")
            ->selectRaw("SUM(estado_pago = 'DESCONTAR') descontar")
            ->selectRaw("SUM(CASE WHEN estado_pago = 'PAGAR' THEN valor ELSE 0 END) neto")
            ->first();

        if ($c->cuando === null) {
            $this->warn('  Sin calcular. Corre courier:calcular --periodo=' . $periodo->codigo);

            return;
        }

        $this->tabla([
            'Calculado el' => $this->fecha($c->cuando, true),
            'Se pagan' => $this->n($c->pagar),
            'Quedan fuera' => $this->n($c->descontar),
            'Neto a pagar' => '$' . $this->n($c->neto),
        ]);
    }

    /*
     * Los pesos de balanza y los controles no vienen de Geolice: salen
     * de la Planilla, con courier:importar-mes. Se cargan una vez por
     * mes y sobreviven a que se vacíen los bultos.
     */
    private function datosDeLaPlanilla(CourierPeriodo $periodo): void
    {
        $this->seccion('5 · Datos que vienen de la Planilla');

        $this->tabla([
            'Pesajes de bodega' => $this->n(
                DB::table('courier_pesajes')->where('courier_periodo_id', $periodo->id)->count()
            ),
            'Bultos con peso de balanza' => $this->n(
                DB::table('courier_bultos')
                    ->where('courier_periodo_id', $periodo->id)
                    ->whereNotNull('peso_bodega')
                    ->count()
            ),
        ]);

        $controles = DB::table('courier_controles')
            ->where('courier_periodo_id', $periodo->id)
            ->selectRaw('tipo, COUNT(*) filas')
            ->groupBy('tipo')
            ->orderBy('tipo')
            ->get();

        if ($controles->isEmpty()) {
            $this->warn('  Sin controles. Cárgalos con courier:importar-mes.');

            return;
        }

        $this->table(
            ['Control', 'Filas'],
            $controles->map(fn ($c) => [
                CourierControl::NOMBRES[$c->tipo] ?? $c->tipo,
                $this->n($c->filas),
            ])->all()
        );
    }

    /*
     * Una sola frase con el siguiente paso, para no tener que deducirlo
     * de las tablas de arriba.
     */
    private function queSigue(CourierPeriodo $periodo): void
    {
        $this->seccion('6 · Qué sigue');

        $bultos = DB::table('courier_bultos')
            ->where('courier_periodo_id', $periodo->id)
            ->count();

        if ($bultos === 0) {
            $this->line('  Importar la descarga de Geolice:');
            $this->line('    php artisan courier:importar-geolice {archivo} --periodo=' . $periodo->codigo);

            return;
        }

        $sinCalcular = DB::table('courier_bultos')
            ->where('courier_periodo_id', $periodo->id)
            ->whereNull('calculado_at')
            ->count();

        if ($sinCalcular > 0) {
            $this->line('  Calcular el pago (' . $this->n($sinCalcular) . ' bultos sin calcular):');
            $this->line('    php artisan courier:calcular --periodo=' . $periodo->codigo);

            return;
        }

        $this->line('  El período está cargado y calculado. Para contrastarlo con la Planilla:');
        $this->line('    php artisan courier:comparar-planilla {planilla} --periodo=' . $periodo->codigo);
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
            collect($datos)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );
    }

    private function fecha(?string $valor, bool $conHora = false): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        try {
            return Carbon::parse($valor)->format($conHora ? 'd-m-Y H:i' : 'd-m-Y');
        } catch (\Throwable) {
            return (string) $valor;
        }
    }

    private function n(int|float|string|null $valor): string
    {
        return number_format((int) $valor, 0, ',', '.');
    }
}
