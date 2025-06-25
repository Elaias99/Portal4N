<?php


namespace App\Exports;

use App\Models\Comuna;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ClasificacionOperativaExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $comunas = Comuna::with([
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
        ->when($this->filtros['region'] ?? null, fn($q, $region) => $q->where('region_id', $region))
        ->when($this->filtros['comuna'] ?? null, fn($q, $comuna) => $q->where('Nombre', 'like', "%$comuna%"))
        ->when($this->filtros['proveedor'] ?? null, fn($q, $prov) => $q->whereHas('clasificacionOperativa.proveedor', fn($q2) => $q2->where('razon_social', 'like', "%$prov%")))
        ->get();

        return $comunas->map(function ($comuna) {
            $clas = $comuna->clasificacionOperativa;
            $ruta = $clas->zonaRutaGeografica ?? null;

            return [
                'Tipo de Zona'        => $clas->tipoZona->nombre ?? '',
                'Número Región'      => $comuna->region->Numero ?? '',
                'Comuna'             => $comuna->Nombre,
                'COD IATA'           => $clas->codigoiata->cod_iata ?? '',
                'COD IATA2'          => $clas->codigoiata->cod_iata2 ?? '',
                'Comuna Matriz'      => $clas->comuna_matriz ?? '',
                'Nombre Operador'    => $clas->proveedor->razon_social ?? '',
                'Rut'                => $clas->proveedor->rut ?? '',
                'Zona Madre'         => $clas->zona->zonaMadre->nombre ?? '',
                'Subzona'            => $clas->subzona->nombre ?? '',
                'Zona'               => $clas->zona->nombre ?? '',
                'Ruta Geo'           => $ruta->nombre ?? '',
                'Transporte'         => $ruta->transporte->nombre ?? '',
                'Origen'             => $ruta->origen->Nombre ?? '',
                'Destino Máximo'     => $ruta->destino->Nombre ?? '',
                'Nombre de la Ruta'  => $ruta->nombre_ruta ?? '',
                'Cobertura'          => $clas->cobertura->nombre ?? '',
                'Provincia'          => $clas->provincia->nombre ?? '',
                'Orden Transporte'   => $comuna->ordenTransporte->orden ?? '',
            ];
        });
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
}
