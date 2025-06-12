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

        ['usuarioPerfil' => $usuarioPerfil, 'trabajador' => $trabajador] = $this->getPerfilYTrabajador();


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
        $bulto = \App\Models\Bultos::select('codigo_bulto', 'descripcion_bulto', 'peso', 'direccion')
            ->where('codigo_bulto', $codigo)
            ->first();


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

        session()->push('codigos_retiro', $codigo);



        session(['ultimo_bulto' => $bulto]);

        return back()->with('success', "Código $codigo agregado exitosamente.");
    }




    public function guardarLoteRetiro(Request $request)
    {
        $codigos = session()->get('codigos_retiro', []);

        if (empty($codigos)) {
            return back()->with('error', 'No hay códigos para registrar.');
        }

        ['usuarioPerfil' => $usuarioPerfil, 'trabajador' => $trabajador] = $this->getPerfilYTrabajador();

        // 🔁 Traer todos los códigos que ya existen en estado 'Retiro'
        $codigosExistentes = TrackingProducto::whereIn('codigo', $codigos)
            ->where('estado', 'Retiro')
            ->pluck('codigo')
            ->toArray();

        $nuevos = array_diff($codigos, $codigosExistentes);

        // 🧠 Usar insert para evitar múltiples `create()`
        $datos = [];
        foreach ($nuevos as $codigo) {
            $datos[] = [
                'codigo' => $codigo,
                'estado' => 'Retiro',
                'user_id' => $usuarioPerfil->id,
                'trabajador_id' => $trabajador->id,
                'area_id' => $trabajador->area_id,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        TrackingProducto::insert($datos);
        session()->forget('codigos_retiro');

        if (empty($nuevos)) {
            return redirect()
                ->route('tracking_productos.retiro')
                ->with('error', 'Todos los códigos escaneados ya habían sido retirados anteriormente.');
        }

        return redirect()
            ->route('tracking_productos.index')
            ->with('success', count($nuevos) . ' producto(s) registrados correctamente.');
    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////// 


    public function recepcion(Request $request)
    {
        $escaneados = session('codigos_recepcion', []);

        // 1. Códigos con estado 'Retiro' que aún no han sido recepcionados
        $codigosRetiro = TrackingProducto::where('estado', 'Retiro')
            ->pluck('codigo')
            ->toArray();

        $codigosRecepcionados = TrackingProducto::where('estado', 'Recepcionado')
            ->pluck('codigo')
            ->toArray();

        // 2. Eliminar duplicados y escaneados en esta sesión
        $codigosPendientes = array_diff($codigosRetiro, $codigosRecepcionados, $escaneados);

        $razonSocial = $request->get('razon_social');

        

        // 3. Traer solo bultos que existan
        $bultos = \App\Models\Bultos::whereIn('codigo_bulto', $codigosPendientes)
            ->when($razonSocial, function ($query) use ($razonSocial) {
                $query->where('razon_social', 'like', '%' . $razonSocial . '%');
            })
            ->select('codigo_bulto', 'descripcion_bulto', 'peso', 'direccion', 'numero_destino', 'referencia', 'razon_social')
            ->get()
            ->keyBy('codigo_bulto');

        $razonesSociales = $bultos->pluck('razon_social')->filter()->unique()->sort()->values();


        $pendientes = [];

        foreach ($codigosPendientes as $codigo) {
            if (!isset($bultos[$codigo])) {
                continue;
            }

            $bulto = $bultos[$codigo];

            $registroRetiro = TrackingProducto::where('codigo', $codigo)
                ->where('estado', 'Retiro')
                ->latest()
                ->first();

            $trabajador = $registroRetiro?->trabajador?->Nombre . ' ' . $registroRetiro?->trabajador?->ApellidoPaterno ?? '—';

            $pendientes[] = [
                'codigo' => $bulto->codigo_bulto,
                'nombre' => $bulto->descripcion_bulto,
                'peso' => $bulto->peso,
                'direccion' => $bulto->direccion,
                'usuario' => $trabajador
            ];
        }

        if (empty($escaneados)) {
            session()->forget('ultimo_bulto');
        }

        $choferes = \App\Models\Trabajador::where('area_id', 5)->get();

        return view('tracking_productos.recepcion', compact('pendientes', 'escaneados', 'choferes', 'razonSocial','razonesSociales'));
    }





    public function agregarCodigoRecepcion(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string'
        ]);

        $codigo = $request->codigo;

        // Validar existencia en bultos
        $bulto = \App\Models\Bultos::select('codigo_bulto', 'descripcion_bulto', 'peso', 'direccion')
            ->where('codigo_bulto', $codigo)
            ->first();

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
        if (in_array($codigo, $escaneados)) {
            return back()->with('error', "El código $codigo ya fue escaneado.");
        }

        session()->push('codigos_recepcion', $codigo);
        session(['ultimo_bulto' => $bulto]);
        return back()->with('success', "Código $codigo agregado exitosamente.");


       
    }



    public function guardarRecepcion(Request $request)
    {
        $codigos = session('codigos_recepcion', []);

        if (empty($codigos)) {
            return back()->with('error', 'No hay códigos para registrar.');
        }

        ['usuarioPerfil' => $usuarioPerfil, 'trabajador' => $trabajador] = $this->getPerfilYTrabajador();

        // Obtener códigos válidos en estado 'Retiro'
        $enRetiro = TrackingProducto::whereIn('codigo', $codigos)
            ->where('estado', 'Retiro')
            ->pluck('codigo')
            ->toArray();

        // Obtener códigos ya recepcionados
        $yaRecepcionados = TrackingProducto::whereIn('codigo', $codigos)
            ->where('estado', 'Recepcionado')
            ->pluck('codigo')
            ->toArray();

        // Códigos válidos para registrar como 'Recepcionado'
        $registrables = array_diff($enRetiro, $yaRecepcionados);

        // Códigos inválidos (no están en retiro)
        $rechazados = array_diff($codigos, $enRetiro);

        // Insertar en bloque
        $datos = [];
        foreach ($registrables as $codigo) {
            $datos[] = [
                'codigo' => $codigo,
                'estado' => 'Recepcionado',
                'user_id' => $usuarioPerfil->id,
                'trabajador_id' => $trabajador->id,
                'area_id' => $trabajador->area_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        TrackingProducto::insert($datos);
        session()->forget('codigos_recepcion');

        if (empty($registrables)) {
            return redirect()
                ->route('tracking_productos.recepcion')
                ->with('error', 'Todos los códigos ya fueron recepcionados anteriormente o no estaban en estado Retiro.');
        }

        $msg = count($registrables) . ' producto(s) recepcionado(s) correctamente.';

        if (!empty($rechazados)) {
            $msg .= ' Los siguientes códigos no fueron procesados porque no tienen un registro previo con estado "Retiro": ';
            $msg .= implode(', ', $rechazados);
        }

        return redirect()->route('tracking_productos.index')->with('success', $msg);
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    public function enRuta(Request $request)
    {
        ['usuarioPerfil' => $usuarioPerfil, 'trabajador' => $trabajador] = $this->getPerfilYTrabajador();
        $areaId = $trabajador->area_id;
        $escaneados = session('codigos_ruta', []);
        $razonSocial = $request->get('razon_social');

        // 1. OPERADOR: ver productos sin chofer asignado
        if ($areaId == 1) {
            $productosSinChofer = TrackingProducto::where('estado', 'Recepcionado')
                ->whereNull('chofer_id')
                ->get();

            $codigos = $productosSinChofer->pluck('codigo')->toArray();

            $bultos = \App\Models\Bultos::whereIn('codigo_bulto', $codigos)
                ->when($razonSocial, function ($query) use ($razonSocial) {
                    $query->where('razon_social', 'like', '%' . $razonSocial . '%');
                })
                ->select('codigo_bulto', 'descripcion_bulto', 'peso', 'direccion', 'razon_social')
                ->get()
                ->keyBy('codigo_bulto');

            $razonesSociales = $bultos->pluck('razon_social')->filter()->unique()->sort()->values();

            $pendientesAsignacion = [];

            foreach ($productosSinChofer as $item) {
                if (!isset($bultos[$item->codigo])) continue;

                $bulto = $bultos[$item->codigo];
                $recepcionadoPor = $item->trabajador?->Nombre . ' ' . $item->trabajador?->ApellidoPaterno ?? '—';

                $pendientesAsignacion[] = [
                    'codigo' => $item->codigo,
                    'nombre' => $bulto->descripcion_bulto,
                    'peso' => $bulto->peso,
                    'direccion' => $bulto->direccion,
                    'usuario' => $recepcionadoPor,
                ];
            }

            $choferes = \App\Models\Trabajador::where('area_id', 5)->get();

            return view('tracking_productos.en_ruta', compact(
                'areaId',
                'choferes',
                'pendientesAsignacion',
                'razonSocial',
                'razonesSociales'
            ));
        }

        // 2. CHOFER: ver productos asignados que aún no estén En Ruta
        $recepcionados = TrackingProducto::where('estado', 'Recepcionado')
            ->where('chofer_id', $trabajador->id)
            ->pluck('codigo')
            ->toArray();

        $yaEnRuta = TrackingProducto::where('estado', 'En Ruta')
            ->pluck('codigo')
            ->toArray();

        $codigosPendientes = array_diff($recepcionados, $yaEnRuta, $escaneados);

        $bultos = \App\Models\Bultos::whereIn('codigo_bulto', $codigosPendientes)
            ->when($razonSocial, function ($query) use ($razonSocial) {
                $query->where('razon_social', 'like', '%' . $razonSocial . '%');
            })
            ->select('codigo_bulto', 'descripcion_bulto', 'peso', 'direccion', 'razon_social')
            ->get()
            ->keyBy('codigo_bulto');

        $razonesSociales = $bultos->pluck('razon_social')->filter()->unique()->sort()->values();

        $pendientes = [];

        foreach ($codigosPendientes as $codigo) {
            if (!isset($bultos[$codigo])) continue;

            $bulto = $bultos[$codigo];

            $registroRecepcion = TrackingProducto::where('codigo', $codigo)
                ->where('estado', 'Recepcionado')
                ->latest()
                ->first();

            $trabajadorNombre = $registroRecepcion?->trabajador?->Nombre . ' ' . $registroRecepcion?->trabajador?->ApellidoPaterno ?? '—';

            $pendientes[] = [
                'codigo' => $bulto->codigo_bulto,
                'nombre' => $bulto->descripcion_bulto,
                'peso' => $bulto->peso,
                'direccion' => $bulto->direccion,
                'usuario' => $trabajadorNombre
            ];
        }

        if (empty($escaneados)) {
            session()->forget('ultimo_bulto');
        }

        return view('tracking_productos.en_ruta', compact(
            'areaId',
            'pendientes',
            'escaneados',
            'razonSocial',
            'razonesSociales'
        ));
    }





    public function agregarCodigoRuta(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string'
        ]);

        $codigo = $request->codigo;

        // Verificar que exista en la tabla de bultos
        $bulto = \App\Models\Bultos::select('codigo_bulto', 'descripcion_bulto', 'peso', 'direccion')
            ->where('codigo_bulto', $codigo)
            ->first();



        if (!$bulto) {
            return back()->with('error', "El código $codigo no existe en la tabla de bultos.");
        }

        // Verificar que esté recepcionado y asignado al chofer actual
        ['usuarioPerfil' => $usuarioPerfil, 'trabajador' => $trabajador] = $this->getPerfilYTrabajador();


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

        $escaneados = session()->get('codigos_ruta', []);
        if (in_array($codigo, $escaneados)) {
            return back()->with('error', "El código $codigo ya fue escaneado.");
        }
        session()->push('codigos_ruta', $codigo);



        session(['ultimo_bulto' => $bulto]);

        return back()->with('success', "Código $codigo agregado exitosamente.");
    }


    public function guardarRuta(Request $request)
    {
        $codigos = session('codigos_ruta', []);

        if (empty($codigos)) {
            return back()->with('error', 'No hay códigos para registrar.');
        }

        ['usuarioPerfil' => $usuarioPerfil, 'trabajador' => $trabajador] = $this->getPerfilYTrabajador();

        // 🧠 Filtrar los códigos válidos que estén en estado 'Recepcionado' y asignados al chofer actual
        $recepcionadosAsignados = TrackingProducto::whereIn('codigo', $codigos)
            ->where('estado', 'Recepcionado')
            ->where('chofer_id', $trabajador->id)
            ->pluck('codigo')
            ->toArray();

        // Filtrar los que ya están marcados como 'En Ruta'
        $yaEnRuta = TrackingProducto::whereIn('codigo', $codigos)
            ->where('estado', 'En Ruta')
            ->pluck('codigo')
            ->toArray();

        // Códigos válidos para registrar como 'En Ruta'
        $registrables = array_diff($recepcionadosAsignados, $yaEnRuta);

        // Códigos rechazados (no asignados al chofer o ya procesados)
        $rechazados = array_diff($codigos, $registrables);

        // Insertar en bloque
        $datos = [];
        foreach ($registrables as $codigo) {
            $datos[] = [
                'codigo' => $codigo,
                'estado' => 'En Ruta',
                'user_id' => $usuarioPerfil->id,
                'trabajador_id' => $trabajador->id,
                'area_id' => $trabajador->area_id,
                'chofer_id' => $trabajador->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        TrackingProducto::insert($datos);

        session()->forget('codigos_ruta');
        session()->forget('ultimo_bulto');

        if (empty($registrables)) {
            return redirect()
                ->route('tracking_productos.en_ruta')
                ->with('error', 'Todos los códigos ya fueron marcados como En Ruta o no te pertenecen.');
        }

        $msg = count($registrables) . ' producto(s) marcados como En Ruta correctamente.';

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
        session()->forget('ultimo_bulto');

        ['usuarioPerfil' => $usuarioPerfil, 'trabajador' => $trabajador] = $this->getPerfilYTrabajador();

        if ($trabajador->area_id != 1) {
            abort(403, 'Solo operadores pueden asignar productos.');
        }

        $choferes = \App\Models\Trabajador::where('area_id', 5)->get();

        // Cargar productos sin chofer con relaciones ya incluidas
        $productosSinChofer = TrackingProducto::with(['bulto', 'trabajador'])
            ->where('estado', 'Recepcionado')
            ->whereNull('chofer_id')
            ->get();

        $pendientesAsignacion = [];

        foreach ($productosSinChofer as $item) {
            if (!$item->bulto) {
                continue; // evitar productos sin bulto asociado
            }

            $pendientesAsignacion[] = [
                'codigo' => $item->codigo,
                'nombre' => $item->bulto->descripcion_bulto ?? '—',
                'peso' => $item->bulto->peso ?? '—',
                'dimensiones' => $item->bulto->referencia ?? '—',
                'usuario' => $item->trabajador?->Nombre . ' ' . $item->trabajador?->ApellidoPaterno ?? '—',
            ];
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
        $bulto = \App\Models\Bultos::select('codigo_bulto', 'descripcion_bulto', 'peso', 'direccion', 'referencia')
            ->where('codigo_bulto', $codigo)
            ->first();



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

        $codigos = session()->get('codigos_asignacion', []);
        if (in_array($codigo, $codigos)) {
            return back()->with('error', "El código $codigo ya fue escaneado.");
        }

        session()->push('codigos_asignacion', $codigo);
        session(['ultimo_bulto' => $bulto]);


        return back()->with('success', "Código $codigo agregado correctamente.");
    }


    ///////////Métodos Privados/////////////////////////////////////

    private function getPerfilYTrabajador()
    {
        $user = Auth::user();
        $correoPerfil = resolvePerfilEmail($user->email);
        $usuarioPerfil = \App\Models\User::where('email', $correoPerfil)->firstOrFail();
        $trabajador = \App\Models\Trabajador::where('user_id', $usuarioPerfil->id)->firstOrFail();

        return compact('usuarioPerfil', 'trabajador');
    }


    















}
