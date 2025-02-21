<?php

// ¿Qué es NotificacionAdmin?
// La clase NotificacionAdmin es una notificación personalizada. Esta clase definirá:

// Qué tipo de notificación se va a enviar (por ejemplo, notificación por base de datos, correo electrónico, etc.).
// El contenido del mensaje que los administradores recibirán.
// Acciones o botones que pueden tomar al recibir la notificación (por ejemplo, ver la solicitud o marcarla como leída).


namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NotificacionAdmin extends Notification
{
    use Queueable;

    public $solicitud; //Esta propiedad almacena la solicitud de modificación que es pasada cuando se crea la notificación

    /**
     * Create a new notification instance.
     *
     * @param $solicitud
     */
    public function __construct($solicitud)
    {
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
        return ['database'];  // Se almacenará en la base de datos
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'mensaje' => 'Nueva solicitud de modificación de ' . $this->solicitud->trabajador->Nombre,
            'url' => url('/solicitudes'),  // Enlace a la página de solicitudes
        ];
    }
}
