<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Reclamos;
use App\Models\Trabajador;

class ReclamosArea extends Component
{
    public $trabajador;
    public $trabajadores;

    public $filtroEstado = '';
    public $filtroImportancia = '';

    public $filtroBulto = '';

    public $filtroTrabajador = '';



    public $reclamosFiltrados = [];

    public function mount()
    {
        $user = Auth::user();
        $resolvedEmail = resolvePerfilEmail($user->email);

        $this->trabajador = Trabajador::whereHas('user', function ($query) use ($resolvedEmail, $user) {
            $query->where('email', $resolvedEmail)
                  ->orWhere('email', $user->email);
        })->first();

        if (!$this->trabajador || !$this->trabajador->area_id) {
            abort(403, 'No se pudo encontrar el perfil asociado.');
        }

        $this->trabajadores = Trabajador::where('area_id', $this->trabajador->area_id)->get();

        $this->aplicarFiltrado(); // carga inicial
    }

    public function aplicarFiltrado()
    {
        $this->reclamosFiltrados = Reclamos::with(['bulto.comuna', 'trabajador', 'comentarios.autor'])


            ->where(function ($query) {
                $query->where('area_id', $this->trabajador->area_id)
                    ->orWhereHas('trabajador', function ($q) {
                        $q->where('area_id', $this->trabajador->area_id);
                    })
                    ->orWhereHas('comentarios', function ($q) {
                        $q->where('user_id', Auth::id());
                    });
            })


            ->when($this->filtroBulto, function ($query) {
                $valor = strtolower(trim($this->filtroBulto));
                $query->where(function ($subQuery) use ($valor) {
                    $subQuery
                        ->whereHas('bulto', function ($q) use ($valor) {
                            $q->whereRaw('LOWER(codigo_bulto) LIKE ?', ["%{$valor}%"]);
                        })
                        ->orWhere(function ($q) use ($valor) {
                            $q->whereNull('id_bulto')
                            ->whereRaw('LOWER(descripcion) LIKE ?', ["%{$valor}%"]);
                        })
                        ->orWhereRaw('CAST(id AS CHAR) LIKE ?', ["%{$valor}%"]);
                });
            })






            ->when($this->filtroEstado, fn($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroImportancia, fn($q) => $q->where('importancia', $this->filtroImportancia))
            ->when($this->filtroTrabajador, fn($q) => $q->where('id_trabajador', $this->filtroTrabajador))

            ->orderByRaw("
                CASE 
                    WHEN importancia = 'urgente' THEN 0
                    WHEN importancia = 'alta' THEN 1
                    WHEN importancia = 'media' THEN 2
                    WHEN importancia = 'baja' THEN 3
                    ELSE 4
                END
            ")->orderBy('created_at', 'desc')
            ->get();

                
    }

    public function resetFiltros()
    {
        $this->filtroEstado = '';
        $this->filtroImportancia = '';
        $this->filtroBulto = '';
        $this->filtroTrabajador = ''; // ✅ Esto es lo que faltaba
        $this->aplicarFiltrado();
    }



    public function render()
    {
        return view('livewire.reclamos-area', [
            'reclamos' => $this->reclamosFiltrados,
        ]);
    }
}
