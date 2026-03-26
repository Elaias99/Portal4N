@component('components.finanzas.modal_documentos_masivos', [
    'modalId' => 'modalProximoPagoCompras',
    'labelId' => 'modalProximoPagoComprasLabel',
    'title' => 'Definir próximo pago',

    'showCount' => false,

    'sinSeleccionId' => 'proximos-pagos-sin-seleccion',
    'sinSeleccionTexto' => 'No hay documentos seleccionados.',

    'formId' => 'form-proximo-pago-compras',
    'action' => route('finanzas_compras.proximo_pago.exportar'),
    'method' => 'POST',

    'closeXId' => 'btn-cerrar-x-proximo-pago-compras',

    'cancelId' => 'btn-cancelar-proximo-pago-compras',
    'cancelText' => 'Cancelar',

    'submitId' => 'btn-submit-proximo-pago-compras',
    'submitText' => 'Guardar próximo pago',
    'submitClass' => 'btn btn-primary',

    'tableBodyId' => 'proximos-pagos-compras-seleccionados',
    'hiddenContainerId' => 'inputs-proximos-pagos-compras-seleccionados',

    'showDateField' => true,
    'dateLabel' => 'Fecha programada',
    'dateName' => 'fecha_programada',
    'dateId' => 'fecha-programada-proximo-pago-compras',
    'dateRequired' => true,

    'showTotals' => true,
    'totalGeneralLabel' => 'Total próximo pago',
    'totalGeneralId' => 'proximos-pagos-total-general',
    'totalesEmpresaId' => 'proximos-pagos-totales-empresa',

    'maxWidth' => '92vw',
])
    @slot('tableHead')
        <tr>
            <th class="hm-nowrap text-start">Folio</th>
            <th class="hm-nowrap text-start">Razón social</th>
            <th class="hm-nowrap text-start">RUT</th>
            <th class="hm-nowrap text-end">Saldo</th>
            <th class="hm-nowrap text-center">Quitar</th>
        </tr>
    @endslot

    @slot('hiddenInputs')
        <div id="inputs-proximos-pagos-compras-seleccionados"></div>
        <div id="inputs-programados-eliminar-compras"></div>
    @endslot

    @slot('fields')
        <div class="mb-0">
            <label class="form-label fw-semibold">Observación</label>
            <textarea
                name="observacion"
                class="form-control"
                rows="3"
                placeholder="Opcional"
            ></textarea>
        </div>
    @endslot

    @slot('footerButtonsBefore')
        <button
            type="button"
            id="btn-eliminar-proximo-pago-compras"
            class="btn btn-outline-danger d-none"
            data-url="{{ route('finanzas_compras.pago-programado.destroy.masivo') }}"
        >
            Eliminar próximos pagos
        </button>
    @endslot
@endcomponent