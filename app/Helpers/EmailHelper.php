<?php

use Illuminate\Support\Facades\Auth;
use App\Models\Reclamos;

/* ============================================================
 | 1. CORRESPONDENCIAS ENTRE CORREOS
 * ============================================================*/

if (!function_exists('getAdminPerfilMappings')) {
    /**
     * Devuelve el listado completo de correspondencias
     * entre correos administrativos y correos de perfil interno.
     *
     * @return array
     */
    function getAdminPerfilMappings()
    {
        return [
            'luisdelabarra@4nlogistica.cl' => 'l.delabarra.b@4nlogistica.cl',
            'raul.suazo@4nlogistica.cl' => 'r.suazo.m@4nlogistica.cl',
            'jp.soza@4nlogistica.cl' => 'j.soza.b@4nlogistica.cl',
            'hansdelabarra@4nlogistica.cl' => 'h.delabarra.b@4nlogistica.cl',
            'Marcelo@4nlogistica.cl'       => 'o.godoy.s@4nlogistica.cl',
            'eliascorreap@gmail.com'       => 'e.correa.p@4nlogistica.cl',
            'NataliaLeyton@4nlogistica.cl'       => 'n.leyton.c@4nlogistica.cl',

        ];
    }
}


if (!function_exists('resolvePerfilEmail')) {
    /**
     * Devuelve el correo del perfil interno asociado a un correo administrativo.
     *
     * @param string $adminEmail
     * @return string
     */
    function resolvePerfilEmail($adminEmail)
    {
        $mapping = getAdminPerfilMappings();
        return $mapping[$adminEmail] ?? $adminEmail;
    }
}


if (!function_exists('resolveAdminEmail')) {
    /**
     * Devuelve el correo administrativo correspondiente a un correo de empleado.
     *
     * @param string $empleadoEmail
     * @return string|null
     */
    function resolveAdminEmail($empleadoEmail)
    {
        $reverse = array_flip(getAdminPerfilMappings());
        return $reverse[$empleadoEmail] ?? null;
    }
}


/* ============================================================
 |  2. CORREO DE NOTIFICACIÓN REAL (OUTLOOK)
 * ============================================================*/

if (!function_exists('resolveCorreoNotificacion')) {
    /**
     * Determina a qué correo real (Outlook) se debe enviar una notificación.
     */
    function resolveCorreoNotificacion($user)
    {
        $mapeoCorreosReales = [
            'e.correa.p@4nlogistica.cl'   => 'eliascorrea@4nlogistica.cl',
            'j.guerrero.g@4nlogistica.cl' => 'jocelynguerrero@4nlogistica.cl',
            'd.medina.p@4nlogistica.cl'   => 'daniela.medina@4nlogistica.cl',
            'e.obreque.f@4nlogistica.cl'  => 'elizabeth.obreque@4nlogistica.cl',
            'r.suazo.m@4nlogistica.cl'    => 'raul.suazo@4nlogistica.cl',
            'l.delabarra.b@4nlogistica.cl'=> 'luisdelabarra@4nlogistica.cl',
            'j.soza.b@4nlogistica.cl'     => 'jp.soza@4nlogistica.cl',
            'h.delabarra.b@4nlogistica.cl'=> 'hansdelabarra@4nlogistica.cl',
            'o.godoy.s@4nlogistica.cl'    => 'Marcelo@4nlogistica.cl',
            'm.salas.a@4nlogistica.cl'    => 'francisca.salas@4nlogistica.cl',
            'm.diaz.s@4nlogistica.cl'     => 'marieladiaz@4nlogistica.cl',
            'n.cuadros.m@4nlogistica.cl'  => 'maritzacuadros@4nlogistica.cl',
        ];

        // Si el correo interno está mapeado, usar el real
        if (array_key_exists($user->email, $mapeoCorreosReales)) {
            return $mapeoCorreosReales[$user->email];
        }

        // Si no, usar el correo personal o el mismo del usuario
        return $user->trabajador->CorreoPersonal ?? $user->email;
    }
}


/* ============================================================
 | 3. UTILIDADES VARIAS
 * ============================================================*/

if (!function_exists('calcularSiguienteViernes')) {
    /**
     * Calcula el siguiente viernes a partir de una fecha dada.
     */
    function calcularSiguienteViernes(string $fecha): string
    {
        $timestamp = strtotime($fecha);
        $diaSemana = date('w', $timestamp); // Domingo=0, Lunes=1, ..., Sábado=6
        $diasAAgregar = (5 - $diaSemana + 7) % 7;
        return date('Y-m-d', strtotime("+$diasAAgregar days", $timestamp));
    }
}


/* ============================================================
 | 4. NOTIFICACIONES INTERNAS (Reclamos y Áreas)
 * ============================================================*/

if (!function_exists('usuariosInvolucradosEnReclamo')) {
    /**
     * Devuelve los usuarios involucrados en un reclamo (autor, área destino, y origen si aplica).
     */
    function usuariosInvolucradosEnReclamo(Reclamos $reclamo, $areaOrigenId = null)
    {
        $usuarios = collect();

        // Autor
        if ($reclamo->trabajador?->user) {
            $usuarios->push($reclamo->trabajador->user);
        }

        // Área de destino
        $usuariosDestino = \App\Models\User::whereHas('trabajador', function ($q) use ($reclamo) {
            $q->where('area_id', $reclamo->area_id);
        })->get();

        // Área de origen (opcional)
        $usuariosOrigen = collect();
        if ($areaOrigenId) {
            $usuariosOrigen = \App\Models\User::whereHas('trabajador', function ($q) use ($areaOrigenId) {
                $q->where('area_id', $areaOrigenId);
            })->get();
        }

        return $usuarios
            ->merge($usuariosDestino)
            ->merge($usuariosOrigen)
            ->unique('id');
    }
}


if (!function_exists('notificarUsuarioYAdmin')) {
    /**
     * Notifica al usuario y, si existe, también a su par administrativo.
     */
    function notificarUsuarioYAdmin($usuario, $notificacion)
    {
        if ($usuario && $usuario->id !== Auth::id()) {
            $usuario->notify($notificacion);

            $adminEmail = resolveAdminEmail($usuario->email);
            if ($adminEmail && $adminEmail !== $usuario->email) {
                $adminUser = \App\Models\User::where('email', $adminEmail)->first();
                if ($adminUser && $adminUser->id !== Auth::id()) {
                    $adminUser->notify($notificacion);
                }
            }
        }
    }
}
