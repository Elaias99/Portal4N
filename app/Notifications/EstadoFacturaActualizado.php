<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EstadoFacturaActualizado extends Notification
{
    use Queueable;

    public $factura;

    /**
     * Create a new notification instance.
     *
     * @param $factura
     */
    public function __construct($factura)
    {
        $this->factura = $factura;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
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
            'mensaje' => 'La factura #' . $this->factura->id . 
                         ' del proveedor ' . $this->factura->proveedor->razon_social . 
                         ' ha cambiado su estado a "' . $this->factura->status . '" el ' . now()->format('d/m/Y') . '.',
            'url' => route('facturas.detail', $this->factura->id),
        ];
        
    }
}
