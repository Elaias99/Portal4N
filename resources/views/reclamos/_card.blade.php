
<div class="card mb-3 shadow-sm">
    <div class="card-body">
        <h5 class="card-title">📦 Bulto: {{ $reclamo->bulto->codigo_bulto ?? '—' }}</h5>

        <p class="mb-1"><strong>Descripción:</strong> {{ $reclamo->descripcion }}</p>
        <p class="mb-1"><strong>Área:</strong> {{ $reclamo->area->nombre ?? '—' }}</p>
        <p class="mb-1">
            <strong>Estado:</strong>
            <span class="badge {{ $reclamo->estado === 'cerrado' ? 'bg-danger' : 'bg-warning text-dark' }}">
                {{ ucfirst($reclamo->estado) }}
            </span>
        </p>
        <p class="text-muted mb-2">Creado el {{ $reclamo->created_at->format('d-m-Y H:i') }}</p>



        @if ($reclamo->estado === 'cerrado')
            <form action="{{ route('reclamos.reabrir', $reclamo->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm mb-2">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reabrir Reclamo
                </button>
            </form>
        @endif

        <a href="{{ route('reclamos.ver', $reclamo->id) }}" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-comments me-1"></i> Ver Historial
        </a>
    </div>
</div>
