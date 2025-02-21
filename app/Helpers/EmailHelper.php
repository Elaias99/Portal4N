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
