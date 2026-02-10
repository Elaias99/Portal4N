<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct()
    {
        // Asegurarse de que el usuario está autenticado
        $this->middleware('auth');
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications->where('id', $id)->first();


        if ($notification) {
            $notification->markAsRead();

            // Redirigir al enlace que estaba en la notificación
            return redirect($notification->data['link'] ?? '/');
        }

        return back();
    }

    
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
    
        // También mantenerse en la misma vista
        return back()->with('success', 'Todas las notificaciones han sido marcadas como leídas.');
    }
    



}



