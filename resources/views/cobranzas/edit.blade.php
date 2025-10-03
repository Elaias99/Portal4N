@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Editar Cobranza</h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('cobranzas.update', $cobranza) }}" method="POST">
                @method('PUT')
                @include('cobranzas.form', ['btnText' => 'Actualizar'])
            </form>
        </div>
    </div>
</div>
@endsection
