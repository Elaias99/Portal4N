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

        $this->aplicarFiltrado(); // carga inicial
    }

    public function aplicarFiltrado()
    {
        $this->reclamosFiltrados = Reclamos::with(['bulto.comuna', 'trabajador', 'comentarios.autor'])


            ->where(function ($query) {
                $query->where('area_id', $this->trabajador->area_id)
                    ->orWhere('id_trabajador', $this->trabajador->id)
                    ->orWhereHas('comentarios', function ($q) {
                        $q->where('user_id', Auth::id());
                    });
            })



            ->when($this->filtroEstado, fn($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroImportancia, fn($q) => $q->where('importancia', $this->filtroImportancia))
            ->latest()
            ->get();
    }

    public function resetFiltros()
    {
        $this->filtroEstado = '';
        $this->filtroImportancia = '';
        $this->aplicarFiltrado();
    }

    public function render()
    {
        return view('livewire.reclamos-area', [
            'reclamos' => $this->reclamosFiltrados,
        ]);
    }
}
