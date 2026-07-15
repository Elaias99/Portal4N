<div class="modal fade"
     id="modalEstadoCompra-{{ $doc->id }}"
     tabindex="-1"
     role="dialog"
     aria-labelledby="modalEstadoLabel-{{ $doc->id }}"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            {{-- ========================================================= --}}
            {{-- HEADER --}}
            {{-- ========================================================= --}}
            <div class="modal-header position-relative">
                <h5 class="modal-title fw-bold"
                    id="modalEstadoLabel-{{ $doc->id }}">

                    Actualizar estado —
                    {{ $doc->razon_social }} —
                    Folio {{ $doc->folio }}
                </h5>

                <button type="button"
                        class="btn btn-light btn-sm rounded-circle shadow-sm"
                        data-dismiss="modal"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"
                        style="
                            position: absolute;
                            top: 16px;
                            right: 16px;
                            width: 32px;
                            height: 32px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">

                    <span aria-hidden="true"
                          class="text-dark"
                          style="font-size: 1.2rem;">
                        &times;
                    </span>
                </button>
            </div>

            {{-- ========================================================= --}}
            {{-- BODY --}}
            {{-- ========================================================= --}}
            <div class="modal-body">

                {{-- ===================================================== --}}
                {{-- FORMULARIO PRINCIPAL DE ESTADO --}}
                {{-- ===================================================== --}}
                <form action="{{ route('finanzas_compras.updateEstado', $doc->id) }}"
                      method="POST"
                      id="form-estado-{{ $doc->id }}">

                    @csrf
                    @method('PATCH')

                    {{-- Estado actual --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Estado actual
                        </label>

                        <input type="text"
                               class="form-control form-control-sm"
                               value="{{ $doc->estado_visible }}"
                               readonly>
                    </div>

                    {{-- Nuevo estado --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Nuevo estado
                        </label>

                        <select name="estado"
                                id="estado-{{ $doc->id }}"
                                class="form-select form-select-sm"
                                onchange="toggleEstadoForm({{ $doc->id }})">

                            <option value="">
                                Sin estado manual
                            </option>

                            <option value="Abono"
                                {{ $doc->estado === 'Abono' ? 'selected' : '' }}>
                                Abono
                            </option>

                            <option value="Cruce"
                                {{ $doc->estado === 'Cruce' ? 'selected' : '' }}>
                                Cruce
                            </option>

                            <option value="Pago"
                                {{ $doc->estado === 'Pago' ? 'selected' : '' }}>
                                Pagado
                            </option>

                            <option value="Pronto pago"
                                {{ $doc->estado === 'Pronto pago' ? 'selected' : '' }}>
                                Pronto pago
                            </option>

                            <option value="Cobranza judicial"
                                {{ $doc->estado === 'Cobranza judicial' ? 'selected' : '' }}>
                                Cobranza judicial
                            </option>
                        </select>
                    </div>
                </form>

                {{-- ===================================================== --}}
                {{-- FORMULARIO DE ABONO --}}
                {{-- ===================================================== --}}
                <form action="{{ route('finanzas_compras.abonos.store', $doc->id) }}"
                      method="POST"
                      id="form-abono-{{ $doc->id }}"
                      style="display: {{ $doc->estado === 'Abono' ? 'block' : 'none' }};">

                    @csrf

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Saldo pendiente
                        </label>

                        <input type="text"
                               class="form-control form-control-sm"
                               value="${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}"
                               readonly>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Monto del abono
                        </label>

                        <input type="number"
                               name="monto"
                               class="form-control form-control-sm"
                               min="1"
                               max="{{ (int) $doc->saldo_pendiente }}"
                               required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Fecha del abono
                        </label>

                        <input type="date"
                               name="fecha_abono"
                               class="form-control form-control-sm"
                               value="{{ now()->format('Y-m-d') }}"
                               required>
                    </div>
                </form>

                {{-- ===================================================== --}}
                {{-- FORMULARIO DE CRUCE --}}
                {{-- ===================================================== --}}
                <form action="{{ route('finanzas_compras.cruces.store', $doc->id) }}"
                      method="POST"
                      id="form-cruce-{{ $doc->id }}"
                      data-saldo-compra="{{ (int) $doc->saldo_pendiente }}"
                      style="display: {{ $doc->estado === 'Cruce' ? 'block' : 'none' }};">

                    @csrf

                    {{-- Saldo pendiente de CxP --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Saldo pendiente del documento CxP
                        </label>

                        <input type="text"
                               class="form-control form-control-sm"
                               value="${{ number_format($doc->saldo_pendiente, 0, ',', '.') }}"
                               readonly>
                    </div>

                    {{-- Monto del cruce --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Monto del cruce
                        </label>

                        <input type="number"
                               name="monto"
                               id="monto-cruce-{{ $doc->id }}"
                               class="form-control form-control-sm @error('monto') is-invalid @enderror"
                               value="{{ old('monto') }}"
                               min="1"
                               max="{{ (int) $doc->saldo_pendiente }}"
                               required>

                        <small class="text-muted"
                               id="ayuda-monto-cruce-{{ $doc->id }}">

                            Al seleccionar documentos de CxC, el sistema calculará
                            automáticamente el monto que se puede cruzar.
                        </small>

                        @error('monto')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror

                        @error('cruce')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Fecha del cruce --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Fecha del cruce
                        </label>

                        <input type="date"
                               name="fecha_cruce"
                               class="form-control form-control-sm @error('fecha_cruce') is-invalid @enderror"
                               value="{{ old('fecha_cruce', now()->format('Y-m-d')) }}"
                               required>

                        @error('fecha_cruce')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Proveedor asociado --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Proveedor asociado
                        </label>

                        <input type="text"
                               class="form-control form-control-sm"
                               value="{{ $doc->cobranzaCompra?->razon_social ?? $doc->razon_social }} — RUT: {{ $doc->cobranzaCompra?->rut_cliente ?? $doc->rut_proveedor }}"
                               readonly>

                        <small class="text-muted">
                            El cruce se asociará automáticamente al proveedor del documento.
                        </small>

                        @error('cobranza_compra_id')
                            <span class="invalid-feedback d-block text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Cliente asociado en CxC --}}
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Cliente asociado en Cuentas por Cobrar
                        </label>

                        @if($doc->cobranzaClienteAsociada)
                            <input type="text"
                                   class="form-control form-control-sm"
                                   value="{{ $doc->cobranzaClienteAsociada->razon_social }} — RUT: {{ $doc->cobranzaClienteAsociada->rut_cliente }}"
                                   readonly>

                            <small class="text-success">
                                Este proveedor también existe como cliente en
                                Cuentas por Cobrar.
                            </small>
                        @else
                            <input type="text"
                                   class="form-control form-control-sm text-muted"
                                   value="No existe cliente asociado en Cuentas por Cobrar para este RUT"
                                   readonly>

                            <small class="text-warning">
                                No se encontró una cobranza cliente con el mismo
                                RUT del proveedor.
                            </small>
                        @endif
                    </div>

                    {{-- Documentos asociados en CxC --}}
                    @if($doc->cobranzaClienteAsociada)

                        @php
                            $documentosFinancierosAsociados = $doc
                                ->documentosFinancierosAsociados
                                ->where('saldo_pendiente', '>', 0)
                                ->whereNotIn('tipo_documento_id', [61, 56])
                                ->sortByDesc('fecha_vencimiento');

                            $documentosSeleccionadosAnteriores = collect(
                                old('documentos_financieros_cruce', [])
                            )->map(function ($id) {
                                return (int) $id;
                            });
                        @endphp

                        <div class="form-group mb-3">
                            <label class="form-label small text-muted">
                                Documentos asociados en CxC
                            </label>

                            @if($documentosFinancierosAsociados->isNotEmpty())

                                <div class="border rounded p-2 bg-light"
                                     style="max-height: 220px; overflow-y: auto;">

                                    @foreach($documentosFinancierosAsociados as $docFinanciero)

                                        <div class="small py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                            <div class="form-check">

                                                <input type="checkbox"
                                                       class="form-check-input js-doc-cruce-check"
                                                       id="doc-financiero-cruce-{{ $doc->id }}-{{ $docFinanciero->id }}"
                                                       name="documentos_financieros_cruce[]"
                                                       value="{{ $docFinanciero->id }}"
                                                       data-form-id="{{ $doc->id }}"
                                                       data-saldo="{{ (int) $docFinanciero->saldo_pendiente }}"
                                                       @checked(
                                                           $documentosSeleccionadosAnteriores->contains(
                                                               (int) $docFinanciero->id
                                                           )
                                                       )>

                                                <label class="form-check-label w-100"
                                                       for="doc-financiero-cruce-{{ $doc->id }}-{{ $docFinanciero->id }}">

                                                    <div class="fw-semibold">
                                                        Folio {{ $docFinanciero->folio }}
                                                        —
                                                        {{ $docFinanciero->tipoDocumento?->nombre ?? 'Documento financiero' }}
                                                    </div>

                                                    <div class="text-muted">
                                                        Empresa:
                                                        {{ $docFinanciero->empresa?->Nombre ?? 'Sin empresa' }}
                                                    </div>

                                                    <div>
                                                        Saldo pendiente:

                                                        <span class="fw-semibold text-danger">
                                                            ${{ number_format($docFinanciero->saldo_pendiente, 0, ',', '.') }}
                                                        </span>
                                                    </div>

                                                    <div class="text-muted">
                                                        Fecha documento:

                                                        {{ $docFinanciero->fecha_docto
                                                            ? \Carbon\Carbon::parse($docFinanciero->fecha_docto)->format('d-m-Y')
                                                            : '-'
                                                        }}

                                                        —

                                                        Vencimiento:

                                                        {{ $docFinanciero->fecha_vencimiento
                                                            ? \Carbon\Carbon::parse($docFinanciero->fecha_vencimiento)->format('d-m-Y')
                                                            : '-'
                                                        }}
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Resumen automático --}}
                                <div class="mt-2 p-2 border rounded bg-white">

                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="small text-muted">
                                            Saldo disponible seleccionado en CxC:
                                        </span>

                                        <span class="fw-semibold js-total-cxc-seleccionado"
                                              data-form-id="{{ $doc->id }}">
                                            $0
                                        </span>
                                    </div>

                                    <div class="d-flex justify-content-between gap-2 mt-1">
                                        <span class="small text-muted">
                                            Monto que se cruzará:
                                        </span>

                                        <span class="fw-bold text-primary js-total-cruce-seleccionado"
                                              data-form-id="{{ $doc->id }}">
                                            $0
                                        </span>
                                    </div>

                                    <div class="small text-muted mt-2"
                                         id="resultado-cruce-{{ $doc->id }}">

                                        Selecciona al menos un documento de CxC
                                        para calcular el cruce.
                                    </div>
                                </div>

                                <small class="text-muted d-block mt-2">
                                    El sistema utilizará solamente el monto necesario
                                    para cubrir el saldo pendiente de CxP. Si el saldo
                                    disponible en CxC no alcanza, se registrará un cruce
                                    parcial.
                                </small>

                            @else
                                <input type="text"
                                       class="form-control form-control-sm text-muted"
                                       value="No hay documentos pendientes en CxC para este cliente"
                                       readonly>

                                <small class="text-warning">
                                    El cliente existe, pero no tiene documentos pendientes
                                    disponibles para cruce.
                                </small>
                            @endif

                            @error('documentos_financieros_cruce')
                                <span class="invalid-feedback d-block text-danger">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    @endif
                </form>

                {{-- ===================================================== --}}
                {{-- FORMULARIO DE PAGO --}}
                {{-- ===================================================== --}}
                <form action="{{ route('documentos.pagos.store', $doc->id) }}"
                      method="POST"
                      id="form-pago-{{ $doc->id }}"
                      style="display: {{ $doc->estado === 'Pago' ? 'block' : 'none' }};">

                    @csrf

                    <input type="hidden"
                           name="tipo"
                           value="compra">

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Fecha del pago
                        </label>

                        <input type="date"
                               name="fecha_pago"
                               class="form-control form-control-sm"
                               value="{{ now()->format('Y-m-d') }}"
                               required>
                    </div>

                    <div class="alert alert-info py-1 px-2 small">
                        Al registrar un pago, el saldo pendiente quedará
                        automáticamente en <strong>0</strong>.
                    </div>
                </form>

                {{-- ===================================================== --}}
                {{-- FORMULARIO DE PRONTO PAGO --}}
                {{-- ===================================================== --}}
                <form action="{{ route('prontopagos.store', $doc->id) }}"
                      method="POST"
                      id="form-prontopago-{{ $doc->id }}"
                      style="display: {{ $doc->estado === 'Pronto pago' ? 'block' : 'none' }};">

                    @csrf

                    <input type="hidden"
                           name="tipo"
                           value="compra">

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted">
                            Fecha del pronto pago
                        </label>

                        <input type="date"
                               name="fecha_pronto_pago"
                               class="form-control form-control-sm"
                               value="{{ now()->format('Y-m-d') }}"
                               required>
                    </div>
                </form>

            </div>

            {{-- ========================================================= --}}
            {{-- FOOTER --}}
            {{-- ========================================================= --}}
            <div class="modal-footer">

                <button type="button"
                        class="btn btn-secondary btn-sm"
                        data-dismiss="modal"
                        data-bs-dismiss="modal">

                    Cancelar
                </button>

                <button type="button"
                        class="btn btn-primary btn-sm"
                        id="btn-guardar-estado-{{ $doc->id }}"
                        onclick="submitEstadoForm({{ $doc->id }})">

                    <i class="bi bi-save"></i>
                    Guardar cambios
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ============================================================= --}}
{{-- SCRIPT --}}
{{-- ============================================================= --}}
<script>
function formatearMontoCruce(valor) {
    return Number(valor || 0).toLocaleString('es-CL', {
        style: 'currency',
        currency: 'CLP',
        maximumFractionDigits: 0
    });
}

function recalcularCruceCompra(formId) {
    const form = document.getElementById('form-cruce-' + formId);

    if (!form) {
        return;
    }

    const saldoCompra = Number(form.dataset.saldoCompra || 0);

    const checks = document.querySelectorAll(
        '.js-doc-cruce-check[data-form-id="' + formId + '"]'
    );

    let totalDisponibleCxC = 0;
    let cantidadSeleccionados = 0;

    checks.forEach(function (item) {
        if (!item.checked) {
            return;
        }

        totalDisponibleCxC += Number(item.dataset.saldo || 0);
        cantidadSeleccionados++;
    });

    /*
     * El cruce no puede superar:
     *
     * 1. El saldo pendiente del documento CxP.
     * 2. La suma disponible de los documentos CxC seleccionados.
     */
    const montoCruce = Math.min(
        saldoCompra,
        totalDisponibleCxC
    );

    const saldoRestanteCompra = Math.max(
        saldoCompra - montoCruce,
        0
    );

    const totalDisponibleElement = document.querySelector(
        '.js-total-cxc-seleccionado[data-form-id="' + formId + '"]'
    );

    const montoCruceElement = document.querySelector(
        '.js-total-cruce-seleccionado[data-form-id="' + formId + '"]'
    );

    const montoCruceInput = document.getElementById(
        'monto-cruce-' + formId
    );

    const ayudaMonto = document.getElementById(
        'ayuda-monto-cruce-' + formId
    );

    const resultadoCruce = document.getElementById(
        'resultado-cruce-' + formId
    );

    if (totalDisponibleElement) {
        totalDisponibleElement.textContent =
            formatearMontoCruce(totalDisponibleCxC);
    }

    if (montoCruceElement) {
        montoCruceElement.textContent =
            formatearMontoCruce(montoCruce);
    }

    /*
     * Con documentos CxC seleccionados, el monto es automático.
     *
     * Sin documentos seleccionados, se conserva la posibilidad
     * de registrar un cruce manual.
     */
    if (montoCruceInput) {
        if (cantidadSeleccionados > 0) {
            montoCruceInput.value = montoCruce;
            montoCruceInput.readOnly = true;
        } else {
            montoCruceInput.value = '';
            montoCruceInput.readOnly = false;
        }
    }

    if (ayudaMonto) {
        ayudaMonto.textContent = cantidadSeleccionados > 0
            ? 'Monto calculado automáticamente según los saldos disponibles de CxP y CxC.'
            : 'Sin documentos CxC seleccionados, puedes ingresar un cruce manual.';
    }

    if (!resultadoCruce) {
        return;
    }

    resultadoCruce.classList.remove(
        'text-success',
        'text-warning',
        'text-danger',
        'text-muted'
    );

    if (cantidadSeleccionados === 0) {
        resultadoCruce.classList.add('text-muted');

        resultadoCruce.textContent =
            'Selecciona al menos un documento de CxC para calcular el cruce.';

        return;
    }

    if (montoCruce <= 0) {
        resultadoCruce.classList.add('text-warning');

        resultadoCruce.textContent =
            'Los documentos seleccionados no tienen saldo disponible para cruzar.';

        return;
    }

    if (saldoRestanteCompra === 0) {
        resultadoCruce.classList.add('text-success');

        resultadoCruce.innerHTML =
            '<strong>Cruce completo:</strong> ' +
            'el documento de CxP quedará con saldo $0.';

        return;
    }

    resultadoCruce.classList.add('text-warning');

    resultadoCruce.innerHTML =
        '<strong>Cruce parcial:</strong> ' +
        'el documento de CxP conservará un saldo de ' +
        formatearMontoCruce(saldoRestanteCompra) +
        '.';
}

function toggleEstadoForm(id) {
    const estadoSelect = document.getElementById(
        'estado-' + id
    );

    if (!estadoSelect) {
        return;
    }

    const estado = estadoSelect.value;

    const forms = [
        'abono',
        'cruce',
        'pago',
        'prontopago'
    ];

    forms.forEach(function (nombreForm) {
        const form = document.getElementById(
            'form-' + nombreForm + '-' + id
        );

        if (form) {
            form.style.display = 'none';
        }
    });

    if (estado === 'Abono') {
        document.getElementById(
            'form-abono-' + id
        )?.style.setProperty('display', 'block');

        return;
    }

    if (estado === 'Cruce') {
        document.getElementById(
            'form-cruce-' + id
        )?.style.setProperty('display', 'block');

        recalcularCruceCompra(id);

        return;
    }

    if (estado === 'Pago') {
        document.getElementById(
            'form-pago-' + id
        )?.style.setProperty('display', 'block');

        return;
    }

    if (estado === 'Pronto pago') {
        document.getElementById(
            'form-prontopago-' + id
        )?.style.setProperty('display', 'block');
    }
}

function getEstadoForm(id) {
    const estado = document.getElementById(
        'estado-' + id
    )?.value;

    if (estado === 'Abono') {
        return document.getElementById(
            'form-abono-' + id
        );
    }

    if (estado === 'Cruce') {
        return document.getElementById(
            'form-cruce-' + id
        );
    }

    if (estado === 'Pago') {
        return document.getElementById(
            'form-pago-' + id
        );
    }

    if (estado === 'Pronto pago') {
        return document.getElementById(
            'form-prontopago-' + id
        );
    }

    return document.getElementById(
        'form-estado-' + id
    );
}

function submitEstadoForm(id) {
    const form = getEstadoForm(id);

    const btnGuardar = document.getElementById(
        'btn-guardar-estado-' + id
    );

    if (!form) {
        return;
    }

    /*
     * form.submit() no ejecuta automáticamente la validación HTML5.
     * Por eso se valida antes de mostrar el cargador.
     */
    if (
        typeof form.reportValidity === 'function' &&
        !form.reportValidity()
    ) {
        return;
    }

    if (btnGuardar) {
        btnGuardar.disabled = true;

        btnGuardar.innerHTML =
            '<i class="bi bi-hourglass-split"></i> Guardando...';
    }

    window.pageLoader?.show({
        timeout: 30000
    });

    try {
        form.submit();
    } catch (error) {
        window.pageLoader?.forceHide?.();

        if (btnGuardar) {
            btnGuardar.disabled = false;

            btnGuardar.innerHTML =
                '<i class="bi bi-save"></i> Guardar cambios';
        }

        console.error(
            'Error al enviar formulario de estado:',
            error
        );
    }
}

document.addEventListener('change', function (event) {
    const checkbox = event.target;

    if (!checkbox.classList.contains('js-doc-cruce-check')) {
        return;
    }

    recalcularCruceCompra(
        checkbox.dataset.formId
    );
});

document.addEventListener('DOMContentLoaded', function () {
    recalcularCruceCompra({{ $doc->id }});
});
</script>