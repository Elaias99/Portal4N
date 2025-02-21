<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Redirigir después de la autenticación según el tipo de usuario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function authenticated(Request $request, $user)
    {
        // Verificamos si el usuario tiene un trabajador asociado
        if ($user->trabajador) {
            return redirect('/empleados/perfil');
        }

        // Si es administrador o usuario sin trabajador, redirigir a la página general de empleados
        return redirect('/empleados');
    }

    /**
     * Personaliza los mensajes de error cuando las credenciales fallan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        // Verificar si el correo existe
        $userExists = \App\Models\User::where('email', $request->email)->exists();

        if (!$userExists) {
            // El correo no existe
            throw ValidationException::withMessages([
                'email' => ['El correo ingresado no existe en nuestros registros.'],
            ]);
        }

        // Si el correo existe, pero la contraseña es incorrecta
        throw ValidationException::withMessages([
            'password' => ['La contraseña ingresada es incorrecta. Por favor, intenta de nuevo.'],
        ]);
    }

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}
