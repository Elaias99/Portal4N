<?php

namespace App\Console\Commands;

use App\Services\Courier\CourierRevisionLocalidadesService;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use PDOException;
use Symfony\Component\Console\Formatter\OutputFormatter;

class CourierRevisarLocalidades extends Command
{
    protected $signature = 'courier:revisar-localidades
        {--limite=20 : Cantidad máxima de grupos a mostrar; 0 muestra todos}';

    protected $description = 'Revisa posibles equivalencias de localidades en el catálogo de cobertura, sin modificar registros';

    public function handle(DatabaseManager $db, CourierRevisionLocalidadesService $revision): int
    {
        $limite = filter_var($this->option('limite'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($limite === false) {
            $this->error('--limite debe ser un entero mayor o igual a 0.');

            return self::INVALID;
        }

        $this->info('REVISIÓN COURIER — SOLO LECTURA');
        $this->line('No se ejecutan INSERT, UPDATE ni DELETE. No se modifican claves, tarifas ni pagos.');

        try {
            $conexion = $db->connection();
            $this->table(['Conexión', 'Motor', 'Base de datos'], [[
                $this->texto($conexion->getName()),
                $this->texto($conexion->getDriverName()),
                $this->texto($conexion->getDatabaseName()),
            ]]);

            // Query Builder evita eventos de modelos. Es un SELECT; no hay
            // limpieza ni persistencia posterior. El catálogo no está
            // versionado por período, así que se revisa completo.
            $filas = $conexion->table('courier_cobertura_comunas as c')
                ->leftJoin('courier_agentes as a', 'a.id', '=', 'c.courier_agente_id')
                ->select([
                    'c.id', 'c.courier_agente_id',
                    'c.localidad', 'c.localidad_clave', 'c.zona',
                    'c.pagar_retorno', 'c.valor_retorno', 'a.nombre as agente_nombre',
                ])
                ->orderBy('c.id')
                ->get();
        } catch (QueryException|PDOException $e) {
            // No imprimir excepciones que puedan revelar credenciales o conexión.
            $this->error('No se pudo consultar el catálogo. Comprueba la conexión y la existencia de las tres tablas Courier.');

            return self::FAILURE;
        }

        $resultado = $revision->analizar($filas);
        $grupos = $resultado['grupos'];
        $conflictos = count(array_filter($grupos, fn (array $g) => $g['conflicto']));
        $involucradas = array_sum(array_map(fn (array $g) => count($g['filas']), $grupos));

        $this->table(['Resumen', 'Cantidad'], [
            ['Coberturas revisadas', $resultado['total']],
            ['Grupos de posibles coincidencias', count($grupos)],
            ['Filas involucradas en esos grupos', $involucradas],
            ['Grupos con condiciones distintas o agente ausente', $conflictos],
        ]);
        $this->line('Se comparan nombres ignorando mayúsculas, tildes en vocales y espacios. Se conservan números, signos y ñ.');
        $this->warn('Una coincidencia NO confirma una equivalencia ni autoriza borrar registros.');

        if ($resultado['sin_nombre'] !== []) {
            $this->warn('Sin nombre utilizable; fuera de la comparación. IDs: '.implode(', ', $resultado['sin_nombre']));
        }
        if ($resultado['total'] === 0) {
            $this->warn('El período no tiene coberturas registradas.');
        } elseif ($grupos === []) {
            $this->info('No se encontraron coincidencias con este criterio.');
        }

        $visibles = $limite === 0 ? $grupos : array_slice($grupos, 0, $limite);
        foreach ($visibles as $indice => $grupo) {
            $this->newLine();
            $this->line('Grupo '.($indice + 1).' | Comparación: '.$this->texto($grupo['clave']));
            if ($grupo['conflicto']) {
                $this->warn('CONFLICTO: revisar '.implode(', ', $grupo['diferencias']));
            } else {
                $this->line('Mismas condiciones actuales; equivalencia pendiente de confirmar.');
            }

            $this->table(['ID', 'Localidad original', 'Clave guardada', 'Agente (ID)', 'Zona', 'Paga retorno', 'Valor retorno'], array_map(
                fn (array $fila) => [
                    $fila['id'], $this->literal($fila['localidad']), $this->literal($fila['localidad_clave']),
                    $this->texto($fila['agente'] ?? 'AGENTE AUSENTE').' ('.$fila['agente_id'].')',
                    $this->texto($fila['zona'] ?? 'NULL'), $fila['pagar_retorno'] ? 'SI' : 'NO',
                    $fila['valor_retorno'] === null ? 'NULL' : (string) $fila['valor_retorno'],
                ],
                $grupo['filas']
            ));
        }

        if (count($visibles) < count($grupos)) {
            $this->line('Mostrados '.count($visibles).' de '.count($grupos).' grupos. Usa --limite=0 para ver todos.');
        }
        $this->info('Revisión terminada. No se modificó ningún registro.');

        return self::SUCCESS;
    }

    private function texto(?string $valor): string
    {
        return OutputFormatter::escape(str_replace(["\r", "\n", "\t", "\e"], ['\\r', '\\n', '\\t', '\\e'], $valor ?? ''));
    }

    private function literal(string $valor): string
    {
        // Las comillas hacen visibles espacios iniciales/finales; JSON escapa controles.
        return OutputFormatter::escape(json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));
    }
}
