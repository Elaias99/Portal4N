<td style="width: 130px;" class="text-center">
    <div class="d-flex flex-column gap-1">
        <a href="{{ $edit }}" class="btn btn-sm btn-warning w-100 text-center d-inline-block">
            <i class="fas fa-edit"></i> {{ $etiquetaEditar ?? 'Editar' }}
        </a>
        <form action="{{ $delete }}" method="POST" class="w-100">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger w-100 text-center d-inline-block"
                onclick="return confirm('{{ $mensaje ?? '¿Estás seguro de que deseas eliminar este registro?' }}')">
                <i class="fas fa-trash-alt"></i> {{ $etiquetaEliminar ?? 'Eliminar' }}
            </button>
        </form>
    </div>
</td>
