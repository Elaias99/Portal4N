<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Trabajador;
use App\Models\TrackingProducto;

class TrackingProductoController extends Controller
{

    public function index()
    {
        $user = Auth::user();

        // Resolver correo de perfil (si viene desde cuenta admin)
        $correoPerfil = resolvePerfilEmail($user->email);

        // Buscar el usuario real (de perfil)
        $usuarioPerfil = \App\Models\User::where('email', $correoPerfil)->first();

        if (!$usuarioPerfil) {
            abort(403, 'No se encontró un usuario asociado al correo corporativo.');
        }

        // Buscar el trabajador por user_id
        $trabajador = \App\Models\Trabajador::where('user_id', $usuarioPerfil->id)->first();

        if (!$trabajador) {
            abort(403, 'No se encontró un perfil de trabajador asociado a este usuario.');
        }

        $areaId = $trabajador->area_id;

        return view('tracking_productos.index', compact('areaId'));
    }

    public function retiro()
    {
        $escaneados = session('codigos_retiro', []);

        // Limpiar la sesión si no hay escaneos activos
        if (empty($escaneados)) {
            session()->forget('ultimo_bulto');

        }

        return view('tracking_productos.retiro');
    }

    public function guardarRetiro(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string'
        ]);

        $user = Auth::user();
        $correoPerfil = resolvePerfilEmail($user->email);
        $usuarioPerfil = \App\Models\User::where('email', $correoPerfil)->firstOrFail();
        $trabajador = \App\Models\Trabajador::where('user_id', $usuarioPerfil->id)->firstOrFail();

        TrackingProducto::create([
            'codigo' => $request->codigo,
            'estado' => 'Retiro',
            'user_id' => $usuarioPerfil->id,
            'trabajador_id' => $trabajador->id,
            'area_id' => $trabajador->area_id,
        ]);

        return back()->with('success', 'Producto registrado como Retiro.');
    }

    public function agregarCodigo(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string'
        ]);

        $codigo = $request->codigo;

        // Validar si existe en bultos
        $bulto = \App\Models\Bultos::where('codigo_bulto', $codigo)->first();

        if (!$bulto) {
            return back()->with('error', "El código $codigo no existe en la base de bultos.");
        }

        // Validar si ya fue retirado anteriormente
        $yaRetirado = TrackingProducto::where('codigo', $codigo)
            ->where('estado', 'Retiro')
            ->exists();

        if ($yaRetirado) {
            return back()->with('error', "El código $codigo ya fue retirado anteriormente.");
        }

        // Verificar si ya está escaneado en la sesión
        $escaneados = session()->get('codigos_retiro', []);

        if (in_array($codigo, $escaneados)) {
            return back()->with('error', "El código $codigo ya fue escaneado.");
        }

        // Agregar a sesión
        $escaneados[] = $codigo;
        session(['codigos_retiro' => $escaneados]);
        session(['ultimo_bulto' => $bulto]);

        return back()->with('success', "Código $codigo agregado exitosamente.");
    }




    public function guardarLoteRetiro(Request $request)
    {
        $codigos = session()->get('codigos_retiro', []);

        if (empty($codigos)) {
            return back()->with('error', 'No hay códigos para registrar.');
        }

        $user = Auth::user();
        $correoPerfil = resolvePerfilEmail($user->email);
        $usuarioPerfil = \App\Models\User::where('email', $correoPerfil)->firstOrFail();
        $trabajador = \App\Models\Trabajador::where('user_id', $usuarioPerfil->id)->firstOrFail();

        $guardados = 0;

        foreach ($codigos as $codigo) {
            $yaExiste = TrackingProducto::where('codigo', $codigo)
                ->where('estado', 'Retiro')
                ->exists();

            if (!$yaExiste) {
                TrackingProducto::create([
                    'codigo' => $codigo,
                    'estado' => 'Retiro',
                    'user_id' => $usuarioPerfil->id,
                    'trabajador_id' => $trabajador->id,
                    'area_id' => $trabajador->area_id,
                ]);
                $guardados++;
            }
        }

        session()->forget('codigos_retiro');

        if ($guardados === 0) {
            return redirect()
                ->route('tracking_productos.retiro')
                ->with('error', 'Todos los códigos escaneados ya habían sido retirados anteriormente.');
        }

        return redirect()
            ->route('tracking_productos.index')
            ->with('success', "$guardados producto(s) registrados correctamente.");

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////// 


    public function recepcion()
    {
        $escaneados = session('codigos_recepcion', []);

        // Obtener todos los códigos en estado 'Retiro' que no han sido recepcionados aún
        $codigosPendientes = TrackingProducto::select('codigo')
            ->where('estado', 'Retiro')
            ->get()
            ->pluck('codigo')
            ->filter(function ($codigo) {
                return !TrackingProducto::where('codigo', $codigo)
                    ->where('estado', 'Recepcionado')
                    ->exists();
            })
            ->values()
            ->toArray();

        // Excluir los que ya se están escaneando en esta sesión
        $codigosPendientes = array_diff($codigosPendientes, $escaneados);

        $pendientes = [];

        foreach ($codigosPendientes as $codigo) {
            $bulto = \App\Models\Bultos::where('codigo_bulto', $codigo)->first();

            $registroRetiro = TrackingProducto::where('codigo', $codigo)
                ->where('estado', 'Retiro')
                ->latest()
                ->first();

            $trabajador = $registroRetiro?->trabajador?->Nombre . ' ' . $registroRetiro?->trabajador?->ApellidoPaterno ?? '—';

            if ($bulto) {
                $pendientes[] = [
                    'codigo' => $bulto->codigo_bulto,
                    'nombre' => $bulto->descripcion_bulto,
                    'peso' => $bulto->peso,
                    'direccion' => $bulto->direccion,
                    'usuario' => $trabajador
                ];
            }
        }

        // Limpiar tarjeta visual si no se ha escaneado nada
        if (empty($escaneados)) {
            session()->forget('ultimo_bulto');
        }

        $choferes = \App\Models\Trabajador::where('area_id', 5)->get();

        return view('tracking_productos.recepcion', compact('pendientes', 'escaneados', 'choferes'));
    }




    public function agregarCodigoRecepcion(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string'
        ]);

        $codigo = $request->codigo;

        // Validar existencia en bultos
        $bulto = \App\Models\Bultos::where('codigo_bulto', $codigo)->first();
        if (!$bulto) {
            return back()->with('error', "El código $codigo no existe en la base de bultos.");
        }

        // Validar existencia previa en estado 'Retiro'
        $existeEnRetiro = TrackingProducto::where('codigo', $codigo)
            ->where('estado', 'Retiro')
            ->exists();

        if (!$existeEnRetiro) {
            return back()->with('error', "El código $codigo no tiene un registro previo en estado 'Retiro'.");
        }

        // Validar que aún no esté recepcionado
        $yaRecepcionado = TrackingProducto::where('codigo', $codigo)
            ->where('estado', 'Recepcionado')
            ->exists();

        if ($yaRecepcionado) {
            return back()->with('error', "El código $codigo ya fue recepcionado anteriormente.");
        }

        // Verificar que no esté repetido en sesión
        $escaneados = session()->get('codigos_recepcion', []);

        if (!in_array($codigo, $escaneados)) {
            $escaneados[] = $codigo;
            session(['codigos_recepcion' => $escaneados]);
            session(['ultimo_bulto' => $bulto]);

            return back()->with('success', "Código $codigo agregado exitosamente.");
        }

        return back()->with('error', "El código $codigo ya fue escaneado.");
    }



    public function guardarRecepcion(Request $request)
    {
        $codigos = session('codigos_recepcion', []);

        if (empty($codigos)) {
            return back()->with('error', 'No hay códigos para registrar.');
        }

        $user = Auth::user();
        $correoPerfil = resolvePerfilEmail($user->email);
        $usuarioPerfil = \App\Models\User::where('email', $correoPerfil)->firstOrFail();
        $trabajador = \App\Models\Trabajador::where('user_id', $usuarioPerfil->id)->firstOrFail();

        $guardados = 0;
        $rechazados = [];

        foreach ($codigos as $codigo) {
            $existeEnRetiro = TrackingProducto::where('codigo', $codigo)
                ->where('estado', 'Retiro')
                ->exists();

            if (!$existeEnRetiro) {
                $rechazados[] = $codigo;
                continue;
            }

            $yaRecepcionado = TrackingProducto::where('codigo', $codigo)
                ->where('estado', 'Recepcionado')
                ->exists();

            if (!$yaRecepcionado) {
                TrackingProducto::create([
                    'codigo' => $codigo,
                    'estado' => 'Recepcionado',
                    'user_id' => $usuarioPerfil->id,
                    'trabajador_id' => $trabajador->id,
                    'area_id' => $trabajador->area_id,
                    // 'chofer_id' => $choferAsignado,
                ]);
                $guardados++;
            }
        }


        session()->forget('codigos_recepcion');

        if ($guardados === 0) {
            return redirect()
                ->route('tracking_productos.recepcion')
                ->with('error', 'Todos los códigos ya fueron recepcionados anteriormente o no estaban en estado Retiro.');
        }

        $msg = "$guardados producto(s) recepcionado(s) correctamente.";

        if (!empty($rechazados)) {
            $msg .= ' Los siguientes códigos no fueron procesados porque no tienen un registro previo con estado "Retiro": ';
            $msg .= implode(', ', $rechazados);
        }

        return redirect()->route('tracking_productos.index')->with('success', $msg);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    public function enRuta()
    {
        $user = Auth::user();
        $correoPerfil = resolvePerfilEmail($user->email);
        $usuarioPerfil = \App\Models\User::where('email', $correoPerfil)->firstOrFail();
        $trabajador = \App\Models\Trabajador::where('user_id', $usuarioPerfil->id)->firstOrFail();
        $areaId = $trabajador->area_id;

        $escaneados = session('codigos_ruta', []);

        // 1. Si es OPERADOR
        if ($areaId == 1) {
            $productosSinChofer = TrackingProducto::where('estado', 'Recepcionado')
                ->whereNull('chofer_id')
                ->get();

            $pendientesAsignacion = [];

            foreach ($productosSinChofer as $item) {
                $bulto = \App\Models\Bultos::where('codigo_bulto', $item->codigo)->first();
                $recepcionadoPor = $item->trabajador?->Nombre . ' ' . $item->trabajador?->ApellidoPaterno ?? '—';

                if ($bulto) {
                    $pendientesAsignacion[] = [
                        'codigo' => $item->codigo,
                        'nombre' => $bulto->descripcion_bulto,
                        'peso' => $bulto->peso,
                        'direccion' => $bulto->direccion,
                        'usuario' => $recepcionadoPor,
                    ];
                }
            }

            $choferes = \App\Models\Trabajador::where('area_id', 5)->get();
            return view('tracking_productos.en_ruta', compact('areaId', 'choferes', 'pendientesAsignacion'));
        }

        // 2. Si es CHOFER (área 5)
        $codigosPendientes = TrackingProducto::where('estado', 'Recepcionado')
            ->where('chofer_id', $trabajador->id)
            ->get()
            ->pluck('codigo')
            ->filter(function ($codigo) {
                return !TrackingProducto::where('codigo', $codigo)
                    ->where('estado', 'En Ruta')
                    ->exists();
            })
            ->values()
            ->toArray();

        $codigosPendientes = array_diff($codigosPendientes, $escaneados);

        $pendientes = [];
        foreach ($codigosPendientes as $codigo) {
            $bulto = \App\Models\Bultos::where('codigo_bulto', $codigo)->first();

            $registroRecepcion = TrackingProducto::where('codigo', $codigo)
                ->where('estado', 'Recepcionado')
                ->latest()
                ->first();

            $trabajadorNombre = $registroRecepcion?->trabajador?->Nombre . ' ' . $registroRecepcion?->trabajador?->ApellidoPaterno ?? '—';

            if ($bulto) {
                $pendientes[] = [
                    'codigo' => $bulto->codigo_bulto,
                    'nombre' => $bulto->descripcion_bulto,
                    'peso' => $bulto->peso,
                    'direccion' => $bulto->direccion,
                    'usuario' => $trabajadorNombre
                ];
            }
        }

        if (empty($escaneados)) {
            session()->forget('ultimo_bulto');
        }

        return view('tracking_productos.en_ruta', compact('areaId', 'pendientes', 'escaneados'));
    }


    public function agregarCodigoRuta(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string'
        ]);

        $codigo = $request->codigo;

        // Verificar que exista en la tabla de bultos
        $bulto = \App\Models\Bultos::where('codigo_bulto', $codigo)->first();
        if (!$bulto) {
            return back()->with('error', "El código $codigo no existe en la tabla de bultos.");
        }

        // Verificar que esté recepcionado y asignado al chofer actual
        $user = Auth::user();
        $correoPerfil = resolvePerfilEmail($user->email);
        $usuarioPerfil = \App\Models\User::where('email', $correoPerfil)->firstOrFail();
        $trabajador = \App\Models\Trabajador::where('user_id', $usuarioPerfil->id)->firstOrFail();

        $existeEnRecepcionado = \App\Models\TrackingProducto::where('codigo', $codigo)
            ->where('estado', 'Recepcionado')
            ->where('chofer_id', $trabajador->id)
            ->exists();

        if (!$existeEnRecepcionado) {
            return back()->with('error', "El código $codigo no tiene estado 'Recepcionado' o no te fue asignado.");
        }

        // Verificar si ya fue marcado como "En Ruta"
        $yaEnRuta = \App\Models\TrackingProducto::where('codigo', $codigo)
            ->where('estado', 'En Ruta')
            ->exists();

        if ($yaEnRuta) {
            return back()->with('error', "El código $codigo ya fue marcado como En Ruta anteriormente.");
        }

        // Verificar si ya está escaneado en sesión
        $escaneados = session()->get('codigos_ruta', []);
        if (in_array($codigo, $escaneados)) {
            return back()->with('error', "El código $codigo ya fue escaneado.");
        }

        $escaneados[] = $codigo;
        session(['codigos_ruta' => $escaneados]);
        session(['ultimo_bulto' => $bulto]);

        return back()->with('success', "Código $codigo agregado exitosamente.");
    }


    public function guardarRuta(Request $request)
    {
        $codigos = session('codigos_ruta', []);

        if (empty($codigos)) {
            return back()->with('error', 'No hay códigos para registrar.');
        }

        $user = Auth::user();
        $correoPerfil = resolvePerfilEmail($user->email);
        $usuarioPerfil = \App\Models\User::where('email', $correoPerfil)->firstOrFail();
        $trabajador = \App\Models\Trabajador::where('user_id', $usuarioPerfil->id)->firstOrFail();

        $guardados = 0;
        $rechazados = [];

        foreach ($codigos as $codigo) {
            $recepcionado = TrackingProducto::where('codigo', $codigo)
                ->where('estado', 'Recepcionado')
                ->where('chofer_id', $trabajador->id)
                ->first();

            if (!$recepcionado) {
                $rechazados[] = $codigo;
                continue;
            }

            $yaEnRuta = TrackingProducto::where('codigo', $codigo)
                ->where('estado', 'En Ruta')
                ->exists();

            if (!$yaEnRuta) {
                TrackingProducto::create([
                    'codigo' => $codigo,
                    'estado' => 'En Ruta',
                    'user_id' => $usuarioPerfil->id,
                    'trabajador_id' => $trabajador->id,
                    'area_id' => $trabajador->area_id,
                    'chofer_id' => $trabajador->id,
                ]);
                $guardados++;
            }
        }

        session()->forget('codigos_ruta');
        session()->forget('ultimo_bulto'); // ← limpieza adicional

        if ($guardados === 0) {
            return redirect()
                ->route('tracking_productos.en_ruta')
                ->with('error', 'Todos los códigos ya fueron marcados como En Ruta o no pertenecen a este chofer.');
        }

        $msg = "$guardados producto(s) marcados como En Ruta correctamente.";

        if (!empty($rechazados)) {
            $msg .= ' Algunos códigos no fueron procesados porque no te pertenecen o ya fueron procesados: ';
            $msg .= implode(', ', $rechazados);
        }

        return redirect()->route('tracking_productos.index')->with('success', $msg);
    }






    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// 

    public function asignarChofer(Request $request)
    {
        $request->validate([
            'chofer_id' => 'required|exists:trabajadors,id',
        ]);

        $choferId = $request->chofer_id;

        // Buscar códigos recepcionados sin chofer asignado
        $productos = TrackingProducto::where('estado', 'Recepcionado')
            ->whereNull('chofer_id')
            ->get();

        if ($productos->isEmpty()) {
            return back()->with('error', 'No hay productos recepcionados sin chofer asignado.');
        }

        foreach ($productos as $producto) {
            $producto->chofer_id = $choferId;
            $producto->save();
        }

        return redirect()->route('tracking_productos.index')->with('success', 'Productos asignados correctamente al chofer.');
    }


    public function asignarIndividual()
    {
        session()->forget('ultimo_bulto'); // ← cambiamos nombre para consistencia

        $user = Auth::user();
        $correoPerfil = resolvePerfilEmail($user->email);
        $usuarioPerfil = \App\Models\User::where('email', $correoPerfil)->firstOrFail();
        $trabajador = \App\Models\Trabajador::where('user_id', $usuarioPerfil->id)->firstOrFail();

        // Seguridad adicional: solo operadores pueden acceder
        if ($trabajador->area_id != 1) {
            abort(403, 'Solo operadores pueden asignar productos.');
        }

        $choferes = \App\Models\Trabajador::where('area_id', 5)->get();

        $productosSinChofer = TrackingProducto::where('estado', 'Recepcionado')
            ->whereNull('chofer_id')
            ->get();

        $pendientesAsignacion = [];

        foreach ($productosSinChofer as $item) {
            $bulto = $item->bulto; // ← gracias a la relación hasOne()

            $recepcionadoPor = $item->trabajador?->Nombre . ' ' . $item->trabajador?->ApellidoPaterno ?? '—';

            if ($bulto) {
                $pendientesAsignacion[] = [
                    'codigo' => $item->codigo,
                    'nombre' => $bulto->descripcion_bulto ?? '—',
                    'peso' => $bulto->peso ?? '—',
                    'dimensiones' => $bulto->referencia ?? '—', // o puedes combinar otras columnas si quieres formato WxHxD
                    'usuario' => $recepcionadoPor,
                ];
            }
        }

        return view('tracking_productos.asignar', compact('choferes', 'pendientesAsignacion'));
    }


    public function asignarSeleccionados(Request $request)
    {
        $request->validate([
            'chofer_id' => 'required|exists:trabajadors,id',
        ]);

        $choferId = $request->chofer_id;

        // Obtener códigos desde la sesión
        $codigos = session('codigos_asignacion', []);

        if (empty($codigos)) {
            return back()->with('error', 'No hay códigos escaneados para asignar.');
        }

        $productos = TrackingProducto::whereIn('codigo', $codigos)
            ->where('estado', 'Recepcionado')
            ->whereNull('chofer_id')
            ->get();

        if ($productos->isEmpty()) {
            return back()->with('error', 'Ningún producto válido para asignar.');
        }

        foreach ($productos as $producto) {
            $producto->chofer_id = $choferId;
            $producto->save();
        }

        // Limpiar sesión
        session()->forget('codigos_asignacion');
        session()->forget('ultimo_bulto');

        return redirect()->route('tracking_productos.index')->with('success', 'Productos asignados correctamente.');
    }



    public function agregarCodigoAsignacion(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string'
        ]);

        $codigo = $request->codigo;

        // Validar que exista en la tabla Bultos
        $bulto = \App\Models\Bultos::where('codigo_bulto', $codigo)->first();
        if (!$bulto) {
            return back()->with('error', "El código $codigo no existe en la base de bultos.");
        }

        // Validar que esté recepcionado y sin chofer asignado
        $recepcionado = TrackingProducto::where('codigo', $codigo)
            ->where('estado', 'Recepcionado')
            ->latest()
            ->first();

        if (!$recepcionado) {
            return back()->with('error', "El código $codigo no tiene un registro en estado 'Recepcionado'.");
        }

        if (!is_null($recepcionado->chofer_id)) {
            return back()->with('error', "El código $codigo ya fue asignado a un chofer.");
        }

        // Verificar que no esté ya escaneado en esta sesión
        $codigos = session()->get('codigos_asignacion', []);
        if (in_array($codigo, $codigos)) {
            return back()->with('error', "El código $codigo ya fue escaneado.");
        }

        // Guardar en sesión
        $codigos[] = $codigo;
        session(['codigos_asignacion' => $codigos]);
        session(['ultimo_bulto' => $bulto]); // nota: cambiamos nombre de sesión para claridad

        return back()->with('success', "Código $codigo agregado correctamente.");
    }














}
