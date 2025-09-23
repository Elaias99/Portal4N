@extends('layouts.app')

@section('content')
<div class="container">

    {{-- Título --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 mb-0">Cotizador</h1>
            <small class="text-muted">Módulo de Finanzas - Cotizaciones</small>
        </div>
    </div>

    {{-- Mensajes de éxito o error --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Formulario de creación de cotización --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('cotizadores.store') }}" method="POST">
                @csrf

                {{-- Cliente --}}
                <div class="mb-3">
                    <label for="nombre_cliente" class="form-label">Cliente</label>
                    <select name="nombre_cliente" id="nombre_cliente" class="form-select" required>
                        <option value="">-- Selecciona un cliente --</option>
                        <option value="Revés Derecho">Revés Derecho</option>
                        <option value="Caja Los Andes">Caja Los Andes</option>
                    </select>
                </div>

                {{-- Tipo de servicio --}}
                <div class="mb-3">
                    <label for="servicio" class="form-label">Servicio</label>
                    <select name="servicio_id" id="servicio" class="form-select" required>
                        <option value="">-- Selecciona un servicio --</option>
                        @foreach($servicios as $servicio)
                            <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Campos dinámicos según servicio --}}
                {{-- Transporte (usa modal) --}}
                <div id="campos-transporte" class="d-none">
                    <div id="btnModalTransporteWrapper">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalTransporte">
                            ➕ Agregar transporte
                        </button>
                    </div>
                </div>

                {{-- Incluir modal de transporte --}}
                @include('cotizadores.modal_transporte')





                <div id="campos-maquila" class="d-none">

                    {{-- Con/Sin insumo --}}
                    <div class="mb-3">
                        <label for="insumo" class="form-label">¿Quién aporta el insumo?</label>
                        <select name="insumo" id="insumo" class="form-select" required>
                            <option value="">-- Selecciona --</option>
                            <option value="proveedor">Proveedor (nosotros)</option>
                            <option value="cliente">Cliente</option>
                        </select>
                    </div>

                    {{-- Botón para abrir modal (solo proveedor) --}}
                    <div id="btnModalWrapper" class="d-none">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalInsumos">
                            ➕ Agregar insumos
                        </button>
                    </div>


                    {{-- Tipo de maquila --}}
                    <div class="mb-3">
                        <label for="tipo_maquila_id" class="form-label">Tipo de maquila</label>
                        <select name="tipo_maquila_id" id="tipo_maquila_id" class="form-select" required>
                            <option value="">-- Selecciona un tipo de maquila --</option>
                            @foreach($tiposMaquila as $tipo)
                                <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Duración estimada del proceso --}}
                    <div class="mb-3">
                        <label for="duracion_proceso" class="form-label">Duración estimada del proceso</label>
                        <input type="text" name="duracion_proceso" id="duracion_proceso" class="form-control" placeholder="Ej: 2 horas, 1 día, 1 semana">
                    </div>

                    {{-- Requiere transporte --}}
                    <div class="mb-3">
                        <label for="requiere_transporte" class="form-label">¿Requiere transporte?</label>
                        <select name="requiere_transporte" id="requiere_transporte" class="form-select">
                            <option value="0">No</option>
                            <option value="1">Sí</option>
                        </select>
                    </div>

                </div>

                {{-- Estado --}}
                <div class="mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="pendiente">Pendiente</option>
                        <option value="aprobada">Aprobada</option>
                        <option value="rechazada">Rechazada</option>
                    </select>
                </div>

                @include('cotizadores.modal_insumos')

                <button type="submit" class="btn btn-primary">Guardar Cotización</button>
            </form>
        </div>
    </div>

    {{-- Listado de cotizaciones --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Listado de Cotizaciones</h5>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Servicio</th>
                            {{-- Columnas dinámicas --}}
                            <th>Detalles</th>
                            <th>Estado</th>
                            <th>Creado</th>
                        </tr>
                    </thead>


                    <tbody>
                        @forelse ($cotizaciones as $coti)
                            <tr>
                                <td>{{ $coti->id }}</td>
                                <td>{{ $coti->nombre_cliente }}</td>
                                <td>{{ $coti->servicio->nombre ?? 'N/A' }}</td>

                                {{-- Mostrar diferente según servicio --}}
                                <td>
                                    @if($coti->servicio->nombre === 'Transporte')
                                        <strong>Tipo de movilización:</strong> {{ $coti->transporte->nombre ?? '-' }} <br>
                                        <strong>Origen:</strong> {{ $coti->Origen ?? '-' }} <br>
                                        <strong>Destino:</strong> {{ $coti->Destino ?? '-' }} <br>
                                        <strong>Distancia:</strong>
                                            @if($coti->distancia_km)
                                                {{ number_format($coti->distancia_km, 2) }} km
                                            @else
                                                -
                                            @endif
                                        <br>
                                        <strong>Lleva pioneta:</strong> {{ $coti->lleva_pioneta ? 'Sí' : 'No' }} <br>

                                        @if($coti->lleva_pioneta)
                                            <strong>Cantidad pionetas:</strong> {{ $coti->cantidad_pionetas ?? '-' }} <br>
                                            <strong>Jornada:</strong> {{ $coti->jornada_pioneta ?? '-' }} <br>
                                        @endif


                                        <strong>Con carga:</strong> {{ $coti->con_carga ? 'Sí' : 'No' }} <br>

                                        @if($coti->con_carga && $coti->cargasTransporte->count() > 0)
                                            <strong>Detalle de cargas:</strong>
                                            <table class="table table-sm mt-2">
                                                <thead>
                                                    <tr>
                                                        <th>Detalle carga</th>
                                                        <th>Cantidad</th>
                                                        <th>Medida</th>
                                                        <th>Peso total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($coti->cargasTransporte as $carga)
                                                        <tr>
                                                            <td>{{ $carga->descripcion }}</td>
                                                            <td>{{ $carga->cantidad }}</td>
                                                            <td>{{ $carga->medida ?? '-' }}</td>
                                                            <td>
                                                                @if($carga->peso_total)
                                                                    {{ rtrim(rtrim(number_format($carga->peso_total, 2, '.', ''), '0'), '.') }} {{ $carga->unidad_peso ?? '' }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif




                                    @elseif($coti->servicio->nombre === 'Maquila' && $coti->maquilado)
                                        @if($coti->maquilado->insumo === 'proveedor')
                                            <strong>Insumos (Proveedor):</strong>
                                            <table class="table table-sm mt-2">
                                                <thead>
                                                    <tr>
                                                        <th>Detalle</th>
                                                        <th>Cantidad</th>
                                                        <th>Precio</th>
                                                        <th>Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($coti->maquilado->insumos as $insumo)
                                                        <tr>
                                                            <td>{{ $insumo->detalle }}</td>
                                                            <td>{{ $insumo->cantidad }}</td>
                                                            <td>{{ number_format($insumo->precio, 0, ',', '.') }}</td>
                                                            <td>{{ number_format($insumo->subtotal, 0, ',', '.') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            <strong>Total: </strong>
                                            {{ number_format($coti->maquilado->insumos->sum('subtotal'), 0, ',', '.') }} <br>
                                        @else
                                            <strong>Insumo:</strong> Cliente <br>
                                            <strong>Tipo de maquila:</strong> {{ $coti->maquilado->tipoMaquila->nombre ?? '-' }} <br>
                                        @endif

                                        {{-- Campos nuevos (se muestran siempre, sea proveedor o cliente) --}}
                                        <strong>Duración estimada:</strong> {{ $coti->maquilado->duracion_proceso ?? '-' }} <br>
                                        <strong>Requiere transporte:</strong> {{ $coti->maquilado->requiere_transporte ? 'Sí' : 'No' }}
                                    @endif






                                </td>

                                <td>
                                    <span class="badge 
                                        @if($coti->estado == 'pendiente') bg-warning 
                                        @elseif($coti->estado == 'aprobada') bg-success 
                                        @else bg-danger @endif">
                                        {{ ucfirst($coti->estado) }}
                                    </span>
                                </td>
                                <td>{{ $coti->created_at->format('d-m-Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No hay cotizaciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>





                </table>
            </div>
        </div>
    </div>


{{-- Script para mostrar/ocultar campos dinámicos --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const servicioSelect = document.getElementById("servicio");
        const camposTransporte = document.getElementById("campos-transporte");
        const camposMaquila = document.getElementById("campos-maquila");
        const insumoSelect = document.getElementById("insumo");

        // ======================
        // Mostrar/ocultar campos
        // ======================
        function toggleCampos() {
            const texto = servicioSelect.options[servicioSelect.selectedIndex].text;

            // Ocultar todo al inicio
            camposTransporte.classList.add("d-none");
            camposMaquila.classList.add("d-none");

            // Quitar required de todo
            document.querySelectorAll("#campos-transporte [required], #campos-maquila [required]").forEach(el => {
                el.removeAttribute("required");
            });

            if (texto === "Transporte") {
                camposTransporte.classList.remove("d-none");
                document.getElementById("transporte_id")?.setAttribute("required", "required");
                document.getElementById("Origen")?.setAttribute("required", "required");
                document.getElementById("Destino")?.setAttribute("required", "required");
            } else if (texto === "Maquila") {
                camposMaquila.classList.remove("d-none");
                document.getElementById("insumo")?.setAttribute("required", "required");
                document.getElementById("tipo_maquila_id")?.setAttribute("required", "required");

                // 👇 Limpiar o inicializar campos nuevos
                document.getElementById("duracion_proceso").value = "";
                document.getElementById("requiere_transporte").value = "0";

                // 👇 Forzar que se apliquen las reglas de insumo
                toggleDetalleInsumo();
            }
        }

        // ==============================
        // Mostrar botón del modal (proveedor)
        // ==============================
        function toggleDetalleInsumo() {
            const btnModalWrapper = document.getElementById("btnModalWrapper");

            if (insumoSelect.value === "proveedor") {
                btnModalWrapper.classList.remove("d-none");

                // Hacer requeridos los insumos dentro de la tabla
                document.querySelectorAll("#tablaInsumosBody input").forEach(el => {
                    el.setAttribute("required", "required");
                });
            } else {
                btnModalWrapper.classList.add("d-none");

                // Quitar required de los insumos si no aplica
                document.querySelectorAll("#tablaInsumosBody input").forEach(el => {
                    el.removeAttribute("required");
                });
            }
        }

        servicioSelect.addEventListener("change", toggleCampos);
        toggleCampos(); // inicializar al cargar

        if (insumoSelect) {
            insumoSelect.addEventListener("change", toggleDetalleInsumo);
            toggleDetalleInsumo(); // inicializar
        }

        // ======================
        // 🚚 Cálculo de distancia
        // ======================
        const btnCalcular = document.getElementById("btnCalcular");
        const resultadoDistancia = document.getElementById("resultadoDistancia");

        async function geocodificar(direccion) {
            const res = await fetch("/api/cotizadores/geocodificar", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ direccion })
            });
            return res.json();
        }

        if (btnCalcular) {
            btnCalcular.addEventListener("click", async function() {
                const resultadoRuta = document.getElementById("resultadoRuta");
                resultadoRuta.innerHTML = "";
                resultadoDistancia.textContent = "Calculando...";

                const origenTxt  = document.getElementById("Origen").value.trim();
                const destinoTxt = document.getElementById("Destino").value.trim();

                let origenLat  = document.getElementById("origen_lat").value;
                let origenLon  = document.getElementById("origen_lon").value;
                let destinoLat = document.getElementById("destino_lat").value;
                let destinoLon = document.getElementById("destino_lon").value;

                try {
                    if ((!origenLat || !origenLon) && origenTxt) {
                        const geoO = await geocodificar(origenTxt);
                        if (geoO.error) throw new Error("Origen: " + geoO.error);
                        origenLat = geoO.lat; origenLon = geoO.lon;
                        document.getElementById("origen_lat").value = origenLat;
                        document.getElementById("origen_lon").value = origenLon;
                    }

                    if ((!destinoLat || !destinoLon) && destinoTxt) {
                        const geoD = await geocodificar(destinoTxt);
                        if (geoD.error) throw new Error("Destino: " + geoD.error);
                        destinoLat = geoD.lat; destinoLon = geoD.lon;
                        document.getElementById("destino_lat").value = destinoLat;
                        document.getElementById("destino_lon").value = destinoLon;
                    }

                    const transporteSelect = document.getElementById("transporte_id");
                    const perfil = transporteSelect.options[transporteSelect.selectedIndex].dataset.perfil;

                    const data = { 
                        origen_lat: origenLat, 
                        origen_lon: origenLon, 
                        destino_lat: destinoLat, 
                        destino_lon: destinoLon,
                        perfil: perfil
                    };

                    const res = await fetch("{{ route('cotizadores.calcular-distancia') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify(data)
                    });

                    const json = await res.json();

                    if (json.distancia_km) {
                        resultadoDistancia.textContent = `Distancia: ${json.distancia_km} km | Duración: ${json.duracion_min} min`;
                        document.getElementById("distancia_km").value = json.distancia_km;

                        resultadoRuta.innerHTML = "<h6>Indicaciones:</h6><ol>" +
                            (json.instrucciones || []).map(instr => `<li>${instr}</li>`).join('') +
                            "</ol>";
                    } else {
                        resultadoDistancia.textContent = json.error || "No se pudo calcular la distancia.";
                    }
                } catch (e) {
                    resultadoDistancia.textContent = "Error: " + e.message;
                }
            });
        }
    });
</script>




@endsection
