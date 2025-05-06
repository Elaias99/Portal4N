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
        // Intentar obtener el trabajador desde el usuario notificado
        $trabajador = $notifiable->trabajador;

        // Si no hay trabajador directo, usar el correo mapeado
        if (!$trabajador) {
            $resolvedEmail = resolvePerfilEmail($notifiable->email);
            $usuarioRelacionado = \App\Models\User::where('email', $resolvedEmail)->first();
            if ($usuarioRelacionado && $usuarioRelacionado->trabajador) {
                $trabajador = $usuarioRelacionado->trabajador;
            }
        }
        

        // Solo mostrar el enlace si hay un trabajador con área
        // Si el usuario tiene un trabajador o su versión mapeada lo tiene → usar el link
        $link = route('perfiles.reclamos.area');


        return [
            'mensaje' => "📦 Se ha registrado un nuevo reclamo para el área: {$this->areaNombre}",
            'link' => $link
        ];
    }

}
