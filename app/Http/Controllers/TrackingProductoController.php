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
            session()->forget('ultimo_producto_base');
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

        // Validar si existe en producto_bases
        $existeEnBase = \App\Models\ProductoBase::where('codigo', $codigo)->exists();

        if (!$existeEnBase) {
            return back()->with('error', "El código $codigo no existe en la base de productos.");
        }

        // Validar si ya fue retirado anteriormente
        $yaRetirado = TrackingProducto::where('codigo', $codigo)
            ->where('estado', 'Retiro')
            ->exists();

        if ($yaRetirado) {
            return back()->with('error', "El código $codigo ya fue retirado anteriormente.");
        }


        $escaneados = session()->get('codigos_retiro', []);

        if (in_array($codigo, $escaneados)) {
            return back()->with('error', "El código $codigo ya fue escaneado.");
        }

        $escaneados[] = $codigo;
        session(['codigos_retiro' => $escaneados]);

        $producto = \App\Models\ProductoBase::where('codigo', $codigo)->first();
        session(['ultimo_producto_base' => $producto]);


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
            $producto = \App\Models\ProductoBase::where('codigo', $codigo)->first();

            $registroRetiro = \App\Models\TrackingProducto::where('codigo', $codigo)
                ->where('estado', 'Retiro')
                ->latest()
                ->first();

            $trabajador = $registroRetiro?->trabajador?->Nombre . ' ' . $registroRetiro?->trabajador?->ApellidoPaterno ?? '—';

            if ($producto) {
                $pendientes[] = [
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'peso' => $producto->peso,
                    'dimensiones' => "{$producto->altura} x {$producto->ancho} x {$producto->profundidad}",
                    'usuario' => $trabajador
                ];
            }
        }


        // Limpiar tarjeta visual si no se ha escaneado nada
        if (empty($escaneados)) {
            session()->forget('ultimo_producto_base');
        }

        $choferes = Trabajador::where('area_id', 5)->get();

        return view('tracking_productos.recepcion', compact('pendientes', 'escaneados', 'choferes'));
    }



    public function agregarCodigoRecepcion(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string'
        ]);

        $codigo = $request->codigo;

        // Validar existencia en producto_bases
        $producto = \App\Models\ProductoBase::where('codigo', $codigo)->first();
        if (!$producto) {
            return back()->with('error', "El código $codigo no existe en la base de productos.");
        }

        // Validar existencia previa en estado 'Retiro'
        $existeEnRetiro = \App\Models\TrackingProducto::where('codigo', $codigo)
            ->where('estado', 'Retiro')
            ->exists();

        if (!$existeEnRetiro) {
            return back()->with('error', "El código $codigo no tiene un registro previo en estado 'Retiro'.");
        }

        $yaRecepcionado = \App\Models\TrackingProducto::where('codigo', $codigo)
            ->where('estado', 'Recepcionado')
            ->exists();

        if ($yaRecepcionado) {
            return back()->with('error', "El código $codigo ya fue recepcionado anteriormente.");
        }


        $escaneados = session()->get('codigos_recepcion', []);

        if (!in_array($codigo, $escaneados)) {
            $escaneados[] = $codigo;
            session(['codigos_recepcion' => $escaneados]);
            session(['ultimo_producto_base' => $producto]);

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

        // Obtener productos recepcionados sin chofer
        $productosSinChofer = TrackingProducto::where('estado', 'Recepcionado')
            ->whereNull('chofer_id')
            ->get();

        $pendientesAsignacion = [];
        foreach ($productosSinChofer as $item) {
            $producto = \App\Models\ProductoBase::where('codigo', $item->codigo)->first();
            $recepcionadoPor = $item->trabajador?->Nombre . ' ' . $item->trabajador?->ApellidoPaterno ?? '—';

            if ($producto) {
                $pendientesAsignacion[] = [
                    'codigo' => $item->codigo,
                    'nombre' => $producto->nombre,
                    'peso' => $producto->peso,
                    'dimensiones' => "{$producto->altura} x {$producto->ancho} x {$producto->profundidad}",
                    'usuario' => $recepcionadoPor,
                ];
            }
        }

        if ($areaId == 1) {
            $choferes = \App\Models\Trabajador::where('area_id', 5)->get();
            return view('tracking_productos.en_ruta', compact('areaId', 'choferes', 'pendientesAsignacion'));
        }

        // Resto del código para choferes (área 5)
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
            $producto = \App\Models\ProductoBase::where('codigo', $codigo)->first();
            $registroRecepcion = \App\Models\TrackingProducto::where('codigo', $codigo)
                ->where('estado', 'Recepcionado')
                ->latest()
                ->first();
            $trabajadorNombre = $registroRecepcion?->trabajador?->Nombre . ' ' . $registroRecepcion?->trabajador?->ApellidoPaterno ?? '—';
            if ($producto) {
                $pendientes[] = [
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'peso' => $producto->peso,
                    'dimensiones' => "{$producto->altura} x {$producto->ancho} x {$producto->profundidad}",
                    'usuario' => $trabajadorNombre
                ];
            }
        }

        if (empty($escaneados)) {
            session()->forget('ultimo_producto_base');
        }

        return view('tracking_productos.en_ruta', compact('areaId', 'pendientes', 'escaneados'));
    }

    public function agregarCodigoRuta(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string'
        ]);

        $codigo = $request->codigo;

        // Validar existencia en producto_bases
        $producto = \App\Models\ProductoBase::where('codigo', $codigo)->first();
        if (!$producto) {
            return back()->with('error', "El código $codigo no existe en la base de productos.");
        }

        // Validar que el código ya fue recepcionado y asignado al chofer actual
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



        $yaEnRuta = \App\Models\TrackingProducto::where('codigo', $codigo)
        ->where('estado', 'En Ruta')
        ->exists();

    if ($yaEnRuta) {
        return back()->with('error', "El código $codigo ya fue marcado como En Ruta anteriormente.");
    }


        $escaneados = session()->get('codigos_ruta', []);

        if (in_array($codigo, $escaneados)) {
            return back()->with('error', "El código $codigo ya fue escaneado.");
        }

        $escaneados[] = $codigo;
        session(['codigos_ruta' => $escaneados]);
        session(['ultimo_producto_base' => $producto]);

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

        session()->forget('ultimo_producto_base'); // ← Limpieza aquí

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
            $producto = \App\Models\ProductoBase::where('codigo', $item->codigo)->first();
            $recepcionadoPor = $item->trabajador?->Nombre . ' ' . $item->trabajador?->ApellidoPaterno ?? '—';

            if ($producto) {
                $pendientesAsignacion[] = [
                    'codigo' => $item->codigo,
                    'nombre' => $producto->nombre,
                    'peso' => $producto->peso,
                    'dimensiones' => "{$producto->altura} x {$producto->ancho} x {$producto->profundidad}",
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
        session()->forget('ultimo_producto_base');

        return redirect()->route('tracking_productos.index')->with('success', 'Productos asignados correctamente.');
    }



    public function agregarCodigoAsignacion(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string'
        ]);

        $codigo = $request->codigo;

        // Verificar que exista en la base de productos
        $producto = \App\Models\ProductoBase::where('codigo', $codigo)->first();
        if (!$producto) {
            return back()->with('error', "El código $codigo no existe en la base de productos.");
        }

        // Verificar que esté recepcionado y sin chofer
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

        // Agregar a sesión
        $codigos[] = $codigo;
        session(['codigos_asignacion' => $codigos]);
        session(['ultimo_producto_base' => $producto]);

        return back()->with('success', "Código $codigo agregado correctamente.");
    }













}
