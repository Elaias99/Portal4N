{{-- 
    resources/views/suscripciones/comisiones_mensuales/partials/modal-comisiones-masivas.blade.php

    Modal para registrar pagos adicionales masivos.

    No guarda directamente en BD.
    El JS construye un objeto independiente por cada pago preparado
    y lo agrega al flujo central de comisiones[].

    Reglas:
    - Se define un monto común para los pagos preparados en esta carga.
    - Cada clic en "Agregar pago" crea un registro independiente.
    - Un mismo proveedor puede recibir más de un pago adicional.
    - El transportista puede revisarse o corregirse por cada pago.
    - El código interno es COMISION.
--}}

<div
    class="modal fade"
    id="modal-comisiones-masivas"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-comisiones-masivas-title"
    aria-hidden="true"
>
    <div
        class="modal-dialog modal-xl modal-dialog-scrollable"
        role="document"
    >
        <div class="modal-content">

            {{-- ENCABEZADO --}}
            <div class="modal-header bg-light">
                <div>
                    <h5
                        class="modal-title mb-1"
                        id="modal-comisiones-masivas-title"
                    >
                        Pagos adicionales masivos
                    </h5>

                    <div class="small text-muted">
                        Define un monto común y agrega los pagos adicionales
                        que correspondan para este periodo.
                    </div>
                </div>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="Cerrar"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="alert alert-info small mb-4">
                    <strong>Importante:</strong>
                    el monto ingresado se aplicará a cada pago preparado.
                    Puedes agregar al mismo proveedor más de una vez si debe recibir
                    más de un pago adicional durante el periodo.
                    Cada clic en <strong>Agregar pago</strong> creará un registro independiente
                    dentro de <code>comisiones[]</code>.
                </div>

                {{-- TEMPLATE DE TRANSPORTISTAS PARA EL JS --}}
                <select
                    id="comision-masiva-transportista-template"
                    class="d-none"
                    aria-hidden="true"
                >
                    <option value="">Seleccionar transportista...</option>

                    @foreach ($transportistas ?? [] as $transportista)
                        <option
                            value="{{ $transportista->id }}"
                            data-label="{{ $transportista->nombre_transportista }}"
                            data-nombre-normalizado="{{ mb_strtoupper(trim($transportista->nombre_transportista)) }}"
                        >
                            {{ $transportista->nombre_transportista }}
                        </option>
                    @endforeach
                </select>

                {{-- ===================================================== --}}
                {{-- SECCIÓN 1: DEFINIR DATOS COMUNES --}}
                {{-- ===================================================== --}}
                <div class="border rounded p-3 mb-4">
                    <div class="mb-3">
                        <h6 class="mb-1">
                            1. Definir pago adicional
                        </h6>

                        <div class="small text-muted">
                            El monto y la observación se aplicarán a cada pago que prepares
                            en las siguientes secciones.
                        </div>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label
                                for="comision-masiva-monto"
                                class="form-label"
                            >
                                Monto por pago adicional
                            </label>

                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>

                                <input
                                    type="number"
                                    id="comision-masiva-monto"
                                    class="form-control"
                                    min="1"
                                    step="1"
                                    placeholder="Ej: 5000"
                                    autocomplete="off"
                                >
                            </div>

                            <div class="form-text">
                                Este monto se repetirá en cada pago preparado.
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label
                                for="comision-masiva-observacion-general"
                                class="form-label"
                            >
                                Observación general opcional
                            </label>

                            <input
                                type="text"
                                id="comision-masiva-observacion-general"
                                class="form-control"
                                placeholder="Ej: Pago adicional correspondiente al periodo"
                                autocomplete="off"
                            >

                            <div class="form-text">
                                La misma observación se aplicará a todos los pagos de esta carga.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===================================================== --}}
                {{-- SECCIÓN 2: BUSCAR Y AGREGAR PAGOS --}}
                {{-- ===================================================== --}}
                <div class="border rounded p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">
                                2. Buscar proveedor y agregar pago
                            </h6>

                            <div class="small text-muted">
                                Busca por razón social, RUT o tipo de documento.
                                Presiona <strong>Agregar pago</strong> por cada registro que necesites crear.
                                Puedes presionar el botón varias veces para el mismo proveedor.
                            </div>
                        </div>

                        <div class="small text-muted">
                            Pagos preparados:
                            <strong id="comision-masiva-seleccionados-contador">
                                0
                            </strong>
                        </div>
                    </div>

                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-8">
                            <label
                                for="comision-masiva-buscador"
                                class="form-label"
                            >
                                Buscar proveedor
                            </label>

                            <input
                                type="text"
                                id="comision-masiva-buscador"
                                class="form-control"
                                placeholder="Ej: ROMOLO, LORENZA, 12328835-1, FACTURA..."
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-md-4 d-flex gap-2">
                            <button
                                type="button"
                                id="btn-comision-masiva-buscar"
                                class="btn btn-outline-primary flex-fill"
                            >
                                Buscar
                            </button>

                            <button
                                type="button"
                                id="btn-comision-masiva-limpiar-busqueda"
                                class="btn btn-outline-secondary flex-fill"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <div
                        class="table-responsive"
                        style="max-height: 280px; overflow-y: auto;"
                    >
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th
                                        style="width: 130px;"
                                        class="text-center"
                                    >
                                        Acción
                                    </th>

                                    <th>
                                        Proveedor / RUT
                                    </th>

                                    <th style="width: 150px;">
                                        Tipo documento
                                    </th>

                                    <th style="width: 230px;">
                                        Detalle
                                    </th>

                                    <th style="width: 150px;">
                                        Final
                                    </th>
                                </tr>
                            </thead>

                            <tbody id="comision-masiva-proveedores-body">
                                @forelse ($proveedores ?? [] as $proveedor)
                                    @php
                                        $cobranzaCompra = $proveedor->cobranzaCompra;

                                        $razonSocial = $cobranzaCompra?->razon_social
                                            ?? 'Proveedor sin razón social';

                                        $rutCliente = $cobranzaCompra?->rut_cliente
                                            ?? 'Sin RUT';

                                        $tipoDocumento = $proveedor->tipo
                                            ?? 'Sin tipo';

                                        $detalleDocumento = $proveedor->detalle_documento
                                            ?? '';

                                        $detalleImpuesto = $proveedor->detalle_impuesto
                                            ?? '';

                                        $final = $proveedor->final
                                            ?? '';

                                        $labelProveedor = trim(
                                            $razonSocial
                                            . ' | '
                                            . $rutCliente
                                            . ' | '
                                            . $tipoDocumento
                                        );

                                        $detalleLabel = trim(
                                            ($detalleDocumento ?: 'Sin detalle documento')
                                            . ' | '
                                            . ($detalleImpuesto ?: 'Sin detalle impuesto')
                                        );

                                        $textoBusqueda = mb_strtoupper(trim(
                                            $razonSocial
                                            . ' '
                                            . $rutCliente
                                            . ' '
                                            . $tipoDocumento
                                            . ' '
                                            . $detalleDocumento
                                            . ' '
                                            . $detalleImpuesto
                                            . ' '
                                            . $final
                                        ));

                                        $razonSocialNormalizada = mb_strtoupper(
                                            trim($razonSocial)
                                        );
                                    @endphp

                                    <tr
                                        data-comision-masiva-proveedor
                                        data-busqueda="{{ $textoBusqueda }}"
                                        data-proveedor-id="{{ $proveedor->id }}"
                                        data-label="{{ $labelProveedor }}"
                                        data-razon-social="{{ $razonSocial }}"
                                        data-razon-social-normalizada="{{ $razonSocialNormalizada }}"
                                        data-rut="{{ $rutCliente }}"
                                        data-tipo="{{ $proveedor->tipo }}"
                                        data-detalle-documento="{{ $proveedor->detalle_documento }}"
                                        data-detalle-impuesto="{{ $proveedor->detalle_impuesto }}"
                                        data-final="{{ $proveedor->final }}"
                                    >
                                        <td class="text-center">
                                            <button
                                                type="button"
                                                class="btn btn-outline-primary btn-sm"
                                                data-comision-masiva-agregar-pago
                                            >
                                                Agregar pago
                                            </button>
                                        </td>

                                        <td>
                                            <div class="fw-semibold">
                                                {{ $razonSocial }}
                                            </div>

                                            <div class="small text-muted">
                                                {{ $rutCliente }}
                                            </div>
                                        </td>

                                        <td>
                                            {{ $tipoDocumento }}
                                        </td>

                                        <td>
                                            {{ $detalleLabel }}
                                        </td>

                                        <td>
                                            {{ $final ?: '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td
                                            colspan="5"
                                            class="text-muted text-center"
                                        >
                                            No hay proveedores disponibles para
                                            pagos adicionales.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ===================================================== --}}
                {{-- SECCIÓN 3: PREVISUALIZACIÓN --}}
                {{-- ===================================================== --}}
                <div class="border rounded p-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="mb-1">
                                3. Revisar pagos preparados
                            </h6>

                            <div class="small text-muted">
                                Cada fila representa un pago adicional independiente.
                                Un proveedor puede aparecer más de una vez.
                                Revisa el transportista relacionado antes de confirmar.
                            </div>
                        </div>

                        <button
                            type="button"
                            id="btn-comision-masiva-limpiar"
                            class="btn btn-outline-secondary btn-sm"
                        >
                            Limpiar pagos preparados
                        </button>
                    </div>

                    <div
                        class="table-responsive"
                        style="max-height: 320px; overflow-y: auto;"
                    >
                        <table class="table table-sm table-bordered align-middle mb-2">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 200px;">
                                        Proveedor
                                    </th>

                                    <th style="min-width: 220px;">
                                        Transportista
                                    </th>

                                    <th
                                        style="width: 150px;"
                                        class="text-end"
                                    >
                                        Monto
                                    </th>

                                    <th style="min-width: 200px;">
                                        Observación
                                    </th>

                                    <th
                                        style="width: 90px;"
                                        class="text-center"
                                    >
                                        Acción
                                    </th>
                                </tr>
                            </thead>

                            <tbody id="comision-masiva-seleccionados-body">
                                <tr data-comision-masiva-empty>
                                    <td
                                        colspan="5"
                                        class="text-muted text-center"
                                    >
                                        No hay pagos adicionales preparados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <div class="border rounded bg-light p-3 h-100">
                                <div class="small text-muted">
                                    Pagos preparados
                                </div>

                                <div
                                    class="fs-5 fw-semibold"
                                    id="comision-masiva-resumen-cantidad"
                                >
                                    0
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded bg-light p-3 h-100">
                                <div class="small text-muted">
                                    Monto por pago
                                </div>

                                <div
                                    class="fs-5 fw-semibold"
                                    id="comision-masiva-monto-preview"
                                >
                                    $0
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded bg-light p-3 h-100">
                                <div class="small text-muted">
                                    Total pagos adicionales
                                </div>

                                <div
                                    class="fs-5 fw-semibold"
                                    id="comision-masiva-total-preview"
                                >
                                    $0
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        id="comision-masiva-error"
                        class="alert alert-danger small d-none mt-3 mb-0"
                        role="alert"
                    ></div>
                </div>
            </div>

            {{-- PIE DEL MODAL --}}
            <div class="modal-footer bg-light">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    data-dismiss="modal"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    id="btn-confirmar-comisiones-masivas"
                    class="btn btn-primary"
                >
                    Agregar pagos adicionales al resumen
                </button>
            </div>
        </div>
    </div>
</div>