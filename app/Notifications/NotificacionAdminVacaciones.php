<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NotificacionAdminVacaciones extends Notification
{
    use Queueable;

    public $solicitud;

    /**
     * Create a new notification instance.
     *
     * @param $solicitud
     */
    public function __construct($solicitud)
    {
        // Almacenamos la solicitud de vacaciones que se pasó al crear la notificación
        $this->solicitud = $solicitud;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Vamos a enviar la notificación solo por base de datos
        return ['database'];
    }

    /**
     * Obtener la representación en la base de datos de la notificación.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'mensaje' => 'Nueva solicitud de vacaciones de ' . $this->solicitud->trabajador->Nombre,
            'url' => url('/solicitudes/vacaciones'),  // Enlace para que el administrador revise las solicitudes de vacaciones
        ];
    }
}
