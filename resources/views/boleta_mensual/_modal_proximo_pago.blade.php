@component('components.finanzas.modal_documentos_masivos', [
    'modalId' => 'modalProximoPago',
    'labelId' => 'modalProximoPagoLabel',
    'title' => 'Definir próximo pago',

    'showCount' => false,

    'sinSeleccionId' => 'honorarios-proximo-pago-sin-seleccion',
    'sinSeleccionTexto' => 'No hay honorarios seleccionados.',

    'formId' => 'form-proximo-pago',
    'action' => route('honorarios.mensual.proximo-pago.exportar'),
    'method' => 'POST',

    'cancelId' => 'btn-cancelar-proximo-pago',
    'cancelText' => 'Cancelar',

    'submitId' => 'btn-submit-proximo-pago',
    'submitText' => 'Guardar próximo pago',
    'submitClass' => 'btn btn-primary',

    'tableBodyId' => 'proximos-pagos-seleccionados',
    'hiddenContainerId' => 'inputs-proximos-pagos-seleccionados',

    'showDateField' => true,
    'dateLabel' => 'Fecha programada',
    'dateName' => 'fecha_programada',
    'dateId' => 'fecha-programada-honorarios',
    'dateRequired' => true,

    'showTotals' => true,
    'totalGeneralLabel' => 'Total próximo pago',
    'totalGeneralId' => 'honorarios-proximo-pago-total-general',
    'totalesEmpresaId' => 'honorarios-proximo-pago-totales-empresa',

    'maxWidth' => '92vw',
])
    @slot('tableHead')
        <tr>
            <th>Folio</th>
            <th>Emisor</th>
            <th>RUT</th>
            <th class="text-end">Saldo</th>
            <th class="text-center">Quitar</th>
        </tr>
    @endslot

    @slot('fields')
        <div class="mb-3">
            <label class="form-label">Observación</label>
            <textarea name="observacion"
                      class="form-control"
                      rows="3"
                      placeholder="Opcional"></textarea>
        </div>
    @endslot
@endcomponent