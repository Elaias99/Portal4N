<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Trabajador extends Model
{
    use HasFactory;

    protected $table = 'trabajadors'; // O ajusta el nombre si es diferente


    protected $fillable = [
        'Rut', 'Nombre', 'SegundoNombre', 'TercerNombre', 'ApellidoPaterno', 'ApellidoMaterno', 
        'FechaNacimiento', 'CorreoPersonal', 'Foto', 'Casino', 'ContratoFirmado', 'AnexoContrato', 
        'empresa_id', 'cargo_id', 'situacion_id', 'estado_civil_id', 'comuna_id', 'afp_id', 'salud_id',
        'salario_bruto', 'calle','numero_celular','nombre_emergencia','contacto_emergencia', 'fecha_inicio_trabajo',
        'user_id', 'turno_id', 'sistema_trabajo_id', 'banco','numero_cuenta','tipo_cuenta','Rut_Empresa',
        'fecha_inicio_contrato', 'id_jefe',
    ];


    // Esto convierte fecha_inicio_trabajo en un objeto Carbon
    protected $casts = [
        'fecha_inicio_trabajo' => 'date',
        'FechaNacimiento' => 'date', // Agregar este cast
        'fecha_inicio_contrato'=> 'date',
    ];
    





    // Definir relaciones con otros modelos
    public function empresa() {
        return $this->belongsTo(Empresa::class);
    }

    
    public function cargo() {
        return $this->belongsTo(Cargo::class);
    }

    public function situacion() {
        return $this->belongsTo(Situacion::class);
    }

    public function estadoCivil() {
        return $this->belongsTo(EstadoCivil::class);
    }

    public function comuna() {
        return $this->belongsTo(Comuna::class);
    }

    public function afp() {
        return $this->belongsTo(AFP::class);
    }

    public function salud() {
        return $this->belongsTo(Salud::class);
    }

    // Nueva relación con Tallas
    public function tallas()
    {
        return $this->hasMany(Talla::class);
    }

    public function hijos()
    {
        return $this->hasMany(Hijo::class);
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }

    public function sistemaTrabajo()
    {
        return $this->belongsTo(SistemaTrabajo::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class);
    }

    // Relación con el modelo Vacacion (un trabajador puede tener muchas solicitudes de vacaciones)
    public function vacaciones()
    {
        return $this->hasMany(Vacacion::class);
    }

    // Relación con el modelo Jefe
    public function jefe()
    {
        return $this->belongsTo(Jefe::class, 'id_jefe');
    }


    public function historialVacaciones()
    {
        return $this->hasMany(HistorialVacacion::class, 'trabajador_id');
    }

    public function asistencias() {
        return $this->hasMany(Asistencia::class);
    }
    




    public function getEdadAttribute()
    {
        return \Carbon\Carbon::parse($this->FechaNacimiento)->age;
    }


    public function calcularCotizacion()
    {
        // Asegurarse de que el trabajador esté asociado a una AFP y que la AFP tenga una tasa asociada
        if ($this->afp && $this->afp->tasaAfp) {
            $tasaAfp = $this->afp->tasaAfp;
            
            // Calcular la cotización sobre el salario bruto
            $cotizacion = ($this->salario_bruto * $tasaAfp->tasa_cotizacion) / 100;

            // Calcular el monto del SIS sobre el salario bruto
            $sis = ($this->salario_bruto * $tasaAfp->tasa_sis) / 100;

            // Retornar un array con los valores calculados
            return [
                'cotizacion' => $cotizacion,
                'sis' => $sis,
                'total' => $cotizacion + $sis,
            ];
        }

        // Retornar null si no hay AFP o no tiene una tasa asociada
        return null;
    }


    // Método para calcular la asignación familiar del trabajador
    public function calcularAsignacionFamiliar()
    {
        $tramo = AsignacionFamiliar::obtenerTramo($this->salario_bruto);

        return $tramo ? $tramo->monto : 0;
    }




}


