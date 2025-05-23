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
        \Carbon\Carbon::setLocale('es'); // ← Esto es clave

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
            ->where(function ($query) use ($trabajador) {
                // $query->where('area_id', $trabajador->area_id)
                //     ->orWhere('id_trabajador', $trabajador->id);
            });


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
            'descripcion' => 'required|string',
            'casuistica_inicial_id' => $request->casuistica_inicial_id === 'otro'
                ? 'required'
                : 'required|exists:casuisticas,id',
            'otra_casuistica' => $request->casuistica_inicial_id === 'otro' ? 'required|string|max:255' : 'nullable',
            'importancia' => 'required|in:baja,media,alta,urgente',
            'foto' => 'nullable|image|max:2048',
        ]);


        // Paso 1: Obtener el usuario autenticado (correo real de Outlook)
        $usuarioOutlook = Auth::user();

        // Paso 2: Resolver el correo interno equivalente
        $correoInterno = resolvePerfilEmail($usuarioOutlook->email);

        // Paso 3: Buscar el usuario interno por ese correo
        $usuarioInterno = \App\Models\User::where('email', $correoInterno)->first();

        // Paso 4: Obtener el trabajador asociado a ese usuario interno
        $trabajador = $usuarioInterno?->trabajador;

        $fotoRuta = null;

        if ($request->hasFile('foto')) {
            $archivo = $request->file('foto');
            $nombre = uniqid() . '.' . $archivo->getClientOriginalExtension();

            // Detectar entorno y definir ruta de guardado
            $rutaDestino = app()->environment('production')
                ? base_path('../public_html/reclamos_fotos')
                : public_path('reclamos_fotos');

            $archivo->move($rutaDestino, $nombre);

            $fotoRuta = 'reclamos_fotos/' . $nombre; // Ruta relativa que guarda en la DB
        }

        // Validación final
        if (!$trabajador) {
            return redirect()->back()->with('error', 'No se pudo asociar el reclamo a un trabajador válido.');
        }

        $casuisticaInicialId = $request->casuistica_inicial_id;

        if ($casuisticaInicialId === 'otro') {
            $nuevaCasuistica = \App\Models\Casuistica::create([
                'nombre' => $request->otra_casuistica,
            ]);
            $casuisticaInicialId = $nuevaCasuistica->id;
        }



        // Crear el reclamo
        $reclamo = Reclamos::create([
            'id_bulto' => $request->id_bulto,
            'id_trabajador' => $trabajador->id,
            'area_id' => $request->area_id,
            'descripcion' => $request->descripcion,
            'casuistica_inicial_id' => $casuisticaInicialId,
            'estado' => 'pendiente',
            'importancia' => $request->importancia,
            'foto' => $fotoRuta,
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

        // Notificar a todos los involucrados: autor, área origen y área destino
        $usuariosParaNotificar = usuariosInvolucradosEnReclamo($reclamo);

        foreach ($usuariosParaNotificar as $usuario) {
            notificarUsuarioYAdmin($usuario, new NuevoReclamoAreaNotification($area->nombre));
        }


        return redirect()->route('reclamos.index')->with('success', 'Reclamo enviado correctamente.');
    }








    public function storeConsulta(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string',
            'area_id' => 'required|exists:areas,id',
            'casuistica_inicial_id' => $request->casuistica_inicial_id === 'otro'
                ? 'required'
                : 'required|exists:casuisticas,id',
            'otra_casuistica' => $request->casuistica_inicial_id === 'otro'
                ? 'required|string|max:255'
                : 'nullable',
            'importancia' => 'required|in:baja,media,alta,urgente',
            'foto' => 'nullable|image|max:2048',
        ]);


        $usuario = Auth::user();
        $correoInterno = resolvePerfilEmail($usuario->email);

        $trabajador = \App\Models\Trabajador::whereHas('user', function ($q) use ($correoInterno) {
            $q->where('email', $correoInterno);
        })->first();

        if (!$trabajador) {
            return redirect()->back()->with('error', 'No se pudo identificar al trabajador para asociar la consulta.');
        }

        $fotoRuta = null;

        if ($request->hasFile('foto')) {
            $archivo = $request->file('foto');
            $nombre = 'consulta_' . uniqid() . '.' . $archivo->getClientOriginalExtension();

            // Detectar entorno y definir ruta de guardado
            $rutaDestino = app()->environment('production')
                ? base_path('../public_html/reclamos_fotos')
                : public_path('reclamos_fotos');

            if (!file_exists($rutaDestino)) {
                mkdir($rutaDestino, 0755, true);
            }

            $archivo->move($rutaDestino, $nombre);
            $fotoRuta = 'reclamos_fotos/' . $nombre;
        }

        $casuisticaInicialId = $request->casuistica_inicial_id;

        if ($casuisticaInicialId === 'otro') {
            $nuevaCasuistica = \App\Models\Casuistica::create([
                'nombre' => $request->otra_casuistica,
            ]);
            $casuisticaInicialId = $nuevaCasuistica->id;
        }


        $reclamo = Reclamos::create([
            'id_bulto' => null,
            'id_trabajador' => $trabajador->id,
            'area_id' => $request->area_id,
            'descripcion' => $request->descripcion,
            'casuistica_inicial_id' => $casuisticaInicialId,
            'estado' => 'pendiente',
            'importancia' => $request->importancia,
            'tipo_solicitud' => 'consulta',
            'foto' => $fotoRuta, // ✅ guardamos la imagen
        ]);

        // Notificar a los miembros del área
        $area = $reclamo->area;

        $usuariosParaNotificar = usuariosInvolucradosEnReclamo($reclamo);

        foreach ($usuariosParaNotificar as $usuarioNotificado) {
            notificarUsuarioYAdmin($usuarioNotificado, new \App\Notifications\NuevoReclamoAreaNotification($area->nombre));
        }


        

        return redirect()->route('reclamos.index')->with('success', 'Consulta enviada correctamente.');
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


        $usuariosParaNotificar = usuariosInvolucradosEnReclamo($reclamo);

        foreach ($usuariosParaNotificar as $usuario) {
            notificarUsuarioYAdmin($usuario, new ReclamoRespondidoNotification($reclamo));
        }


        


        return redirect()->back()->with('success', 'Reclamo respondido correctamente.');
    }


    public function comentar(Request $request, $id)
    {
        $autorId = Auth::id();

        $request->validate([
            'comentario' => 'required|string|max:1000',
            'foto_comentario' => 'nullable|image|max:2048',
        ]);

        $reclamo = \App\Models\Reclamos::findOrFail($id);


        $fotoRuta = null;

        if ($request->hasFile('foto_comentario')) {
            $archivo = $request->file('foto_comentario');
            $nombre = 'comentario_' . uniqid() . '.' . $archivo->getClientOriginalExtension();

            $rutaDestino = app()->environment('production')
                ? base_path('../public_html/reclamos_comentarios_fotos')
                : public_path('reclamos_comentarios_fotos');

            if (!file_exists($rutaDestino)) {
                mkdir($rutaDestino, 0755, true);
            }

            $archivo->move($rutaDestino, $nombre);

            $fotoRuta = 'reclamos_comentarios_fotos/' . $nombre;
        }


        $nuevoComentario = \App\Models\ReclamoComentario::create([
            'reclamo_id' => $reclamo->id,
            'user_id' => Auth::id(),
            'comentario' => $request->comentario,
            'foto_comentario' => $fotoRuta,
        ]);

        $usuariosParaNotificar = usuariosInvolucradosEnReclamo($reclamo);

        foreach ($usuariosParaNotificar as $usuario) {
            notificarUsuarioYAdmin($usuario, new \App\Notifications\NuevoComentarioReclamoNotification($nuevoComentario));
        }


        return back()->with('success', 'Comentario agregado correctamente.');
    }






    public function reabrir(Request $request, $id)
    {
        $reclamo = \App\Models\Reclamos::findOrFail($id);

        if ($reclamo->estado !== 'cerrado') {
            return back()->with('error', 'Este reclamo no está cerrado.');
        }

        $correoInterno = resolvePerfilEmail(Auth::user()->email);

        $trabajador = \App\Models\Trabajador::whereHas('user', function ($q) use ($correoInterno) {
            $q->where('email', $correoInterno);
        })->first();

        if (!$trabajador || $trabajador->area_id === null) {
            return back()->with('error', 'No tienes permiso para reabrir este reclamo.');
        }

        // Validación de campos enviados desde el formulario
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'descripcion' => 'required|string',
            'casuistica_inicial_id' => $request->casuistica_inicial_id === 'otro'
                ? 'required'
                : 'required|exists:casuisticas,id',
            'otra_casuistica' => $request->casuistica_inicial_id === 'otro'
                ? 'required|string|max:255'
                : 'nullable',
            'importancia' => 'required|in:baja,media,alta,urgente',
            'foto' => 'nullable|image|max:2048',
        ]);

        $casuisticaInicialId = $request->casuistica_inicial_id;

        if ($casuisticaInicialId === 'otro') {
            $nuevaCasuistica = \App\Models\Casuistica::create([
                'nombre' => $request->otra_casuistica,
            ]);
            $casuisticaInicialId = $nuevaCasuistica->id;
        }


        $areaOrigenId = $reclamo->area_id;

        $reclamo->estado = 'pendiente';
        $reclamo->area_id = $request->input('area_id');



        $reclamo->descripcion = $request->input('descripcion');
        $reclamo->importancia = $request->importancia;
        $reclamo->casuistica_inicial_id = $casuisticaInicialId;
        $reclamo->save();

        // 1. Comentario automático de reapertura (debe aparecer primero)
        \App\Models\ReclamoComentario::create([
            'reclamo_id' => $reclamo->id,
            'user_id' => Auth::id(),
            'comentario' => '🔁 Reclamo reabierto por ' . Auth::user()->name . ' el ' . now()->format('d-m-Y H:i') . '.',
        ]);

        // 2. Guardar la imagen si se subió
        $fotoRuta = null;
        if ($request->hasFile('foto')) {
            $archivo = $request->file('foto');
            $nombre = 'reapertura_' . uniqid() . '.' . $archivo->getClientOriginalExtension();

            $rutaDestino = app()->environment('production')
                ? base_path('../public_html/reclamos_comentarios_fotos')
                : public_path('reclamos_comentarios_fotos');

            if (!file_exists($rutaDestino)) {
                mkdir($rutaDestino, 0755, true);
            }

            $archivo->move($rutaDestino, $nombre);
            $fotoRuta = 'reclamos_comentarios_fotos/' . $nombre;
        }

        // 3. Comentario del usuario con la descripción e imagen (aparece después)
        \App\Models\ReclamoComentario::create([
            'reclamo_id' => $reclamo->id,
            'user_id' => Auth::id(),
            'comentario' => '📝 ' . $request->input('descripcion'),
            'foto_comentario' => $fotoRuta,
        ]);

        $usuariosParaNotificar = usuariosInvolucradosEnReclamo($reclamo, $areaOrigenId);

        foreach ($usuariosParaNotificar as $usuario) {
            notificarUsuarioYAdmin($usuario, new \App\Notifications\ReclamoReabiertoNotification($reclamo, Auth::user()));
        }



        return back()->with('success', 'Reclamo reabierto exitosamente.');
    }





    public function cerrar($id)
    {
        $reclamo = \App\Models\Reclamos::findOrFail($id);
        $autorId = Auth::id();

        if ($reclamo->estado === 'cerrado') {
            return back()->with('error', 'Este reclamo está cerrado y no puede recibir más comentarios.');
        }

        $correoUsuario = Auth::user()->email;
        $correoInterno = resolvePerfilEmail($correoUsuario);

        $trabajador = \App\Models\Trabajador::whereHas('user', function ($q) use ($correoInterno) {
            $q->where('email', $correoInterno);
        })->first();

        if ($trabajador && $trabajador->area_id !== null) {

            request()->validate([
                'tipo_solicitud' => 'required|in:reclamo,instruccion,consulta',
                'area_id' => request('tipo_solicitud') === 'reclamo' ? 'required|exists:areas,id' : 'nullable',
                'casuistica_id' => request('casuistica_id') === 'otro' ? 'required' : 'required|exists:casuisticas,id',
                'otra_casuistica' => request('casuistica_id') === 'otro' ? 'required|string|max:255' : 'nullable',
            ]);

            $reclamo->tipo_solicitud = request('tipo_solicitud');

            if (request('tipo_solicitud') === 'reclamo') {
                $reclamo->area_id = request('area_id'); // como responsable final
            }

            // Procesar casuística personalizada si aplica
            $casuisticaId = request('casuistica_id');
            if ($casuisticaId === 'otro') {
                $nuevaCasuistica = \App\Models\Casuistica::create([
                    'nombre' => request('otra_casuistica'),
                ]);
                $casuisticaId = $nuevaCasuistica->id;
            }

            $reclamo->casuistica_id = $casuisticaId;
            $reclamo->estado = 'cerrado';
            $reclamo->save();

            \App\Models\ReclamoComentario::create([
                'reclamo_id' => $reclamo->id,
                'user_id' => $autorId,
                'comentario' => '🛑 Reclamo cerrado por ' . Auth::user()->name . ' el ' . now()->format('d-m-Y H:i') . '.',
            ]);

            $usuariosParaNotificar = usuariosInvolucradosEnReclamo($reclamo);

            foreach ($usuariosParaNotificar as $usuario) {
                notificarUsuarioYAdmin($usuario, new ReclamoCerradoNotification($reclamo, Auth::user()));
            }




            return redirect()->route('reclamos.dashboard')->with('success', 'Reclamo cerrado correctamente y redirigido al Dashboard.');
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

        $correoInterno = resolvePerfilEmail($usuario->email);

        $trabajador = \App\Models\Trabajador::whereHas('user', function ($q) use ($correoInterno, $usuario) {
            $q->where('email', $correoInterno)->orWhere('email', $usuario->email);
        })->first();

        if (!$trabajador) {
            return redirect('/')->with('error', 'No se pudo identificar al trabajador.');
        }

        $reclamosAbiertos = \App\Models\Reclamos::where('id_trabajador', $trabajador->id)
            ->where('estado', '!=', 'cerrado')
            ->with('bulto', 'comentarios.autor', 'area')
            ->latest()
            ->get();

        $reclamosCerrados = \App\Models\Reclamos::where('id_trabajador', $trabajador->id)
            ->where('estado', 'cerrado')
            ->with('bulto', 'comentarios.autor', 'area')
            ->latest()
            ->get();

        return view('reclamos.mis_reclamos', compact('reclamosAbiertos', 'reclamosCerrados'));
    }











}
