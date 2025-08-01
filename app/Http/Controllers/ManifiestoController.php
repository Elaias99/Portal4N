<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use Illuminate\Support\Str; // arriba del archivo


class ManifiestoController extends Controller
{
    //
    public function index()
    {
        return view('manifiesto.index', [
            'filasSinArea' => [],
            'codigosDuplicados' => [],
            'headers' => [],
            'rows' => [],
            'fechaSinArea' => null,
        ]);
    }





    public function store(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls'
        ]);

        $data = Excel::toArray([], $request->file('archivo'))[0]; // Hoja 1

        // Verificamos que hay datos
        if (empty($data) || count($data) < 2) {
            return back()->with('error', 'El archivo está vacío o no tiene suficientes filas.');
        }

        // Encabezados esperados (orden no importa)
        $requiredHeaders = ['fecha', 'Bulto', 'CODIGO', 'Tamaño', 'Atencion', 'Comuna', 'Area'];

        // Convertimos a minúsculas para comparar sin problema
        $headers = array_map('strtolower', $data[0]);
        $expected = array_map('strtolower', $requiredHeaders);

        // Verificamos que todos los campos estén presentes
        foreach ($expected as $header) {
            if (!in_array($header, $headers)) {
                return back()->with('error', "Falta la columna obligatoria: $header");
            }
        }
        $filas = array_slice($data, 1); // Omitimos encabezado

        // Si llega aquí, los encabezados son válidos
        return view('manifiesto.index', [
            'headers' => $data[0],
            'rows' => $filas,
        ]);
    }


    public function paste(Request $request)
    {
        $request->validate([
            'tabla' => 'required|string',
            'fecha_individual' => 'required|date'
        ]);

        $fecha = $request->input('fecha_individual');
        $texto = trim($request->input('tabla'));
        $lineas = preg_split('/\r\n|\n|\r/', $texto);
        $lineas = array_map('trim', $lineas);
        $lineas = array_values(array_filter($lineas));

        if (count($lineas) < 7) {
            return back()->with('error', 'No hay suficientes filas para procesar.');
        }

        $variantes = [
            '# bulto' => ['# bulto', 'nº bulto', 'bulto'],
            'codigo' => ['codigo', 'cod envio', 'código', 'cod'],
            'tamaño' => ['tamaño', 'tamano'],
            'atencion' => ['atencion', 'atención'],
            'comuna' => ['comuna'],
            'area' => ['area'],
        ];

        $cabecera = array_slice($lineas, 0, 6);
        $cabecera = array_map('strtolower', $cabecera);

        foreach (array_keys($variantes) as $i => $clave) {
            $valorPegado = strtolower(trim($cabecera[$i] ?? ''));
            $aliasValidos = array_map('strtolower', $variantes[$clave]);
            if (!in_array($valorPegado, $aliasValidos)) {
                return back()->with('error', 'Los encabezados no coinciden con el formato esperado.');
            }
        }

        $datos = array_slice($lineas, 6);
        $filas = [];
        $hayAreaFaltante = false;
        $areasConocidas = ['ecommerce', 'mayorista', 'sac'];

        // 👉 Si vienen pares y muy simples (solo bulto + código)
        if (count($datos) % 2 === 0 && $this->esFormatoReducido($datos)) {
            for ($i = 0; $i < count($datos); $i += 2) {
                $bulto = $datos[$i] ?? '';
                $codigo = strtoupper(trim($datos[$i + 1] ?? ''));

                $filas[] = [$bulto, $codigo, '', '', '', null];
                $hayAreaFaltante = true;
            }
        } else {
            // 🧠 Mantener tu lógica original
            for ($i = 0; $i < count($datos); ) {
                $bloque = array_slice($datos, $i, 4);

                $bulto = $bloque[0] ?? '';
                $codigo = strtoupper(trim($bloque[1] ?? ''));
                $tercero = $bloque[2] ?? '';
                $cuarto = $bloque[3] ?? '';

                $terceroLower = strtolower(trim($tercero));
                $cuartoLower = strtolower(trim($cuarto));

                if (in_array($cuartoLower, $areasConocidas)) {
                    $filas[] = [$bulto, $codigo, $tercero, '', '', strtoupper($cuartoLower)];
                    $i += 4;
                } elseif (in_array($terceroLower, $areasConocidas)) {
                    $filas[] = [$bulto, $codigo, '', '', '', strtoupper($terceroLower)];
                    $i += 3;
                } else {
                    $filas[] = [$bulto, $codigo, $tercero, '', '', null];
                    $hayAreaFaltante = true;
                    $i += 3;
                }
            }
        }

        if ($hayAreaFaltante) {
            return view('manifiesto.index', [
                'headers' => array_keys($variantes),
                'rows' => [],
                'codigosDuplicados' => [],
                'filasSinArea' => $filas,
                'fechaSinArea' => $fecha,
            ]);
        }

        $acumulado = session('manifiestos_acumulados', []);
        $acumulado[] = [
            'fecha' => $fecha,
            'filas' => $filas
        ];
        session(['manifiestos_acumulados' => $acumulado]);

        $filasTotales = collect($acumulado)->flatMap(function ($bloque) {
            return collect($bloque['filas'])->map(function ($fila) use ($bloque) {
                return array_merge([$bloque['fecha']], $fila);
            });
        })->toArray();

        $codigos = array_column($filasTotales, 2);
        $frecuencias = array_count_values($codigos);
        $codigosDuplicados = array_filter($frecuencias, fn($veces) => $veces > 1);

        return view('manifiesto.index', [
            'headers' => array_merge(['Fecha'], array_keys($variantes)),
            'rows' => $filasTotales,
            'codigosDuplicados' => $codigosDuplicados,
            'filasSinArea' => [],
        ]);
    }

    // 👉 Agregamos este método privado al controlador
    private function esFormatoReducido(array $datos): bool
    {
        // Si todas las entradas pares parecen bultos numéricos y las impares códigos alfanuméricos
        for ($i = 0; $i < count($datos); $i += 2) {
            if (!is_numeric($datos[$i])) return false;
            if (!preg_match('/^[a-zA-Z0-9\-]+$/', $datos[$i + 1] ?? '')) return false;
        }
        return true;
    }





    public function confirmarArea(Request $request)
    {
        $request->validate([
            'area' => 'required|string|in:SAC,Ecommerce,Mayorista',
            'filas' => 'required|string',
            'fecha_confirmar' => 'required|date',

        ]);

        $area = strtoupper($request->input('area'));
        $fecha = $request->input('fecha_confirmar');
        $filas = json_decode($request->input('filas'), true);

        // Reasignar el área elegida a todas las filas
        $filasConArea = collect($filas)->map(function ($fila) use ($area) {
            $fila[5] = $area;
            return $fila;
        })->toArray();

        // Agregar al acumulado en sesión
        $acumulado = session('manifiestos_acumulados', []);
        $acumulado[] = [
            'fecha' => $fecha,
            'filas' => $filasConArea
        ];
        session(['manifiestos_acumulados' => $acumulado]);

        // Aplanar filas con fecha
        $filasTotales = collect($acumulado)->flatMap(function ($bloque) {
            return collect($bloque['filas'])->map(function ($fila) use ($bloque) {
                return array_merge([$bloque['fecha']], $fila);
            });
        })->toArray();

        // Detectar duplicados
        $codigos = array_column($filasTotales, 2); // columna 2 = código
        $frecuencias = array_count_values($codigos);
        $codigosDuplicados = array_filter($frecuencias, fn($veces) => $veces > 1);

        return view('manifiesto.index', [
            'headers' => ['Fecha','# bulto', 'codigo', 'tamaño', 'atencion', 'comuna', 'area'],
            'rows' => $filasTotales,
            'codigosDuplicados' => $codigosDuplicados,
            'filasSinArea' => [],


            
        ]);
    }







    public function limpiar()
    {
        session()->forget('manifiestos_acumulados');
        return redirect()->route('manifiesto.index')->with('success', 'Registros limpiados exitosamente.');
    }





    public function export(Request $request)
    {
        if (!session()->has('manifiestos_acumulados')) {
            return back()->with('error', 'No hay registros disponibles para exportar.');
        }

        $acumulados = session('manifiestos_acumulados'); // [{fecha: ..., filas: [...]}, ...]

        // Aplanar todos los registros, agregando su fecha individual
        $registros = collect($acumulados)->flatMap(function ($bloque) {
            return collect($bloque['filas'])->map(function ($fila) use ($bloque) {
                return array_merge([$bloque['fecha']], $fila); // ['fecha', bulto, codigo, ...]
            });
        });

        // Crear mapa de orden de hermanos
        $codigosOrdenados = $registros->pluck(2) // Columna del código con índice 2 porque agregamos 'fecha' al principio
            ->map(fn($codigo) => strtoupper($codigo))
            ->filter(fn($codigo) => str_contains($codigo, '-'))
            ->unique()
            ->groupBy(fn($codigo) => explode('-', $codigo)[0])
            ->map(function ($grupo) {
                return collect($grupo)
                    ->sortBy(function ($codigo) {
                        preg_match('/-(\d+)$/', $codigo, $matches);
                        return isset($matches[1]) ? (int)$matches[1] : 0;
                    })
                    ->values()
                    ->mapWithKeys(function ($codigo, $index) {
                        return [$codigo => $index + 1];
                    });
            })
            ->collapse();

        // Hoja 1: datos con columnas extra
        $data = $registros->map(function ($fila) use ($codigosOrdenados) {
            $fila = array_pad($fila, 7, ''); // Aseguramos que tenga al menos: fecha + 6 columnas

            // 👉 Capitalizar la columna de "Area" (índice 6)
            if (!empty($fila[6])) {
                $fila[6] = strtoupper($fila[6]) === 'SAC' ? 'SAC' : ucfirst(strtolower($fila[6]));

            }



            $codigo = strtoupper($fila[2]); // código está en índice 2 ahora
            $codigoBase = explode('-', $codigo)[0];
            $hermano = str_contains($codigo, '-') ? ($codigosOrdenados[$codigo] ?? '') : '';

            return array_merge(
                array_merge([Carbon::parse($fila[0])->format('d-m-Y')], array_slice($fila, 1, 6)),
                [''],
                [$codigoBase, $hermano]
            );

        })->toArray();

        // Duplicados
        $conteo = $registros->pluck(2)->countBy(); // columna 2 = código
        $duplicados = $registros->filter(function ($fila) use ($conteo) {
            return $conteo[$fila[2]] > 1;
        })->map(function ($fila) {
            return array_slice($fila, 0, 7); // fecha + columnas originales
        })->toArray();

        // Hermanos resumen
        $hermanos = $registros
            ->pluck(2)
            ->map(fn($codigo) => explode('-', strtoupper($codigo))[0])
            ->countBy()
            ->sortDesc()
            ->map(fn($count, $codigoBase) => [$codigoBase, $count])
            ->values()
            ->toArray();

        // Encabezados finales
        $headers = ['Fecha', '# Bulto', 'Código', 'Tamaño', 'Atención', 'Comuna', 'Area', '' ,'Código Base', 'Hermanos'];

        return Excel::download(
            new \App\Exports\ManifiestoMultiExport($data, $duplicados, $hermanos, $headers, now()->format('d-m-Y')),
            'manifiesto_' . now()->format('Ymd_His') . '.xlsx'
        );
    }













}
