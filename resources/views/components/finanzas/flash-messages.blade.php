@if(session('success'))
    <div class="alert alert-success custom-alert mx-auto shadow-sm" style="max-width:100%; border-left:5px solid #28a745; border-radius:10px; padding:12px 16px;">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill text-success me-2"></i>
            <div><strong>Éxito:</strong> {{ session('success') }}</div>
        </div>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning custom-alert mx-auto shadow-sm" style="max-width:100%; border-left:5px solid #ffc107; border-radius:10px; padding:12px 16px;">
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
            <div class="d-flex align-items-center mb-2 mb-md-0">
                <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                <div><strong>Atención:</strong> {{ session('warning') }}</div>
            </div>

            @if(session('detalles_errores'))
                <button class="btn btn-link btn-sm p-0 text-decoration-none text-warning"
                        type="button"
                        data-toggle="collapse"
                        data-target="#detallesErrores"
                        aria-expanded="false"
                        aria-controls="detallesErrores">
                    <i class="bi bi-caret-down-fill"></i> Ver detalles
                </button>
            @endif
        </div>

        @if(session('detalles_errores'))
            <div id="detallesErrores" class="collapse mt-2">
                <div class="error-list border-top pt-2"
                    style="max-height:180px; overflow-y:auto; background:#fffef5; border-radius:8px; padding:8px 10px;">
                    <ul class="small mb-0 ps-3" style="list-style-type:'⚠️ '; line-height:1.4;">
                        @foreach (session('detalles_errores') as $error)
                            <li class="mb-1">Folio duplicado: {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger custom-alert mx-auto shadow-sm" style="max-width:100%; border-left:5px solid #dc3545; border-radius:10px; padding:12px 16px;">
        <div class="d-flex align-items-center">
            <i class="bi bi-x-circle-fill text-danger me-2"></i>
            <div><strong>Error:</strong> {{ session('error') }}</div>
        </div>
    </div>
@endif