<div class="modal fade"
     id="modalFactoryMasivo"
     tabindex="-1"
     role="dialog"
     aria-labelledby="modalFactoryMasivoLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered"
         role="document"
         style="max-width: 98vw;">

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
                    <span aria-hidden="true"
                          class="text-dark"
                          style="font-size:1.2rem;">
                        &times;
                    </span>
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

                <div id="factory-masivo-sin-seleccion"
                     class="alert alert-warning mb-3">
                    No hay documentos seleccionados. Marca al menos un documento disponible en la tabla.
                </div>

                <form id="form-factory-masivo"
                      action="{{ route('documentos.factory.masivo.store') }}"
                      method="POST">
                    @csrf

                    {{-- DATOS GENERALES DE LA OPERACIÓN --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light fw-bold">
                            Datos generales de la operación de Factoring
                        </div>

                        <div class="card-body">

                            <div class="row g-3 align-items-end mb-3">

                                {{-- Cesión --}}
                                <div class="col-md-3">
                                    <label for="factory-masivo-global-cesion"
                                           class="form-label small text-muted">
                                        N° Cesión
                                    </label>

                                    <input type="text"
                                           name="cesion"
                                           id="factory-masivo-global-cesion"
                                           class="form-control form-control-sm @error('cesion') is-invalid @enderror"
                                           value="{{ old('cesion') }}"
                                           placeholder="Ej: 665162"
                                           required>

                                    @error('cesion')
                                        <span class="invalid-feedback d-block text-danger">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                {{-- Banco / Entidad Factoring --}}
                                <div class="col-md-4">
                                    <label for="factory-masivo-global-banco"
                                           class="form-label small text-muted">
                                        Entidad Factoring / Banco
                                    </label>

                                    <select name="banco_id"
                                            id="factory-masivo-global-banco"
                                            class="form-select form-select-sm @error('banco_id') is-invalid @enderror"
                                            required>
                                        <option value="">Seleccione entidad / banco</option>

                                        @foreach(($bancos ?? collect()) as $banco)
                                            <option value="{{ $banco->id }}"
                                                {{ old('banco_id') == $banco->id ? 'selected' : '' }}>
                                                {{ $banco->nombre }}
                                            </option>
                                        @endforeach

                                        <option value="__otro__"
                                            {{ old('banco_id') === '__otro__' ? 'selected' : '' }}>
                                            Otra entidad
                                        </option>
                                    </select>

                                    @error('banco_id')
                                        <span class="invalid-feedback d-block text-danger">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                    <div id="factory-masivo-global-banco-otro-wrapper"
                                         class="mt-2"
                                         style="display: {{ old('banco_id') === '__otro__' ? 'block' : 'none' }};">

                                        <label for="factory-masivo-global-banco-otro"
                                               class="form-label small text-muted">
                                            Nueva entidad Factoring / Banco
                                        </label>

                                        <input type="text"
                                               name="banco_otro"
                                               id="factory-masivo-global-banco-otro"
                                               class="form-control form-control-sm @error('banco_otro') is-invalid @enderror"
                                               value="{{ old('banco_otro') }}"
                                               placeholder="Ingrese nueva entidad / banco"
                                               {{ old('banco_id') === '__otro__' ? 'required' : '' }}>

                                        @error('banco_otro')
                                            <span class="invalid-feedback d-block text-danger">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Fecha operación --}}
                                <div class="col-md-3">
                                    <label for="factory-masivo-global-fecha"
                                           class="form-label small text-muted">
                                        Fecha operación
                                    </label>

                                    <input type="date"
                                           name="fecha_factory"
                                           id="factory-masivo-global-fecha"
                                           class="form-control form-control-sm @error('fecha_factory') is-invalid @enderror"
                                           value="{{ old('fecha_factory', now()->format('Y-m-d')) }}"
                                           required>

                                    @error('fecha_factory')
                                        <span class="invalid-feedback d-block text-danger">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                {{-- Comisión Total --}}
                                <div class="col-md-2">
                                    <label for="factory-masivo-global-comision-total"
                                           class="form-label small text-muted">
                                        Comisión Total (1)
                                    </label>

                                    <input type="number"
                                           name="comision_total"
                                           id="factory-masivo-global-comision-total"
                                           class="form-control form-control-sm @error('comision_total') is-invalid @enderror"
                                           value="{{ old('comision_total') }}"
                                           min="0"
                                           step="1"
                                           placeholder="Ej: 71118"
                                           required>

                                    @error('comision_total')
                                        <span class="invalid-feedback d-block text-danger">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <hr class="my-3">

                            {{--
                            |--------------------------------------------------------------------------
                            | Herramienta para copiar montos a todas las filas
                            |--------------------------------------------------------------------------
                            | Estos campos no llevan name porque no se envían al backend.
                            | Solo permiten cargar rápidamente los montos individuales.
                            |--------------------------------------------------------------------------
                            --}}

                            <div class="row g-3 align-items-end">

                                <div class="col-md-3">
                                    <label for="factory-masivo-global-saldo-liquido"
                                           class="form-label small text-muted">
                                        Monto Líquido para aplicar a todos
                                    </label>

                                    <input type="number"
                                           id="factory-masivo-global-saldo-liquido"
                                           class="form-control form-control-sm"
                                           min="0"
                                           step="1"
                                           placeholder="Ej: 3099297">
                                </div>

                                <div class="col-md-3">
                                    <label for="factory-masivo-global-monto-no-anticipado"
                                           class="form-label small text-muted">
                                        Monto No Anticipado para aplicar a todos
                                    </label>

                                    <input type="number"
                                           id="factory-masivo-global-monto-no-anticipado"
                                           class="form-control form-control-sm"
                                           min="0"
                                           step="1"
                                           placeholder="Ej: 31968">
                                </div>

                                <div class="col-md-3">
                                    <button type="button"
                                            id="btn-aplicar-datos-factory-masivo"
                                            class="btn btn-outline-primary btn-sm w-100">
                                        Aplicar montos a todos
                                    </button>
                                </div>
                            </div>

                            <small class="text-muted d-block mt-3">
                                La cesión, la entidad Factoring / Banco, la fecha de operación y la
                                comisión total corresponden a la operación completa. Los montos por
                                documento pueden copiarse inicialmente a todas las filas y luego
                                ajustarse individualmente antes de registrar.
                            </small>
                        </div>
                    </div>

                    {{-- DOCUMENTOS SELECCIONADOS --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light fw-bold">
                            Documentos incluidos en la operación
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-nowrap">Empresa</th>
                                            <th class="text-nowrap">Folio</th>
                                            <th class="text-nowrap">Razón Social</th>

                                            <th class="text-end text-nowrap"
                                                style="min-width: 150px;">
                                                Monto
                                            </th>

                                            <th class="text-nowrap"
                                                style="min-width: 180px;">
                                                Monto Líquido
                                            </th>

                                            <th class="text-nowrap"
                                                style="min-width: 190px;">
                                                Monto No Anticipado
                                            </th>

                                            <th class="text-end text-nowrap"
                                                style="min-width: 180px;">
                                                Diferencia de Precio
                                            </th>

                                            <th class="text-center text-nowrap"
                                                style="width: 80px;">
                                                Quitar
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody id="factory-masivo-documentos-seleccionados">
                                        {{-- JS insertará aquí los documentos seleccionados --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- RESUMEN CONSOLIDADO ESTILO COMPROBANTE --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light fw-bold">
                            Resumen de la operación de Factoring
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle text-center mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-nowrap">
                                                Cant. Docto.
                                            </th>

                                            <th class="text-nowrap text-end">
                                                Monto Docto.
                                            </th>

                                            <th class="text-nowrap text-end">
                                                Monto Anticipado
                                            </th>

                                            <th class="text-nowrap text-end">
                                                Diferencia de Precio
                                            </th>

                                            <th class="text-nowrap text-end">
                                                Monto Líquido
                                            </th>

                                            <th class="text-nowrap text-end">
                                                Precio de Compra
                                            </th>

                                            <th class="text-nowrap text-end">
                                                Comisión Total (1)
                                            </th>

                                            <th class="text-nowrap text-end">
                                                Monto a Recibir
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr class="fw-semibold">
                                            <td id="factory-masivo-total-documentos">
                                                0
                                            </td>

                                            <td id="factory-masivo-total-general"
                                                class="text-end text-primary">
                                                $0
                                            </td>

                                            <td id="factory-masivo-total-liquido"
                                                class="text-end text-success">
                                                $0
                                            </td>

                                            <td id="factory-masivo-total-diferencia-precio"
                                                class="text-end text-danger">
                                                $0
                                            </td>

                                            <td id="factory-masivo-total-monto-liquido-resumen"
                                                class="text-end">
                                                $0
                                            </td>

                                            <td id="factory-masivo-total-precio-compra"
                                                class="text-end">
                                                $0
                                            </td>

                                            <td id="factory-masivo-total-comision"
                                                class="text-end text-warning">
                                                $0
                                            </td>

                                            <td id="factory-masivo-total-monto-a-recibir"
                                                class="text-end fw-bold text-success">
                                                $0
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <small class="text-muted d-block mt-2">
                                En este resumen, el Monto Anticipado corresponde a la suma de los
                                montos líquidos ingresados por documento. El Monto Líquido y el
                                Precio de Compra se obtienen sumando el Monto Anticipado y la
                                Diferencia de Precio. El Monto a Recibir se obtiene descontando
                                del Monto Líquido la Comisión Total y la Diferencia de Precio.
                            </small>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 small mb-3">
                        Para cada documento, la
                        <strong>Diferencia de Precio</strong> se calculará como:
                        <strong>Monto - Monto Líquido - Monto No Anticipado</strong>.
                        Para la operación completa, el
                        <strong>Monto a Recibir</strong> se calculará como:
                        <strong>Monto Líquido - Comisión Total - Diferencia de Precio</strong>.
                    </div>

                    {{-- ACCIONES --}}
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
                </form>

                {{-- TEMPLATE DE FILA PARA JS --}}
                <template id="factory-masivo-row-template">
                    <tr data-documento-id="__ID__"
                        data-monto="__SALDO__">

                        <td class="text-nowrap">
                            __EMPRESA__
                        </td>

                        <td class="text-nowrap fw-semibold">
                            __FOLIO__
                        </td>

                        <td class="text-nowrap">
                            __RAZON__
                        </td>

                        <td class="text-end fw-bold text-primary">
                            __SALDO_FORMAT__
                        </td>

                        <td>
                            <input type="number"
                                   name="documentos[__ID__][saldo_liquido]"
                                   class="form-control form-control-sm js-factory-masivo-saldo-liquido"
                                   data-documento-id="__ID__"
                                   min="0"
                                   step="1"
                                   placeholder="Monto líquido"
                                   required>
                        </td>

                        <td>
                            <input type="number"
                                   name="documentos[__ID__][monto_no_anticipado]"
                                   class="form-control form-control-sm js-factory-masivo-monto-no-anticipado"
                                   data-documento-id="__ID__"
                                   min="0"
                                   step="1"
                                   placeholder="Monto no anticipado"
                                   required>
                        </td>

                        <td class="text-end fw-bold">
                            <span class="js-factory-masivo-diferencia-precio text-muted"
                                  data-documento-id="__ID__">
                                —
                            </span>
                        </td>

                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger js-factory-masivo-quitar"
                                    data-documento-id="__ID__">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </td>
                    </tr>
                </template>
            </div>
        </div>
    </div>
</div>