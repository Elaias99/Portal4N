<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reclamos;
use App\Models\Casuistica;

class ReclamoDashboardController extends Controller
{
    //
    public function index()
    {
        $resumenPorCasuistica = Casuistica::withCount([
            'reclamos as cantidad_cerrados' => function ($query) {
                $query->where('estado', 'cerrado');
            }
        ])->get();

        return view('reclamos.dashboard', compact('resumenPorCasuistica'));
    }

}
