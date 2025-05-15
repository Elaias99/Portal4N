<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Reclamos;
use App\Models\Trabajador;

class ReclamosArea extends Component
{
    public $trabajador;
    public $reclamos;

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
        $compañerosIds = Trabajador::where('area_id', $this->trabajador->area_id)->pluck('id');

        $this->reclamos = Reclamos::with(['bulto.comuna', 'trabajador', 'comentarios.autor'])
            ->where(function ($query) use ($compañerosIds) {
                $query->where('area_id', $this->trabajador->area_id)
                    ->orWhereIn('id_trabajador', $compañerosIds);
            })
            ->latest()
            ->get();
    }


    public function render()
    {
        return view('livewire.reclamos-area');
    }
}
