<?php

//Envia una notificación al empleado

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SolicitudActualizada extends Notification
{
    use Queueable;

    public $estado;

    /**
     * Create a new notification instance.
     * 
     * @param string $estado
     */
    public function __construct($estado)
    {
        $this->estado = $estado;
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
        // Aquí definimos los datos que guardaremos en la base de datos
        return [
            'mensaje' => 'Tu solicitud ha sido ' . $this->estado,
            'estado' => $this->estado,
            'url' => url('/empleados/perfil'), // URL a la que puede ir el empleado
        ];
    }
}
