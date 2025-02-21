<?php

namespace App\Imports;

use App\Models\Factura;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FacturasImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Factura([
            'proveedor_id' => $row['proveedor_id'],
            'centro_costo' => $row['centro_costo'],
            'empresa_id' => $row['empresa_id'],
            'glosa' => $row['glosa'],
            'comentario' => $row['comentario'],
            'pagador' => $row['pagador'],
            'tipo_documento' => $row['tipo_documento'],
            'status' => $row['status'],
        ]);
    }
}
