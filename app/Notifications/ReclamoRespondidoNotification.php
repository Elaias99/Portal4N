<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Reclamos;

class ReclamoRespondidoNotification extends Notification
{
    use Queueable;

    protected $reclamo;

    public function __construct(Reclamos $reclamo)
    {
        $this->reclamo = $reclamo;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return new DatabaseMessage([
            'mensaje' => 'Un reclamo en tu área ha sido respondido.',
            'link' => route('perfiles.reclamos.area'), // Esto debería ser la ruta a ver los reclamos
        ]);
    }
}
