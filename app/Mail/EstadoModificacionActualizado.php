<?php

namespace App\Mail;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EstadoModificacionActualizado extends Mailable
{
    use Queueable, SerializesModels;

    public $solicitud;
    public $estado;

    /**
     * Create a new message instance.
     */
    public function __construct(Solicitud $solicitud, string $estado)
    {
        $this->solicitud = $solicitud;
        $this->estado = $estado;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $asunto = $this->estado === 'aprobada'
            ? '✅ Solicitud de Modificación Aprobada'
            : '❌ Solicitud de Modificación Rechazada';

        return $this->subject($asunto)
                    ->view('emails.modificaciones.estado_actualizado');
    }
}
