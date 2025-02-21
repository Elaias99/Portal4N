<?php

// namespace App\Console\Commands;

// use Illuminate\Console\Command;

// class ImportComunas extends Command
// {
//     /**
//      * The name and signature of the console command.
//      *
//      * @var string
//      */
//     protected $signature = 'app:import-comunas';

//     /**
//      * The console command description.
//      *
//      * @var string
//      */
//     protected $description = 'Command description';

//     /**
//      * Execute the console command.
//      */
//     public function handle()
//     {
//         //
//     }
// }




namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Comuna;

class ImportComunas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-comunas {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar comunas desde un archivo Excel';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Ruta al archivo Excel en storage/app
        $file = storage_path('app/' . $this->argument('file'));

        // Verifica que el archivo existe
        if (!file_exists($file)) {
            $this->error('El archivo no existe.');
            return;
        }

        // Importa los datos desde el archivo Excel
        $data = Excel::toCollection(null, $file)->first();

        // Recorrer cada fila (comuna) en el archivo Excel
        foreach ($data as $row) {
            if (!empty($row[0])) {
                $comuna_nombre = $row[0];  // El nombre de la comuna

                // Crear la comuna con region_id por defecto (null)
                Comuna::firstOrCreate([
                    'Nombre' => $comuna_nombre,
                    'region_id' => null,  // Puedes poner un valor por defecto, como `1` si quieres.
                ]);

                $this->info("Comuna {$comuna_nombre} importada con region_id por defecto.");
            }
        }

        $this->info('Importación de comunas completada exitosamente.');
    }
}
