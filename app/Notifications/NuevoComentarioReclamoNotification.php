<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\ReclamoComentario;

class NuevoComentarioReclamoNotification extends Notification
{
    use Queueable;

    protected $comentario;

    public function __construct(ReclamoComentario $comentario)
    {
        $this->comentario = $comentario;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'mensaje' => "💬 Nuevo comentario en un reclamo de tu área.",
            'link' => route('perfiles.reclamos.area'),
            'comentario_id' => $this->comentario->id,
            'autor' => $this->comentario->autor->name,
            'fragmento' => substr($this->comentario->comentario, 0, 60) . '...',
        ];
    }
}
