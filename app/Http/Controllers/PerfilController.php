<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


class PerfilController extends Controller
{

    public function edit()
    {
        // Obtener el trabajador autenticado
        $user = Auth::user();
        $trabajador = $user->trabajador;

        // Asegurarse de que el usuario tiene un trabajador asociado
        if (!$trabajador) {
            return redirect('/')->with('error', 'No tienes un perfil de empleado asociado.');
        }

        // Retornar la vista de edición del perfil con los datos del trabajador
        return view('perfiles.editar', compact('trabajador'));
    }

    public function update(Request $request)
    {
        // Validar los datos que llegan del formulario
        $request->validate([
            'Nombre' => 'required|string|max:255',
            'ApellidoPaterno' => 'required|string|max:255',
            'numero_celular' => 'nullable|string|max:20',
            'nombre_emergencia' => 'nullable|string|max:255',
            'contacto_emergencia' => 'nullable|string|max:20',
        ]);

        // Obtener el trabajador autenticado
        $user = Auth::user();
        $trabajador = $user->trabajador;

        // Actualizar los datos del trabajador con los datos validados
        $trabajador->update($request->all());

        // Redirigir de vuelta al perfil con un mensaje de éxito
        return redirect()->route('empleados.perfil')->with('success', 'Perfil actualizado con éxito.');
    }


    public function verSolicitudes() //tiene como objetivo principal mostrar al empleado todas las solicitudes que ha realizado
    {
        // Obtener las solicitudes del empleado actual
        $solicitudes = Auth::user()->trabajador->solicitudes()->orderBy('created_at', 'desc')->get();

        // Retornar la vista con las solicitudes
        return view('perfiles.solicitudes', compact('solicitudes'));
    }

    public function show()
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        // Resolver el correo corporativo asociado al administrador
        $resolvedEmail = resolvePerfilEmail($user->email);

        // Buscar el trabajador asociado al correo resuelto
        $trabajador = \App\Models\Trabajador::whereHas('user', function ($query) use ($resolvedEmail) {
            $query->where('email', $resolvedEmail);
        })->first();

        // Si no se encuentra un trabajador, redirigir con un error
        if (!$trabajador) {
            return redirect('/')->with('error', 'No se pudo encontrar el perfil asociado.');
        }

        // Retornar la vista del perfil con el trabajador encontrado
        return view('perfiles.perfil', compact('trabajador'));
    }



    public function showChangePasswordForm()
    {
        // Retorna la vista del formulario de cambio de contraseña
        return view('perfiles.cambiar_contraseña');
    }

    
    public function changePassword(Request $request)
    {
        // Validación de la entrada del formulario
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|confirmed|min:8',
        ]);

        $user = Auth::user();

        // Verificar que la contraseña actual es correcta
        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->with('error', 'La contraseña actual no es correcta.');
        }

        // Actualizar la contraseña directamente en la base de datos
        DB::table('users')
            ->where('id', $user->id)
            ->update(['password' => Hash::make($request->new_password)]);

        return redirect()->route('empleados.perfil')->with('success', 'Contraseña cambiada con éxito.');
    }


    
}
