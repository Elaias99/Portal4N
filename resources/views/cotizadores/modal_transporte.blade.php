{{-- resources/views/cotizadores/modal_transporte.blade.php --}}

<div class="modal fade" id="modalTransporte" tabindex="-1" aria-labelledby="modalTransporteLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      
      {{-- Header --}}
      <div class="modal-header">
        <h5 class="modal-title" id="modalTransporteLabel">Detalle de Transporte</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      {{-- Body --}}
      {{-- Body --}}
    <div class="modal-body">

        {{-- Tipo de movilización --}}
        <div class="mb-3">
            <label for="transporte_id" class="form-label">Tipo de movilización</label>
            <select name="transporte_id" id="transporte_id" class="form-select" >
                <option value="">-- Selecciona un tipo --</option>
                @foreach($transportes as $transporte)
                    <option value="{{ $transporte->id }}" data-perfil="{{ $transporte->perfil_api }}">
                        {{ $transporte->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Origen --}}
        <div class="mb-3">
            <label for="Origen" class="form-label">Dirección de origen</label>
            <input type="text" name="Origen" id="Origen" class="form-control" >
        </div>

        {{-- Destino --}}
        <div class="mb-3">
            <label for="Destino" class="form-label">Dirección de destino</label>
            <input type="text" name="Destino" id="Destino" class="form-control" >
        </div>

        {{-- Lleva pioneta --}}
        <div class="mb-3">
            <label for="lleva_pioneta" class="form-label">¿Lleva peoneta?</label>
            <select name="lleva_pioneta" id="lleva_pioneta" class="form-select" >
                <option value="0">No</option>
                <option value="1">Sí</option>
            </select>
        </div>

        {{-- Detalle de pionetas (se muestra solo si lleva_pioneta = 1) --}}
        <div id="detallePionetaWrapper" class="d-none">
            <div class="mb-3">
                <label for="cantidad_pionetas" class="form-label">Cantidad de peonetas</label>
                <input type="number" name="cantidad_pionetas" id="cantidad_pionetas" class="form-control" min="1">
            </div>

            <div class="mb-3">
                <label for="jornada_pioneta" class="form-label">Jornada del peoneta</label>
                <input type="text" name="jornada_pioneta" id="jornada_pioneta" class="form-control">
            </div>

        </div>


        {{-- Con o sin carga --}}
        <div class="mb-3">
            <label for="con_carga" class="form-label">¿Con carga?</label>
            <select name="con_carga" id="con_carga" class="form-select" >
                <option value="1">Sí</option>
                <option value="0">No</option>
            </select>
        </div>

        {{-- Tabla de cargas, inicialmente oculta --}}
        <div id="tablaCargasWrapper" class="d-none mt-3">
            <h6>Detalle de cargas</h6>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Detalle Carga</th>
                        <th>Cantidad</th>
                        <th>Medida</th>
                        <th>Peso total</th>
                        <th>Unidad de peso</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="tablaCargasBody">
                    {{-- Filas dinámicas con JS --}}
                </tbody>
            </table>
            <button type="button" id="btnAgregarCarga" class="btn btn-outline-primary">
                Agregar carga
            </button>
        </div>

        {{-- Coordenadas ocultas --}}
        <input type="hidden" name="origen_lat" id="origen_lat">
        <input type="hidden" name="origen_lon" id="origen_lon">
        <input type="hidden" name="destino_lat" id="destino_lat">
        <input type="hidden" name="destino_lon" id="destino_lon">

        {{-- Distancia calculada --}}
        <div class="mb-3">
            <label for="distancia_km" class="form-label">Distancia (km)</label>
            <input type="number" step="0.01" name="distancia_km" id="distancia_km" class="form-control" readonly>
        </div>

        {{-- Botón calcular distancia --}}
        <div class="mb-3">
            <button type="button" id="btnCalcular" class="btn btn-secondary">Calcular distancia</button>
            <span id="resultadoDistancia" class="ms-3 text-primary fw-bold"></span>
        </div>

        {{-- Resultado de la ruta --}}
        <div id="resultadoRuta" class="mt-3"></div>

    </div>


      {{-- Footer --}}
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Guardar</button>
      </div>

    </div>
  </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- CARGAS ---
        const conCargaSelect = document.getElementById("con_carga");
        const tablaCargasWrapper = document.getElementById("tablaCargasWrapper");
        const tablaCargasBody = document.getElementById("tablaCargasBody");
        const btnAgregarCarga = document.getElementById("btnAgregarCarga");

        function toggleTablaCargas() {
            if (conCargaSelect.value === "1") {
                tablaCargasWrapper.classList.remove("d-none");
            } else {
                tablaCargasWrapper.classList.add("d-none");
                tablaCargasBody.innerHTML = ""; // limpiar si cambia a "No"
            }
        }

        function agregarFilaCarga() {
            const index = tablaCargasBody.children.length;
            const row = document.createElement("tr");
            row.innerHTML = `
                <td><input type="text" name="cargas[${index}][descripcion]" class="form-control" required></td>
                <td><input type="number" name="cargas[${index}][cantidad]" class="form-control" min="1" required></td>
                <td><input type="text" name="cargas[${index}][medida]" class="form-control"></td>
                <td><input type="number" step="0.01" name="cargas[${index}][peso_total]" class="form-control"></td>
                <td><input type="text" name="cargas[${index}][unidad_peso]" class="form-control"></td>
                <td><button type="button" class="btn btn-sm btn-danger btnEliminarCarga">Eliminar</button></td>
            `;
            tablaCargasBody.appendChild(row);

            row.querySelector(".btnEliminarCarga").addEventListener("click", () => {
                row.remove();
            });
        }

        // Eventos de cargas
        conCargaSelect.addEventListener("change", toggleTablaCargas);
        btnAgregarCarga.addEventListener("click", agregarFilaCarga);
        toggleTablaCargas(); // inicializar


        // --- PIONETA ---
        const llevaPionetaSelect = document.getElementById("lleva_pioneta");
        const detallePionetaWrapper = document.getElementById("detallePionetaWrapper");

        function toggleDetallePioneta() {
            if (llevaPionetaSelect.value === "1") {
                detallePionetaWrapper.classList.remove("d-none");
            } else {
                detallePionetaWrapper.classList.add("d-none");
                document.getElementById("cantidad_pionetas").value = "";
                document.getElementById("jornada_pioneta").value = "";
            }
        }

        // Eventos de pioneta
        llevaPionetaSelect.addEventListener("change", toggleDetallePioneta);
        toggleDetallePioneta(); // inicializar
    });
</script>
