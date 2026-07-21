<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SuscripcionPrefacturaPruebaMail extends Mailable
{
    use Queueable, SerializesModels;

    /*
     * Datos disponibles dentro de la vista Blade del correo.
     */
    public string $nombreProveedor;
    public string $rutProveedor;
    public string $mesNombre;
    public int $anio;
    public string $oc;
    public float $totalLiquido;
    public ?string $correoProveedorReal;
    public ?string $grupoPrefacturaLabel;

    /*
     * El contenido binario y el nombre del PDF sólo se utilizan
     * para construir el archivo adjunto.
     */
    private string $contenidoPdf;
    private string $nombreArchivo;

    public function __construct(
        string $contenidoPdf,
        string $nombreArchivo,
        string $nombreProveedor,
        string $rutProveedor,
        string $mesNombre,
        int $anio,
        string $oc,
        float $totalLiquido,
        ?string $correoProveedorReal = null,
        ?string $grupoPrefacturaLabel = null
    ) {
        $this->contenidoPdf = $contenidoPdf;
        $this->nombreArchivo = $nombreArchivo;

        $this->nombreProveedor = $nombreProveedor;
        $this->rutProveedor = $rutProveedor;
        $this->mesNombre = $mesNombre;
        $this->anio = $anio;
        $this->oc = $oc;
        $this->totalLiquido = $totalLiquido;
        $this->correoProveedorReal = $correoProveedorReal;
        $this->grupoPrefacturaLabel = $grupoPrefacturaLabel;
    }

    public function build(): self
    {



        $mesNumero = str_pad(
            (string) array_search(
                ucfirst(mb_strtolower($this->mesNombre)),
                [
                    1 => 'Enero',
                    2 => 'Febrero',
                    3 => 'Marzo',
                    4 => 'Abril',
                    5 => 'Mayo',
                    6 => 'Junio',
                    7 => 'Julio',
                    8 => 'Agosto',
                    9 => 'Septiembre',
                    10 => 'Octubre',
                    11 => 'Noviembre',
                    12 => 'Diciembre',
                ],
                true
            ),
            2,
            '0',
            STR_PAD_LEFT
        );

        $asunto = 'Prefactura Distribución Suscripciones '
            . $mesNumero
            . $this->anio;

        return $this
            ->from(
                'proveedores@4nlogistica.cl',
                '4N Logística - Suscripciones'
            )
            ->subject($asunto)
            ->view('emails.suscripciones.prefactura_prueba')
            ->with([
                'nombreProveedor' => $this->nombreProveedor,
                'rutProveedor' => $this->rutProveedor,
                'mesNombre' => $this->mesNombre,
                'anio' => $this->anio,
                'oc' => $this->oc,
                'totalLiquido' => $this->totalLiquido,
                'correoProveedorReal' => $this->correoProveedorReal,
                'grupoPrefacturaLabel' => $this->grupoPrefacturaLabel,
            ])
            ->attachData(
                $this->contenidoPdf,
                $this->nombreArchivo,
                [
                    'mime' => 'application/pdf',
                ]
            );
    }
}