<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trabajador;
use App\Models\Empresa;
use App\Models\Cargo;
use App\Models\Situacion;
use App\Models\EstadoCivil;
use App\Models\Comuna;
use App\Models\AFP;
use App\Models\Salud;
use App\Models\Region;
use Maatwebsite\Excel\Facades\Excel;

class ImportRut extends Command
{
    protected $signature = 'app:import-rut {file}';
    protected $description = 'Importar solo la columna Rut desde un archivo Excel a la tabla trabajadors';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error('El archivo no existe.');
            return;
        }

        $data = Excel::toCollection(null, $file)->first();

        // Ignorar las dos primeras filas que son el encabezado
        $ruts = $data->slice(2);

        // Asegurarse de que existen las entradas relacionadas necesarias
        $empresa = Empresa::firstOrCreate(['Nombre' => 'Empresa por defecto']);
        $cargo = Cargo::firstOrCreate(['Nombre' => 'Cargo por defecto']);
        $situacion = Situacion::firstOrCreate(['Nombre' => 'Situacion por defecto']);
        $estadoCivil = EstadoCivil::firstOrCreate(['Nombre' => 'Estado Civil por defecto']);

        // Crear una región por defecto si no existe
        $region = Region::firstOrCreate(['Nombre' => 'Region por defecto'], ['Numero' => 1]);

        $comuna = Comuna::firstOrCreate(['Nombre' => 'Comuna por defecto', 'region_id' => $region->id]);

        $afp = AFP::firstOrCreate(['Nombre' => 'AFP por defecto']);
        $salud = Salud::firstOrCreate(['Nombre' => 'Salud por defecto']);

        foreach ($ruts as $row) {
            if (!empty($row[0])) { // Asumiendo que el 'Rut' está en la primera columna
                Trabajador::updateOrCreate(
                    ['Rut' => $row[0]], // Criterio de búsqueda
                    [
                        'Rut' => $row[0],
                        'Nombre' => 'Nombre por defecto', // Valores por defecto
                        'ApellidoPaterno' => 'Apellido por defecto',
                        'ApellidoMaterno' => 'Apellido por defecto',
                        'FechaNacimiento' => '2000-01-01', // Fecha por defecto
                        'Correo' => 'correo@defecto.com',
                        'Casino' => 'No',
                        'ContratoFirmado' => 'No',
                        'AnexoContrato' => 'No',
                        'empresa_id' => $empresa->id,
                        'cargo_id' => $cargo->id,
                        'situacion_id' => $situacion->id,
                        'estado_civil_id' => $estadoCivil->id,
                        'comuna_id' => $comuna->id,
                        'afp_id' => $afp->id,
                        'salud_id' => $salud->id,
                    ]
                );
            }
        }

        $this->info('Ruts importados exitosamente.');
    }
}
