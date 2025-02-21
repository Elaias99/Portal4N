<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class FeriadoController extends Controller
{
    public function obtenerFeriados()
    {
        // Realizar una solicitud GET a la API de feriados
        $response = Http::get('https://apis.digital.gob.cl/fl/feriados');

        // Verificar si la solicitud fue exitosa
        if ($response->successful()) {
            // Decodificar la respuesta JSON a un array de PHP
            $feriados = $response->json();

            // Retornar la vista con los datos de los feriados
            return view('feriados', ['feriados' => $feriados]);
        } else {
            // Manejar el error en caso de que la solicitud falle
            return response()->json(['error' => 'No se pudieron obtener los feriados'], $response->status());
        }
    }
}
