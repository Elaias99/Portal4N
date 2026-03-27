@component('components.finanzas.modal_documentos_masivos', [
    'modalId' => 'modalPagoComprasProgramadas',
    'labelId' => 'modalPagoComprasProgramadasLabel',
    'title' => 'Pagar compras programadas',

    'showCount' => true,
    'countLabel' => 'Documentos seleccionados',
    'countId' => 'pago-compras-programadas-count',

    'sinSeleccionId' => 'pago-compras-programadas-sin-seleccion',
    'sinSeleccionTexto' => 'No hay documentos seleccionados.',

    'formId' => 'form-pago-compras-programadas',
    'action' => route('documentos.pagos.masivo.panel_hoy'),
    'method' => 'POST',

    'closeXId' => 'btn-cerrar-x-pago-compras-programadas',

    'cancelId' => 'btn-cancelar-pago-compras-programadas',
    'cancelText' => 'Cancelar',

    'submitId' => 'btn-submit-pago-compras-programadas',
    'submitText' => 'Registrar pagos',
    'submitClass' => 'btn btn-success',

    'tableBodyId' => 'pago-compras-programadas-seleccionados',
    'hiddenContainerId' => 'inputs-pago-compras-programadas-seleccionados',

    'showDateField' => true,
    'dateLabel' => 'Fecha de pago',
    'dateName' => 'fecha_pago',
    'dateId' => 'fecha-pago-compras-programadas',
    'dateRequired' => true,

    'showTotals' => true,
    'totalGeneralLabel' => 'Total a pagar',
    'totalGeneralId' => 'pago-compras-programadas-total-general',
    'totalesEmpresaId' => 'pago-compras-programadas-totales-empresa',

    'maxWidth' => '92vw',
])
    @slot('tableHead')
        <tr>
            <th class="hm-nowrap text-start">Empresa</th>
            <th class="hm-nowrap text-start">Folio</th>
            <th class="hm-nowrap text-start">Proveedor</th>
            <th class="hm-nowrap text-start">RUT</th>
            <th class="hm-nowrap text-start">Fecha programada</th>
            <th class="hm-nowrap text-end">Saldo</th>
            <th class="hm-nowrap text-center">Quitar</th>
        </tr>
    @endslot

    @slot('hiddenInputs')
        <div id="inputs-pago-compras-programadas-seleccionados"></div>
    @endslot

    @slot('fields')
        <div class="alert alert-light border mb-0">
            Los documentos seleccionados se registrarán como <strong>Pago</strong> y se eliminará su programación activa.
        </div>
    @endslot
@endcomponent