@component('components.finanzas.modal_documentos_masivos', [
    'modalId' => 'modalPagoMasivo',
    'labelId' => 'modalPagoMasivoLabel',
    'title' => 'Pago masivo de honorarios',

    'showCount' => false,

    'sinSeleccionId' => 'honorarios-sin-seleccion',
    'sinSeleccionTexto' => 'No hay honorarios seleccionados.',

    'formId' => 'form-pago-masivo',
    'action' => route('honorarios.mensual.pago.masivo.exportar'),
    'method' => 'POST',

    'cancelId' => 'btn-cancelar-pago-masivo',
    'cancelText' => 'Cancelar',

    'submitId' => 'btn-submit-pago-masivo',
    'submitText' => 'Confirmar pago masivo',
    'submitClass' => 'btn btn-success',

    'tableBodyId' => 'honorarios-seleccionados',
    'hiddenContainerId' => 'inputs-honorarios-seleccionados',

    'showDateField' => true,
    'dateLabel' => 'Fecha de pago',
    'dateName' => 'fecha_pago',
    'dateId' => 'fecha-pago-masivo-honorarios',
    'dateRequired' => true,

    'showTotals' => false,

    'maxWidth' => '94vw',
])
    @slot('beforeTable')
        <div id="bloque-buscador">

            <div class="mb-3">
                <label class="form-label">Buscar honorarios</label>
                <input type="text"
                       id="buscador-honorarios"
                       class="form-control"
                       placeholder="Buscar por folio, RUT o emisor">
            </div>

            <div class="mb-3">
                <label class="form-label">Resultados</label>

                <div id="resultados-honorarios"
                     class="border rounded p-2"
                     style="max-height: 200px; overflow-y: auto;">
                </div>
            </div>

            <hr>
        </div>
    @endslot

    @slot('tableHead')
        <tr>
            <th>Empresa</th>
            <th>Rut</th>
            <th>Emisión</th>
            <th>Folio</th>
            <th>Fecha Emisión</th>
            <th>Fecha Vencimiento</th>
            <th class="text-end">Monto</th>
            <th class="text-center">Quitar</th>
        </tr>
    @endslot

    @slot('footerLeft')
        <div class="fw-semibold mb-1">
            Total a pagar:
            <span id="total-pago-masivo">$0</span>
        </div>

        <div id="empresa-totales-pago-masivo" class="small text-muted"></div>
    @endslot
@endcomponent