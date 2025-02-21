<div class="dropdown">
    <!-- Botón de la campana de notificación -->
    <button class="btn btn-link position-relative" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bell fa-lg"></i>
        @if ($notifications->count() > 0)
            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                {{ $notifications->count() }}
            </span>
        @endif
    </button>

    <!-- Menú desplegable de notificaciones -->
    <ul class="dropdown-menu dropdown-menu-end shadow-lg notification-panel" aria-labelledby="notificationDropdown" style="width: 350px; max-height: 400px; overflow-y: auto; overflow-x: hidden;">
        <li class="dropdown-header fw-bold">Notificaciones</li>
        @if ($notifications->count() > 0)
            @foreach ($notifications as $notification)
                <li class="dropdown-item d-flex align-items-start notification-item">
                    <div class="me-3">
                        <i class="fas fa-info-circle text-primary"></i> <!-- Ícono según tipo -->
                    </div>
                    <div class="notification-text">
                        <!-- Cambiar a enlace para marcar como leída y redirigir -->
                        <a href="{{ route('notifications.markAsRead', $notification->id) }}" class="text-decoration-none">
                            <p class="mb-0 fw-bold">{{ $notification->data['mensaje'] }}</p>
                        </a>
                        <small class="text-muted">
                            {{ $notification->created_at->diffForHumans() }}
                        </small>
                    </div>
                </li>
            @endforeach
            <li><hr class="dropdown-divider"></li>
            <li class="text-center">
                <form method="POST" action="{{ route('notifications.markAllAsRead') }}">
                    @csrf
                    <button type="submit" class="btn btn-link text-primary fw-bold">Marcar todas como leídas</button>
                </form>
            </li>
        @else
            <li class="dropdown-item text-center">No tienes notificaciones nuevas.</li>
        @endif
    </ul>
</div>


<!-- Estilos CSS para la mejora del dropdown -->
<style>
    .notification-bell {
        position: relative;
    }

    .badge {
        background-color: red;
        color: white;
        border-radius: 50%;
        padding: 4px 8px;
        position: absolute;
        top: -5px;
        right: -10px;
    }

    .notification-panel {
        width: 350px;
        max-height: 400px;
        overflow-y: auto;
        overflow-x: hidden; /* Eliminar desplazamiento horizontal */
        border-radius: 8px;
    }

    .notification-item {
        border-bottom: 1px solid #e0e0e0;
        padding-bottom: 10px;
        margin-bottom: 10px;
        word-wrap: break-word; /* Asegura que el texto se ajuste en caso de ser largo */
    }

    .notification-item:last-child {
        border-bottom: none;
    }

    .dropdown-item.d-flex {
        align-items: flex-start;
    }

    .notification-text {
        flex: 1;
        min-width: 0;
    }

    .notification-text p {
        margin: 0;
        white-space: normal; /* Permitir que el texto se divida en varias líneas */
        word-wrap: break-word; /* Evitar desplazamientos horizontales con texto largo */
    }
</style>
