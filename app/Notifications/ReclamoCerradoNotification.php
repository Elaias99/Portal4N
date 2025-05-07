<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Reclamos;

class ReclamoCerradoNotification extends Notification
{
    use Queueable;

    public $reclamo;

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
        return [
            'mensaje' => '🛑 El reclamo ha sido cerrado por ' . $this->reclamo->trabajador->Nombre,
            'reclamo_id' => $this->reclamo->id,
            'link' => route('perfiles.reclamos.area'),
        ];
    }
}
