<div class="modal fade"
     id="modalFactoryMasivo"
     tabindex="-1"
     role="dialog"
     aria-labelledby="modalFactoryMasivoLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 98vw;">
        <div class="modal-content">

            {{-- HEADER --}}
            <div class="modal-header position-relative">
                <h5 class="modal-title fw-bold" id="modalFactoryMasivoLabel">
                    Registrar Factory Masivo
                </h5>

                <button type="button"
                        class="btn btn-light btn-sm rounded-circle shadow-sm"
                        data-dismiss="modal"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"
                        style="position:absolute;top:16px;right:16px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
                    <span aria-hidden="true" class="text-dark" style="font-size:1.2rem;">&times;</span>
                </button>
            </div>

            {{-- BODY --}}
            <div class="modal-body">

                @if($errors->has('factory_masivo'))
                    <div class="alert alert-danger">
                        @php
                            $erroresFactoryMasivo = $errors->get('factory_masivo');
                        @endphp

                        @foreach($erroresFactoryMasivo as $errorFactoryMasivo)
                            @if(is_array($errorFactoryMasivo))
                                <ul class="mb-0">
                                    @foreach($errorFactoryMasivo as $detalleErrorFactory)
                                        <li>{{ $detalleErrorFactory }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <div>{{ $errorFactoryMasivo }}</div>
                            @endif
                        @endforeach
                    </div>
                @endif

                <div id="factory-masivo-sin-seleccion" class="alert alert-warning mb-3">
                    No hay documentos seleccionados. Marca al menos un documento disponible en la tabla.
                </div>

                <form id="form-factory-masivo"
                      action="{{ route('documentos.factory.masivo.store') }}"
                      method="POST">
                    @csrf

                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-3">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Empresa</th>
                                    <th class="text-nowrap">Folio</th>
                                    <th class="text-nowrap">Razón Social</th>
                                    <th class="text-nowrap">RUT</th>
                                    <th class="text-nowrap" style="min-width: 130px;">Cesión</th>
                                    <th class="text-end text-nowrap">Saldo Pendiente</th>
                                    <th class="text-nowrap" style="min-width: 230px;">Nombre Factory / Banco</th>
                                    <th class="text-nowrap" style="min-width: 160px;">RUT Factory</th>
                                    <th class="text-nowrap" style="min-width: 150px;">Fecha Factory</th>
                                    <th class="text-nowrap" style="min-width: 160px;">Saldo Líquido</th>
                                    <th class="text-end text-nowrap">Diferencia</th>
                                    <th class="text-center text-nowrap" style="width: 80px;">Quitar</th>
                                </tr>
                            </thead>

                            <tbody id="factory-masivo-documentos-seleccionados">
                                {{-- JS insertará aquí los documentos seleccionados --}}
                            </tbody>
                        </table>
                    </div>

                    <div id="factory-masivo-inputs-hidden">
                        {{-- Si después necesitas inputs hidden extra, quedan aquí --}}
                    </div>

                    <div class="border-top pt-3">
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <div class="border rounded p-2 bg-light">
                                    <span class="text-muted small d-block">Total saldo pendiente:</span>
                                    <span id="factory-masivo-total-general" class="fw-bold text-primary">
                                        $0
                                    </span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="border rounded p-2 bg-light">
                                    <span class="text-muted small d-block">Total saldo líquido:</span>
                                    <span id="factory-masivo-total-liquido" class="fw-bold text-success">
                                        $0
                                    </span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="border rounded p-2 bg-light">
                                    <span class="text-muted small d-block">Total diferencia:</span>
                                    <span id="factory-masivo-total-diferencia" class="fw-bold text-danger">
                                        $0
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button"
                                    class="btn btn-secondary btn-sm"
                                    data-dismiss="modal"
                                    data-bs-dismiss="modal">
                                Cancelar
                            </button>

                            <button type="submit"
                                    id="btn-submit-factory-masivo"
                                    class="btn btn-primary btn-sm">
                                Registrar Factory Masivo
                            </button>
                        </div>
                    </div>
                </form>

                {{-- TEMPLATE DE FILA PARA JS --}}
                <template id="factory-masivo-row-template">
                    <tr data-documento-id="__ID__">
                        <td class="text-nowrap">
                            __EMPRESA__
                        </td>

                        <td class="text-nowrap fw-semibold">
                            __FOLIO__
                        </td>

                        <td class="text-nowrap">
                            __RAZON__
                        </td>

                        <td class="text-nowrap">
                            __RUT__
                        </td>

                        <td>
                            <input type="text"
                                   name="documentos[__ID__][cesion]"
                                   class="form-control form-control-sm"
                                   placeholder="Ej: 665162"
                                   required>
                        </td>

                        <td class="text-end fw-bold text-danger">
                            __SALDO_FORMAT__
                        </td>

                        <td>
                            <select name="documentos[__ID__][banco_id]"
                                    class="form-select form-select-sm js-factory-masivo-banco"
                                    data-documento-id="__ID__"
                                    required>
                                <option value="">Seleccione banco / factory</option>

                                @foreach(($bancos ?? collect()) as $banco)
                                    <option value="{{ $banco->id }}">
                                        {{ $banco->nombre }}
                                    </option>
                                @endforeach

                                <option value="__otro__">Otro</option>
                            </select>

                            <div class="mt-2 js-factory-masivo-banco-otro-wrapper"
                                 data-documento-id="__ID__"
                                 style="display:none;">
                                <input type="text"
                                       name="documentos[__ID__][banco_otro]"
                                       class="form-control form-control-sm js-factory-masivo-banco-otro"
                                       data-documento-id="__ID__"
                                       placeholder="Ingrese nuevo banco / Factory">
                            </div>
                        </td>

                        <td>
                            <input type="text"
                                   name="documentos[__ID__][rut_factory]"
                                   class="form-control form-control-sm"
                                   placeholder="Ej: 76000000-0"
                                   required>
                        </td>

                        <td>
                            <input type="date"
                                   name="documentos[__ID__][fecha_factory]"
                                   class="form-control form-control-sm"
                                   value="{{ now()->format('Y-m-d') }}"
                                   required>
                        </td>

                        <td>
                            <input type="text"
                                   name="documentos[__ID__][saldo_liquido]"
                                   class="form-control form-control-sm js-factory-masivo-saldo-liquido"
                                   data-documento-id="__ID__"
                                   data-saldo="__SALDO_RAW__"
                                   placeholder="Ej: 4910196"
                                   required>
                        </td>

                        <td class="text-end fw-bold text-danger">
                            <span class="js-factory-masivo-diferencia"
                                  data-documento-id="__ID__"
                                  data-saldo="__SALDO_RAW__">
                                $0
                            </span>
                        </td>

                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger js-factory-masivo-quitar"
                                    data-documento-id="__ID__">
                                Quitar
                            </button>
                        </td>
                    </tr>
                </template>

            </div>
        </div>
    </div>
</div>