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
                    Registrar Factoring Masivo
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

                    {{-- CAMPOS GENERALES PARA APLICAR A TODOS LOS DOCUMENTOS --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light fw-bold">
                            Datos generales de la operación de Factoring
                        </div>

                        <div class="card-body">
                            <div class="row g-3 align-items-end">

                                <div class="col-md-2">
                                    <label for="factory-masivo-global-cesion" class="form-label small text-muted">
                                        N° Cesión
                                    </label>

                                    <input type="text"
                                           id="factory-masivo-global-cesion"
                                           class="form-control form-control-sm"
                                           placeholder="Ej: 665162"
                                           required>
                                </div>

                                <div class="col-md-3">
                                    <label for="factory-masivo-global-banco" class="form-label small text-muted">
                                        Entidad Factoring / Banco
                                    </label>

                                    <select id="factory-masivo-global-banco"
                                            class="form-select form-select-sm"
                                            required>
                                        <option value="">Seleccione entidad / banco</option>

                                        @foreach(($bancos ?? collect()) as $banco)
                                            <option value="{{ $banco->id }}">
                                                {{ $banco->nombre }}
                                            </option>
                                        @endforeach

                                        <option value="__otro__">Otra entidad</option>
                                    </select>

                                    <div id="factory-masivo-global-banco-otro-wrapper"
                                         class="mt-2"
                                         style="display:none;">
                                        <label for="factory-masivo-global-banco-otro" class="form-label small text-muted">
                                            Nueva entidad Factoring / Banco
                                        </label>

                                        <input type="text"
                                               id="factory-masivo-global-banco-otro"
                                               class="form-control form-control-sm"
                                               placeholder="Ingrese nueva entidad / banco">
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <label for="factory-masivo-global-rut" class="form-label small text-muted">
                                        RUT Entidad Factoring
                                    </label>

                                    <input type="text"
                                           id="factory-masivo-global-rut"
                                           class="form-control form-control-sm"
                                           placeholder="Ej: 76000000-0"
                                           required>
                                </div>

                                <div class="col-md-3">
                                    <label for="factory-masivo-global-saldo-liquido" class="form-label small text-muted">
                                        Monto aplicado por Factoring
                                    </label>

                                    <input type="number"
                                           id="factory-masivo-global-saldo-liquido"
                                           class="form-control form-control-sm"
                                           min="0"
                                           step="1"
                                           placeholder="Ej: 850000"
                                           required>
                                </div>

                                <div class="col-md-2">
                                    <button type="button"
                                            id="btn-aplicar-datos-factory-masivo"
                                            class="btn btn-outline-primary btn-sm w-100">
                                        Aplicar a todos
                                    </button>
                                </div>

                            </div>

                            <small class="text-muted d-block mt-2">
                                Estos datos se copiarán a todos los documentos seleccionados del modal.
                                El monto aplicado por Factoring se descontará del saldo pendiente actual de cada documento.
                            </small>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-3">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Empresa</th>
                                    <th class="text-nowrap">Folio</th>
                                    <th class="text-nowrap">Razón Social</th>
                                    <th class="text-nowrap">RUT Cliente</th>
                                    <th class="text-nowrap" style="min-width: 130px;">N° Cesión</th>
                                    <th class="text-end text-nowrap">Saldo pendiente actual</th>
                                    <th class="text-nowrap" style="min-width: 185px;">Monto aplicado por Factoring</th>
                                    <th class="text-nowrap" style="min-width: 230px;">Entidad Factoring / Banco</th>
                                    <th class="text-nowrap" style="min-width: 175px;">RUT Entidad Factoring</th>
                                    <th class="text-nowrap" style="min-width: 150px;">Fecha operación</th>
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
                                    <span class="text-muted small d-block">
                                        Total saldo pendiente seleccionado:
                                    </span>

                                    <span id="factory-masivo-total-general" class="fw-bold text-primary">
                                        $0
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 px-3 small mb-3">
                            Al registrar Factoring masivo, cada documento seleccionado guardará el
                            <strong>monto aplicado por Factoring</strong> y quedará con saldo pendiente igual a la
                            <strong>diferencia entre su saldo pendiente actual y dicho monto</strong>.
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
                                Registrar Factoring Masivo
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
                            <input type="number"
                                   name="documentos[__ID__][saldo_liquido]"
                                   class="form-control form-control-sm"
                                   min="0"
                                   step="1"
                                   placeholder="Monto aplicado"
                                   required>
                        </td>

                        <td>
                            <select name="documentos[__ID__][banco_id]"
                                    class="form-select form-select-sm js-factory-masivo-banco"
                                    data-documento-id="__ID__"
                                    required>
                                <option value="">Seleccione entidad / banco</option>

                                @foreach(($bancos ?? collect()) as $banco)
                                    <option value="{{ $banco->id }}">
                                        {{ $banco->nombre }}
                                    </option>
                                @endforeach

                                <option value="__otro__">Otra entidad</option>
                            </select>

                            <div class="mt-2 js-factory-masivo-banco-otro-wrapper"
                                 data-documento-id="__ID__"
                                 style="display:none;">
                                <input type="text"
                                       name="documentos[__ID__][banco_otro]"
                                       class="form-control form-control-sm js-factory-masivo-banco-otro"
                                       data-documento-id="__ID__"
                                       placeholder="Ingrese nueva entidad / banco">
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