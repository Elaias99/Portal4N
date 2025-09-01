<h2>🎉 Empleados que cumplen años hoy</h2>

<ul>
@foreach ($cumpleanieros as $empleado)
    <li>{{ $empleado->Nombre }} {{ $empleado->ApellidoPaterno }} ({{ $empleado->FechaNacimiento->format('d/m') }})</li>
@endforeach
</ul>
