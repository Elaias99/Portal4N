@extends('layouts.app')

@vite('resources/css/panel-finanzas.css')

@section('content')

<div class="container-fluid mt-4" id="modulo-finanzas">

    {{-- ====== CABECERA ====== --}}
    <div class="text-center mb-4">
        <h2 class="fw-bold">Módulo Boleta Honorarios</h2>
        <p class="text-muted mb-0">Panel central de gestión — Documentos, abonos, cruces y movimientos</p>
    </div>


    {{-- ====== ACCESOS DIRECTOS ====== --}}
    <div class="row justify-content-center text-center g-4 mb-4">

        {{-- === Cuentas por Cobrar === --}}
        <div class="col-md-3">
            <a href="{{ route('honorarios.mensual.index') }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                        <h6 class="fw-semibold mb-1">Honorario Mensual REC</h6>
                        <p class="text-muted small mb-0">Honorario Mensual REC</p>
                    </div>
                </div>
            </a>
        </div>




        {{-- === Cuentas por Pagar === --}}
        <div class="col-md-3">
            <a href="{{ route('honorarios.resumen.index') }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                        <h6 class="fw-semibold mb-1">Honorario Resumen Anual</h6>
                        <p class="text-muted small mb-0">Honorario Resumen Anual</p>
                    </div>
                </div>
            </a>
        </div>

        </div>



    </div>

    <div class="text-center mt-4">
        <a href="{{ route('cobranzas.general') }}" class="btn btn-outline-primary px-4 py-2 rounded-pill">
            <- Ir a Panel General de Cobranza
        </a>
    </div>





    <footer class="text-center mt-5">
        <small class="text-muted">© 4NLogística — Área de Finanzas</small>
    </footer>
</div>
@endsection
