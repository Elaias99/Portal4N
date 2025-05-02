<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reclamos;
use App\Models\Bultos;
use App\Models\Area;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // Importamos Auth para manejar la autenticación
use App\Notifications\NuevoReclamoAreaNotification;

class ReclamoController extends Controller
{
    // Listar los reclamos del trabajador autenticado
    public function index()
    {
        // Obtener todas las áreas, si las necesitas para filtros, select, etc.
        $areas = Area::all();

        // Obtener todos los reclamos pendientes del sistema (sin filtrar por trabajador)
        $reclamos = Reclamos::where('estado', 'pendiente')->get();

        return view('reclamos.index', compact('reclamos', 'areas'));
    }




    // Mostrar formulario para crear un reclamo asociado a un bulto
    public function create($id_bulto)
    {
        $bulto = Bultos::findOrFail($id_bulto);
        return view('reclamos.create', compact('bulto'));
    }

    // Guardar un nuevo reclamo
    public function store(Request $request)
    {
        $request->validate([
            'id_bulto' => 'required|exists:bultos,id',
            'area_id' => 'required|exists:areas,id',
            'descripcion' => 'required|string'
        ]);

        // Paso 1: Obtener el usuario autenticado (correo real de Outlook)
        $usuarioOutlook = Auth::user();

        // Paso 2: Resolver el correo interno equivalente
        $correoInterno = resolvePerfilEmail($usuarioOutlook->email);

        // Paso 3: Buscar el usuario interno por ese correo
        $usuarioInterno = \App\Models\User::where('email', $correoInterno)->first();

        // Paso 4: Obtener el trabajador asociado a ese usuario interno
        $trabajador = $usuarioInterno?->trabajador;

        // Validación final
        if (!$trabajador) {
            return redirect()->back()->with('error', 'No se pudo asociar el reclamo a un trabajador válido.');
        }

        // Crear el reclamo
        $reclamo = Reclamos::create([
            'id_bulto' => $request->id_bulto,
            'id_trabajador' => $trabajador->id,
            'area_id' => $request->area_id,
            'descripcion' => $request->descripcion,
            'estado' => 'pendiente',
        ]);


        



        // Obtener el área del reclamo
        $area = $reclamo->area;




        // Obtener los correos reales de los trabajadores del área
        $correosDeEnvio = $area->trabajadores
            ->map(function ($trabajador) {
                $user = $trabajador->user;
                return $user ? resolveCorreoNotificacion($user) : null;
            })
            ->filter()
            ->unique()
            // ->intersect(['eliascorrea@4nlogistica.cl'])
            ->values();

        // Loguear en modo debug
        // Log::info('Correos que se notificarían para el área: ' . $area->nombre, $correosDeEnvio->toArray());
        Log::debug('Cantidad de trabajadores en el área:', ['count' => $area->trabajadores->count()]);

        foreach ($area->trabajadores as $trabajador) {
            if ($trabajador->user) {
                Log::debug('Notificando a', ['email' => $trabajador->user->email]);
                $trabajador->user->notify(new NuevoReclamoAreaNotification($area->nombre));
            }
        }
        
        
        

        return redirect()->route('reclamos.index')->with('success', 'Reclamo enviado correctamente.');
    }

}
