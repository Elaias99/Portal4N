<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Carbon\Carbon;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservasDiariasMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $mensaje;
    public string $mensaje_tabular;

    /**
     * Recibe ambos mensajes
     */
    public function __construct(string $mensaje, string $mensaje_tabular)
    {
        $this->mensaje = $mensaje;
        $this->mensaje_tabular = $mensaje_tabular;
    }

    public function envelope(): Envelope
    {
        $fecha = Carbon::now()->format('d-m-Y');

        return new Envelope(
            subject: "Reservas LATAM 4NLOGISTICA SPA $fecha",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservas_diarias',
            with: [
                'mensaje' => $this->mensaje,
                'mensaje_tabular' => $this->mensaje_tabular,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

