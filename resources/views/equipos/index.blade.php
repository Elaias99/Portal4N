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

  {{-- Búsqueda client-side (no toca controlador) --}}
  <div class="input-group mb-3" role="search" aria-label="Buscar en la tabla">
    <span class="input-group-text" id="searchIcon">🔎</span>
    <input id="tableSearch" type="search" class="form-control" placeholder="Buscar en la página (tipo, marca, modelo, ubicación, usuario asignado, estado)…" aria-describedby="searchIcon" autocomplete="off">
  </div>

  <div class="table-responsive">
    <table id="equiposTable" class="table table-striped align-middle table-hover">
      <thead class="position-sticky top-0 bg-white">
        <tr>
          <th scope="col" data-col="id" class="sortable" tabindex="0" role="button" aria-sort="none">ID</th>
          <th scope="col" data-col="tipo" class="sortable" tabindex="0" role="button" aria-sort="none">Tipo</th>
          <th scope="col" data-col="marca" class="sortable" tabindex="0" role="button" aria-sort="none">Marca</th>
          <th scope="col" data-col="modelo" class="sortable" tabindex="0" role="button" aria-sort="none">Modelo</th>
          <th scope="col" data-col="ubicacion" class="sortable" tabindex="0" role="button" aria-sort="none">Ubicación</th>
          <th scope="col" data-col="usuario_asignado" class="sortable" tabindex="0" role="button" aria-sort="none">Usuario Asignado</th>
          <th scope="col" data-col="estado" class="sortable" tabindex="0" role="button" aria-sort="none">Estado</th>
          <th scope="col" data-col="acciones" class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($equipos as $equipo)
          <tr>
            <td data-col="id">{{ $equipo->id }}</td>
            <td data-col="tipo" class="text-nowrap">{{ $equipo->tipo }}</td>
            <td data-col="marca" class="text-nowrap">{{ $equipo->marca }}</td>
            <td data-col="modelo" class="text-nowrap">{{ $equipo->modelo }}</td>
            <td data-col="ubicacion" class="text-nowrap">{{ $equipo->ubicacion }}</td>
            <td data-col="usuario_asignado" class="text-nowrap">{{ $equipo->usuario_asignado }}</td>
            <td data-col="estado">
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
            <td data-col="acciones" class="text-end">
              <div class="btn-group" role="group" aria-label="Acciones">
                <a href="{{ route('equipos.show', $equipo) }}"
                   class="btn btn-sm btn-outline-secondary"
                   aria-label="Ver equipo {{ $equipo->id }}">Ver</a>

                <a href="{{ route('equipos.edit', $equipo) }}"
                   class="btn btn-sm btn-outline-secondary"
                   aria-label="Editar equipo {{ $equipo->id }}">Editar</a>

                <button type="button"
                        class="btn btn-sm btn-outline-danger dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown" aria-expanded="false"
                        aria-label="Más acciones">
                  <span class="visually-hidden">Más</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <form action="{{ route('equipos.destroy', $equipo) }}"
                          method="POST"
                          onsubmit="return confirm('¿Seguro que quieres eliminar este equipo?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="dropdown-item text-danger">
                        Eliminar
                      </button>
                    </form>
                  </li>
                </ul>
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

  {{-- paginación si existe (no tocamos controlador) --}}
  @if (is_object($equipos) && method_exists($equipos, 'links'))
    <div class="d-flex justify-content-end">
      {{ $equipos->links() }}
    </div>
  @endif

</div>
@endsection

@push('styles')
<style>
  /* sticky header con sombra sutil al hacer scroll */
  thead.position-sticky { box-shadow: 0 2px 0 rgba(0,0,0,.03); z-index: 1; }
  /* modo denso */
  .table.table-dense tr > * { padding-top: .35rem; padding-bottom: .35rem; }
  /* indicación visual de columna ordenable */
  th.sortable { user-select: none; }
  th.sortable::after { content:''; margin-left:.25rem; border:4px solid transparent; border-top-color:rgba(0,0,0,.25); display:inline-block; vertical-align:middle; }
  th.sortable[aria-sort="ascending"]::after { border-top-color:transparent; border-bottom-color:rgba(0,0,0,.55); }
  th.sortable[aria-sort="descending"]::after { border-top-color:rgba(0,0,0,.55); }
  /* ocultar columnas (se controla por data-col) */
  .col-hidden { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
(function(){
  const table = document.getElementById('equiposTable');
  const tbody = table.querySelector('tbody');
  const headers = table.querySelectorAll('th.sortable');
  const searchInput = document.getElementById('tableSearch');
  const densityBtn = document.getElementById('densityToggle');
  const colToggles = document.querySelectorAll('.col-toggle');

  // --- Densidad ---
  const DENSITY_KEY = 'equipos.table.density';
  const COLS_KEY = 'equipos.table.cols';
  const savedDensity = localStorage.getItem(DENSITY_KEY) || 'normal';
  setDensity(savedDensity);
  densityBtn.addEventListener('click', () => {
    const v = table.classList.contains('table-dense') ? 'normal' : 'dense';
    setDensity(v);
    localStorage.setItem(DENSITY_KEY, v);
  });
  function setDensity(v){
    table.classList.toggle('table-dense', v === 'dense');
    densityBtn.textContent = (v === 'dense') ? 'Normal' : 'Denso';
    densityBtn.setAttribute('aria-pressed', v === 'dense' ? 'true' : 'false');
  }

  // --- Ocultar/mostrar columnas ---
  // estado inicial desde localStorage
  let savedCols = {};
  try { savedCols = JSON.parse(localStorage.getItem(COLS_KEY) || '{}'); } catch(e){ savedCols = {}; }
  // aplicar a carga
  applyColumnVisibility(savedCols);
  // listeners
  colToggles.forEach(chk => {
    const col = chk.dataset.col;
    // estado inicial de checks
    if (savedCols.hasOwnProperty(col)) chk.checked = savedCols[col];
    chk.addEventListener('change', (e) => {
      savedCols[col] = e.target.checked;
      localStorage.setItem(COLS_KEY, JSON.stringify(savedCols));
      applyColumnVisibility(savedCols);
    });
  });
  function applyColumnVisibility(state){
    const cols = ['id','tipo','marca','modelo','ubicacion','usuario_asignado','estado','acciones'];
    cols.forEach(col => {
      const show = state.hasOwnProperty(col) ? state[col] : true;
      // thead
      table.querySelectorAll('th[data-col="'+col+'"]').forEach(th => {
        th.classList.toggle('col-hidden', !show);
      });
      // body
      table.querySelectorAll('td[data-col="'+col+'"]').forEach(td => {
        td.classList.toggle('col-hidden', !show);
      });
    });
  }

  // --- Búsqueda client-side ---
  if (searchInput) {
    searchInput.addEventListener('input', function(){
      const q = this.value.trim().toLowerCase();
      Array.from(tbody.rows).forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
      });
    });
  }

  // --- Ordenamiento client-side (por página) ---
  headers.forEach(th => th.addEventListener('click', () => sortBy(th)));
  headers.forEach(th => th.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sortBy(th); }
  }));

  function sortBy(th){
    const idx = Array.from(th.parentNode.children).indexOf(th);
    const current = th.getAttribute('aria-sort');
    const next = (current === 'ascending') ? 'descending' : 'ascending';
    // reset otros th
    headers.forEach(h => h.setAttribute('aria-sort','none'));
    th.setAttribute('aria-sort', next);

    const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => r.style.display !== 'none');
    const dir = (next === 'ascending') ? 1 : -1;

    rows.sort((a,b) => {
      const A = a.children[idx].innerText.trim().toLowerCase();
      const B = b.children[idx].innerText.trim().toLowerCase();
      // intento numérico si aplica
      const An = parseFloat(A.replace(',', '.'));
      const Bn = parseFloat(B.replace(',', '.'));
      if (!isNaN(An) && !isNaN(Bn)) return (An - Bn) * dir;
      return A.localeCompare(B, 'es', {numeric:true, sensitivity:'base'}) * dir;
    });

    // re-render
    const frag = document.createDocumentFragment();
    rows.forEach(r => frag.appendChild(r));
    tbody.appendChild(frag);
  }
})();
</script>
@endpush
