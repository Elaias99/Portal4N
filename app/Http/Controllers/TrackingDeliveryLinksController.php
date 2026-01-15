<?php

namespace App\Http\Controllers;

use App\Models\SystemEvents;
use Illuminate\Http\Request;

class TrackingDeliveryLinksController extends Controller
{
    public function index()
    {
        // Obtener eventos 'tracking.found' con prueba de entrega
        $events = SystemEvents::query()
            ->where('event_type', 'tracking.found')
            ->whereNotNull('payload->photos')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('reference_id');


        // Preparar datos para la vista
        $trackings = $events->map(function ($event) {

            $photos = $event->payload['photos'] ?? [];

            return [
                'id'       => $event->id, // 👈 ahora sí tienes el ID
                'tracking' => $event->reference_id,
                'state'    => $event->payload['delivery_state'] ?? '-',
                'photos'   => count($photos),
                'date'     => $event->created_at->format('d-m-Y H:i'),
                'link'     => $photos[0] ?? null, // 👈 LINK REAL A LA FOTO
            ];
        });


        return view('reports.delivery-links', compact('trackings'));
    }
}
