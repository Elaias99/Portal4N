@props([
    'modalId',
    'labelId' => null,
    'title' => 'Gestión masiva de documentos',
    'subtitle' => null,

    'showCount' => true,
    'countLabel' => 'Seleccionados',
    'countId' => 'dm-count',

    'sinSeleccionId' => 'dm-sin-seleccion',
    'sinSeleccionTexto' => 'No hay documentos seleccionados.',

    'formId' => 'form-documentos-masivos',
    'action' => '#',
    'method' => 'POST',

    'closeXId' => null,
    'cancelId' => null,
    'cancelText' => 'Cancelar',

    'submitId' => 'btn-submit-documentos-masivos',
    'submitText' => 'Guardar',
    'submitClass' => 'btn btn-success',
    'submitHtml' => null,

    'tableBodyId' => 'dm-body',
    'hiddenContainerId' => 'dm-hidden',

    'showDateField' => true,
    'dateLabel' => 'Fecha',
    'dateName' => 'fecha',
    'dateId' => 'dm-fecha',
    'dateRequired' => true,

    'showTotals' => false,
    'totalGeneralLabel' => 'Total',
    'totalGeneralId' => 'dm-total-general',
    'totalesEmpresaId' => 'dm-totales-empresa',

    'maxWidth' => '94vw',
    'dismissOnClose' => true,
])

@php
    $labelId = $labelId ?: $modalId . 'Label';
    $methodUpper = strtoupper($method);
@endphp

<div
    class="modal fade"
    id="{{ $modalId }}"
    tabindex="-1"
    aria-labelledby="{{ $labelId }}"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: {{ $maxWidth }};">
        <div class="modal-content border-0 shadow-sm rounded-3">

            {{-- HEADER --}}
            <div class="modal-header px-4 py-3 border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="{{ $labelId }}">
                        {{ $title }}
                    </h5>

                    @if ($showCount)
                        <div class="small text-muted mt-1">
                            {{ $countLabel }}: <span id="{{ $countId }}">0</span>
                        </div>
                    @endif

                    @if ($subtitle)
                        <div class="small text-muted mt-1">
                            {{ $subtitle }}
                        </div>
                    @endif

                    @isset($headerExtra)
                        <div class="mt-2">
                            {{ $headerExtra }}
                        </div>
                    @endisset
                </div>

                <button
                    type="button"
                    class="btn-close"
                    @if($closeXId) id="{{ $closeXId }}" @endif
                    @if($dismissOnClose) data-bs-dismiss="modal" @endif
                    aria-label="Cerrar"
                ></button>
            </div>

            {{-- BODY --}}
            <div class="modal-body px-4 py-4">

                {{-- Mensaje sin selección --}}
                <div id="{{ $sinSeleccionId }}" class="alert alert-warning d-none mb-4">
                    {{ $sinSeleccionTexto }}
                </div>

                {{-- FORM --}}
                <form
                    id="{{ $formId }}"
                    action="{{ $action }}"
                    method="{{ in_array($methodUpper, ['GET', 'POST']) ? strtolower($methodUpper) : 'post' }}"
                >
                    @if ($methodUpper !== 'GET')
                        @csrf
                    @endif

                    @if (!in_array($methodUpper, ['GET', 'POST']))
                        @method($methodUpper)
                    @endif

                    @isset($beforeTable)
                        {{ $beforeTable }}
                    @endisset


                    {{-- Tabla de seleccionados --}}
                    <div class="border rounded-3 mb-4 p-3">
                        <label class="form-label fw-semibold mb-3">
                            Documentos seleccionados
                        </label>

                        <div class="table-responsive rounded border">
                            <table class="table table-sm table-borderless mb-0 align-middle" style="white-space: nowrap;">
                                <thead>
                                    @isset($tableHead)
                                        {{ $tableHead }}
                                    @else
                                        <tr>
                                            <th class="px-2 py-2 fw-semibold text-dark">Empresa</th>
                                            <th class="px-2 py-2 fw-semibold text-dark">RUT</th>
                                            <th class="px-2 py-2 fw-semibold text-dark">Razón social</th>
                                            <th class="px-2 py-2 fw-semibold text-dark">Folio</th>
                                            <th class="px-2 py-2 fw-semibold text-dark">Fecha emisión</th>
                                            <th class="px-2 py-2 fw-semibold text-dark">Fecha vencimiento</th>
                                            <th class="px-2 py-2 fw-semibold text-dark text-end">Monto</th>
                                            <th class="px-2 py-2 fw-semibold text-dark" style="min-width: 160px;">Operación</th>
                                            <th class="px-2 py-2 fw-semibold text-dark" style="min-width: 180px;">Monto a pagar</th>
                                            <th class="px-2 py-2 fw-semibold text-dark text-end">Saldo pendiente</th>
                                            <th class="px-2 py-2 fw-semibold text-dark text-center" style="min-width: 70px;">Quitar</th>
                                        </tr>
                                    @endisset
                                </thead>

                                <tbody id="{{ $tableBodyId }}">
                                    @isset($tableBody)
                                        {{ $tableBody }}
                                    @endisset
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Hidden inputs --}}
                    @isset($hiddenInputs)
                        {{ $hiddenInputs }}
                    @else
                        <div id="{{ $hiddenContainerId }}"></div>
                    @endisset

                    {{-- Bloque inferior --}}
                    <div class="border rounded-3 p-3">

                        @if ($showDateField)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">{{ $dateLabel }}</label>
                                <input
                                    type="date"
                                    name="{{ $dateName }}"
                                    id="{{ $dateId }}"
                                    class="form-control"
                                    @if($dateRequired) required @endif
                                >
                            </div>
                        @endif

                        @isset($fields)
                            <div class="{{ $showDateField ? 'mt-3' : '' }}">
                                {{ $fields }}
                            </div>
                        @endisset

                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mt-3">

                            <div>
                                @if ($showTotals)
                                    <div class="fw-semibold mb-1">
                                        {{ $totalGeneralLabel }}:
                                        <span id="{{ $totalGeneralId }}">$0</span>
                                    </div>

                                    <div id="{{ $totalesEmpresaId }}" class="small text-muted"></div>
                                @endif

                                @isset($footerLeft)
                                    <div class="{{ $showTotals ? 'mt-2' : '' }}">
                                        {{ $footerLeft }}
                                    </div>
                                @endisset
                            </div>

                            <div class="text-md-end d-flex gap-2">
                                @isset($footerButtonsBefore)
                                    {{ $footerButtonsBefore }}
                                @endisset

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    @if($cancelId) id="{{ $cancelId }}" @endif
                                    @if($dismissOnClose) data-bs-dismiss="modal" @endif
                                >
                                    {{ $cancelText }}
                                </button>

                                <button
                                    type="submit"
                                    id="{{ $submitId }}"
                                    class="{{ $submitClass }}"
                                >
                                    @if ($submitHtml)
                                        {!! $submitHtml !!}
                                    @else
                                        {{ $submitText }}
                                    @endif
                                </button>

                                @isset($footerButtonsAfter)
                                    {{ $footerButtonsAfter }}
                                @endisset
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>