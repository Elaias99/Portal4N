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
    
        // 🔁 Siempre quedarse en la misma vista
        return back();
    }
    
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
    
        // 🔁 También mantenerse en la misma vista
        return back()->with('success', 'Todas las notificaciones han sido marcadas como leídas.');
    }
    



}



