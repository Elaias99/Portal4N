<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = ['Nombre', 'logo', 'rut','giro','direccion', 'cta_corriente', 'mail_formalizado', 'banco_id', 'comuna_id'];

    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }

    public function trabajadores()
    {
        return $this->hasMany(Trabajador::class, 'empresa_id');
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class);
    }

    public function comuna() 
    {
        return $this->belongsTo(Comuna::class);
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoFinanciero::class);
    }

    public function honorariosMensualesRec()
    {
        return $this->hasMany(HonorarioMensualRec::class);
    }




}
