<h3 class="mt-4">Datos Básicos</h3>
<hr>
<div class="form-group">
    <label for="razon_social">Razón Social *</label>
    <input type="text" name="razon_social" id="razon_social" class="form-control"
           value="{{ old('razon_social', $proveedor->razon_social ?? '') }}" required
           placeholder="Nombre legal de la empresa (Ejemplo: Logística Sur S.A.)">
</div>

<div class="form-group">
    <label for="rut">RUT Empresa *</label>
    <input type="text" name="rut" id="rut" class="form-control"
           value="{{ old('rut', $proveedor->rut ?? '') }}" required
           placeholder="Identificación tributaria de la empresa (Ejemplo: 76.543.210-K)">
</div>

<div class="form-group">
    <label for="telefono_empresa">Teléfono Empresa *</label>
    <input type="text" name="telefono_empresa" id="telefono_empresa" class="form-control"
           value="{{ old('telefono_empresa', $proveedor->telefono_empresa ?? '') }}" required
           placeholder="Número de contacto principal (Ejemplo: +56 9 1234 5678)">
</div>

<div class="form-group">
    <label for="giro_comercial">Giro Comercial *</label>
    <input type="text" name="giro_comercial" id="giro_comercial" class="form-control"
           value="{{ old('giro_comercial', $proveedor->giro_comercial ?? '') }}" required
           placeholder="Actividad económica principal (Ejemplo: Transporte de Mercancías)">
</div>

<div class="form-group">
    <label for="direccion_facturacion">Dirección Facturación</label>
    <input type="text" name="direccion_facturacion" id="direccion_facturacion" class="form-control"
           value="{{ old('direccion_facturacion', $proveedor->direccion_facturacion ?? '') }}"
           placeholder="Lugar donde recibirás facturas (Ejemplo: Av. Libertad 123)">
</div>

{{--  --}}

<h3 class="mt-4">Direcciones</h3>
<hr>


<div class="form-group">
    <label for="direccion_despacho">Dirección Despacho</label>
    <input type="text" name="direccion_despacho" id="direccion_despacho" class="form-control"
           value="{{ old('direccion_despacho', $proveedor->direccion_despacho ?? '') }}"
           placeholder="Lugar donde se entregan productos (Ejemplo: Av. Los Leones 456)">
</div>

<div class="form-group">
    <label for="comuna_empresa">Comuna Empresa</label>
    <input type="text" name="comuna_empresa" id="comuna_empresa" class="form-control"
           value="{{ old('comuna_empresa', $proveedor->comuna_empresa ?? '') }}"
           placeholder="Comuna donde está ubicada la empresa (Ejemplo: Providencia)">
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
</div>

<div class="form-group">
    <label for="Rut_RepresentanteLegal">RUT</label>
    <input type="text" name="Rut_RepresentanteLegal" id="Rut_RepresentanteLegal" class="form-control"
           value="{{ old('Rut_RepresentanteLegal', $proveedor->Rut_RepresentanteLegal ?? '') }}"
           placeholder="RUT del representante legal (Ejemplo: 12.345.678-9)">
</div>

<div class="form-group">
    <label for="Telefono_RepresentanteLegal">Teléfono</label>
    <input type="text" name="Telefono_RepresentanteLegal" id="Telefono_RepresentanteLegal" class="form-control"
           value="{{ old('Telefono_RepresentanteLegal', $proveedor->Telefono_RepresentanteLegal ?? '') }}"
           placeholder="Número de contacto del representante legal">
</div>

<div class="form-group">
    <label for="Correo_RepresentanteLegal">Correo Electrónico</label>
    <input type="email" name="Correo_RepresentanteLegal" id="Correo_RepresentanteLegal" class="form-control"
           value="{{ old('Correo_RepresentanteLegal', $proveedor->Correo_RepresentanteLegal ?? '') }}"
           placeholder="Correo electrónico del representante legal">
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
</div>
<div class="form-group">
    <label for="contacto_telefono">Teléfono</label>
    <input type="text" name="contacto_telefono" id="contacto_telefono" class="form-control"
           value="{{ old('contacto_telefono', $proveedor->contacto_telefono ?? '') }}"
           placeholder="Teléfono del contacto">
</div>
<div class="form-group">
    <label for="contacto_correo">Correo Electrónico</label>
    <input type="email" name="contacto_correo" id="contacto_correo" class="form-control"
           value="{{ old('contacto_correo', $proveedor->contacto_correo ?? '') }}"
           placeholder="Correo del contacto">
</div>
<div class="form-group">
    <label for="cargo_contacto1">Cargo</label>
    <input type="text" name="cargo_contacto1" id="cargo_contacto1" class="form-control"
           value="{{ old('cargo_contacto1', $proveedor->cargo_contacto1 ?? '') }}"
           placeholder="Cargo del contacto (Ejemplo: Gerente de Compras)">
</div>

<h4>Contacto 2</h4>

<div class="form-group">
    <label for="nombre_contacto2">Nombre</label>
    <input type="text" name="nombre_contacto2" id="nombre_contacto2" class="form-control"
           value="{{ old('nombre_contacto2', $proveedor->nombre_contacto2 ?? '') }}"
           placeholder="Nombre del segundo contacto">
</div>

<div class="form-group">
    <label for="telefono_contacto2">Teléfono</label>
    <input type="text" name="telefono_contacto2" id="telefono_contacto2" class="form-control"
           value="{{ old('telefono_contacto2', $proveedor->telefono_contacto2 ?? '') }}"
           placeholder="Teléfono del segundo contacto">
</div>

<div class="form-group">
    <label for="correo_contacto2">Correo Electrónico</label>
    <input type="email" name="correo_contacto2" id="correo_contacto2" class="form-control"
           value="{{ old('correo_contacto2', $proveedor->correo_contacto2 ?? '') }}"
           placeholder="Correo del segundo contacto">
</div>
<div class="form-group">
    <label for="cargo_contacto2">Cargo</label>
    <input type="text" name="cargo_contacto2" id="cargo_contacto2" class="form-control"
           value="{{ old('cargo_contacto2', $proveedor->cargo_contacto2 ?? '') }}"
           placeholder="Cargo del segundo contacto (Ejemplo: Asistente Administrativo)">
</div>


<h3 class="mt-4">Datos Bancarios</h3>
<hr>
<p class="text-muted">Información necesaria para registrar los pagos y transferencias.</p>

<div class="form-group">
    <label for="banco">Banco *</label>
    <select name="banco" id="banco" class="form-control">
        @foreach(['Banco Estado', 'Santander', 'Bci', 'Efectivo', 'Banco Chile', 'Banco Falabella', 'Banco BICE',
        'Banco Consorcio', 'Banco Scotiabank', 'Banco Security','Banco Corpbanca','Banco Ripley','Banco Itau','Banco Paris','Banco del Desarrollo',
        'Banco Copeuch','Banco BBVA','Web pago Online','Mercado Pago','Tenpo','Banco Edwars','Efectivo'] as $banco)
            <option value="{{ $banco }}" {{ old('banco', $proveedor->banco ?? '') == $banco ? 'selected' : '' }}>
                {{ $banco }}
            </option>
        @endforeach
    </select>
</div>





<div class="form-group">
    <label for="nro_cuenta">Número de Cuenta Bancaria *</label>
    <input type="text" name="nro_cuenta" id="nro_cuenta" class="form-control"
           value="{{ old('nro_cuenta', $proveedor->nro_cuenta ?? '') }}" required
           placeholder="Número de cuenta bancaria del proveedor">
</div>



<div class="form-group">
    <label for="tipo_cuenta">Tipo de Cuenta *</label>
    <select name="tipo_cuenta" id="tipo_cuenta" class="form-control">
        @foreach(['Cuenta Corriente', 'Cuenta Vista', 'Cuenta Rut' ,'Ahorro'] as $tipo_cuenta)
            <option value="{{ $tipo_cuenta }}" {{ old('tipo_cuenta', $proveedor->tipo_cuenta ?? '') == $tipo_cuenta ? 'selected' : '' }}>
                {{ $tipo_cuenta }}
            </option>
        @endforeach
    </select>
</div>



<div class="form-group">
    <label for="correo_banco">Correo Bancario</label>
    <input type="email" name="correo_banco" id="correo_banco" class="form-control"
           value="{{ old('correo_banco', $proveedor->correo_banco ?? '') }}"
           placeholder="Correo para notificaciones bancarias">
</div>


<div class="form-group">
    <label for="tipo_pago">Método de Pago *</label>
    <select name="tipo_pago" id="tipo_pago" class="form-control">
        @foreach(['Transferencia', 'Cheque', 'Efectivo'] as $metodo)
            <option value="{{ $metodo }}" {{ old('tipo_pago', $proveedor->tipo_pago ?? '') == $metodo ? 'selected' : '' }}>
                {{ $metodo }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="nombre_razon_social_banco">Razón Social Asociada a la Cuenta</label>
    <input type="text" name="nombre_razon_social_banco" id="nombre_razon_social_banco" class="form-control"
           value="{{ old('nombre_razon_social_banco', $proveedor->nombre_razon_social_banco ?? '') }}"
           placeholder="Razón social del titular de la cuenta bancaria">
</div>



