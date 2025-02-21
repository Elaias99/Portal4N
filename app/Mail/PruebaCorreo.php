<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PruebaCorreo extends Mailable
{
    use Queueable, SerializesModels;

    public function build()
    {
        return $this->from(env('MAIL_FROM_ADDRESS'))
                    ->subject('Correo de Prueba desde Laravel')
                    ->view('emails.prueba');
    }
}
