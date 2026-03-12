@extends('layouts.app')

@vite('resources/css/panel-finanzas.css')

@section('content')

<div class="container-fluid mt-4" id="modulo-finanzas">

    {{-- ====== CABECERA ====== --}}
    <div class="text-center mb-4">
        <h2 class="fw-bold">Módulo Finanzas</h2>
        <p class="text-muted mb-0">Panel central de gestión — Documentos, abonos, cruces y movimientos</p>
    </div>



    @if($comprasProgramadasHoy->isNotEmpty())
        <div class="alert alert-warning shadow-sm mb-4" style="border-left: 5px solid #f59e0b; border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Compras con pago programado para hoy</h5>
                    <p class="mb-0">
                        Hay <strong>{{ $comprasProgramadasHoy->count() }}</strong> documento(s) de compra con próximo pago definido para hoy.
                    </p>
                </div>

                <div>
                    <a href="{{ route('finanzas_compras.index') }}" class="btn btn-sm btn-outline-warning">
                        Revisar compras
                    </a>
                </div>
            </div>
        </div>
    @endif

    @if($comprasProgramadasAtrasadas->isNotEmpty())
        <div class="alert alert-danger shadow-sm mb-4" style="border-left: 5px solid #dc3545; border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Compras con pago programado atrasado</h5>
                    <p class="mb-0">
                        Hay <strong>{{ $comprasProgramadasAtrasadas->count() }}</strong> documento(s) con fecha programada vencida.
                    </p>
                </div>

                <div>
                    <a href="{{ route('finanzas_compras.index') }}" class="btn btn-sm btn-outline-danger">
                        Revisar pendientes
                    </a>
                </div>
            </div>
        </div>
    @endif


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



        <div class="col-md-3">
            <a href="{{ route('boleta.mensual.panel') }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                        <h6 class="fw-semibold mb-1">Panel boleta Honorarios</h6>
                        <p class="text-muted small mb-0">Boleta honorarios</p>
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
