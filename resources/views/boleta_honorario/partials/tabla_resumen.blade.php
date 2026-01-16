<table class="table table-sm table-bordered mt-3">
    <thead class="table-light">
        <tr>
            <th>Mes</th>
            <th class="text-end">Folio Inicial</th>
            <th class="text-end">Folio Final</th>
            <th class="text-end">Vigentes</th>
            <th class="text-end">Nulas</th>
            <th class="text-end">Bruto</th>
            <th class="text-end">Retenciones</th>
            <th class="text-end">Líquido</th>
        </tr>
    </thead>
    <tbody>

        @foreach($resumen as $r)
            <tr>
                <td>{{ $r['mes_nombre'] ?? $r->mes_nombre }}</td>
                <td class="text-end">{{ $r['folio_inicial'] ?? $r->folio_inicial }}</td>
                <td class="text-end">{{ $r['folio_final'] ?? $r->folio_final }}</td>
                <td class="text-end">{{ $r['boletas_vigentes'] ?? $r->boletas_vigentes }}</td>
                <td class="text-end">{{ $r['boletas_nulas'] ?? $r->boletas_nulas }}</td>
                <td class="text-end">{{ number_format($r['honorario_bruto'] ?? $r->honorario_bruto, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($r['retenciones'] ?? $r->retenciones, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($r['total_liquido'] ?? $r->total_liquido, 0, ',', '.') }}</td>
            </tr>
        @endforeach

        @if($totales)
            <tr class="table-secondary fw-bold">
                <td colspan="5">Totales</td>
                <td class="text-end">{{ number_format($totales['honorario_bruto'] ?? $totales->honorario_bruto, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($totales['retenciones'] ?? $totales->retenciones, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($totales['total_liquido'] ?? $totales->total_liquido, 0, ',', '.') }}</td>
            </tr>
        @endif

    </tbody>
</table>
