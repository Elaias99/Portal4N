<?php

namespace App\Exports;

use App\Models\Comuna;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ClasificacionOperativaExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithChunkReading
{
    protected $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function query()
    {
        return Comuna::with([
                'clasificacionOperativa.zona.zonaMadre',
                'clasificacionOperativa.subzona',
                'clasificacionOperativa.tipoZona',
                'clasificacionOperativa.proveedor',
                'clasificacionOperativa.zonaRutaGeografica.transporte',
                'clasificacionOperativa.zonaRutaGeografica.origen',
                'clasificacionOperativa.zonaRutaGeografica.destino',
                'clasificacionOperativa.codigoiata',
                'clasificacionOperativa.cobertura',
                'clasificacionOperativa.provincia',
                'region',
                'ordenTransporte'
            ])
            ->when($this->filtros['region'] ?? null, fn(Builder $q, $region) => $q->where('region_id', $region))
            ->when($this->filtros['comuna'] ?? null, fn(Builder $q, $comuna) => $q->where('Nombre', 'like', "%$comuna%"))
            ->when($this->filtros['proveedor'] ?? null, fn(Builder $q, $prov) =>
                $q->whereHas('clasificacionOperativa.proveedor', fn($q2) => $q2->where('razon_social', 'like', "%$prov%"))
            );
    }

    public function map($comuna): array
    {
        $clas = $comuna->clasificacionOperativa;
        $ruta = $clas->zonaRutaGeografica ?? null;

        return [
            $clas->tipoZona->nombre ?? '',
            $comuna->region->Numero ?? '',
            $comuna->Nombre,
            $clas->codigoiata->cod_iata ?? '',
            $clas->codigoiata->cod_iata2 ?? '',
            $clas->comuna_matriz ?? '',
            $clas->proveedor->razon_social ?? '',
            $clas->proveedor->rut ?? '',
            $clas->zona->zonaMadre->nombre ?? '',
            $clas->subzona->nombre ?? '',
            $clas->zona->nombre ?? '',
            $ruta->nombre ?? '',
            $ruta->transporte->nombre ?? '',
            $ruta->origen->Nombre ?? '',
            $ruta->destino->Nombre ?? '',
            $ruta->nombre_ruta ?? '',
            $clas->cobertura->nombre ?? '',
            $clas->provincia->nombre ?? '',
            $comuna->ordenTransporte->orden ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'Tipo de Zona',
            'Número Región',
            'Comuna',
            'COD IATA',
            'COD IATA2',
            'Comuna Matriz',
            'Nombre Operador',
            'Rut',
            'Zona Madre',
            'Subzona',
            'Zona',
            'Ruta Geo',
            'Transporte',
            'Origen',
            'Destino Máximo',
            'Nombre de la Ruta',
            'Cobertura',
            'Provincia',
            'Orden Transporte',
        ];
    }

    public function chunkSize(): int
    {
        return 500; // podés ajustar a 1000 si querés más velocidad y tenés buena RAM
    }
}
