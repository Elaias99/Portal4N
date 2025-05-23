<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Reclamos;

class ReclamoReabiertoNotification extends Notification
{
    use Queueable;

    public $reclamo;
    public $reabiertoPor;

    public function __construct(Reclamos $reclamo, $reabiertoPor)
    {
        $this->reclamo = $reclamo;
        $this->reabiertoPor = $reabiertoPor;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'mensaje' => '🔁 Reclamo reabierto por ' . $this->reabiertoPor->name,
            'reclamo_id' => $this->reclamo->id,
            'link' => route('perfiles.reclamos.area'),
        ];
    }
}
