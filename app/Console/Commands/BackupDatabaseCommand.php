<?php

namespace App\Console\Commands;

use App\Mail\BackupRealizadoMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Genera un backup SQL de la base de datos en storage/app/backups';

    public function handle(): int
    {
        try {
            $fecha = now('America/Santiago');

            $nombreArchivo = 'backup_bd_' . $fecha->format('Y_m_d_His') . '.sql';
            $directorio = storage_path('app/backups');
            $rutaArchivo = $directorio . DIRECTORY_SEPARATOR . $nombreArchivo;

            if (!is_dir($directorio)) {
                mkdir($directorio, 0755, true);
            }

            $sql = "";

            $sql .= "-- ==========================================\n";
            $sql .= "-- Respaldo generado por comando Laravel\n";
            $sql .= "-- Fecha: " . $fecha->format('Y-m-d H:i:s') . "\n";
            $sql .= "-- ==========================================\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            $tablas = DB::select("SHOW TABLES");
            $nombrePropiedad = 'Tables_in_' . DB::getDatabaseName();

            foreach ($tablas as $tablaObj) {
                $tabla = $tablaObj->{$nombrePropiedad};

                $sql .= "-- --------------------------------------------------\n";
                $sql .= "-- Estructura de tabla `$tabla`\n";
                $sql .= "-- --------------------------------------------------\n\n";

                $resultado = DB::select("SHOW CREATE TABLE `$tabla`");
                $createTable = $resultado[0]->{'Create Table'};

                $sql .= "DROP TABLE IF EXISTS `$tabla`;\n";
                $sql .= $createTable . ";\n\n";

                $sql .= "-- Datos de `$tabla`\n\n";

                $filas = DB::table($tabla)->get();

                if ($filas->count() > 0) {
                    foreach ($filas as $fila) {
                        $columnas = array_keys((array) $fila);
                        $valores = array_values((array) $fila);

                        $valores = array_map(function ($valor) {
                            if ($valor === null) {
                                return "NULL";
                            }

                            return "'" . addslashes((string) $valor) . "'";
                        }, $valores);

                        $sql .= "INSERT INTO `$tabla` (`" . implode('`,`', $columnas) . "`) VALUES (" . implode(',', $valores) . ");\n";
                    }
                }

                $sql .= "\n\n";
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            file_put_contents($rutaArchivo, $sql);

            $this->info("Backup generado correctamente:");
            $this->line($rutaArchivo);

            $correoDestino = 'eliascorrea@4nlogistica.cl';

            try {
                Mail::to($correoDestino)->send(
                    new BackupRealizadoMail(
                        $nombreArchivo,
                        $rutaArchivo,
                        $fecha->format('d/m/Y H:i:s')
                    )
                );

                $this->info("Correo de notificación enviado a: {$correoDestino}");
            } catch (\Throwable $mailError) {
                Log::error('El backup se generó, pero falló el correo de notificación.', [
                    'archivo' => $nombreArchivo,
                    'ruta' => $rutaArchivo,
                    'error' => $mailError->getMessage(),
                ]);

                $this->warn('El backup se generó, pero no se pudo enviar el correo de notificación.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Error al generar backup de base de datos.', [
                'error' => $e->getMessage(),
            ]);

            $this->error("Error al generar el backup:");
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}