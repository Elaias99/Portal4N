<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CumpleaniosNotificacion extends Mailable
{
    use Queueable, SerializesModels;

    public $cumpleanieros;

    public function __construct(Collection $cumpleanieros)
    {
        $this->cumpleanieros = $cumpleanieros;
    }

    public function build()
    {
        return $this->subject('🎂 Empleados de cumpleaños hoy')
                    ->view('emails.cumpleanios');
    }
}
