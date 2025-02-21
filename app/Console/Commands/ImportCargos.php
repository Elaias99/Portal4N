<?php

// namespace App\Console\Commands;

// use Illuminate\Console\Command;

// class ImportCargos extends Command
// {
//     /**
//      * The name and signature of the console command.
//      *
//      * @var string
//      */
//     protected $signature = 'app:import-cargos';

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
use App\Models\Cargo;

class ImportCargos extends Command
{
    protected $signature = 'app:import-cargos {file}';
    protected $description = 'Importar cargos desde un archivo Excel';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $file = storage_path('app/' . $this->argument('file'));

        // Verifica que el archivo existe
        if (!file_exists($file)) {
            $this->error('El archivo no existe.');
            return;
        }

        // Importa los datos desde el archivo Excel
        $data = Excel::toCollection(null, $file)->first();

        // Limpia y guarda los datos
        foreach ($data as $row) {
            if (!empty($row[0])) {  // Usa el primer valor de la fila (columna 0)
                Cargo::create(['Nombre' => $row[0]]);
            }
        }

        $this->info('Cargos importados exitosamente.');
    }
}

