<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NuevoReclamoAreaNotification extends Notification
{
    use Queueable;

    protected $areaNombre;

    public function __construct($areaNombre)
    {
        $this->areaNombre = $areaNombre;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'mensaje' => "📦 Se ha registrado un nuevo reclamo para el área: {$this->areaNombre}",
            'link' => route('perfiles.reclamos.area')
        ];
    }
}
