@extends('layouts.app')

@section('content')
<div class="container">

  {{-- Título + toolbar --}}
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h1 class="h4 mb-0">Listado de Equipos</h1>
      <small class="text-muted">
        @php
          $total = (is_object($equipos) && method_exists($equipos,'total')) ? $equipos->total() : (is_countable($equipos) ? count($equipos) : 0);
        @endphp
        Inventario general · {{ $total }} en total
      </small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('equipos.create') }}" class="btn btn-primary">
        ➕ Agregar equipo
      </a>
    </div>
  </div>

  {{-- Flash feedback --}}
  @if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
  @endif

  <div class="table-responsive">
    <table id="equiposTable" class="table table-striped align-middle table-hover">
      <thead class="position-sticky top-0 bg-white">
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Tipo</th>
          <th scope="col">Marca</th>
          <th scope="col">Modelo</th>
          <th scope="col">Ubicación</th>
          <th scope="col">Usuario Asignado</th>
          <th scope="col">Estado</th>
          <th scope="col" class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($equipos as $equipo)
          <tr>
            <td>{{ $equipo->id }}</td>
            <td class="text-nowrap">{{ $equipo->tipo }}</td>
            <td class="text-nowrap">{{ $equipo->marca }}</td>
            <td class="text-nowrap">{{ $equipo->modelo }}</td>
            <td class="text-nowrap">{{ $equipo->ubicacion }}</td>
            <td class="text-nowrap">{{ $equipo->usuario_asignado }}</td>
            <td>
              @php
                $map = [
                  'activo' => ['txt'=>'Operativo','class'=>'bg-success'],
                  'en funcionamiento' => ['txt'=>'Operativo','class'=>'bg-success'],
                  'en reparación' => ['txt'=>'En reparación','class'=>'bg-warning text-dark'],
                  'dado de baja' => ['txt'=>'Baja','class'=>'bg-secondary'],
                ];
                $key = strtolower(trim($equipo->estado));
                $badge = $map[$key] ?? ['txt'=>$equipo->estado,'class'=>'bg-info'];
              @endphp
              <span class="badge {{ $badge['class'] }}">
                <span class="visually-hidden">Estado: </span>{{ $badge['txt'] }}
              </span>
            </td>
            <td class="text-end">
              <div class="btn-group" role="group" aria-label="Acciones">
                <a href="{{ route('equipos.show', $equipo) }}"
                   class="btn btn-sm btn-outline-secondary"
                   aria-label="Ver equipo {{ $equipo->id }}">Ver</a>

                <a href="{{ route('equipos.edit', $equipo) }}"
                   class="btn btn-sm btn-outline-secondary"
                   aria-label="Editar equipo {{ $equipo->id }}">Editar</a>

                <form action="{{ route('equipos.destroy', $equipo) }}"
                      method="POST"
                      onsubmit="return confirm('¿Seguro que quieres eliminar este equipo?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    Eliminar
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center py-4">
              No hay equipos registrados.
              <div class="mt-2">
                <a href="{{ route('equipos.create') }}" class="btn btn-sm btn-primary">Crear primer equipo</a>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

</div>
@endsection
