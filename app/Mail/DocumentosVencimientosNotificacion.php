<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DocumentosVencimientosExport;
use Carbon\Carbon;

class DocumentosVencimientosNotificacion extends Mailable
{
    use Queueable, SerializesModels;

    public $ventas;
    public $compras;

    public function __construct(EloquentCollection $ventas, EloquentCollection $compras)
    {
        $this->ventas = $ventas;
        $this->compras = $compras;
    }

    public function build()
    {
        $inicio = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $fin = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        // Generar archivo Excel temporal
        $excel = Excel::raw(
            new DocumentosVencimientosExport($this->ventas, $this->compras, $inicio, $fin),
            \Maatwebsite\Excel\Excel::XLSX
        );

        $fileName = 'Documentos_Por_Vencer_' . $inicio->format('Y-m-d') . '_al_' . $fin->format('Y-m-d') . '.xlsx';

        return $this->subject('📅 Documentos por vencer esta semana')
            ->view('emails.documentos_vencimientos')
            ->with([
                'ventas' => $this->ventas,
                'compras' => $this->compras,
                'inicio' => $inicio,
                'fin' => $fin,
            ])
            ->attachData($excel, $fileName, [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
