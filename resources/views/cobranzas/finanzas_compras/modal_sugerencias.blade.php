
@if(session('sugerencias_notas_compras') && count(session('sugerencias_notas_compras')) > 0)
<div class="modal fade" id="modalSugerenciasNotas" tabindex="-1" aria-labelledby="modalSugerenciasNotasLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalSugerenciasNotasLabel">
                    Sugerencias de Referencias para Notas de Crédito / Débito
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body">

                <form id="formReferencias">

                    @foreach(session('sugerencias_notas_compras') as $item)
                        @php
                            $nota = $item['nota'];
                            $sugerida = $item['sugerida'];
                            $alternativas = $item['alternativas'];
                            $tieneOpciones = $sugerida || (is_array($alternativas) && count($alternativas) > 0);
                        @endphp

                        <div class="border rounded p-3 mb-4 bg-light">

                            <h6 class="fw-bold text-primary">
                                Nota: Folio {{ $nota->folio }}
                                — Fecha: {{ $nota->fecha_docto ? \Carbon\Carbon::parse($nota->fecha_docto)->format('d-m-Y') : '—' }}
                                — Monto: ${{ number_format($nota->monto_total, 0, ',', '.') }}
                            </h6>

                            <p class="mb-1">
                                <strong>Proveedor:</strong> {{ $nota->razon_social }} ({{ $nota->rut_proveedor }})
                            </p>

                            <!-- SUGERENCIA PRINCIPAL -->
                            <div class="alert alert-success py-2">
                                <strong>Sugerencia principal:</strong><br>

                                @if($sugerida)
                                    <label class="mt-2">
                                        <input type="radio" 
                                               name="referencia[{{ $nota->id }}]" 
                                               value="{{ $sugerida->id }}" 
                                               checked>
                                        Factura folio <strong>{{ $sugerida->folio }}</strong> — 
                                        ${{ number_format($sugerida->monto_total, 0, ',', '.') }} —
                                        Fecha: {{ \Carbon\Carbon::parse($sugerida->fecha_docto)->format('d-m-Y') }}
                                    </label>
                                @else
                                    <em>No se encontró una sugerencia principal.</em>
                                @endif
                            </div>

                            <!-- ALTERNATIVAS -->
                            @if($alternativas && count($alternativas) > 0)
                                <div class="mt-2">
                                    <strong>Alternativas:</strong>

                                    <ul class="small mt-2">
                                        @foreach($alternativas as $alt)
                                            <li>
                                                <label>
                                                    <input type="radio" 
                                                           name="referencia[{{ $nota->id }}]" 
                                                           value="{{ $alt->id }}">
                                                    Folio <strong>{{ $alt->folio }}</strong> —
                                                    ${{ number_format($alt->monto_total, 0, ',', '.') }} —
                                                    Fecha: {{ \Carbon\Carbon::parse($alt->fecha_docto)->format('d-m-Y') }}
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                        </div>

                    @endforeach

                </form>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer">


                <button type="button"
                        id="btnGuardarReferencias"
                        class="btn btn-success"
                        {{ !$tieneOpciones ? 'disabled' : '' }}>
                    Guardar referencias seleccionadas
                </button>




                <button type="button" id="btnCerrarSugerencias" class="btn btn-secondary">
                    Cerrar
                </button>

            </div>

        </div>
    </div>
</div>

<!-- AUTO-ABRIR MODAL -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(
        document.getElementById('modalSugerenciasNotas')
    );
    modal.show();
});
</script>

<!-- GUARDAR REFERENCIAS  -->
<script>
document.getElementById('btnGuardarReferencias').addEventListener('click', function () {

    const form = document.getElementById('formReferencias');
    const data = new FormData(form);

    fetch("{{ route('compras.asignar_referencias') }}", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
        },
        body: data
    })
    .then(res => res.json())
    .then(res => {
        if(res.success){
            alert("Referencias guardadas correctamente");

            // Cerrar modal
            $('#modalSugerenciasNotas').modal('hide');

            // Recargar
            setTimeout(() => location.reload(), 500);
        }
    });
});
</script>


<script>
document.getElementById('btnCerrarSugerencias')?.addEventListener('click', function () {
    fetch("{{ route('compras.asignar_referencias') }}", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        }
    }).then(() => {
        $('#modalSugerenciasNotas').modal('hide');
        location.reload();
    });
});
</script>


@endif
