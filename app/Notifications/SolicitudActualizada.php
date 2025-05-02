<?php

//Envia una notificación al empleado

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SolicitudActualizada extends Notification
{
    use Queueable;

    public $estado;
    public $solicitud;

    /**
     * Create a new notification instance.
     * 
     * @param string $estado
     */
    public function __construct($estado, $solicitud)
    {
        $this->estado = $estado;
        $this->solicitud = $solicitud;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<string>
     */
    public function via($notifiable)
    {
        // La notificación se guardará en la base de datos
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable)
    {
        $tipo = 'solicitud';
        $icono = '✅'; // Ícono por defecto

        if ($this->solicitud->campo && strtolower($this->solicitud->campo) !== 'vacaciones') {
            $tipo = 'modificar tu ' . strtolower($this->solicitud->campo);
            $icono = '✏️';
        } elseif ($this->solicitud->tipo_dia) {
            $tipo = 'permiso ' . str_replace('_', ' ', strtolower($this->solicitud->tipo_dia));
            $icono = '🗓️';
        }

        return [
            'mensaje' => "{$icono} Tu solicitud de {$tipo} fue {$this->estado}.",
            'estado' => $this->estado,
            'url' => url('/empleados/solicitudes')
        ];
    }



}
