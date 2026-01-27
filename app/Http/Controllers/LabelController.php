<?php

namespace App\Http\Controllers;

use App\Services\ZplLabelService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;

class LabelController extends Controller
{
    public function download(ZplLabelService $service)
    {
        // Datos de prueba (igual que antes)
        $zpl = $service->makeLabel([
            'QR' => '4N202601193152-431',
            'Atencion' => 'Juan Pérez',
            'Direccion' => 'Av. Providencia 1234',
            'Comuna' => 'Providencia',
        ]);

        return response($zpl, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="etiqueta.zpl"',
        ]);
    }


    public function uploadExcel(Request $request, ZplLabelService $service)
    {
        // GET → mostramos formulario
        if ($request->isMethod('get')) {
            return response()->make('
                <h3>Subir Excel de Etiquetas</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="'.csrf_token().'">
                    <input type="file" name="file" required>
                    <br><br>
                    <button type="submit">Generar ZPL</button>
                    <button type="submit" name="action" value="print">Imprimir</button>
                </form>
            ');
        }

        // POST → procesamos Excel
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

        $action = $request->input('action', 'download');

        if ($action === 'print') {
            $ip = '192.168.1.200';
            $port = 9100;

            $socket = fsockopen($ip, $port, $errno, $errstr, 5);

            if (! $socket) {
                return back()->withErrors("Error conectando a la impresora: $errstr ($errno)");
            }

            fwrite($socket, $zpl);
            fclose($socket);

            return back()->with('success', 'Etiquetas enviadas a la impresora');
        }

        // fallback: descarga como siempre
        return response($zpl, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="etiquetas.zpl"',
        ]);

    }





}
