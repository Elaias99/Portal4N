@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">Correspondencias Administrativas (Admin ↔ Perfil)</h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Correo Administrador</th>
                        <th>Correo Perfil Interno</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($correspondencias as $admin => $perfil)
                        <tr>
                            <td>{{ $admin }}</td>
                            <td>{{ $perfil }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center">No hay correspondencias definidas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
