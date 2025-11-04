@extends('layouts.app')

@vite('resources/css/panel-finanzas.css')

@section('content')

<div class="container-fluid mt-4" id="modulo-finanzas">

    {{-- ====== CABECERA ====== --}}
    <div class="text-center mb-4">
        <h2 class="fw-bold">Módulo Finanzas</h2>
        <p class="text-muted mb-0">Panel central de gestión — Documentos, abonos, cruces y movimientos</p>
    </div>


    {{-- ====== ACCESOS DIRECTOS ====== --}}
    <div class="row justify-content-center text-center g-4 mb-4">

        {{-- === Cuentas por Cobrar === --}}
        <div class="col-md-3">
            <a href="{{ route('cobranzas.documentos') }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                        <h6 class="fw-semibold mb-1">Cuentas por Cobrar</h6>
                        <p class="text-muted small mb-0">Gestión de facturas y notas de crédito</p>
                    </div>
                </div>
            </a>
        </div>




        {{-- === Cuentas por Pagar === --}}
        <div class="col-md-3">
            <a href="{{ route('finanzas_compras.index') }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                        <h6 class="fw-semibold mb-1">Cuentas por Pagar</h6>
                        <p class="text-muted small mb-0">Gestión de facturas de compras y proveedores</p>
                    </div>
                </div>
            </a>
        </div>



        {{-- === Historial de Movimientos === --}}
        @if (Auth::id() != 375)
            <div class="col-md-3">
                <a href="{{ route('panelfinanza.show') }}" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                            <h6 class="fw-semibold mb-1">Historial de Movimientos</h6>
                            <p class="text-muted small mb-0">Ver abonos y cruces en un solo listado</p>
                        </div>
                    </div>
                </a>
            </div>
        @endif

        <div class="col-md-3">
            <a href="{{ route('panelfinanza.show_compras') }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                        <h6 class="fw-semibold mb-1">Historial de Compras</h6>
                        <p class="text-muted small mb-0">Ver abonos, cruces y pagos de proveedores</p>
                    </div>
                </div>
            </a>
        </div>








    </div>




    <footer class="text-center mt-5">
        <small class="text-muted">© 4NLogística — Área de Finanzas</small>
    </footer>
</div>
@endsection
