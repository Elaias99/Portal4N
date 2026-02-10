@extends('layouts.app')

@section('content')
<div class="container">
    {{-- Título principal --}}
    <h1 class="text-center mb-4" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">
        Lista de Proveedores
    </h1>

    {{-- Error de plantilla --}}
    @if (session('faltantes_plantilla'))
        <div class="alert alert-danger shadow-sm">
            <strong>El archivo no coincide con la plantilla oficial</strong>
            <p class="mb-1">Faltan las siguientes columnas:</p>
            <ul class="mb-0" style="columns: 2">
                @foreach (session('faltantes_plantilla') as $col)
                    <li><code>{{ $col }}</code></li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Resultado de importación --}}
    @if (session('import_result_proveedores'))
        <div class="alert alert-info shadow-sm">
            <strong>Importación finalizada</strong>
            <ul>
                <li>Proveedores importados: <strong>{{ session('import_result_proveedores.importadas') }}</strong></li>
                <li>Proveedores omitidos: <strong>{{ session('import_result_proveedores.omitidas') }}</strong></li>
            </ul>

            {{-- Secciones desplegables --}}
            @if (count(session('import_result_proveedores.exitosos', [])))
                <details class="mt-2">
                    <summary>✅ Ver proveedores importados ({{ count(session('import_result_proveedores.exitosos')) }})</summary>
                    <ul class="mt-2">
                        @foreach (session('import_result_proveedores.exitosos') as $exito)
                            <li>✅ {{ $exito }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif

            @if (count(session('import_result_proveedores.erroresDuplicados', [])))
                <details class="mt-3">
                    <summary>🔁 Ver duplicados ({{ count(session('import_result_proveedores.erroresDuplicados')) }})</summary>
                    <ul>
                        @foreach (session('import_result_proveedores.erroresDuplicados') as $error)
                            <li>🔁 {{ $error }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif

            @if (count(session('import_result_proveedores.erroresFaltantes', [])))
                <details class="mt-3">
                    <summary>❌ Faltan campos obligatorios ({{ count(session('import_result_proveedores.erroresFaltantes')) }})</summary>
                    <ul>
                        @foreach (session('import_result_proveedores.erroresFaltantes') as $error)
                            <li>❌ {{ $error }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif

            @if (count(session('import_result_proveedores.erroresCamposInvalidos', [])))
                <details class="mt-3">
                    <summary>❗ Campos inválidos ({{ count(session('import_result_proveedores.erroresCamposInvalidos')) }})</summary>
                    <ul>
                        @foreach (session('import_result_proveedores.erroresCamposInvalidos') as $error)
                            <li>❗ {{ $error }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif

            @if (count(session('import_result_proveedores.incompletos', [])))
                <details class="mt-3">
                    <summary>⚠️ Campos con "Sin Registro" ({{ count(session('import_result_proveedores.incompletos')) }})</summary>
                    <ul>
                        @foreach (session('import_result_proveedores.incompletos') as $incompleto)
                            <li>🔍 {{ $incompleto }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    @endif

    <div class="row">
        {{-- Columna izquierda --}}
        <div class="col-lg-2 mb-4">
            @component('layouts.columna_izquierda', [
                'tituloTarjeta' => 'Gestión Masiva de Proveedores',
                'tituloFiltros' => 'Filtrar Proveedores',
                'action' => route('proveedores.index')
            ])
                @slot('acciones')
                    {{-- Importar --}}
                    <button type="button" class="btn btn-outline-success btn-block py-2 mb-2 d-flex align-items-center justify-content-center"
                        data-toggle="modal" data-target="#modalImportarExcelProveedores">
                        <i class="fa-solid fa-file-excel me-1"></i> Importar Excel
                    </button>

                    {{-- Exportar --}}
                    <button type="button" class="btn btn-outline-success btn-block py-2 d-flex align-items-center justify-content-center"
                        data-toggle="modal" data-target="#modalExportarProveedores">
                        <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
                    </button>
                @endslot

                @slot('filtros')
                    <div class="mb-3">
                        <label class="form-label">Razón Social:</label>
                        <input type="text" name="razon_social" class="form-control" placeholder="Ej: Acme Ltda." value="{{ request('razon_social') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">RUT:</label>
                        <input type="text" name="rut" class="form-control" placeholder="Ej: 12345678-9" value="{{ request('rut') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Banco:</label>
                        <select name="banco" class="form-select form-select-sm">
                            <option value="">- Seleccionar Banco -</option>
                            @foreach($bancos as $banco)
                                <option value="{{ $banco->id }}" {{ request('banco') == $banco->id ? 'selected' : '' }}>
                                    {{ $banco->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo de Cuenta:</label>
                        <select name="tipo_cuenta" class="form-select form-select-sm">
                            <option value="">- Seleccionar Tipo de Cuenta -</option>
                            @foreach($tiposCuenta as $cuenta)
                                <option value="{{ $cuenta->id }}" {{ request('tipo_cuenta') == $cuenta->id ? 'selected' : '' }}>
                                    {{ $cuenta->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo de Documento:</label>
                        <select name="tipo_pago" class="form-select form-select-sm">
                            <option value="">- Seleccionar Tipo de Documento -</option>
                            @foreach($tiposDocumento as $documento)
                                <option value="{{ $documento->id }}" {{ request('tipo_pago') == $documento->id ? 'selected' : '' }}>
                                    {{ $documento->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endslot
            @endcomponent
        </div>

        {{-- Contenido principal --}}
        <div class="col-lg-9">
            {{-- Acciones principales --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <button type="button" class="btn btn-outline-primary btn-sm"
                    data-toggle="modal" data-target="#modalImportarProveedores" title="Plantillas"> 
                    <i class="fa fa-info-circle mr-1"></i> Ver estructura y plantilla
                </button>

                <a href="{{ route('proveedores.create') }}" class="btn btn-primary btn-sm shadow-sm" title="Agregar Proveedor">
                    <i class="fa-solid fa-user-plus me-1"></i> Agregar
                </a>
            </div>

            {{-- Mensaje de éxito --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fa-regular fa-circle-check me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Tabla de proveedores --}}
            <div class="table-responsive shadow-sm rounded">
                <table class="table table-hover align-middle">
                    <thead class="bg-secondary text-white">
                        <tr>
                            <th>Razón Social</th>
                            <th>RUT Razón Social</th>
                            <th>Teléfono Empresa</th>
                            <th>Banco</th>
                            <th>Representante Legal</th>
                            <th>Teléfono Representante</th>
                            <th class="text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($proveedores as $proveedor)
                            <tr class="accordion-toggle" data-toggle="collapse" data-target="#details-{{ $proveedor->id }}">
                                <td>{{ $proveedor->razon_social }}</td>
                                <td>{{ $proveedor->rut }}</td>
                                <td>{{ $proveedor->telefono_empresa }}</td>
                                <td>{{ $proveedor->banco->nombre ?? 'Sin banco' }}</td>
                                <td>{{ $proveedor->Nombre_RepresentanteLegal }}</td>
                                <td>{{ $proveedor->Telefono_RepresentanteLegal }}</td>
                                <td class="text-center">
                                    <button class="btn btn-outline-primary btn-sm" title="Ver Detalles">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </td>
                            </tr>

                            {{-- Fila expandible con más datos --}}
                            <tr class="collapse" id="details-{{ $proveedor->id }}">
                                <td colspan="9" class="bg-light">
                                    <div class="p-4 rounded shadow-sm border">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h4 class="mb-3">Direcciones</h4>
                                                <p><strong>Facturación:</strong> {{ $proveedor->direccion_facturacion }}</p>
                                                <p><strong>Despacho:</strong> {{ $proveedor->direccion_despacho }}</p>
                                                <p><strong>Comuna:</strong> {{ $proveedor->comuna->Nombre ?? 'No asignada' }}</p>

                                                <h4 class="mt-4 mb-3">Datos Bancarios</h4>
                                                <p><strong>Banco:</strong> {{ $proveedor->banco->nombre ?? 'Sin banco' }}</p>
                                                <p><strong>Tipo de Cuenta:</strong> {{ $proveedor->tipoCuenta->nombre ?? 'Sin Tipo' }}</p>
                                                <p><strong>Número de Cuenta:</strong> {{ $proveedor->nro_cuenta }}</p>
                                                <p><strong>Correo Bancario:</strong> {{ $proveedor->correo_banco }}</p>
                                                <p><strong>Razón Social Asociada:</strong> {{ $proveedor->nombre_razon_social_banco }}</p>
                                                <p><strong>Método de Pago:</strong> {{ $proveedor->tipoPago->nombre ?? 'Sin asignar' }}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <h4 class="mb-3">Representante Legal</h4>
                                                <p><strong>Correo:</strong> {{ $proveedor->Correo_RepresentanteLegal }}</p>

                                                <h4 class="mt-4 mb-3">Contactos Adicionales</h4>
                                                <ul>
                                                    <li><strong>Nombre:</strong> {{ $proveedor->contacto_nombre }}</li>
                                                    <li><strong>Teléfono:</strong> {{ $proveedor->contacto_telefono }}</li>
                                                    <li><strong>Correo:</strong> {{ $proveedor->contacto_correo }}</li>
                                                    <li><strong>Cargo:</strong> {{ $proveedor->cargo_contacto1 }}</li>
                                                </ul>
                                                <ul>
                                                    <li><strong>Nombre:</strong> {{ $proveedor->nombre_contacto2 }}</li>
                                                    <li><strong>Teléfono:</strong> {{ $proveedor->telefono_contacto2 }}</li>
                                                    <li><strong>Correo:</strong> {{ $proveedor->correo_contacto2 }}</li>
                                                    <li><strong>Cargo:</strong> {{ $proveedor->cargo_contacto2 }}</li>
                                                </ul>
                                            </div>
                                        </div>

                                        {{-- Acciones --}}
                                        <div class="d-flex justify-content-end gap-2 mt-3">
                                            <a href="{{ route('proveedores.edit', $proveedor->id) }}" 
                                               class="btn btn-outline-warning btn-sm shadow-sm" title="Editar">
                                                <i class="fa-regular fa-pen-to-square"></i> 
                                            </a>
                                        
                                            <form action="{{ route('proveedores.destroy', $proveedor->id) }}" method="POST"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar este proveedor?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm shadow-sm" title="Eliminar">
                                                    <i class="fa-solid fa-trash"></i> 
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No hay proveedores registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            <div class="mt-3 d-flex justify-content-center">
                {{ $proveedores->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>

{{-- Tooltips --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'))
        tooltipTriggerList.map(function (el) {
            return new bootstrap.Tooltip(el)
        });
    });
</script>

{{-- Modales --}}
@include('proveedores.modal_importar')
@include('proveedores.modal_importar_proveedores')
@include('proveedores.modal_exportar_proveedores')
@endsection
