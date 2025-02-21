<!-- Propuesta para el formulario (form.blade.php) -->
<div class="container">
    <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if ($method === 'PUT')
            @method('PUT')
        @endif

        <!-- Ordenado según plantilla y modelo -->
        <div class="mb-3">
            <label for="centro_costo" class="form-label">Centro de Costo</label>
        
            <select id="centro_costo_select" class="form-control" onchange="toggleInput()">
                <option value="">Seleccione un Centro de Costo</option>
                @php
                    $valorCentroCosto = old('centro_costo', $compra->centro_costo ?? '');
                    $centroCostoEnLista = in_array($valorCentroCosto, $centrosCosto);
                @endphp
        
                @foreach($centrosCosto as $centro)
                    <option value="{{ $centro }}" {{ $valorCentroCosto == $centro ? 'selected' : '' }}>
                        {{ $centro }}
                    </option>
                @endforeach
        
                <option value="otro" {{ !$centroCostoEnLista && $valorCentroCosto ? 'selected' : '' }}>
                    Otro (Ingresar manualmente)
                </option>
            </select>
        
            <input type="text" name="centro_costo" id="centro_costo_input" class="form-control mt-2"
                   value="{{ !$centroCostoEnLista ? $valorCentroCosto : '' }}" 
                   placeholder="Ingrese un nuevo centro de costo" 
                   style="{{ !$centroCostoEnLista && $valorCentroCosto ? 'display: block;' : 'display: none;' }}" 
                   {{ !$centroCostoEnLista && $valorCentroCosto ? 'required' : '' }}>
        </div>

        <div class="mb-3">
            <label for="glosa" class="form-label">Glosa</label>
            <textarea name="glosa" id="glosa" class="form-control" rows="3" required>{{ old('glosa', $compra->glosa ?? '') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="observacion" class="form-label">Observacion</label>
            <textarea name="observacion" id="observacion" class="form-control" rows="3">{{ old('observacion', $compra->observacion ?? '') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="tipo_pago">Tipo de Pago</label>
            <select name="tipo_pago" id="tipo_pago" class="form-control">
                @foreach(['Contado','Quincena','30 Días','45 Días','60 Días'] as $tipopago)
                    <option value="{{ $tipopago }}" {{ old('tipo_pago', $compra->tipo_pago ?? '') == $tipopago ? 'selected' : '' }}>
                        {{ $tipopago }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3" id="menu_contado" style="display: none;">
            <label for="opcion_contado" class="form-label">Pago al contado</label>
            <select name="opcion_contado" id="opcion_contado" class="form-control">
                <option value="hoy" {{ old('opcion_contado', 'hoy') == 'hoy' ? 'selected' : '' }}>Pagar hoy (Fecha del Documento)</option>
                <option value="viernes" {{ old('opcion_contado') == 'viernes' ? 'selected' : '' }}>Pagar al Viernes más cercano</option>
            </select>
        </div>

        <div class="form-group">
            <label for="empresa_id">Empresa Facturadora</label>
            <select name="empresa_id" id="empresa_id" class="form-control" required>
                @foreach($empresas as $empresa)
                    <option value="{{ $empresa->id }}" {{ old('empresa_id', $compra->empresa_id ?? '') == $empresa->id ? 'selected' : '' }}>
                        {{ $empresa->Nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="año" class="form-label">Año</label>
            <input type="number" name="año" id="año" class="form-control"
                   value="{{ old('año', $compra->año ?? date('Y')) }}" required>
        </div>
        

        <div class="mb-3">
            <label for="mes" class="form-label">Mes de servicio</label>
            <select name="mes" id="mes" class="form-control" required>
                @foreach(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $index => $nombreMes)
                    <option value="{{ $nombreMes }}" {{ old('mes', $compra->mes ?? date('F')) == $nombreMes ? 'selected' : '' }}>
                        {{ $nombreMes }}
                    </option>
                @endforeach
            </select>
        </div>
        

        <div class="mb-3">
            <label for="proveedor_id" class="form-label">Razon Social (Proveedor)</label>
            <select name="proveedor_id" id="proveedor_id" class="form-select" required>
                <option value="">Seleccione un proveedor</option>
                @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" 
                        {{ old('proveedor_id', $compra->proveedor_id ?? '') == $proveedor->id ? 'selected' : '' }}>
                        {{ $proveedor->razon_social }}
                    </option>
                @endforeach
            </select>
        </div>





        <div class="mb-3">
            <label for="tipo_documento">Tipo de Documento</label>
            <select name="tipo_documento" id="tipo_documento" class="form-control">
                @foreach(['Boleta','Factura','Boleta Honorarios','Boleta de Tercero','Documento','Factura Exenta','Factura Pendiente'] as $tipo_documento )
                <option value="{{$tipo_documento}}" {{ old('tipo_documento', $compra->tipo_documento ?? '') == $tipo_documento ? 'selected' : '' }} >
                    {{ $tipo_documento }}
                </option>
                @endforeach
            </select>
        </div>






        

        <div class="mb-3">
            <label for="fecha_documento" class="form-label">Fecha del Documento</label>
            <input type="date" name="fecha_documento" id="fecha_documento" class="form-control"
                   value="{{ old('fecha_documento', $compra->fecha_documento ?? date('Y-m-d')) }}">
        </div>
        

        <div class="mb-3">
            <label for="numero_documento" class="form-label">Número del Documento</label>
            <input type="text" name="numero_documento" id="numero_documento" class="form-control"
                   value="{{ old('numero_documento', $compra->numero_documento ?? '') }}">
        </div>

        <div class="mb-3">
            <label for="oc" class="form-label">Orden de Compra (O.C)</label>
            <input type="text" name="oc" id="oc" class="form-control"
                   value="{{ old('oc', $compra->oc ?? '') }}">
        </div>


        <div class="mb-3">
            <label for="forma_pago">Forma de pago</label>
            <select name="forma_pago" id="forma_pago" class="form-control">
                @foreach(['Tarjeta 4N','4N - Transferencia','Pmcb - Transferencia','Marcelo Godoy','Caja Chica','Caja Chica Negra',
                'Cruce'] as $forma_pago)
                    <option value="{{ $forma_pago }}" {{ old('forma_pago', $compra->forma_pago ?? '') == $forma_pago ? 'selected' : '' }}>
                        {{ $forma_pago }}
                    </option>
                @endforeach
            </select>
        </div>


        <div class="mb-3">
            <label for="pago_total" class="form-label">Pago Total</label>
            <input type="number" step="0.01" name="pago_total" id="pago_total" class="form-control"
                   value="{{ old('pago_total', $compra->pago_total ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Estado de la Compra</label>
            <select name="status" id="status" class="form-select">
                <option value="Pendiente" {{ old('status', $compra->status ?? '') == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="Pagado" {{ old('status', $compra->status ?? '') == 'Pagado' ? 'selected' : '' }}>Pagado</option>
                <option value="Abonado" {{ old('status', $compra->status ?? '') == 'Abonado' ? 'selected' : '' }}>Abonado</option>
                <option value="No Pagar" {{ old('status', $compra->status ?? '') == 'No Pagar' ? 'selected' : '' }}>No Pagar</option>
            </select>
        </div>
        

        <div class="mb-3">
            <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" class="form-control"
                   value="{{ old('fecha_vencimiento', $compra->fecha_vencimiento ?? '') }}" required>
        </div>


        <!-- Archivos -->
        <div class="mb-3">
            <label for="archivo_oc" class="form-label">Adjuntar O.C</label>
            <input type="file" name="archivo_oc" id="archivo_oc" class="form-control">
        </div>

        <div class="mb-3">
            <label for="archivo_documento" class="form-label">Adjuntar Documento</label>
            <input type="file" name="archivo_documento" id="archivo_documento" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <br>
        <br>
        
    </form>
</div>
<script>
    function toggleInput() {
        let select = document.getElementById("centro_costo_select");
        let input = document.getElementById("centro_costo_input");
        
        if (select.value === "otro") {
            input.style.display = "block";
            input.setAttribute("required", "true");
            input.value = "";
        } else {
            input.style.display = "none";
            input.removeAttribute("required");
            input.value = select.value; // ✅ Asigna el valor del select al input oculto
        }
    }

</script>
<!-- Script para manejar la lógica del menú de "Contado" -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tipoPagoSelect = document.getElementById('tipo_pago');
        const menuContado = document.getElementById('menu_contado'); // Este menú se muestra solo para "Contado"
        const opcionContado = document.getElementById('opcion_contado');
        const fechaDocumentoInput = document.getElementById('fecha_documento');
        const fechaVencimientoInput = document.getElementById('fecha_vencimiento');
    
        // Función que calcula el siguiente viernes a partir de una fecha dada
        function getNextFriday(date) {
            const day = date.getDay(); // Domingo=0, Lunes=1, ... Sábado=6
            let diff = (5 - day + 7) % 7;
            // Si el día ya es viernes, diff será 0 y se quedará en esa fecha.
            return new Date(date.getFullYear(), date.getMonth(), date.getDate() + diff);
        }
    
        // Función para formatear una fecha en formato YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            let month = (date.getMonth() + 1).toString();
            let day = date.getDate().toString();
            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;
            return `${year}-${month}-${day}`;
        }
    
        // Función que actualiza la fecha de vencimiento según el tipo de pago seleccionado
        function updateFechaVencimiento() {
            const fechaDocumento = fechaDocumentoInput.value;
            if (!fechaDocumento) return;
            let nuevaFecha;
            const tipoPago = tipoPagoSelect.value;
    
            if (tipoPago === 'Contado') {
                // Para "Contado", se usa el submenú: "hoy" o "viernes"
                menuContado.style.display = 'block';
                if (opcionContado.value === 'hoy') {
                    nuevaFecha = fechaDocumento;
                } else if (opcionContado.value === 'viernes') {
                    const docDate = new Date(fechaDocumento);
                    const nextFriday = getNextFriday(docDate);
                    nuevaFecha = formatDate(nextFriday);
                }
            } else {
                // Para los otros tipos, se calcula automáticamente:
                // Determinar el offset en días según el tipo de pago
                let offsetDays = 0;
                switch (tipoPago) {
                    case 'Quincena':
                        offsetDays = 15;
                        break;
                    case '30 Días':
                        offsetDays = 30;
                        break;
                    case '45 Días':
                        offsetDays = 45;
                        break;
                    case '60 Días':
                        offsetDays = 60;
                        break;
                    default:
                        offsetDays = 0;
                }
                // Ocultamos el menú adicional de "Contado" si no es ese tipo de pago
                menuContado.style.display = 'none';
    
                const docDate = new Date(fechaDocumento);
                // Sumamos el número de días correspondiente
                docDate.setDate(docDate.getDate() + offsetDays);
                // Ahora, calculamos el viernes más cercano a esa fecha
                const nextFriday = getNextFriday(docDate);
                nuevaFecha = formatDate(nextFriday);
            }
    
            // Actualizar el campo de Fecha de Vencimiento
            fechaVencimientoInput.value = nuevaFecha;
        }
    
        // Asignar eventos:
        tipoPagoSelect.addEventListener('change', updateFechaVencimiento);
        fechaDocumentoInput.addEventListener('change', updateFechaVencimiento);
        // Si el tipo de pago es Contado, también se actualiza al cambiar la opción
        if (opcionContado) {
            opcionContado.addEventListener('change', updateFechaVencimiento);
        }
    
        // Al cargar la página, ejecutamos la función para establecer la fecha inicialmente.
        updateFechaVencimiento();
    });
</script>
    