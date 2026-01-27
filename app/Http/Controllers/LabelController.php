<?php

namespace App\Http\Controllers;

use App\Services\ZplLabelService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LabelController extends Controller
{
    public function uploadExcel(Request $request, ZplLabelService $service)
    {
        // GET → formulario
        if ($request->isMethod('get')) {
            return response()->make('
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <title>Generador de Etiquetas ZPL</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background: #f4f6f8;
                        }
                        .box {
                            max-width: 480px;
                            margin: 80px auto;
                            background: #fff;
                            padding: 30px;
                            border-radius: 8px;
                            box-shadow: 0 8px 20px rgba(0,0,0,.08);
                        }
                        h3 {
                            margin-top: 0;
                        }
                        input[type=file] {
                            width: 100%;
                            margin: 20px 0;
                        }
                        button {
                            width: 100%;
                            padding: 12px;
                            background: #2563eb;
                            color: #fff;
                            border: none;
                            border-radius: 6px;
                            font-size: 15px;
                            cursor: pointer;
                        }
                        button:hover {
                            background: #1e40af;
                        }
                        .note {
                            margin-top: 20px;
                            font-size: 13px;
                            color: #555;
                            background: #f1f5f9;
                            padding: 12px;
                            border-radius: 6px;
                        }
                    </style>
                </head>
                <body>
                    <div class="box">
                        <h3>Generador de Etiquetas Zebra</h3>
                        <p>Sube un archivo Excel y descarga el archivo ZPL listo para imprimir.</p>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="_token" value="'.csrf_token().'">
                            <input type="file" name="file" required>
                            <button type="submit">Generar archivo ZPL</button>
                        </form>

                        <div class="note">
                            <strong>Formato del Excel:</strong><br>
                            QR | Atención | Dirección | Comuna
                        </div>
                    </div>
                </body>
                </html>
            ');
        }

        // POST → procesar Excel
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $rows = Excel::toCollection(null, $request->file('file'))[0];

        $zpl = '';

        foreach ($rows->skip(1) as $row) {
            $zpl .= $service->makeLabel([
                'QR'        => $row[0] ?? '',
                'Atencion'  => $row[1] ?? '',
                'Direccion' => $row[2] ?? '',
                'Comuna'    => $row[3] ?? '',
            ]);
        }

        return response($zpl, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="etiquetas_' . now()->format('Ymd_His') . '.zpl"',
        ]);
    }
}
