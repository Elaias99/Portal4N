<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DocumentosAtrasadosExport;
use Carbon\Carbon;

class DocumentosAtrasadosMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ventas;
    public $compras;

    public function __construct(Collection $ventas, Collection $compras)
    {
        $this->ventas = $ventas;
        $this->compras = $compras;
    }

    public function build()
    {
        $fecha = Carbon::now();

        // Generar archivo Excel temporal en memoria
        $excel = Excel::raw(
            new DocumentosAtrasadosExport($this->ventas, $this->compras, $fecha),
            \Maatwebsite\Excel\Excel::XLSX
        );

        // Nombre del archivo
        $fileName = 'Documentos_Vencidos_' . $fecha->format('Y-m-d') . '.xlsx';

        return $this->subject('⚠️ Documentos financieros vencidos con saldo pendiente')
            ->view('emails.documentos_atrasados')
            ->with([
                'ventas' => $this->ventas,
                'compras' => $this->compras,
                'fecha'  => $fecha,
            ])
            ->attachData($excel, $fileName, [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
