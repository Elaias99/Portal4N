<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\Comuna;

use Illuminate\Http\Request;

use App\Http\Requests\Region\StoreRegionRequest;
use App\Http\Requests\Region\UpdateRegionRequest;

class RegionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        // $regions = Region::all();
        // return view('regions.index', compact('regions'));


        $search = $request->input('search');
        $query = Region::query();

        if ($search) {
            $query->where('Nombre', 'like', "%{$search}%");
        }

        $regions = $query->get();

        return view('regions.index', compact('regions'));
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('regions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRegionRequest $request)
    {
        // Crear la Región con los datos validados
        Region::create($request->validated());

        return redirect()->route('regions.index')->with('success', 'Región creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Region $region)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Region $region)
    {
        return view('regions.edit', compact('region'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRegionRequest $request, Region $region)
    {
        // Actualizar la Región con los datos validados
        $region->update($request->validated());

        return redirect()->route('regions.index')->with('success', 'Región actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Region $region)
    {
        $region->delete();
        return redirect()->route('regions.index')->with('success', 'Región eliminada exitosamente.');
    }
}
