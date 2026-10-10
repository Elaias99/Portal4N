<?php

namespace App\Services\Suscripciones;

use App\Models\SuscripcionCorreoTexto;
use Illuminate\Support\Facades\Schema;

class SuscripcionCorreoTextoService
{
    /*
     * Texto inicial cuando todavía no hay ninguno guardado.
     */
    public const TEXTO_BASE =
        'Es indispensable que emita el documento correspondiente hasta el día '
        . 'martes DD/MM, para que el pago sea realizado el día viernes DD/MM. '
        . 'Si su documento es emitido fuera de plazo, el pago será realizado '
        . 'el día viernes DD/MM.';

    private ?bool $tablaExiste = null;

    public function guardado(int $anio, int $mes): ?SuscripcionCorreoTexto
    {
        /*
         * Si la migración todavía no corre, se comporta como
         * "sin texto" en vez de botar la página.
         */
        $this->tablaExiste ??= Schema::hasTable('suscripcion_correo_textos');

        if (!$this->tablaExiste) {
            return null;
        }

        return SuscripcionCorreoTexto::query()
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->first();
    }

    /**
     * Texto que se muestra para editar un período.
     *
     * Si el período no tiene texto, se propone el del período
     * guardado más reciente (hay que revisar sus fechas).
     *
     * @return array{cuerpo: string, origen: string, origen_anio: ?int, origen_mes: ?int, guardado_at: mixed}
     */
    public function paraEditar(int $anio, int $mes): array
    {
        $guardado = $this->guardado($anio, $mes);

        if ($guardado) {
            return [
                'cuerpo' => $guardado->cuerpo,
                'origen' => 'guardado',
                'origen_anio' => $anio,
                'origen_mes' => $mes,
                'guardado_at' => $guardado->updated_at,
            ];
        }

        $anterior = $this->tablaExiste
            ? SuscripcionCorreoTexto::query()
                ->orderByDesc('anio')
                ->orderByDesc('mes')
                ->first()
            : null;

        if ($anterior) {
            return [
                'cuerpo' => $anterior->cuerpo,
                'origen' => 'copiado',
                'origen_anio' => $anterior->anio,
                'origen_mes' => $anterior->mes,
                'guardado_at' => null,
            ];
        }

        return [
            'cuerpo' => self::TEXTO_BASE,
            'origen' => 'base',
            'origen_anio' => null,
            'origen_mes' => null,
            'guardado_at' => null,
        ];
    }

    public function guardar(int $anio, int $mes, string $cuerpo): SuscripcionCorreoTexto
    {
        return SuscripcionCorreoTexto::query()->updateOrCreate(
            [
                'anio' => $anio,
                'mes' => $mes,
            ],
            [
                'cuerpo' => $this->normalizar($cuerpo),
            ]
        );
    }

    /**
     * Separa el texto en párrafos (una línea en blanco = párrafo nuevo).
     *
     * @return string[]
     */
    public function parrafos(string $cuerpo): array
    {
        $parrafos = preg_split('/\n\s*\n/', $this->normalizar($cuerpo));

        return array_values(array_filter(
            array_map('trim', $parrafos),
            fn (string $parrafo) => $parrafo !== ''
        ));
    }

    private function normalizar(string $cuerpo): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $cuerpo));
    }
}
