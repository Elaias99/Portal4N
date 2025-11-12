<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

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
        return $this->subject('📅 Documentos por vencer esta semana')
                    ->view('emails.documentos_vencimientos')
                    ->with([
                        'ventas' => $this->ventas,
                        'compras' => $this->compras,
                    ]);
    }
}
