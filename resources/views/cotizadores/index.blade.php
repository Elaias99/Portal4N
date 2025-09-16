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
                <div id="campos-transporte" class="d-none">
                    <div class="mb-3">
                        <label for="Origen" class="form-label">Origen</label>
                        <input type="text" name="Origen" id="Origen" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="Destino" class="form-label">Destino</label>
                        <input type="text" name="Destino" id="Destino" class="form-control">
                    </div>

                    <input type="hidden" name="origen_lat" id="origen_lat">
                    <input type="hidden" name="origen_lon" id="origen_lon">
                    <input type="hidden" name="destino_lat" id="destino_lat">
                    <input type="hidden" name="destino_lon" id="destino_lon">

                    <div class="mb-3">
                        <label for="distancia_km" class="form-label">Distancia (km)</label>
                        <input type="number" step="0.01" name="distancia_km" id="distancia_km" class="form-control">
                    </div>

                    <div class="mb-3">
                        <button type="button" id="btnCalcular" class="btn btn-secondary">Calcular distancia</button>
                        <span id="resultadoDistancia" class="ms-3 text-primary fw-bold"></span>
                    </div>

                    <div id="resultadoRuta" class="mt-3"></div>
                </div>

                <div id="campos-courier" class="d-none">
                    <div class="mb-3">
                        <label for="detalle_courier" class="form-label">Detalle Courier</label>
                        <input type="text" id="detalle_courier" class="form-control" placeholder="Ej: Empresa courier">
                    </div>
                </div>

                <div id="campos-almacenaje" class="d-none">
                    <div class="mb-3">
                        <label for="detalle_almacenaje" class="form-label">Detalle Almacenaje</label>
                        <input type="text" id="detalle_almacenaje" class="form-control" placeholder="Ej: Tiempo estimado en días">
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
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Distancia (km)</th>
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
                                <td>{{ $coti->Origen }}</td>
                                <td>{{ $coti->Destino }}</td>
                                <td>
                                    @if($coti->distancia_km)
                                        {{ number_format($coti->distancia_km, 2) }} km
                                    @else
                                        -
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
                                <td colspan="8" class="text-center">No hay cotizaciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Script para mostrar/ocultar campos dinámicos --}}
<script>
document.addEventListener("DOMContentLoaded", function() {
    const servicioSelect = document.getElementById("servicio");
    const camposTransporte = document.getElementById("campos-transporte");
    const camposCourier = document.getElementById("campos-courier");
    const camposAlmacenaje = document.getElementById("campos-almacenaje");

    function toggleCampos() {
        const texto = servicioSelect.options[servicioSelect.selectedIndex].text;
        camposTransporte.classList.add("d-none");
        camposCourier.classList.add("d-none");
        camposAlmacenaje.classList.add("d-none");

        if (texto === "Transporte") {
            camposTransporte.classList.remove("d-none");
        } else if (texto === "Maquila") {
            camposCourier.classList.remove("d-none");
        } else if (texto === "Almacenaje") {
            camposAlmacenaje.classList.remove("d-none");
        }
    }


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


    servicioSelect.addEventListener("change", toggleCampos);
    toggleCampos(); // inicializar al cargar

    // Cálculo de distancia
    const btnCalcular = document.getElementById("btnCalcular");
        const resultadoDistancia = document.getElementById("resultadoDistancia");

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
                // Si faltan coords de origen, geocodifica
                if ((!origenLat || !origenLon) && origenTxt) {
                    const geoO = await geocodificar(origenTxt);
                    if (geoO.error) throw new Error("Origen: " + geoO.error);
                    origenLat = geoO.lat; origenLon = geoO.lon;
                    document.getElementById("origen_lat").value = origenLat;
                    document.getElementById("origen_lon").value = origenLon;
                }

                // Si faltan coords de destino, geocodifica
                if ((!destinoLat || !destinoLon) && destinoTxt) {
                    const geoD = await geocodificar(destinoTxt);
                    if (geoD.error) throw new Error("Destino: " + geoD.error);
                    destinoLat = geoD.lat; destinoLon = geoD.lon;
                    document.getElementById("destino_lat").value = destinoLat;
                    document.getElementById("destino_lon").value = destinoLon;
                }

                // Ahora calculamos distancia
                const data = { origen_lat: origenLat, origen_lon: origenLon, destino_lat: destinoLat, destino_lon: destinoLon };
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

});
</script>
@endsection
