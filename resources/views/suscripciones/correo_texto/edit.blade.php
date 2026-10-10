@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="mb-1">Texto del correo</h1>

            <div class="text-muted">
                Correo de las pre-facturas de
                <strong>{{ mb_strtoupper($mesNombre) }} {{ $anio }}</strong>
            </div>
        </div>

        <a href="{{ route('suscripciones.liquidacion-detalles.index', [
            'anio' => $anio,
            'mes' => $mes,
        ]) }}"
           class="btn btn-secondary">
            Volver a liquidaciones
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Cambiar de período --}}
    <form method="GET" action="{{ route('suscripciones.correo-texto.edit') }}" class="row g-2 align-items-end mb-4">
        <div class="col-auto">
            <label for="ct-anio" class="form-label small mb-1">Año</label>
            <input id="ct-anio" type="number" name="anio" value="{{ $anio }}" min="2020" max="2100" class="form-control form-control-sm">
        </div>

        <div class="col-auto">
            <label for="ct-mes" class="form-label small mb-1">Mes</label>
            <select id="ct-mes" name="mes" class="form-select form-select-sm">
                @foreach($meses as $numero => $nombre)
                    <option value="{{ $numero }}" @selected($numero === $mes)>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-outline-primary">Ver</button>
        </div>
    </form>

    @if($texto['origen'] === 'guardado')
        <div class="alert alert-success">
            Este mes ya tiene su texto guardado
            ({{ optional($texto['guardado_at'])->format('d/m/Y H:i') }}).
            Puedes cambiarlo y volver a guardar.
        </div>
    @elseif($texto['origen'] === 'copiado')
        <div class="alert alert-warning">
            <strong>Este mes todavía no tiene texto.</strong>
            Te propongo el de {{ $meses[$texto['origen_mes']] }} {{ $texto['origen_anio'] }}:
            <strong>cambia las fechas</strong> y guarda. Hasta que lo guardes, no se pueden enviar
            las pre-facturas de {{ $mesNombre }} {{ $anio }}.
        </div>
    @else
        <div class="alert alert-warning">
            <strong>Este mes todavía no tiene texto.</strong>
            Reemplaza cada DD/MM por la fecha real y guarda.
        </div>
    @endif

    <form method="POST" action="{{ route('suscripciones.correo-texto.update') }}" class="mb-4">
        @csrf
        <input type="hidden" name="anio" value="{{ $anio }}">
        <input type="hidden" name="mes" value="{{ $mes }}">

        <label for="ct-cuerpo" class="form-label">
            Texto que va después de "Adjunto encontrará prefactura…"
        </label>

        <textarea
            id="ct-cuerpo"
            name="cuerpo"
            rows="7"
            class="form-control"
            required
        >{{ old('cuerpo', $texto['cuerpo']) }}</textarea>

        <div class="form-text mb-3">
            Deja una línea en blanco para empezar otro párrafo.
            El saludo con el nombre del proveedor y la línea del adjunto se agregan solos.
        </div>

        <button type="submit" class="btn btn-primary">
            Guardar texto de {{ $mesNombre }} {{ $anio }}
        </button>
    </form>

    {{-- Vista previa: mismo partial que usa el correo real. --}}
    <div class="card">
        <div class="card-header">Así se verá el correo</div>

        <div class="card-body" id="ct-vista-previa" style="font-family: Arial, sans-serif; color: #222; line-height: 1.5;">
            @include('emails.suscripciones.partials.prefactura_cuerpo', [
                'nombreProveedor' => 'NOMBRE DEL PROVEEDOR',
                'mesNombre' => $mesNombre,
                'anio' => $anio,
                'parrafosCuerpo' => app(\App\Services\Suscripciones\SuscripcionCorreoTextoService::class)
                    ->parrafos(old('cuerpo', $texto['cuerpo'])),
            ])
        </div>
    </div>

    <script>
        (function () {
            const textarea = document.getElementById('ct-cuerpo');
            const vista = document.getElementById('ct-vista-previa');
            const fijos = vista.querySelectorAll('p');
            const saludo = fijos[0].outerHTML;
            const adjunto = fijos[1].outerHTML;

            const escapar = (texto) => texto
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            textarea.addEventListener('input', function () {
                const parrafos = textarea.value
                    .replace(/\r\n?/g, '\n')
                    .split(/\n\s*\n/)
                    .map((p) => p.trim())
                    .filter((p) => p !== '')
                    .map((p) => '<p>' + escapar(p).replace(/\n/g, '<br>') + '</p>');

                vista.innerHTML = saludo + adjunto + parrafos.join('');
            });
        })();
    </script>

</div>
@endsection
