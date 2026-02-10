<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProveedoresImport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Exports\PlantillaProveedoresExport;

class ProveedorImportController extends Controller
{
    

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls'
        ]);

        try {
            // Cargar encabezados del archivo
            $archivo = $request->file('archivo');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo);
            $hoja = $spreadsheet->getActiveSheet();
            $encabezadosArchivo = $hoja->rangeToArray('A1:' . $hoja->getHighestColumn() . '1')[0];

            // Obtener encabezados esperados desde la plantilla oficial
            $encabezadosEsperados = (new \App\Exports\PlantillaProveedoresExport)->headings();



            // Validar si faltan columnas
            $faltantes = array_diff(
                array_map('strtolower', $encabezadosEsperados),
                array_map('strtolower', $encabezadosArchivo)
            );

            if (!empty($faltantes)) {
                return back()->with('faltantes_plantilla', $faltantes);

            }

            // Si todo está correcto, importar
            $importador = new \App\Imports\ProveedoresImport();
            \Maatwebsite\Excel\Facades\Excel::import($importador, $archivo);

            session()->flash('import_result_proveedores', [
                'importadas' => $importador->importadas,
                'omitidas' => $importador->omitidas,
                'errores' => $importador->errores,
                'exitosos' => $importador->exitosos,
                'incompletos' => $importador->conDatosIncompletos,
                'hayErrores' => count($importador->errores) > 0,
                'hayOmitidas' => $importador->omitidas > 0,
                'hayIncompletos' => count($importador->conDatosIncompletos) > 0,

                'erroresDuplicados' => collect($importador->errores)->filter(
                    fn($e) => str_contains($e, 'ya existe el proveedor')
                )->values(),

                'erroresFaltantes' => collect($importador->errores)->filter(
                    fn($e) => str_contains($e, 'falta RUT') || str_contains($e, 'falta RUT o razón social')
                )->values(),

                'erroresCamposInvalidos' => collect($importador->errores)->reject(
                    fn($e) => str_contains($e, 'ya existe el proveedor') || str_contains($e, 'falta RUT')
                )->values(),
            ]);

            return back();
        } catch (\Exception $e) {
            return back()->with('error', 'Error durante la importación: ' . $e->getMessage());
        }
    }





    public function mapearColumnas(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls'
        ]);

        $archivo = $request->file('archivo');
        $spreadsheet = IOFactory::load($archivo);
        $hoja = $spreadsheet->getActiveSheet();
        $encabezadosOriginales = $hoja->rangeToArray('A1:' . $hoja->getHighestColumn() . '1')[0];

        // Diccionario de sinónimos básicos
        // Corrección + expansión recomendada
        $sinonimos = [
            'razon_social' => ['razón social', 'nombre proveedor','razon_social','razon social (nombre pila o real)'],
            'rut' => ['rut', 'r.u.t', 'ruc', 'rut proveedor'],
            'banco' => ['banco', 'entidad bancaria', 'nombre banco'],
            'tipo_cuenta' => ['tipo cuenta', 'tipo de cuenta', 'cuenta tipo'],
            'nro_cuenta' => ['número de cuenta', 'cuenta bancaria', 'nro cuenta'], // nuevo alias útil
            'tipo_de_documento' => ['tipo documento', 'tipo doc', 'forma de pago', 'tipodocumento', 'tipo pago'], // expandido
            'telefono_empresa' => ['teléfono empresa', 'telefono contacto empresa'],
            'nombre_representantelegal' => ['representante legal', 'nombre representante'],
            'rut_representantelegal' => ['rut representante'],
            'telefono_representantelegal' => ['teléfono representante'],
            'correo_representantelegal' => ['correo representante'],
            'contacto_nombre' => ['contacto 1', 'nombre contacto'],
            'contacto_telefono' => ['teléfono contacto'],
            'contacto_correo' => ['correo contacto'],
            'giro_comercial' => ['giro', 'actividad comercial'],
            'direccion_facturacion' => ['dirección facturación'],
            'direccion_despacho' => ['dirección despacho'],
            'nombre_contacto2' => ['contacto 2'],
            'telefono_contacto2' => ['teléfono contacto 2'],
            'correo_contacto2' => ['correo contacto 2'],
            'correo_banco' => ['correo banco'],
            'nombre_razon_social_banco' => ['razón social banco', 'nombre banco pago', 'nombre cuenta'], // "nombre cuenta" se reubica aquí
            'cargo_contacto1' => ['cargo contacto', 'cargo 1'],
            'cargo_contacto2' => ['cargo contacto 2'],
            'comuna_empresa' => ['comuna', 'comuna empresa'],
        ];


        $mapeo = [];

        foreach ($encabezadosOriginales as $columnaOriginal) {
            $normalizado = strtolower(trim($columnaOriginal));
            $claveSugerida = null;

            foreach ($sinonimos as $columnaEsperada => $variantes) {
                foreach ($variantes as $sinonimo) {
                    if (str_contains($normalizado, strtolower($sinonimo))) {
                        $claveSugerida = $columnaEsperada;
                        break 2;
                    }
                }
            }

            $mapeo[$columnaOriginal] = $claveSugerida ?? '⚠️ No reconocido';
        }

        // Mostrar resultado por ahora
        dd($mapeo);
    }







    public function generarArchivoCorregido(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls'
        ]);

        $archivo = $request->file('archivo');
        $spreadsheet = IOFactory::load($archivo);
        $hoja = $spreadsheet->getActiveSheet();

        $encabezadosOriginales = $hoja->rangeToArray('A1:' . $hoja->getHighestColumn() . '1')[0];
        $datos = $hoja->toArray(null, true, true, true);

        // Diccionario de sinónimos
        $sinonimos = [
            'razon_social' => ['razón social', 'nombre proveedor','razon_social','razon social (nombre pila o real)'],
            'rut' => ['rut', 'r.u.t', 'ruc', 'rut proveedor'],
            'banco' => ['banco', 'entidad bancaria', 'nombre banco'],
            'tipo_cuenta' => ['tipo cuenta', 'tipo de cuenta', 'cuenta tipo'],
            'nro_cuenta' => ['número de cuenta', 'cuenta bancaria', 'nro cuenta'],
            'tipo_de_documento' => ['tipo documento', 'tipo doc', 'forma de pago', 'tipodocumento', 'tipo pago'],
            'telefono_empresa' => ['teléfono empresa', 'telefono contacto empresa'],
            'nombre_representantelegal' => ['representante legal', 'nombre representante'],
            'rut_representantelegal' => ['rut representante'],
            'telefono_representantelegal' => ['teléfono representante'],
            'correo_representantelegal' => ['correo representante'],
            'contacto_nombre' => ['contacto 1', 'nombre contacto'],
            'contacto_telefono' => ['teléfono contacto'],
            'contacto_correo' => ['correo contacto'],
            'giro_comercial' => ['giro', 'actividad comercial'],
            'direccion_facturacion' => ['dirección facturación'],
            'direccion_despacho' => ['dirección despacho'],
            'nombre_contacto2' => ['contacto 2'],
            'telefono_contacto2' => ['teléfono contacto 2'],
            'correo_contacto2' => ['correo contacto 2'],
            'correo_banco' => ['correo banco'],
            'nombre_razon_social_banco' => ['razón social banco', 'nombre banco pago', 'nombre cuenta'],
            'cargo_contacto1' => ['cargo contacto', 'cargo 1'],
            'cargo_contacto2' => ['cargo contacto 2'],
            'comuna_empresa' => ['comuna', 'comuna empresa'],
        ];

        $ordenPlantilla = [
            'razon_social', 'rut', 'banco', 'tipo_cuenta', 'nro_cuenta',
            'tipo_de_documento', 'telefono_empresa', 'nombre_representantelegal', 'rut_representantelegal',
            'telefono_representantelegal', 'correo_representantelegal', 'contacto_nombre', 'contacto_telefono',
            'contacto_correo', 'giro_comercial', 'direccion_facturacion', 'direccion_despacho',
            'nombre_contacto2', 'telefono_contacto2', 'correo_contacto2', 'correo_banco',
            'nombre_razon_social_banco', 'cargo_contacto1', 'cargo_contacto2', 'comuna_empresa'
        ];

        // Mapear letra A, B, ... => campo esperado
        $letras = array_keys($datos[2]);
        $letraParaCampo = [];

        foreach ($letras as $index => $letra) {
            $encabezado = $encabezadosOriginales[$index] ?? null;
            $encabezadoNormalizado = strtolower(trim($encabezado ?? ''));

            foreach ($sinonimos as $colEsperada => $alias) {
                foreach ($alias as $sinonimo) {
                    if (str_contains($encabezadoNormalizado, strtolower($sinonimo))) {
                        $letraParaCampo[$colEsperada] = $letra;
                        break 2;
                    }
                }
            }
        }

        // Armar data con columnas ordenadas
        $nuevaData = [];
        $nuevaData[] = $ordenPlantilla;

        foreach (array_slice($datos, 1) as $filaOriginal) {
            $filaReordenada = [];

            foreach ($ordenPlantilla as $colEsperada) {
                $letra = $letraParaCampo[$colEsperada] ?? null;
                $filaReordenada[] = $letra ? ($filaOriginal[$letra] ?? '') : '';
            }

            $nuevaData[] = $filaReordenada;
        }

        // Crear archivo
        $nuevo = new Spreadsheet();
        $nuevo->getActiveSheet()->fromArray($nuevaData);

        $nombreArchivo = 'plantilla_convertida_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($nuevo) {
            $writer = new Xlsx($nuevo);
            $writer->save('php://output');
        }, $nombreArchivo);
    }












}

