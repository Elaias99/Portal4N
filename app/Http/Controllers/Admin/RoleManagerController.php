<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;


class RoleManagerController extends Controller
{
    // Muestra todos los usuarios y sus roles
    public function index()
    {
        $users = User::with('roles')->get();
        $roles = Role::all();

        return view('admin.roles.index', compact('users', 'roles'));
    }

    public function assign(Request $request, User $user)
    {
        // Validar que se haya enviado un rol
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        // Remover roles anteriores y asignar el nuevo
        $user->syncRoles([$request->role]);

        return redirect()->back()->with('success', 'Rol actualizado correctamente.');
    }

    public function correspondencias()
    {
        // Obtener el listado dinámico desde el helper
        $correspondencias = collect(getAdminPerfilMappings());

        return view('admin.correspondencias.index', compact('correspondencias'));
    }




    


    

}



