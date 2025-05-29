@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h3>Escaneo de Productos</h3>

    @if(isset($mensaje))
        <div class="alert alert-info">
            {{ $mensaje }}
        </div>
    @endif

    <form method="POST" action="{{ route('escaneo.store') }}">
        @csrf
        <label for="codigo">Escanea un código:</label>
        <input type="text" name="codigo" id="codigo" class="form-control" autofocus>
        <button class="btn btn-success mt-2">Guardar</button>
    </form>


    @if(isset($productoInfo))
        <div class="card mt-4">
            <div class="card-header">
                <strong>Información del producto escaneado</strong>
            </div>
            <div class="card-body">
                <p><strong>Nombre:</strong> {{ $productoInfo->nombre }}</p>
                <p><strong>Peso:</strong> {{ $productoInfo->peso }}</p>
                <p><strong>Altura:</strong> {{ $productoInfo->altura }}</p>
                <p><strong>Ancho:</strong> {{ $productoInfo->ancho }}</p>
                <p><strong>Profundidad:</strong> {{ $productoInfo->profundidad }}</p>
            </div>
        </div>
    @endif


    {{-- <hr class="my-4">

    <h4>Solicitar token de acceso (simulación Twee)</h4>

    <form method="POST" action="/fake-api/token" id="tokenForm">
        @csrf
        <div class="mb-2">
            <input type="text" name="Username" placeholder="Usuario" class="form-control" value="admin">
        </div>
        <div class="mb-2">
            <input type="password" name="Password" placeholder="Contraseña" class="form-control" value="1234">
        </div>
        <input type="hidden" name="grant_type" value="password">
        <button class="btn btn-primary">Obtener token</button>
    </form>

    <div class="mt-3" id="tokenResultado"></div> --}}


    @if($productos->count())
        <div class="mt-4">
            <h5>Códigos Escaneados</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productos as $index => $p)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $p->codigo }}</td>
                            <td>{{ $p->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
















</div>
@endsection

@if(isset($productoInfo))
    <audio id="coinSound">
        <source src="{{ asset('sounds/coin.mp3') }}" type="audio/mpeg">
    </audio>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const audio = document.getElementById('coinSound');

            // Forzar que suene cuando el DOM ya se haya cargado
            setTimeout(() => {
                audio.play().catch(e => console.log('Autoplay bloqueado por el navegador'));
            }, 100); // leve retardo para garantizar carga del DOM
        });
    </script>
@endif


<script>
    // Evita que el formulario recargue la página
    document.getElementById('tokenForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const response = await fetch('/fake-api/token', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();
        const resultado = document.getElementById('tokenResultado');

        if (response.ok) {
            resultado.innerHTML = `<div class="alert alert-success">
                <strong>Token recibido:</strong> ${data.access_token}
            </div>`;
        } else {
            resultado.innerHTML = `<div class="alert alert-danger">
                Error: ${data.error}
            </div>`;
        }
    });
</script>


