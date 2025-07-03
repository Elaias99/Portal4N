<?php

namespace App\Exports;

use App\Models\Trabajador;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class TrabajadorExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{

    protected $columnas;

    public function __construct(array $columnas)
    {
        $this->columnas = $columnas;
    }



    public function collection()
    {
        return Trabajador::with(['empresa', 'cargo', 'comuna.region', 'afp', 'salud', 'turno', 'sistemaTrabajo', 'situacion', 'estadoCivil', 'hijos', 'user'])
            ->get()
            ->map(function ($empleado) {
                $diasProporcionales = $empleado->calcularDiasProporcionales();

                $hijos = $empleado->hijos->map(function ($hijo) {
                    return "{$hijo->nombre} ({$hijo->edad} años)";
                })->join(', ');

                $valores = [];

                foreach ($this->columnas as $col) {
                    $valores[] = match ($col) {
                        'empresa' => $empleado->empresa->Nombre ?? '',
                        'nombre_completo' => trim("{$empleado->Nombre} {$empleado->SegundoNombre} {$empleado->TercerNombre} {$empleado->ApellidoPaterno} {$empleado->ApellidoMaterno}"),
                        'rut' => $empleado->Rut ?? '',
                        'fecha_ingreso' => $empleado->fecha_inicio_trabajo ? Carbon::parse($empleado->fecha_inicio_trabajo)->format('Y-m-d') : '',
                        'cargo' => $empleado->cargo->Nombre ?? '',
                        'turno' => $empleado->turno->nombre ?? '',
                        'sistema_trabajo' => $empleado->sistemaTrabajo->nombre ?? '',
                        'tipo_contrato' => $empleado->ContratoFirmado ?? '',
                        'anexo_contrato' => $empleado->AnexoContrato ?? '',
                        'situacion' => $empleado->situacion->Nombre ?? '',
                        'casino' => $empleado->Casino ?? '',
                        'fecha_nacimiento' => $empleado->FechaNacimiento ? Carbon::parse($empleado->FechaNacimiento)->format('Y-m-d') : '',
                        'sueldo' => $empleado->salario_bruto ?? '',
                        'estado_civil' => $empleado->estadoCivil->Nombre ?? '',
                        'correo_personal' => $empleado->CorreoPersonal ?? '',
                        'direccion' => $empleado->calle ?? '',
                        'comuna' => $empleado->comuna->Nombre ?? '',
                        'afp' => $empleado->afp->Nombre ?? '',
                        'salud' => $empleado->salud->Nombre ?? '',
                        'banco' => $empleado->banco ?? '',
                        'cuenta' => $empleado->numero_cuenta ?? '',
                        'tipo_cuenta' => $empleado->tipo_cuenta ?? '',
                        'telefono' => $empleado->numero_celular ?? '',
                        'contacto_emergencia_nombre' => $empleado->nombre_emergencia ?? '',
                        'contacto_emergencia_telefono' => $empleado->contacto_emergencia ?? '',
                        'hijos' => $hijos,
                        'dias_vacaciones' => $diasProporcionales,
                        default => '',
                    };
                }

                return $valores;
            });
    }






    public function headings(): array
    {
        $titulos = [
            'empresa' => 'Empresa',
            'nombre_completo' => 'Nombre completo',
            'rut' => 'Rut',
            'fecha_ingreso' => 'Fecha Ingreso',
            'cargo' => 'Cargo',
            'turno' => 'Turno',
            'sistema_trabajo' => 'Sistema de Trabajo',
            'tipo_contrato' => 'Tipo Contrato',
            'anexo_contrato' => 'Anexo Contrato',
            'situacion' => 'Situación',
            'casino' => 'Casino',
            'fecha_nacimiento' => 'Fecha Nacimiento',
            'sueldo' => 'Sueldo Base',
            'estado_civil' => 'Estado Civil',
            'correo_personal' => 'Mail personal',
            'direccion' => 'Dirección',
            'comuna' => 'Comuna',
            'afp' => 'AFP',
            'salud' => 'Salud',
            'banco' => 'Banco',
            'cuenta' => 'Cta',
            'tipo_cuenta' => 'N° Cuenta',
            'telefono' => 'Teléfono Trabajador',
            'contacto_emergencia_nombre' => 'Contacto de emergencia (Parentezco)',
            'contacto_emergencia_telefono' => 'Teléfono emergencia',
            'hijos' => 'Hijos (Nombre y Edad)',
            'dias_vacaciones' => 'Días Vacaciones Disponibles',
        ];

        return collect($this->columnas)
            ->map(fn($col) => $titulos[$col] ?? $col)
            ->toArray();
    }


    public function columnWidths(): array
    {
        $anchos = [
            'empresa' => 20,
            'nombre_completo' => 40,
            'rut' => 15,
            'fecha_ingreso' => 15,
            'cargo' => 25,
            'turno' => 15,
            'sistema_trabajo' => 20,
            'tipo_contrato' => 20,
            'anexo_contrato' => 20,
            'situacion' => 15,
            'casino' => 10,
            'fecha_nacimiento' => 15,
            'sueldo' => 15,
            'estado_civil' => 15,
            'correo_personal' => 35,
            'direccion' => 35,
            'comuna' => 15,
            'afp' => 15,
            'salud' => 15,
            'banco' => 20,
            'cuenta' => 15,
            'tipo_cuenta' => 15,
            'telefono' => 20,
            'contacto_emergencia_nombre' => 30,
            'contacto_emergencia_telefono' => 20,
            'hijos' => 45,
            'dias_vacaciones' => 20,
        ];

        $columnWidths = [];

        foreach (range('A', 'Z') as $i => $col) {
            if (!isset($this->columnas[$i])) continue;

            $nombreCol = $this->columnas[$i];
            $columnWidths[$col] = $anchos[$nombreCol] ?? 15;
        }

        return $columnWidths;
    }


    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
