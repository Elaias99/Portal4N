<form action="{{ route('importar.proveedores') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="archivo" required>
    <button type="submit">Importar Proveedores</button>
</form>
