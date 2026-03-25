@component('components.finanzas.modal_documentos_masivos', [
    'modalId' => 'modalPagosMasivos',
    'labelId' => 'modalPagosMasivosLabel',
    'title' => 'Pagos Masivos — Resumen de documentos seleccionados',

    'countId' => 'pm-count',

    'sinSeleccionId' => 'pm-sin-seleccion',
    'sinSeleccionTexto' => 'No hay documentos seleccionados en la tabla. Marca al menos uno y vuelve a abrir el pago masivo.',

    'formId' => 'form-pagos-masivos',
    'action' => route('documentos.pagos.masivo'),
    'method' => 'POST',

    'cancelId' => 'btn-cerrar-pagos-masivos',
    'cancelText' => 'Cancelar',

    'submitId' => 'btn-registrar-pagos',
    'submitClass' => 'btn btn-success',
    'submitHtml' => '<i class="bi bi-check-circle"></i> Registrar Pagos Seleccionados',

    'tableBodyId' => 'pm-body',
    'hiddenContainerId' => 'contenedor-montos-hidden',

    'showDateField' => true,
    'dateLabel' => 'Fecha de pago',
    'dateName' => 'fecha_pago',
    'dateId' => 'pm-fecha-pago',
    'dateRequired' => true,

    'showTotals' => true,
    'totalGeneralLabel' => 'Total a pagar',
    'totalGeneralId' => 'pm-total-general',
    'totalesEmpresaId' => 'pm-totales-empresa',

    'maxWidth' => '94vw',
])
    @slot('tableHead')
        <tr>
            <th class="px-2 py-2 fw-semibold text-dark">Empresa</th>
            <th class="px-2 py-2 fw-semibold text-dark">RUT</th>
            <th class="px-2 py-2 fw-semibold text-dark">Emisión</th>
            <th class="px-2 py-2 fw-semibold text-dark">Folio</th>
            <th class="px-2 py-2 fw-semibold text-dark">Fecha Emisión</th>
            <th class="px-2 py-2 fw-semibold text-dark">Fecha Vencimiento</th>
            <th class="px-2 py-2 fw-semibold text-dark text-end">Monto</th>
            <th class="px-2 py-2 fw-semibold text-dark" style="min-width: 160px;">Operación</th>
            <th class="px-2 py-2 fw-semibold text-dark" style="min-width: 180px;">Monto a pagar</th>
            <th class="px-2 py-2 fw-semibold text-dark text-end">Saldo Pendiente</th>
            <th class="px-2 py-2 fw-semibold text-dark text-center" style="min-width: 70px;">Quitar</th>
        </tr>
    @endslot
@endcomponent