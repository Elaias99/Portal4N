@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h3 class="fw-bold text-dark">📌 Registro de Asistencia</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('asistencia.store') }}" method="POST">
        @csrf
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Asistió</th>
                </tr>
            </thead>
            <tbody>
                @foreach($empleados as $empleado)
                <tr>
                    <td>{{ $empleado->Nombre }} {{ $empleado->ApellidoPaterno }}</td>
                    <td>
                        <input type="checkbox" name="asistencias[{{ $empleado->id }}]" value="1"
                               {{ $empleado->asistencias->where('fecha', now()->toDateString())->first()?->asistio ? 'checked' : '' }}>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary">Guardar Asistencia</button>
    </form>
</div>
@endsection
