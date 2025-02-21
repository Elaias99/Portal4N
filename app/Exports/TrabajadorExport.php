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
    public function collection()
    {
        return Trabajador::with(['empresa', 'cargo', 'comuna.region', 'afp', 'salud', 'turno', 'sistemaTrabajo', 'situacion', 'estadoCivil', 'hijos'])
            ->get()
            ->map(function ($empleado) {
                // Obtener la información de los hijos (nombre y edad)
                $hijos = $empleado->hijos->map(function ($hijo) {
                    return "{$hijo->nombre} ({$hijo->edad} años)";
                })->join(', '); // Concatenar los datos de los hijos en una sola cadena

                return [
                    $empleado->empresa->Nombre ?? '', // Empresa
                    "{$empleado->Nombre} {$empleado->SegundoNombre} {$empleado->TercerNombre} {$empleado->ApellidoPaterno} {$empleado->ApellidoMaterno}", // Nombre completo
                    $empleado->Rut ?? '', // Rut
                    $empleado->fecha_inicio_trabajo ? Carbon::parse($empleado->fecha_inicio_trabajo)->format('Y-m-d') : '', // Fecha Ingreso
                    $empleado->cargo->Nombre ?? '', // Cargo
                    $empleado->turno->nombre ?? '', // Turno
                    $empleado->sistemaTrabajo->nombre ?? '', // Sistema de Trabajo
                    $empleado->ContratoFirmado ?? '', // Tipo Contrato
                    $empleado->AnexoContrato ?? '', // Anexo Contrato
                    $empleado->situacion->Nombre ?? '', // Situacion
                    $empleado->Casino ?? '', // Casino
                    $empleado->FechaNacimiento ? Carbon::parse($empleado->FechaNacimiento)->format('Y-m-d') : '', // Fecha Nacimiento
                    $empleado->salario_bruto ?? '', // Sueldo Base
                    $empleado->estadoCivil->Nombre ?? '', // Estado Civil
                    $empleado->CorreoPersonal ?? '', // Mail personal
                    $empleado->calle ?? '', // Direccion
                    $empleado->comuna->Nombre ?? '', // Comuna
                    $empleado->afp->Nombre ?? '', // AFP
                    $empleado->salud->Nombre ?? '', // Salud
                    $empleado->banco ?? '', // Banco
                    $empleado->numero_cuenta ?? '', // Cta
                    $empleado->tipo_cuenta ?? '', // N° Cuenta
                    $empleado->numero_celular ?? '', // Telefono Trabajador
                    $empleado->nombre_emergencia ?? '', // Contacto de emergencia (Parentezco)
                    $empleado->contacto_emergencia ?? '', // Telefono emergencia
                    $hijos, // Hijos (nombre y edad)
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Empresa', 'Nombre completo', 'Rut', 'Fecha Ingreso', 'Cargo', 'Turno', 
            'Sistema de Trabajo', 'Tipo Contrato', 'Anexo Contrato', 'Situacion', 'Casino', 
            'Fecha Nacimiento', 'Sueldo Base', 'Estado Civil', 'Mail personal', 'Direccion', 
            'Comuna', 'AFP', 'Salud', 'Banco', 'Cta', 'N° Cuenta', 
            'Telefono Trabajador', 'Contacto de emergencia (Parentezco)', 'Telefono emergencia',
            'Hijos (Nombre y Edad)' // Nueva columna
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, 'B' => 40, 'C' => 15, 'D' => 15, 'E' => 35,
            'F' => 15, 'G' => 15, 'H' => 15, 'I' => 15, 'J' => 15,
            'K' => 10, 'L' => 15, 'M' => 15, 'N' => 15, 'O' => 35,
            'P' => 45, 'Q' => 15, 'R' => 15, 'S' => 15, 'T' => 20,
            'U' => 15, 'V' => 15, 'W' => 20, 'X' => 40, 'Y' => 20,
            'Z' => 110 // Ancho para la columna de hijos
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
