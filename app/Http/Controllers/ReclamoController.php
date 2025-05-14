<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reclamos;
use App\Models\Bultos;
use App\Models\Area;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // Importamos Auth para manejar la autenticación
use App\Notifications\NuevoReclamoAreaNotification;
use App\Notifications\ReclamoCerradoNotification;
use App\Notifications\ReclamoRespondidoNotification;

class ReclamoController extends Controller
{
    // Listar los reclamos del trabajador autenticado
    public function index()
    {
        $areas = Area::all();
        $casuisticas = \App\Models\Casuistica::all();
        $correoInterno = resolvePerfilEmail(Auth::user()->email);

        $trabajador = \App\Models\Trabajador::whereHas('user', function ($q) use ($correoInterno) {
            $q->where('email', $correoInterno);
        })->first();

        if (!$trabajador) {
            return redirect()->back()->with('error', 'Trabajador no encontrado.');
        }

        // Solo trabajadores del área del usuario actual
        $trabajadores = \App\Models\Trabajador::where('area_id', $trabajador->area_id)->get();

        // Reclamos del área del usuario
        $reclamosQuery = \App\Models\Reclamos::where('estado', 'pendiente')
            ->where('area_id', $trabajador->area_id);

        // Si se aplica un filtro por trabajador específico
        if (request('trabajador_id')) {
            $reclamosQuery->where('id_trabajador', request('trabajador_id'));
        }

        $reclamos = $reclamosQuery->orderByDesc('created_at')->get();

        return view('reclamos.index', compact('reclamos', 'areas', 'casuisticas', 'trabajadores'));
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
            if (
                $trabajador->user &&
                $trabajador->user->id !== $usuarioOutlook->id
            ) {
                Log::debug('Notificando a', ['email' => $trabajador->user->email]);
                $trabajador->user->notify(new NuevoReclamoAreaNotification($area->nombre));

                // Notificar al usuario administrativo (solo si no es también el autor)
                $adminEmail = resolveAdminEmail($trabajador->user->email);
                if ($adminEmail && $adminEmail !== $usuarioOutlook->email) {
                    $adminUser = \App\Models\User::where('email', $adminEmail)->first();
                    if ($adminUser && $adminUser->id !== $usuarioOutlook->id) {
                        Log::debug('Notificando a usuario administrativo', ['email' => $adminUser->email]);
                        $adminUser->notify(new NuevoReclamoAreaNotification($area->nombre));
                    }
                }
            }
        }

        
        
        

        return redirect()->route('reclamos.index')->with('success', 'Reclamo enviado correctamente.');
    }


    public function responder(Request $request, $id)
    {
        $autorId = Auth::id();

        
        $request->validate([
            'respuesta_admin' => 'required|string|max:1000',
        ]);

        $reclamo = \App\Models\Reclamos::findOrFail($id);
        $reclamo->respuesta_admin = $request->respuesta_admin;
        $reclamo->estado = 'resuelto';
        $reclamo->save();

        $usuariosArea = \App\Models\User::whereHas('trabajador', function ($query) use ($reclamo) {
            $query->where('area_id', $reclamo->area_id);
        })->get();

        foreach ($usuariosArea as $usuario) {
            if ($usuario->id !== $autorId) {
                $usuario->notify(new ReclamoRespondidoNotification($reclamo));
            }

            $adminEmail = resolveAdminEmail($usuario->email);
            if ($adminEmail) {
                $adminUser = \App\Models\User::where('email', $adminEmail)->first();
                if ($adminUser && $adminUser->id !== $autorId) {
                    $adminUser->notify(new ReclamoRespondidoNotification($reclamo));
                }
            }
        }


        return redirect()->back()->with('success', 'Reclamo respondido correctamente.');
    }


    public function comentar(Request $request, $id)
    {
        $autorId = Auth::id();

        $request->validate([
            'comentario' => 'required|string|max:1000',
        ]);

        $reclamo = \App\Models\Reclamos::findOrFail($id);

        $nuevoComentario = \App\Models\ReclamoComentario::create([
            'reclamo_id' => $reclamo->id,
            'user_id' => Auth::id(),
            'comentario' => $request->comentario,
        ]);

        $usuariosArea = \App\Models\User::whereHas('trabajador', function ($query) use ($reclamo) {
            $query->where('area_id', $reclamo->area_id);
        })->get();

        foreach ($usuariosArea as $usuario) {
            if ($usuario->id !== $autorId) {
                $usuario->notify(new \App\Notifications\NuevoComentarioReclamoNotification($nuevoComentario));
            }

            $adminEmail = resolveAdminEmail($usuario->email);
            if ($adminEmail) {
                $adminUser = \App\Models\User::where('email', $adminEmail)->first();
                if ($adminUser && $adminUser->id !== $autorId) {
                    $adminUser->notify(new \App\Notifications\NuevoComentarioReclamoNotification($nuevoComentario));
                }
            }
        }

        return back()->with('success', 'Comentario agregado correctamente.');
    }


    public function cerrar($id)
    {
        $reclamo = \App\Models\Reclamos::findOrFail($id);
        $autorId = Auth::id();

        if ($reclamo->estado === 'cerrado') {
            return back()->with('error', 'Este reclamo está cerrado y no puede recibir más comentarios.');
        }
        

        // Obtener el correo del usuario autenticado
        $correoUsuario = Auth::user()->email;

        // Usar resolvePerfilEmail para obtener el correo interno si es necesario
        $correoInterno = resolvePerfilEmail($correoUsuario);

        // Buscar el trabajador correspondiente al correo interno
        $trabajador = \App\Models\Trabajador::whereHas('user', function ($q) use ($correoInterno) {
            $q->where('email', $correoInterno);
        })->first();

        // Verificar si ese trabajador es el creador del reclamo
        if ($trabajador && $trabajador->area_id === $reclamo->area_id) {



            $reclamo->tipo_solicitud = request('tipo_solicitud');

            if (request('tipo_solicitud') === 'reclamo') {
                $reclamo->area_id = request('area_id'); // como responsable final
                $reclamo->casuistica_id = request('casuistica_id');
            }

            $reclamo->estado = 'cerrado';
            $reclamo->save();

        
            // Crear comentario automático
            \App\Models\ReclamoComentario::create([
                'reclamo_id' => $reclamo->id,
                'user_id' => Auth::id(),
                'comentario' => '🛑 Reclamo cerrado por ' . Auth::user()->name . ' el ' . now()->format('d-m-Y') . '.',
            ]);
        
            // Notificar a usuarios del área
            $usuariosArea = \App\Models\User::whereHas('trabajador', function ($query) use ($reclamo) {
                $query->where('area_id', $reclamo->area_id);
            })->get();
        
            foreach ($usuariosArea as $usuario) {
                if ($usuario->id !== $autorId) {
                    $usuario->notify(new ReclamoCerradoNotification($reclamo));
                }

                $adminEmail = resolveAdminEmail($usuario->email);
                if ($adminEmail) {
                    $adminUser = \App\Models\User::where('email', $adminEmail)->first();
                    if ($adminUser && $adminUser->id !== $autorId) {
                        $adminUser->notify(new ReclamoCerradoNotification($reclamo));
                    }
                }
            }
        
            return back()->with('success', 'Reclamo cerrado correctamente.');
        }
        

        return back()->with('error', 'No tienes permiso para cerrar este reclamo.');
    }


    // ReclamoController.php
    public function verReclamo($id)
    {
        $reclamo = \App\Models\Reclamos::with(['comentarios.autor', 'bulto', 'area'])->findOrFail($id);

        return view('reclamos.reclamo_individual', compact('reclamo'));
    }



    public function misReclamos()
    {
        $usuario = Auth::user();

        // Resolver correo interno si es necesario
        $correoInterno = resolvePerfilEmail($usuario->email);

        // Buscar al trabajador asociado
        $trabajador = \App\Models\Trabajador::whereHas('user', function ($q) use ($correoInterno, $usuario) {
            $q->where('email', $correoInterno)->orWhere('email', $usuario->email);
        })->first();

        if (!$trabajador) {
            return redirect('/')->with('error', 'No se pudo identificar al trabajador.');
        }

        $reclamos = \App\Models\Reclamos::where('id_trabajador', $trabajador->id)
            ->with('bulto', 'comentarios.autor', 'area')
            ->latest()
            ->get();

        return view('reclamos.mis_reclamos', compact('reclamos'));
    }










}
