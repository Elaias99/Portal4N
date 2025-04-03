<?php

namespace App\Mail;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EstadoVacacionesActualizado extends Mailable
{
    use Queueable, SerializesModels;

    public $solicitud;
    public $estado;
    public $pdfPath;

    public function __construct(Solicitud $solicitud, string $estado, ?string $pdfPath = null)
    {
        $this->solicitud = $solicitud;
        $this->estado = $estado;
        $this->pdfPath = $pdfPath;
    }

    public function build()
    {
        $asunto = $this->estado === 'aprobada'
            ? '✅ Vacaciones Aprobadas'
            : '❌ Vacaciones Rechazadas';

        $correo = $this->subject($asunto)
                       ->view('emails.vacaciones.estado_actualizado');

        if ($this->pdfPath) {
            $correo->attach(storage_path('app/' . $this->pdfPath));
        }

        return $correo;
    }
}
