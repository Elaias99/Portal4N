<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

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
        return $this->subject('⚠️ Documentos financieros vencidos con saldo pendiente')
                    ->view('emails.documentos_atrasados')
                    ->with([
                        'ventas' => $this->ventas,
                        'compras' => $this->compras,
                    ]);
    }
}
