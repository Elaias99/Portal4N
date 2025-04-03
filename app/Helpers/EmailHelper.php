<?php

if (!function_exists('resolvePerfilEmail')) {
    /**
     * Resuelve el correo corporativo asociado a un correo administrativo.
     *
     * @param string $adminEmail
     * @return string
     */
    function resolvePerfilEmail($adminEmail)
    {
        $mapping = [


            'luisdelabarra@4nlogistica.cl' => 'l.delabarra.b@4nlogistica.cl',
            'raul.suazo@4nlogistica.cl' => 'r.suazo.m@4nlogistica.cl',
            'jp.soza@4nlogistica.cl' => 'j.soza.b@4nlogistica.cl',
            'benjaminrojas@4nlogistica.cl' => 'b.rojas.s@4nlogistica.cl',
            'hansdelabarra@4nlogistica.cl' => 'h.delabarra.b@4nlogistica.cl',
            'Marcelo@4nlogistica.cl' => 'o.godoy.s@4nlogistica.cl'


            // Agrega más relaciones aquí...
        ];

        return $mapping[$adminEmail] ?? $adminEmail; // Devuelve el mismo correo si no hay mapeo
    }
}
if (! function_exists('calcularSiguienteViernes')) {
    /**
     * Calcula el siguiente viernes a partir de una fecha dada.
     *
     * @param string $fecha (formato 'Y-m-d')
     * @return string Fecha del siguiente viernes en formato 'Y-m-d'
     */
    function calcularSiguienteViernes(string $fecha): string {
        $timestamp = strtotime($fecha);
        $diaSemana = date('w', $timestamp); // Domingo=0, Lunes=1, ..., Sábado=6
        // Viernes es 5.
        $diasAAgregar = (5 - $diaSemana + 7) % 7;
        // Si ya es viernes y queremos mantener esa fecha, dejamos 0; 
        // si se requiere el siguiente viernes siempre, entonces:
        // if($diasAAgregar === 0) $diasAAgregar = 7;
        return date('Y-m-d', strtotime("+$diasAAgregar days", $timestamp));
    }
}



if (!function_exists('resolveCorreoNotificacion')) {
    function resolveCorreoNotificacion($user)
    {
        // Mapeo entre correos internos del sistema y los correos reales de Outlook
        $mapeoCorreosReales = [
            'e.correa.p@4nlogistica.cl' => 'eliascorrea@4nlogistica.cl', //
            // 'j.guerrero.g@4nlogistica.cl' => 'jocelynguerrero@4nlogistica.cl', //
            // 'd.medina.p@4nlogistica.cl' => 'daniela.medina@4nlogistica.cl', //
            // 'e.obreque.f@4nlogistica.cl'=>'elizabeth.obreque@4nlogistica.cl', //
            // 'r.suazo.m@4nlogistica.cl'=>'raul.suazo@4nlogistica.cl', //
            // 'l.delabarra.b@4nlogistica.cl'=>'luisdelabarra@4nlogistica.cl', //
            // 'j.soza.b@4nlogistica.cl'=>'jp.soza@4nlogistica.cl', //
            // 'b.rojas.s@4nlogistica.cl'=>'benjaminrojas@4nlogistica.cl', //
            // 'h.delabarra.b@4nlogistica.cl'=>'hansdelabarra@4nlogistica.cl', //
            // 'o.godoy.s@4nlogistica.cl'=>'Marcelo@4nlogistica.cl',
            // 'm.salas.a@4nlogistica.cl'=>'francisca.salas@4nlogistica.cl', //
            // 'm.diaz.s@4nlogistica.cl'=>'marieladiaz@4nlogistica.cl', //
            // 'n.cuadros.m@4nlogistica.cl'=>'maritzacuadros@4nlogistica.cl', //

            // Agrega aquí más relaciones reales según tu empresa
        ];

        // Si el correo interno está mapeado, usar el real
        if (array_key_exists($user->email, $mapeoCorreosReales)) {
            return $mapeoCorreosReales[$user->email];
        }

        // Si no, usar el correo personal como respaldo
        return $user->trabajador->CorreoPersonal ?? $user->email;
    }
}


