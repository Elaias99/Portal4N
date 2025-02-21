<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;

class NotificationsDropdown extends Component
{
    public $notifications;
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        // Obtener las notificaciones no leídas del usuario autenticado
        $this->notifications = Auth::user()->unreadNotifications;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.notifications-dropdown');
    }
}
