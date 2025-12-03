<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AutomaticEmail;

class AutomaticEmailController extends Controller
{
    public function index()
    {
        $emails = AutomaticEmail::all();

        return view('admin.correspondencias.automatic_email.index', compact('emails'));
    }

    public function create()
    {
        return view('admin.correspondencias.automatic_email.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'          => 'required|string|max:255',
            'asunto'          => 'required|string|max:255',
            'cuerpo_html'     => 'required|string',
            'destinatarios'   => 'required|string',
            'tipo_frecuencia' => 'required|in:diario,semanal,mensual',
            'hora_envio'      => 'nullable',
            'dias_semana'     => 'nullable|array',
            'activo'          => 'required|boolean',
        ]);

        $data['dias_semana'] = $request->dias_semana ? json_encode($request->dias_semana) : null;

        AutomaticEmail::create($data);

        return redirect()
            ->route('admin.automatic_emails.index')
            ->with('success', 'Correo automático creado correctamente.');
    }

    public function edit(AutomaticEmail $automatic_email)
    {
        return view('admin.correspondencias.automatic_email.edit', [
            'email' => $automatic_email
        ]);
    }

    public function update(Request $request, AutomaticEmail $automatic_email)
    {
        $data = $request->validate([
            'nombre'          => 'required|string|max:255',
            'asunto'          => 'required|string|max:255',
            'cuerpo_html'     => 'required|string',
            'destinatarios'   => 'required|string',
            'tipo_frecuencia' => 'required|in:diario,semanal,mensual',
            'hora_envio'      => 'nullable',
            'dias_semana'     => 'nullable|array',
            'activo'          => 'required|boolean',
        ]);

        $data['dias_semana'] = $request->dias_semana ? json_encode($request->dias_semana) : null;

        $automatic_email->update($data);

        return redirect()
            ->route('admin.automatic_emails.index')
            ->with('success', 'Correo automático actualizado.');
    }

    public function destroy(AutomaticEmail $automatic_email)
    {
        $automatic_email->delete();

        return redirect()
            ->route('admin.automatic_emails.index')
            ->with('success', 'Correo automático eliminado.');
    }






    /////////////////////////////////////////////////////////
    /* Método para lanzar el correo                        */                                                     
    /////////////////////////////////////////////////////////


    













}
