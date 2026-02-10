<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AutomaticEmail;
use App\Services\AutomaticEmailService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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


    


    public function test(AutomaticEmail $automatic_email, AutomaticEmailService $service)
    {

        $ahora = Carbon::now();

        // Forzar envío ignorando hora/frecuencia
        $service->enviarCorreoManual($automatic_email);

        return redirect()
            ->route('admin.automatic_emails.index')
            ->with('success', 'Correo de prueba enviado correctamente.');
    }



    



public function simulate(
    AutomaticEmail $automatic_email,
    AutomaticEmailService $service
) {
    $ahora = Carbon::now();


    // Usamos la MISMA lógica del scheduler,
    // pero solo para este correo
    $resultado = $service->procesarSimulacion($automatic_email);

    if ($resultado === true) {
        return redirect()
            ->route('admin.automatic_emails.index')
            ->with('success', '✅ El correo cumplía las reglas y fue enviado.');
    }

    return redirect()
        ->route('admin.automatic_emails.index')
        ->with('warning', '⏭ El correo NO cumplía las reglas (hora / día / frecuencia).');
}










}
