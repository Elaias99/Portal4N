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
        $horaActual = $ahora->format('H:i');

        $correos = AutomaticEmail::where('activo', true)->get();

        foreach ($correos as $correo) {

            $horaCorreo = substr($correo->hora_envio, 0, 5);

            //Validar hora exacta
            if ($horaCorreo !== $horaActual) {
                continue;
            }

            // Evitar duplicados
            if ($correo->last_sent_at &&
                Carbon::parse($correo->last_sent_at)->isSameMinute($ahora)
            ) {
                continue;
            }

            // Validar frecuencia
            if (!$this->correspondeFrecuencia($correo)) {
                continue;
            }

            //  Enviar
            $this->enviarCorreo($correo);

            //  Marcar como enviado
            $correo->update([
                'last_sent_at' => $ahora
            ]);

        }
    }

    private function correspondeFrecuencia(AutomaticEmail $correo): bool
    {
        $hoy = Carbon::now();

        switch ($correo->tipo_frecuencia) {

            case 'diario':
                return true;

            case 'semanal':
                if (!$correo->dias_semana) return false;

                return in_array($hoy->dayOfWeek, $correo->dias_semana);

            case 'mensual':
                return $hoy->day === 1;

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


    public function procesarSimulacion(AutomaticEmail $correo): bool
    {
        $ahora = Carbon::now();
        $horaActual = $ahora->format('H:i');

        $horaCorreo = substr($correo->hora_envio, 0, 5);

        if ($horaCorreo !== $horaActual) {
            return false;
        }

        //  Evitar duplicados
        if ($correo->last_sent_at &&
            Carbon::parse($correo->last_sent_at)->isSameMinute($ahora)
        ) {
            
            return false;
        }

        //  Validar frecuencia
        if (!$this->correspondeFrecuencia($correo)) {
            
            return false;
        }

        //  Enviar
        $this->enviarCorreo($correo);

        //  Marcar como enviado
        $correo->update([
            'last_sent_at' => $ahora
        ]);

        

        return true;
    }
















    public function enviarCorreoManual(AutomaticEmail $correo)
    {

        $this->enviarCorreo($correo);
    }






















}
