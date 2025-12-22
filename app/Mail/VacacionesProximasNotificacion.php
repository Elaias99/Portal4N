<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Vacacion;

class VacacionesProximasNotificacion extends Mailable
{
    use Queueable, SerializesModels;

    public Vacacion $vacacion;
    public int $diasRestantes;

    /**
     * Create a new message instance.
     */
    public function __construct(Vacacion $vacacion, int $diasRestantes)
    {
        $this->vacacion = $vacacion;
        $this->diasRestantes = $diasRestantes;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject(
                '⏳ Vacaciones próximas - ' .
                $this->vacacion->trabajador->Nombre . ' ' .
                $this->vacacion->trabajador->ApellidoPaterno
            )
            ->view('emails.vacaciones_proximas');
    }
}
