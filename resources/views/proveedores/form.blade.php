@if($errors->any())
    <div class="alert alert-danger shadow-sm">
        <strong>Por favor corrige los siguientes errores:</strong>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ⚠️ Mensaje de campos obligatorios --}}
<div class="alert alert-info shadow-sm">
    <strong>Atención:</strong> Los campos marcados con <span class="text-danger">*</span> son obligatorios.
</div>

<h3 class="mt-4">Datos Básicos</h3>
<hr>
<div class="form-group">
    <label for="razon_social">Razón Social *</label>
    <input type="text" name="razon_social" id="razon_social" class="form-control"
           value="{{ old('razon_social', $proveedor->razon_social ?? '') }}" required
           placeholder="Nombre legal de la empresa (Ejemplo: Logística Sur S.A.)">

    @error('razon_social')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="rut">RUT Empresa *</label>
    <input type="text" name="rut" id="rut" class="form-control"
           value="{{ old('rut', $proveedor->rut ?? '') }}" required
           placeholder="Puede repetirse si el proveedor ofrece distintos servicios">
    @error('rut')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="telefono_empresa">Teléfono Empresa *</label>
    <input type="text" name="telefono_empresa" id="telefono_empresa" class="form-control"
           value="{{ old('telefono_empresa', $proveedor->telefono_empresa ?? '') }}" required
           placeholder="Número de contacto principal (Ejemplo: +56 9 1234 5678)">


           
    @error('telefono_empresa')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="giro_comercial">Giro Comercial *</label>
    <input type="text" name="giro_comercial" id="giro_comercial" class="form-control"
           value="{{ old('giro_comercial', $proveedor->giro_comercial ?? '') }}" required
           placeholder="Actividad económica principal (Ejemplo: Transporte de Mercancías)">

    @error('giro_comercial')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror

</div>

<div class="form-group">
    <label for="direccion_facturacion">Dirección Facturación</label>
    <input type="text" name="direccion_facturacion" id="direccion_facturacion" class="form-control"
           value="{{ old('direccion_facturacion', $proveedor->direccion_facturacion ?? '') }}"
           placeholder="Lugar donde recibirás facturas (Ejemplo: Av. Libertad 123)">

    @error('direccion_facturacion')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

{{--  --}}

<h3 class="mt-4">Direcciones</h3>
<hr>


<div class="form-group">
    <label for="direccion_despacho">Dirección Despacho *</label>
    <input type="text" name="direccion_despacho" id="direccion_despacho" class="form-control"
           value="{{ old('direccion_despacho', $proveedor->direccion_despacho ?? '') }}"
           placeholder="Lugar donde se entregan productos (Ejemplo: Av. Los Leones 456)">
    @error('direccion_despacho')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror       
</div>

<div class="form-group">
    <label for="comuna_id">Comuna *</label>
    <select name="comuna_id" id="comuna_id" class="form-control">
        <option value="">Seleccione una comuna</option>
        @foreach ($comunas as $comuna)
            <option value="{{ $comuna->id }}" 
                {{ old('comuna_id', $proveedor->comuna_id ?? '') == $comuna->id ? 'selected' : '' }}>
                {{ $comuna->Nombre }}
            </option>
        @endforeach
    </select>
    @error('comuna_id')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>




{{--  --}}

<h3 class="mt-4">Representante Legal (Opcional)</h3>
<hr>
<p class="text-muted">Información del representante legal de la empresa. Este es el responsable en temas legales y administrativos importantes.</p>
<div class="form-group">
    <label for="Nombre_RepresentanteLegal">Nombre</label>
    <input type="text" name="Nombre_RepresentanteLegal" id="Nombre_RepresentanteLegal" class="form-control"
           value="{{ old('Nombre_RepresentanteLegal', $proveedor->Nombre_RepresentanteLegal ?? '') }}"
           placeholder="Nombre completo del representante legal">
    @error('Nombre_RepresentanteLegal')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="Rut_RepresentanteLegal">RUT</label>
    <input type="text" name="Rut_RepresentanteLegal" id="Rut_RepresentanteLegal" class="form-control"
           value="{{ old('Rut_RepresentanteLegal', $proveedor->Rut_RepresentanteLegal ?? '') }}"
           placeholder="Puede coincidir con otros proveedores si el representante es el mismo">
    @error('Rut_RepresentanteLegal')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="Telefono_RepresentanteLegal">Teléfono</label>
    <input type="text" name="Telefono_RepresentanteLegal" id="Telefono_RepresentanteLegal" class="form-control"
           value="{{ old('Telefono_RepresentanteLegal', $proveedor->Telefono_RepresentanteLegal ?? '') }}"
           placeholder="Número de contacto del representante legal">
    @error('Telefono_RepresentanteLegal')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="Correo_RepresentanteLegal">Correo Electrónico</label>
    <input type="email" name="Correo_RepresentanteLegal" id="Correo_RepresentanteLegal" class="form-control"
           value="{{ old('Correo_RepresentanteLegal', $proveedor->Correo_RepresentanteLegal ?? '') }}"
           placeholder="Correo electrónico del representante legal">
    @error('Correo_RepresentanteLegal')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

{{--  --}}

<h3 class="mt-4">Contactos Adicionales</h3>
<hr>
<p class="text-muted">Contactos operativos o administrativos dentro de la empresa para coordinación de tareas.</p>

<h4>Contacto 1</h4>
<div class="form-group">
    <label for="contacto_nombre">Nombre</label>
    <input type="text" name="contacto_nombre" id="contacto_nombre" class="form-control"
           value="{{ old('contacto_nombre', $proveedor->contacto_nombre ?? '') }}"
           placeholder="Nombre del contacto">
    @error('contacto_nombre')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="contacto_telefono">Teléfono</label>
    <input type="text" name="contacto_telefono" id="contacto_telefono" class="form-control"
           value="{{ old('contacto_telefono', $proveedor->contacto_telefono ?? '') }}"
           placeholder="Teléfono del contacto">
    @error('contacto_telefono')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="contacto_correo">Correo Electrónico</label>
    <input type="email" name="contacto_correo" id="contacto_correo" class="form-control"
           value="{{ old('contacto_correo', $proveedor->contacto_correo ?? '') }}"
           placeholder="Correo del contacto">
    @error('contacto_correo')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="cargo_contacto1">Cargo</label>
    <input type="text" name="cargo_contacto1" id="cargo_contacto1" class="form-control"
           value="{{ old('cargo_contacto1', $proveedor->cargo_contacto1 ?? '') }}"
           placeholder="Cargo del contacto (Ejemplo: Gerente de Compras)">
    @error('cargo_contacto1')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<h4>Contacto 2</h4>

<div class="form-group">
    <label for="nombre_contacto2">Nombre</label>
    <input type="text" name="nombre_contacto2" id="nombre_contacto2" class="form-control"
           value="{{ old('nombre_contacto2', $proveedor->nombre_contacto2 ?? '') }}"
           placeholder="Nombre del segundo contacto">
    @error('nombre_contacto2')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="telefono_contacto2">Teléfono</label>
    <input type="text" name="telefono_contacto2" id="telefono_contacto2" class="form-control"
           value="{{ old('telefono_contacto2', $proveedor->telefono_contacto2 ?? '') }}"
           placeholder="Teléfono del segundo contacto">
    @error('telefono_contacto2')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="correo_contacto2">Correo Electrónico</label>
    <input type="email" name="correo_contacto2" id="correo_contacto2" class="form-control"
           value="{{ old('correo_contacto2', $proveedor->correo_contacto2 ?? '') }}"
           placeholder="Correo del segundo contacto">
    @error('correo_contacto2')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="cargo_contacto2">Cargo</label>
    <input type="text" name="cargo_contacto2" id="cargo_contacto2" class="form-control"
           value="{{ old('cargo_contacto2', $proveedor->cargo_contacto2 ?? '') }}"
           placeholder="Cargo del segundo contacto (Ejemplo: Asistente Administrativo)">
    @error('cargo_contacto2')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>



<h3 class="mt-4">Datos Bancarios</h3>
<hr>
<p class="text-muted">Información necesaria para registrar los pagos y transferencias.</p>

<div class="form-group">
    <label for="banco_id">Banco *</label>
    <select name="banco_id" id="banco_id" class="form-control">
        <option value="">Seleccione un banco</option>
        @foreach($bancos as $banco)
            <option value="{{ $banco->id }}" {{ old('banco_id', $proveedor->banco_id ?? '') == $banco->id ? 'selected' : '' }}>
                {{ $banco->nombre }}
            </option>
        @endforeach
    </select>
    @error('banco_id')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="nro_cuenta">Número de Cuenta Bancaria *</label>
    <input type="text" name="nro_cuenta" id="nro_cuenta" class="form-control"
           value="{{ old('nro_cuenta', $proveedor->nro_cuenta ?? '') }}" required
           placeholder="Número de cuenta bancaria del proveedor">
    @error('nro_cuenta')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="tipo_cuenta_id">Tipo de Cuenta *</label>
    <select name="tipo_cuenta_id" id="tipo_cuenta_id" class="form-control">
        <option value="">Seleccione un tipo de cuenta</option>
        @foreach($tiposCuentas as $tipoCuenta)
            <option value="{{ $tipoCuenta->id }}" 
                {{ old('tipo_cuenta_id', $proveedor->tipo_cuenta_id ?? '') == $tipoCuenta->id ? 'selected' : '' }}>
                {{ $tipoCuenta->nombre }}
            </option>
        @endforeach
    </select>
    @error('tipo_cuenta_id')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="correo_banco">Correo Bancario</label>
    <input type="email" name="correo_banco" id="correo_banco" class="form-control"
           value="{{ old('correo_banco', $proveedor->correo_banco ?? '') }}"
           placeholder="Correo para notificaciones bancarias">
    @error('correo_banco')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="tipo_pago_id">Método de Pago *</label>
    <select name="tipo_pago_id" id="tipo_pago_id" class="form-control">
        <option value="">Seleccione un método de pago</option>
        @foreach($tiposPagos as $tipoPago)
            <option value="{{ $tipoPago->id }}" {{ old('tipo_pago_id', $proveedor->tipo_pago_id ?? '') == $tipoPago->id ? 'selected' : '' }}>
                {{ $tipoPago->nombre }}
            </option>
        @endforeach
    </select>
    @error('tipo_pago_id')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="nombre_razon_social_banco">Razón Social Asociada a la Cuenta</label>
    <input type="text" name="nombre_razon_social_banco" id="nombre_razon_social_banco" class="form-control"
           value="{{ old('nombre_razon_social_banco', $proveedor->nombre_razon_social_banco ?? '') }}"
           placeholder="Razón social del titular de la cuenta bancaria">
    @error('nombre_razon_social_banco')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>




