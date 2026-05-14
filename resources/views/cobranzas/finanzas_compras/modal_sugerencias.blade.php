@php
    $sugerenciasNotas = collect(session('sugerencias_notas_compras', []));
    $pendientesCompra = collect(session('sin_compra_pendientes', []));

    $haySugerenciasNotas = $sugerenciasNotas->count() > 0;
    $hayPendientesCompra = $pendientesCompra->count() > 0;

    $hayOpcionesGuardables = false;
@endphp

@if(!$hayPendientesCompra && $haySugerenciasNotas)
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

                <div id="msgReferenciasSugerencias" class="alert d-none mb-3"></div>

                <form id="formReferencias">

                    @foreach($sugerenciasNotas as $item)
                        @php
                            $nota = $item['nota'] ?? null;
                            $sugerida = $item['sugerida'] ?? null;
                            $alternativas = collect($item['alternativas'] ?? []);
                        @endphp

                        @continue(!$nota)

                        @php
                            $tieneOpciones = $sugerida || $alternativas->count() > 0;

                            if ($tieneOpciones) {
                                $hayOpcionesGuardables = true;
                            }
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
                            @if($alternativas->count() > 0)
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
                        {{ !$hayOpcionesGuardables ? 'disabled' : '' }}>
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
    const modalElement = document.getElementById('modalSugerenciasNotas');

    if (!modalElement || typeof $ === 'undefined') {
        return;
    }

    $('#modalSugerenciasNotas').modal('show');
});
</script>

<!-- GUARDAR / CERRAR REFERENCIAS -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('modalSugerenciasNotas');
    const form = document.getElementById('formReferencias');
    const msg = document.getElementById('msgReferenciasSugerencias');
    const btnGuardar = document.getElementById('btnGuardarReferencias');
    const btnCerrar = document.getElementById('btnCerrarSugerencias');

    if (!modalElement || !msg || typeof $ === 'undefined') {
        return;
    }

    function mostrarMensaje(tipo, texto) {
        msg.className = `alert alert-${tipo} mb-3`;
        msg.textContent = texto;
    }

    function limpiarMensaje() {
        msg.className = 'alert d-none mb-3';
        msg.textContent = '';
    }

    function setGuardando() {
        if (btnGuardar) {
            btnGuardar.disabled = true;
            btnGuardar.textContent = 'Guardando referencias...';
        }

        if (btnCerrar) {
            btnCerrar.disabled = true;
        }
    }

    function setCerrando() {
        if (btnGuardar) {
            btnGuardar.disabled = true;
        }

        if (btnCerrar) {
            btnCerrar.disabled = true;
            btnCerrar.textContent = 'Cerrando...';
        }
    }

    function restaurarBotones() {
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.textContent = 'Guardar referencias seleccionadas';
        }

        if (btnCerrar) {
            btnCerrar.disabled = false;
            btnCerrar.textContent = 'Cerrar';
        }
    }

    function cerrarYRecargar(delay = 700) {
        setTimeout(() => {
            $('#modalSugerenciasNotas').modal('hide');

            setTimeout(() => {
                location.reload();
            }, 250);
        }, delay);
    }

    btnGuardar?.addEventListener('click', function () {
        if (!form) {
            return;
        }

        limpiarMensaje();
        setGuardando();

        const data = new FormData(form);

        mostrarMensaje('info', 'Guardando referencias seleccionadas...');

        fetch("{{ route('compras.asignar_referencias') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json",
            },
            body: data
        })
        .then(async response => {
            const result = await response.json().catch(() => null);

            if (!response.ok || !result?.success) {
                throw new Error(result?.message || 'No se pudieron guardar las referencias.');
            }

            return result;
        })
        .then(() => {
            mostrarMensaje('success', 'Referencias guardadas correctamente. Actualizando documentos...');
            cerrarYRecargar();
        })
        .catch(error => {
            mostrarMensaje('danger', error?.message || 'Ocurrió un error al guardar las referencias.');
            restaurarBotones();
        });
    });

    btnCerrar?.addEventListener('click', function () {
        limpiarMensaje();
        setCerrando();

        mostrarMensaje('info', 'Cerrando sugerencias...');

        fetch("{{ route('compras.asignar_referencias') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json",
            }
        })
        .then(async response => {
            if (!response.ok) {
                const result = await response.json().catch(() => null);
                throw new Error(result?.message || 'No se pudo cerrar el modal de sugerencias.');
            }

            mostrarMensaje('success', 'Sugerencias cerradas. Actualizando vista...');
            cerrarYRecargar(500);
        })
        .catch(error => {
            mostrarMensaje('danger', error?.message || 'Ocurrió un error al cerrar las sugerencias.');
            restaurarBotones();
        });
    });
});
</script>
@endif