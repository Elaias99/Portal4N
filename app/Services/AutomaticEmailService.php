<?php

namespace App\Services;

use App\Models\AutomaticEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutomaticEmailService
{
    public function procesar()
    {
        $ahora = Carbon::now();
        $horaActual = $ahora->format('H:i:s');
        $diaActual = strtolower($ahora->locale('es')->dayName); // lunes, martes, etc.

        // 1️⃣ Buscar correos activos que coincidan con la hora
        $correos = AutomaticEmail::where('activo', true)->get();




        foreach ($correos as $correo) {

            // ▶ Evitar enviar cosas que NO correspondan a su frecuencia
            if (!$this->correspondeFrecuencia($correo, $diaActual)) {
                continue;
            }

            // ▶ Enviar correo
            $this->enviarCorreo($correo);

            Log::info("📧 Correo automático enviado: {$correo->nombre}");
        }
    }

    private function correspondeFrecuencia(AutomaticEmail $correo, string $diaActual): bool
    {
        switch ($correo->tipo_frecuencia) {

            case 'diario':
                return true;

            case 'semanal':
                if (!$correo->dias_semana) return false;

                // dias_semana es un array: ["lunes","miercoles"]
                return in_array($diaActual, $correo->dias_semana);

            case 'mensual':
                // el día 1, 5, 10, etc
                return Carbon::now()->day === 1;

            default:
                return false;
        }
    }

    private function enviarCorreo(AutomaticEmail $correo)
    {
        $destinatarios = array_map('trim', explode(',', $correo->destinatarios));

        Mail::html($correo->cuerpo_html, function ($message) use ($correo, $destinatarios) {
            $message->to($destinatarios)
                    ->subject($correo->asunto);
        });
    }

}
