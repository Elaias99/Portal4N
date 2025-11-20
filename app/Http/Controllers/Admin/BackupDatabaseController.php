<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BackupDatabaseController extends Controller
{

    public function index()
    {
        return view('admin.backup.backupBD');
    }

    /**
     * Método principal que luego generará el backup.
     * Por ahora queda vacío.
     */
    public function export()
    {
        // 1) Verificación de permisos (implementación futura según tu sistema)
        // $this->authorize('realizar-backup');

        // 2) Nombre del archivo
        $nombreArchivo = 'backup_bd_' . date('Y_m_d_His') . '.sql';

        // 3) Inicializar buffer SQL
        $sql = "";

        // 4) Encabezado
        $sql .= "-- ==========================================\n";
        $sql .= "-- Respaldo generado por el sistema Laravel\n";
        $sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- ==========================================\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        // 5) Obtener todas las tablas
        $tablas = DB::select("SHOW TABLES");
        $nombrePropiedad = 'Tables_in_' . DB::getDatabaseName();

        foreach ($tablas as $tablaObj) {

            $tabla = $tablaObj->{$nombrePropiedad};

            // -----------------------------------------------------
            // ESTRUCTURA DE TABLA
            // -----------------------------------------------------
            $sql .= "-- --------------------------------------------------\n";
            $sql .= "-- Estructura de tabla `$tabla`\n";
            $sql .= "-- --------------------------------------------------\n\n";

            // Obtener sentencia CREATE TABLE
            $resultado = DB::select("SHOW CREATE TABLE `$tabla`");
            $createTable = $resultado[0]->{'Create Table'};

            // Agregar DROP TABLE + CREATE TABLE
            $sql .= "DROP TABLE IF EXISTS `$tabla`;\n";
            $sql .= $createTable . ";\n\n";

            // -----------------------------------------------------
            // DATOS DE TABLA (INSERTS)
            // -----------------------------------------------------
            $sql .= "-- Datos de `$tabla`\n\n";

            $filas = DB::table($tabla)->get();

            if ($filas->count() > 0) {

                foreach ($filas as $fila) {

                    $columnas = array_keys((array)$fila);
                    $valores = array_values((array)$fila);

                    // Prevenir comillas rotas
                    $valores = array_map(function($valor) {
                        if ($valor === null) return "NULL";
                        return "'" . addslashes($valor) . "'";
                    }, $valores);

                    // Insert final
                    $sql .= "INSERT INTO `$tabla` (`" . implode('`,`', $columnas) . "`) VALUES (" . implode(',', $valores) . ");\n";
                }
            }

            $sql .= "\n\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        // 6) Descargar el archivo
        return response($sql)
            ->header('Content-Type', 'application/sql')
            ->header('Content-Disposition', "attachment; filename=\"{$nombreArchivo}\"");
    }

}
