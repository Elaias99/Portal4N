<?php

namespace App\Support\Ventas;

class DocumentoFinancieroImportState
{
    public array $errores = [];
    public array $importados = [];
    public array $duplicados = [];
    public array $sinCobranza = [];
    public array $notasCredito = [];

    public bool $estructuraVerificada = false;
    public array $foliosProcesados = [];

    public function agregarError(string $mensaje): void
    {
        $this->errores[] = $mensaje;
    }

    public function agregarImportado(string $folio): void
    {
        $this->importados[] = $folio;
    }

    public function agregarDuplicado(string $folio): void
    {
        $this->duplicados[] = $folio;
    }

    public function agregarSinCobranza(string $rutCliente, ?string $razonSocial, string $folio): void
    {
        $index = collect($this->sinCobranza)->search(
            fn ($item) => ($item['rut_cliente'] ?? null) === $rutCliente
        );

        if ($index === false) {
            $this->sinCobranza[] = [
                'razon_social' => $razonSocial,
                'rut_cliente'  => $rutCliente,
                'folios'       => [$folio],
            ];
            return;
        }

        $this->sinCobranza[$index]['folios'][] = $folio;
    }

    public function syncToImport(object $import): void
    {
        $import->errores = $this->errores;
        $import->importados = $this->importados;
        $import->duplicados = $this->duplicados;
        $import->sinCobranza = $this->sinCobranza;
        $import->notasCredito = $this->notasCredito;
    }
}