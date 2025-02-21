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
        $notification = Auth::user()->notifications->find($id);
        if ($notification) {
            $notification->markAsRead();
        }
        return redirect($notification->data['url']);
    }


    public function markAllAsRead()
    {
        // Marca todas las notificaciones no leídas como leídas
        Auth::user()->unreadNotifications->markAsRead();

        // Redirigir a la página anterior con un mensaje de éxito
        return redirect()->back()->with('success', 'Todas las notificaciones han sido marcadas como leídas.');
    }



}



