<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BackupRealizadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $nombreArchivo;
    public string $rutaArchivo;
    public string $fechaGeneracion;

    public function __construct(string $nombreArchivo, string $rutaArchivo, string $fechaGeneracion)
    {
        $this->nombreArchivo = $nombreArchivo;
        $this->rutaArchivo = $rutaArchivo;
        $this->fechaGeneracion = $fechaGeneracion;
    }

    public function build()
    {
        return $this->subject('✅ Backup de base de datos generado correctamente')
                    ->view('emails.backup_realizado');
    }
}