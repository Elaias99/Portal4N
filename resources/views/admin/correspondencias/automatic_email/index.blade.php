@extends('layouts.app')

@section('content')

<div class="container">

    <h1 class="mb-4">📨 Correos Automáticos</h1>

    <a href="{{ route('admin.automatic_emails.create') }}" class="btn btn-primary mb-3">
        <i class="fas fa-plus mr-1"></i> Nuevo Correo Automático
    </a>

    <div class="card shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>Nombre</th>
                            <th>Asunto</th>
                            <th>Frecuencia</th>
                            <th>Hora</th>
                            <th>Activo</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($emails as $email)
                            <tr>
                                <td>{{ $email->nombre }}</td>
                                <td>{{ $email->asunto }}</td>
                                <td>{{ ucfirst($email->tipo_frecuencia) }}</td>
                                <td>{{ $email->hora_envio ? substr($email->hora_envio, 0, 5) : '-' }}</td>

                                <td>
                                    @if($email->activo)
                                        <span class="badge badge-success">Activo</span>
                                    @else
                                        <span class="badge badge-danger">Inactivo</span>
                                    @endif
                                </td>

                                <td>
                                    <a href="{{ route('admin.automatic_emails.edit', $email->id) }}" 
                                       class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form method="POST" 
                                          action="{{ route('admin.automatic_emails.destroy', $email->id) }}" 
                                          class="d-inline"
                                          onsubmit="return confirm('¿Seguro que deseas eliminar este correo?');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    


                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    No hay correos automáticos creados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

@endsection
