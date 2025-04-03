<?php

namespace App\Mail;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VacacionesSolicitadas extends Mailable
{
    use Queueable, SerializesModels;

    public $solicitud;

    /**
     * Create a new message instance.
     */
    public function __construct(Solicitud $solicitud)
    {
        $this->solicitud = $solicitud;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('🟡 Nueva Solicitud de Vacaciones')
                    ->view('emails.vacaciones.solicitada');
    }
}
